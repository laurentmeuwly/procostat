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

        if ($context->assignedValue->isIndependent()) {
            // Assigned value independent of participants -> Z
            $context->primaryIndicator = IndicatorType::Z;

            // Trace
            $context->trace->isCertifiedReference = true;
            $context->trace->primaryIndicator     = 'z';
            $context->trace->addStep('zscore');
            // End trace

        } else {
            // Assigned value derived from participants -> Z'
            $context->primaryIndicator = IndicatorType::Z_PRIME;

            // Trace
            $context->trace->isCertifiedReference = false;
            $context->trace->primaryIndicator     = 'z_prime';
            $context->trace->addStep('zprime');
            // End trace

        }

        return $context;
    }
}
