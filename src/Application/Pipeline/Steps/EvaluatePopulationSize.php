<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\PopulationRules;
use RuntimeException;

final class EvaluatePopulationSize implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'EvaluatePopulationSize requires an existing Population.'
            );
        }

        $n = $context->population->count();

        $context->populationStatus = PopulationRules::evaluate($n);

        return $context;
    }
}
