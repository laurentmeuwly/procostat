<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

final class DecidePrimaryIndicator implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'DecidePrimaryIndicator requires PopulationStatus.'
            );
        }

        if ($context->assignedValue === null) {
            throw new RuntimeException(
                'DecidePrimaryIndicator requires AssignedValue.'
            );
        }

        // Population not exploitable -> no performance indicator
        if ($context->populationStatus === PopulationStatus::NOT_EXPLOITABLE) {
            $context->primaryIndicator = null;

            // Trace
            $context->trace->primaryIndicator      = null;
            $context->trace->isCertifiedReference  = false;
            // End trace

            return $context;
        }

        if ($context->populationStatus === PopulationStatus::DESCRIPTIVE_ONLY) {
            $context->primaryIndicator = IndicatorType::ZETA;

            // Trace
            $context->trace->isCertifiedReference = false;
            $context->trace->primaryIndicator     = 'zeta';
            $context->trace->addStep('zeta');
            // End trace

            return $context;
        }

        // ── PROCORAD workflow override ────────────────────────────────────────
        //
        // ISO 13528 strict : valeur certifiée (indépendante) → Z
        //                    valeur consensus (participants)  → Z'
        //
        // PROCORAD : toujours Z', quelle que soit la source de la valeur assignée.
        // Raison : le client intègre systématiquement l'incertitude du labo (u_lab)
        // dans le score de performance, même pour les valeurs certifiées.
        //
        // Configurable dans procostat.php → 'workflow.force_z_prime'
        // Mettre à false pour revenir au comportement ISO 13528 strict.
        //
        $forceZPrime = config('procostat.workflow.force_z_prime', false);

        if ($forceZPrime || ! $context->assignedValue->isIndependent()) {
            // Z' — valeur consensus OU override PROCORAD
            $context->primaryIndicator = IndicatorType::Z_PRIME;

            // Trace
            $context->trace->isCertifiedReference = $context->assignedValue->isIndependent();
            $context->trace->primaryIndicator     = 'z_prime';
            $context->trace->addStep('zprime');
            // End trace

        } else {
            // Z — valeur certifiée, comportement ISO 13528 strict
            $context->primaryIndicator = IndicatorType::Z;

            // Trace
            $context->trace->isCertifiedReference = true;
            $context->trace->primaryIndicator     = 'z';
            $context->trace->addStep('zscore');
            // End trace
        }

        return $context;
    }
}
