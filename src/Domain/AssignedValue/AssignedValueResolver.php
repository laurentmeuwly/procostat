<?php

namespace Procorad\Procostat\Domain\AssignedValue;

use Procorad\Procostat\Domain\Statistics\RobustStatisticsInterface;

final class AssignedValueResolver
{
    public function resolve(
        AssignedValueSpecification $spec,
        RobustStatisticsInterface $stats,
        int $populationSize
    ): AssignedValue {
        return match ($spec->type) {
            AssignedValueType::CERTIFIED => AssignedValue::certified(
                $spec->value,
                $spec->expandedUncertaintyK2
            ),

            AssignedValueType::ROBUST_MEAN => AssignedValue::robust(
                $stats->mean(),
                $this->expandedUncertaintyFromRobustMean(
                    $stats->stdDev(),
                    $populationSize
                )
            ),
        };
    }

    private function expandedUncertaintyFromRobustMean(
        float $stdDev,
        int $populationSize
    ): float {
        return 2 * (1.25 * $stdDev / sqrt($populationSize));
    }
}
