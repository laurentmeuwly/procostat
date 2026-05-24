<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\OutliersRules;
use Procorad\Procostat\Domain\Rules\PopulationRules;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;
use Procorad\Procostat\Domain\Statistics\RobustStatisticsCalculator;
use RuntimeException;

/**
 * Calcule les statistiques robustes (x*, s*) et gere la troncature z>5.
 *
 * Logigramme PROCORAD §11 — branche full_evaluation (n > 12) :
 *
 *   1. Algorithme A (ISO 13528) → x*, s*
 *   2. z-score de chaque resultat → si z > 5 : troncature
 *   3. Apres troncature, recalculer populationStatus avec le n restant.
 *      - Si n_apres_troncature <= 12 : revenir a la branche descriptive_only
 *        + executer Grubbs sur la population tronquee
 *        + stats descriptives tronquees
 *      - Sinon : recalculer x*, s* sur la population tronquee (moyenne/ecart-type
 *        robuste tronquee) et rester en full_evaluation
 *
 * Ce step ne s'execute que si populationStatus === FULL_EVALUATION.
 * Il peut modifier populationStatus vers DESCRIPTIVE_ONLY en cas de
 * retour de branche apres troncature.
 */
final class ComputeRobustStatistics implements PipelineStep
{
    /**
     * Seuil de troncature selon le logigramme PROCOSTAT (z > 5).
     */
    private const TRUNCATION_THRESHOLD = 5.0;

    /**
     * Seuil Grubbs : s'applique si n <= 12 (plan statistique §9.2.1).
     */
    private const GRUBBS_MAX_N = 12;

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'ComputeRobustStatistics requires an existing Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'ComputeRobustStatistics requires PopulationStatus.'
            );
        }

        // Branche descriptive_only ou not_exploitable : pas de stats robustes
        if (! $context->populationStatus->isFullyExploitable()) {
            $context->robustStatistics = null;
            $context->trace->robustStatisticsComputed = false;
            return $context;
        }

        // ── Phase 1 : calcul initial x*, s* sur la population complete ──────
        $context->robustStatistics = RobustStatisticsCalculator::compute($context->population);
        $context->trace->robustStatisticsComputed = true;
        $context->trace->addStep('robust_mean');

        $mean   = $context->robustStatistics->mean();
        $stdDev = $context->robustStatistics->stdDev();

        if ($stdDev <= 0) {
            // Tous les resultats identiques : pas de troncature possible
            return $context;
        }

        // ── Phase 2 : detection z > 5 ─────────────────────────────────────
        $measurements = $context->population->measurements();
        $zScores = [];
        foreach ($measurements as $i => $m) {
            $zScores[$i] = abs($m->value() - $mean) / $stdDev;
        }

        $maxZ = max($zScores);
        $context->trace->maxZScoreBeforeTruncation = $maxZ;

        if ($maxZ <= self::TRUNCATION_THRESHOLD) {
            // Pas de troncature
            return $context;
        }

        // ── Phase 3 : troncature ─────────────────────────────────────────────
        $context->trace->truncationTriggered           = true;
        $context->trace->robustMeanBeforeTruncation    = $mean;
        $context->trace->robustStdDevBeforeTruncation  = $stdDev;
        $context->trace->addStep('truncation');

        // Conserver la population pre-troncature comme originalPopulation
        // (permet a EvaluateLaboratories de retrouver tous les labos)
        $context->originalPopulation = $context->population;

        $truncatedCodes = [];
        foreach ($measurements as $i => $m) {
            if ($zScores[$i] > self::TRUNCATION_THRESHOLD) {
                $code = (string) $m->laboratoryCode();
                $truncatedCodes[] = $code;
                $context->trace->truncatedLabs[] = $code;
            }
        }

        // Construire la population tronquee
        $truncatedPopulation = $context->population;
        foreach ($truncatedCodes as $code) {
            $truncatedPopulation = $truncatedPopulation->withoutLaboratory($code);
        }

        $nAfter = $truncatedPopulation->count();

        // ── Phase 4 : recalculer populationStatus avec n_apres_troncature ────
        //
        // Cas cle (logigramme §11) :
        //   Si n_avant > 12 et n_apres <= 12 → la branche bascule en
        //   descriptive_only, et il faut y appliquer le test de Grubbs
        //   (plan §9.2.1 : Grubbs si n <= 12).
        //
        $statusAfter = PopulationRules::evaluate(
            $nAfter,
            // On utilise les seuils PROCORAD (min_full_evaluation = 12)
            // Le contexte ne porte pas les thresholds directement;
            // on passe par PopulationRules avec les valeurs PROCORAD.
            new \Procorad\Procostat\Domain\Rules\PopulationThresholds(
                minExploitable:    3,
                minFullEvaluation: 13  // PROCORAD : full si n > 12
            )
        );

        if ($statusAfter !== PopulationStatus::FULL_EVALUATION) {
            // ── Retour en branche descriptive_only (ou not_exploitable) ──────
            $context->trace->addStep('truncation_branch_downgrade');
            $context->trace->record('truncation.branch_downgrade', [
                'n_before' => count($measurements),
                'n_after'  => $nAfter,
                'new_status' => $statusAfter->value,
            ]);

            $context->population       = $truncatedPopulation;
            $context->populationStatus = $statusAfter;
            $context->robustStatistics = null;   // pas de stats robustes en descriptive_only

            // Grubbs sur la population tronquee si n_apres >= 3 et <= 12
            if ($statusAfter->isExploitable() && $nAfter <= self::GRUBBS_MAX_N) {
                $this->applyGrubbsAfterTruncation($context, $truncatedPopulation);
            }

            // Stats descriptives recalculees sur la population tronquee
            // (ou tronquee + Grubbs si un nouvel aberrant a ete detecte)
            $context->trace->addStep('truncation_recompute_descriptive');

        } else {
            // ── Rester en full_evaluation : recalculer x*, s* tronquees ──────
            $context->population       = $truncatedPopulation;
            $context->robustStatistics = RobustStatisticsCalculator::compute($truncatedPopulation);
            $context->trace->addStep('truncation_recompute');
        }

        return $context;
    }

    /**
     * Applique le test de Grubbs sur la population tronquee.
     *
     * Appele uniquement quand, apres troncature z>5, n retombe dans la
     * zone descriptive_only (3 <= n <= 12).
     *
     * Si un aberrant Grubbs est detecte :
     *   - il est exclu de la population
     *   - la trace est mise a jour (grubbsExcludedLab_postTruncation)
     *   - context->population est reduite
     */
    private function applyGrubbsAfterTruncation(
        AnalysisContext $context,
        Population      $population,
    ): void {
        $measurements = $population->measurements();
        $values = array_map(static fn ($m) => $m->value(), $measurements);
        $n = count($values);

        if ($n < 3) {
            return;
        }

        $grubbsResult = Grubbs::compute($values);
        $detected     = OutliersRules::isSuspiciousGrubbs($grubbsResult['G'], $n);

        $context->trace->record('grubbs_post_truncation', [
            'n'        => $n,
            'G'        => $grubbsResult['G'],
            'index'    => $grubbsResult['index'],
            'detected' => $detected,
        ]);
        $context->trace->addStep('grubbs_post_truncation');

        if ($detected && $grubbsResult['index'] !== -1) {
            $index        = $grubbsResult['index'];
            $excludedCode = (string) $measurements[$index]->laboratoryCode();

            // Stocker dans la trace (champ dedie pour ne pas ecraser
            // un eventuel Grubbs anterior effectue avant la troncature)
            $context->trace->grubbsExcludedLabPostTruncation = $excludedCode;
            $context->trace->addStep('grubbs_post_truncation_exclusion');

            $context->population = $population->withoutIndex($index);
        }
    }
}
