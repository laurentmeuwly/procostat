<?php

namespace Procorad\Procostat\Domain\Statistics\Performance;

final class BiasCalculator
{
    public static function compute(
        float $result,
        float $referenceValue
    ): float {

        if ($referenceValue == 0.0) {
            throw new \InvalidArgumentException('Reference value must be non-zero.');
        }

        return (($result - $referenceValue) / $referenceValue) * 100.0;
    }
}
