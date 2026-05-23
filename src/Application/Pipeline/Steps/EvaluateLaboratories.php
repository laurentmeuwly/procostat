<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Performance\EvaluationReference;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Rules\Thresholds;
use Procorad\Procostat\Domain\Statistics\Performance\ZetaScore;
use Procorad\Procostat\Domain\Statistics\Performance\ZPrimeScore;
use Procorad\Procostat\Domain\Statistics\Performance\ZScore;
use Procorad\Procostat\DTO\LabEvaluation;
use RuntimeException;

/**
 * Évalue chaque laboratoire selon la référence d'évaluation construite
 * par BuildEvaluationReference.
 *
 * Ne connaît pas la branche du workflow — toute la logique de sélection
 * est dans EvaluationReference. Ce step calcule et décide, c'est tout.
 *
 * Branches couvertes :
 *   - not_exploitable   → early return, labEvaluations reste []
 *   - descriptive_only  → zeta uniquement (sigma null, pas de z/z')
 *   - full_evaluation   → z' (certified ou robust_mean)
 */
final class EvaluateLaboratories implements PipelineStep
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null || $context->assignedValue === null) {
            throw new RuntimeException(
                'EvaluateLaboratories requires population and assignedValue.'
            );
        }

        // not_exploitable ou référence non construite → pas d'évaluation
        if ($context->evaluationReference === null) {
            return $context;
        }

        $thresholds = $this->thresholdsResolver->resolve($context->thresholdStandard);

        // Codes des labos exclus par troncature (z > 5)
        $truncatedCodes = array_flip($context->trace->truncatedLabs ?? []);

        // Itérer sur la population ORIGINALE si elle existe (troncature appliquée),
        // sinon sur la population courante — pour que les labos exclus soient
        // présents dans labEvaluations avec leur score et is_excluded = true.
        $allMeasurements = ($context->originalPopulation ?? $context->population)->measurements();

        foreach ($allMeasurements as $measurement) {
            $labCode = (string) $measurement->laboratoryCode();
            $isExcluded = isset($truncatedCodes[$labCode]);

            if ($isExcluded) {
                // Labo exclu par troncature (z > 5) :
                // on stocke uniquement le z-score brut qui a justifié l'exclusion,
                // calculé sur la population COMPLÈTE (x*/s* avant troncature).
                // Aucun z', zeta, biais, ni fitness — il n'est pas évalué.
                $zBeforeTruncation = null;

                if ($context->trace->robustMeanBeforeTruncation !== null
                    && $context->trace->robustStdDevBeforeTruncation > 0
                ) {
                    $zBeforeTruncation = abs(
                        ($measurement->value() - $context->trace->robustMeanBeforeTruncation)
                        / $context->trace->robustStdDevBeforeTruncation
                    );
                }

                $context->labEvaluations[$labCode] = new LabEvaluation(
                    laboratoryCode:  $labCode,
                    zScore:          $zBeforeTruncation,   // z qui a déclenché l'exclusion
                    zPrimeScore:     null,
                    zetaScore:       null,
                    biasPercent:     null,
                    fitnessStatus:   \Procorad\Procostat\Domain\Decision\FitnessStatus::NON_EVALUABLE,
                    decisionBasis:   'z',
                    isExcluded:      true,
                    exclusionReason: 'truncation_z5',
                );

                continue;
            }


            $context->labEvaluations[$labCode] = $this->evaluateLab(
                $measurement,
                $context->evaluationReference,
                $thresholds,
                $isExcluded,
            );
        }

        return $context;
    }

    // ── Évaluation individuelle ───────────────────────────────────────────────

    private function evaluateLab(
        Measurement $measurement,
        EvaluationReference $ref,
        Thresholds $thresholds,
        bool $isExcluded = false,
    ): LabEvaluation {
        $xLab = $measurement->value();
        $uLab = $measurement->uncertainty()?->toStandard(); // k=1

        $z      = null;
        $zPrime = null;
        $zeta   = null;

        // Calcul des scores selon la branche

        // Toujours calculer z brut si sigma disponible (full_evaluation)
        // même si ce n'est pas le score de décision
        if ($ref->sigma !== null && $ref->sigma > 0.0) {
            $z = ZScore::compute(
                result:        $xLab,
                assignedValue: $ref->centralValue,
                sigmaPt:       $ref->sigma
            );
        }

        if ($ref->decisionBasis === IndicatorType::Z_PRIME) {
            if ($ref->sigma === null || $ref->sigma <= 0.0) {
                throw new \LogicException(
                    'EvaluationReference.sigma must be strictly positive for Z\' score.'
                );
            }
            $zPrime = ZPrimeScore::compute(
                result:        $xLab,
                assignedValue: $ref->centralValue,
                sigmaPt:       $ref->sigma,
                uAssigned:     $ref->uRef ?? 0.0
            );
            $decisionScore = $zPrime;
            $decisionBasis = IndicatorType::Z_PRIME->value;

        } elseif ($ref->decisionBasis === IndicatorType::Z) {
            $decisionScore = $z;
            $decisionBasis = IndicatorType::Z->value;

        } else {
            // IndicatorType::ZETA → descriptive_only
            // Pas de sigma d'aptitude : le zeta est le seul indicateur
            if ($uLab === null) {
                throw new \LogicException(
                    'Lab uncertainty is required to compute Zeta score in descriptive_only branch.'
                );
            }
            $zeta = ZetaScore::compute(
                result:        $xLab,
                assignedValue: $ref->centralValue,
                uResult:       $uLab,
                uAssigned:     $ref->uRef ?? 0.0
            );
            $decisionScore = $zeta;
            $decisionBasis = IndicatorType::ZETA->value;
        }

        // ── Zeta complémentaire (toujours calculé si incertitudes disponibles) ─
        // Dans les branches z et z', le zeta est un indicateur secondaire.
        // Dans la branche zeta, il est déjà calculé ci-dessus.
        if ($zeta === null && $uLab !== null && $ref->uRef !== null) {
            $zeta = ZetaScore::compute(
                result:        $xLab,
                assignedValue: $ref->centralValue,
                uResult:       $uLab,
                uAssigned:     $ref->uRef
            );
        }

        // ── Biais ─────────────────────────────────────────────────────────────
        $biasPercent = $ref->centralValue != 0.0
            ? ($xLab - $ref->centralValue) / $ref->centralValue * 100
            : null;

        // ── Décision de conformité ────────────────────────────────────────────
        $fitnessStatus = FitnessDecision::decideFromScore($decisionScore, $thresholds);

        return new LabEvaluation(
            laboratoryCode: (string) $measurement->laboratoryCode(),
            zScore:         $z,
            zPrimeScore:    $zPrime,
            zetaScore:      $zeta,
            biasPercent:    $biasPercent,
            fitnessStatus:  $fitnessStatus,
            decisionBasis:  $decisionBasis,
            isExcluded:      $isExcluded,
            exclusionReason: $isExcluded ? 'truncation_z5' : null
        );
    }
}
