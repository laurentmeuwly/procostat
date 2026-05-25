<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

/**
 * Valide la valeur certifiee (Xref) par rapport a la moyenne robuste (X*)
 * selon le paragraphe 9.2.2 du plan statistique PROCORAD 2026.
 *
 * Critere (NF ISO 13528, paragraphe 7.8.1) :
 *
 *   |Xref - X*| <= 2 * sqrt( u2(ref) + (1.25 * s_star / sqrt(p))^2 )
 *
 * ── Passe 1 (expertDecision = null) ─────────────────────────────────────────
 *   Critere OK  : valeur certifiee retenue, pipeline continue normalement.
 *   Critere KO  : substitution automatique par X*, expertValidationRequired = true.
 *                 L'UI affiche le bouton [Validation par l'expert].
 *
 * ── Passe 2 (expertDecision fourni) ─────────────────────────────────────────
 *   keepCertifiedValue = true  : l'expert maintient Xref malgre l'inegalite.
 *                                expertOverride = true, justification tracee.
 *                                assignedValue reste CERTIFIED.
 *   keepCertifiedValue = false : l'expert accepte la substitution par X*.
 *                                Meme comportement que la substitution automatique.
 *
 * Ce step ne s'execute que si :
 *   - populationStatus === FULL_EVALUATION
 *   - assignedValue est de type CERTIFIED
 */
final class ValidateCertifiedValue implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->populationStatus === null) {
            throw new RuntimeException('ValidateCertifiedValue requires PopulationStatus.');
        }

        if ($context->assignedValue === null) {
            throw new RuntimeException('ValidateCertifiedValue requires AssignedValue.');
        }

        if ($context->populationStatus !== PopulationStatus::FULL_EVALUATION) {
            return $context;
        }

        if ($context->assignedValue->type() !== AssignedValueType::CERTIFIED) {
            return $context;
        }

        if ($context->robustStatistics === null) {
            throw new RuntimeException(
                'ValidateCertifiedValue requires RobustStatistics for full_evaluation.'
            );
        }

        $p = $context->population?->count()
            ?? throw new RuntimeException('ValidateCertifiedValue requires Population.');

        $xRef  = $context->assignedValue->value();
        $uRef  = $context->assignedValue->standardUncertainty()
            ?? throw new RuntimeException(
                'ValidateCertifiedValue requires a standard uncertainty on the certified value.'
            );
        $xStar = $context->robustStatistics->mean();
        $sStar = $context->robustStatistics->stdDev();

        // -- Critere paragraphe 9.2.2 ----------------------------------------
        $uConsensus = 1.25 * $sStar / sqrt($p);
        $threshold  = 2.0 * sqrt($uRef ** 2 + $uConsensus ** 2);
        $gap        = abs($xRef - $xStar);
        $validated  = $gap <= $threshold;

        // -- Trace -----------------------------------------------------------
        $context->trace->certifiedValueValidationGap       = $gap;
        $context->trace->certifiedValueValidationThreshold = $threshold;
        $context->trace->certifiedValueValidated           = $validated;
        $context->trace->expertValidationRequired          = ! $validated;

        $context->trace->record('certified_value.validation', [
            'x_ref'           => $xRef,
            'x_star'          => $xStar,
            'u_ref_k1'        => $uRef,
            'u_consensus_k1'  => $uConsensus,
            'gap'             => $gap,
            'threshold'       => $threshold,
            'validated'       => $validated,
            'expert_required' => ! $validated,
        ]);

        $context->trace->addStep(
            $validated ? 'certified_value_validated' : 'certified_value_rejected'
        );

        // -- Substitution ou conservation ------------------------------------
        //
        // Critere OK : rien a faire, assignedValue reste CERTIFIED.
        if ($validated) {
            return $context;
        }

        // Critere KO — trois cas selon la presence et le contenu de expertDecision :
        //
        //   cas A (passe 2, expert conserve Xref) :
        //     -> on ne substitue PAS, on trace l'override et on sort.
        //
        //   cas B (passe 2, expert accepte le robuste) :
        //   cas C (passe 1, substitution automatique) :
        //     -> on substitue par X* (meme comportement dans les deux cas).

        if ($context->expertDecision !== null && $context->expertDecision->keepCertifiedValue) {
            // Cas A : expert maintient la valeur certifiee malgre l'inegalite
            $context->trace->expertOverride      = true;
            $context->trace->expertJustification = $context->expertDecision->justification;
            $context->trace->addStep('certified_value_expert_override');
            // assignedValue reste CERTIFIED — pas de substitution
            return $context;
        }

        // Cas B ou C : substitution par la moyenne robuste
        // Cas B : expert a explicitement accepte le robuste
        if ($context->expertDecision !== null) {
            $context->trace->expertConfirmedRobust = true;
            $context->trace->addStep('certified_value_expert_accepted_robust');
        }

        $context->assignedValue = AssignedValue::robust(
            value:                 $xStar,
            expandedUncertaintyK2: 2.0 * $uConsensus,
        );
        $context->trace->addStep('certified_value_fallback_to_robust');

        return $context;
    }
}
