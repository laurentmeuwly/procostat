<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Population\Population;
use RuntimeException;

final class BuildPopulation implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        $dataset = $context->dataset;

        $measurements = $dataset->measurements();

        if ($measurements === []) {
            // Additional security (normally already blocked by previous step ValidateDataset)
            throw new RuntimeException(
                'Cannot build population from empty dataset.'
            );
        }

        $context->population = new Population($measurements);

        return $context;
    }
}
