<?php

namespace Procorad\Procostat\Domain\Statistics\Performance;

final class ZetaScore
{
    public static function compute(
        float $result,
        float $assignedValue,
        float $uResult,
        float $uAssigned
    ): float {
        if ($uResult < 0.0 || $uAssigned < 0.0) {
            throw new \InvalidArgumentException('Uncertainties must be non-negative.');
        }

        $denominator = sqrt(
            ($uResult ** 2) + ($uAssigned ** 2)
        );

        if ($denominator === 0.0) {
            throw new \InvalidArgumentException('Combined uncertainty must be strictly positive.');
        }

        return ($result - $assignedValue) / $denominator;
    }
}
