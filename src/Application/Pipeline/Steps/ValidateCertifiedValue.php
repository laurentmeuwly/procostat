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
 *   |Xref - X*| <= 2 * sqrt( u2(ref) + (1.25 * s* / sqrt(p))^2 )
 *
 * Si l'inegalite est respectee  ->  valeur certifiee validee.
 * Si l'inegalite n'est PAS respectee :
 *   - par defaut : la moyenne robuste est substituee comme valeur assignee,
 *   - l'expert peut maintenir la valeur certifiee apres investigation ;
 *     dans ce cas le flag expertValidationRequired reste true dans la trace
 *     et dans ProcostatResult afin que l'UI affiche [Validation par l'expert].
 *
 * Ce step ne s'execute que si :
 *   - populationStatus === FULL_EVALUATION  (stats robustes disponibles)
 *   - assignedValue est de type CERTIFIED
 *
 * Positionnement dans le pipeline :
 *   ResolveAssignedValue -> ValidateCertifiedValue -> DecidePrimaryIndicator -> ...
 */
final class ValidateCertifiedValue implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'ValidateCertifiedValue requires PopulationStatus.'
            );
        }

        if ($context->assignedValue === null) {
            throw new RuntimeException(
                'ValidateCertifiedValue requires AssignedValue.'
            );
        }

        // Ce step ne s'applique qu'en full_evaluation avec une valeur certifiee
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
        $uRef  = $context->assignedValue->standardUncertainty()  // k=1
            ?? throw new RuntimeException(
                'ValidateCertifiedValue requires a standard uncertainty on the certified value.'
            );
        $xStar = $context->robustStatistics->mean();
        $sStar = $context->robustStatistics->stdDev();

        // -- Critere paragraphe 9.2.2 ----------------------------------------
        //
        //   |Xref - X*| <= 2 * sqrt( u2(ref) + (1.25 * s*/sqrt(p))^2 )
        //
        $uConsensus = 1.25 * $sStar / sqrt($p);  // u(X*) k=1 = 1.25 s*/sqrt(p)
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

        // -- Substitution automatique si non validee --------------------------
        // Selon paragraphe 9.2.2 : "l'expert choisit comme valeur assignee
        // la moyenne robuste" (comportement par defaut).
        // expertValidationRequired reste true pour alerter l'UI.
        if (! $validated) {
            $context->assignedValue = AssignedValue::robust(
                value:                 $xStar,
                expandedUncertaintyK2: 2.0 * $uConsensus,  // U(X*) k=2
            );

            $context->trace->addStep('certified_value_fallback_to_robust');
        }

        return $context;
    }
}
