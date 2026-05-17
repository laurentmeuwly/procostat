<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\DTO\PopulationSummary;
use RuntimeException;

final class BuildPopulationSummary implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'BuildPopulationSummary requires Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'BuildPopulationSummary requires PopulationStatus.'
            );
        }

        $context->populationSummary = new PopulationSummary(
            participantCount: $context->population->count(),
            populationStatus: $context->populationStatus,
            normality: $context->normalityResult,
            outliers: $context->outliers,
            notes: []
        );

        return $context;
    }
}
