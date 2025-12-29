<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatisticsCalculator;
use Procorad\Procostat\Domain\Statistics\Robust\RobustStdDev;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
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

        /*
        if (
            !isset($context['dataset'], $context['populationStatus'])
            || !$context['dataset'] instanceof AnalysisDataset
        ) {
            throw new RuntimeException(
                'ComputePopulationStatistics requires dataset and populationStatus.'
            );
        }

        $dataset = $context['dataset'];
        $status = $context['populationStatus'];

        // Default: no statistics
        $context['assignedValue'] = null;
        $context['populationStdDev'] = null;

        if (!ApplicabilityRules::canComputeRobustStatistics($status)) return $context;

        $values = $dataset->values();

        if (count($values) === 0) {
            throw new RuntimeException(
                'Cannot compute population statistics on empty dataset.'
            );
        }

        foreach ($values as $v) {
            if (!is_finite($v)) throw new RuntimeException('Dataset contains non-finite values.');
        }

        $context['assignedValue'] = AssignedValueCalculator::fromValues($values);
        $context['populationStdDev'] = RobustStdDev::fromValues($values);

        return $context;*/
    }
}
