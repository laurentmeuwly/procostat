<?php

namespace Procorad\Procostat\Domain\Rules;

final class ApplicabilityRules
{
    public static function canCheckNormality(PopulationStatus $population): bool
    {
        return $population->isExploitable();
    }

    public static function canDetectOutliers(
        PopulationStatus $population,
        bool $isNormal
    ): bool {
        // PROCORAD : Grubbs s'applique dès n >= 3 (isExploitable),
        //return $population->isExploitable() && $isNormal;

        // PROCORAD : Grubbs s'applique dès n >= 3 (isExploitable), indépendamment de la normalité
        return $population->isExploitable();
    }

    public static function canComputeRobustStatistics(
        PopulationStatus $population
    ): bool {
        return $population->isFullyExploitable();
    }

    public static function canApplyDixon(int $n): bool
    {
        return $n >= 3 && $n <= 25;
    }
}
