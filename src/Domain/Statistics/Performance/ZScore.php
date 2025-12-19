<?php

namespace Procorad\Procostat\Domain\Statistics\Performance;

final class ZScore
{
    public static function compute(
        float $result,
        float $assignedValue,
        float $sigmaPt
    ): float {
        if ($sigmaPt <= 0.0) {
            throw new \InvalidArgumentException('Sigma_pt must be strictly positive.');
        }

        return ($result - $assignedValue) / $sigmaPt;
    }
}
