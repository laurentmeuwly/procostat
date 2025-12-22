<?php

namespace Procorad\Procostat\Domain\Rules;

final class ApplicabilityRules
{
    public static function canCheckNormality(PopulationStatus $population): bool
    {
        return $population->isFullyExploitable();
    }

    public static function canDetectOutliers(
        PopulationStatus $population,
        bool $isNormal
    ): bool {
        return $population->isFullyExploitable() && $isNormal;
    }

    public static function canComputeRobustStatistics(
        PopulationStatus $population
    ): bool {
        return $population->isExploitable();
    }
}
