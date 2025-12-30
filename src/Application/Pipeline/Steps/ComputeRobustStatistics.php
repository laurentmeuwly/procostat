<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Statistics\RobustStatisticsCalculator;
use RuntimeException;

final class ComputeRobustStatistics implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'ComputeRobustStatistics requires an existing Population.'
            );
        }

        $context->robustStatistics =
            RobustStatisticsCalculator::compute($context->population);

        return $context;
    }
}
