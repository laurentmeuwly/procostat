<?php

namespace Procorad\Procostat\Domain\Statistics\Performance;

final class ZPrimeScore
{
    public static function compute(
        float $result,
        float $assignedValue,
        float $sigmaPt,
        float $uAssigned
    ): float {
        if ($sigmaPt <= 0.0) {
            throw new \InvalidArgumentException('Sigma_pt must be strictly positive.');
        }

        if ($uAssigned < 0.0) {
            throw new \InvalidArgumentException('Assigned value uncertainty must be non-negative.');
        }

        $denominator = sqrt(
            ($sigmaPt ** 2) + ($uAssigned ** 2)
        );

        return ($result - $assignedValue) / $denominator;
    }
}
