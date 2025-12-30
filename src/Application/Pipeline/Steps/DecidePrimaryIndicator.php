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

            return $context;
        }

        // Assigned value independent of participants -> Z
        if ($context->assignedValue->isIndependent()) {
            $context->primaryIndicator = IndicatorType::Z;

            return $context;
        }

        // Assigned value derived from participants -> Z'
        $context->primaryIndicator = IndicatorType::Z_PRIME;

        return $context;
    }
}
