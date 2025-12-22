<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\Robust\AssignedValueCalculator;
use Procorad\Procostat\Domain\Statistics\Robust\RobustStdDev;
use RuntimeException;

final class ComputeRobustStatistics
{
    /**
     * @param array{
     *   dataset: AnalysisDataset,
     *   populationStatus: PopulationStatus
     * } $context
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $context): array
    {
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

        if ($status === PopulationStatus::NOT_EXPLOITABLE) {
            return $context;
        }

        $values = $dataset->values();

        if (count($values) === 0) {
            throw new RuntimeException(
                'Cannot compute population statistics on empty dataset.'
            );
        }

        $context['assignedValue'] = AssignedValueCalculator::fromValues($values);
        $context['populationStdDev'] = RobustStdDev::fromValues($values);

        return $context;
    }
}
