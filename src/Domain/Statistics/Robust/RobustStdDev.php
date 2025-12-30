<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

use RuntimeException;

final class RobustStdDev
{
    /**
     * ISO 13528 Algorithm A — robust standard deviation
     *
     * @param  float[]  $values
     */
    public static function fromValues(array $values): float
    {
        if (count($values) < 3) {
            throw new RuntimeException(
                'Robust standard deviation requires at least 3 values.'
            );
        }

        sort($values);

        $xStar = RobustMean::fromValues($values);
        $sStar = RobustMean::initialScaleForStdDev($values);

        $delta = 1.5 * $sStar;

        $sumSquares = 0.0;
        $count = 0;

        foreach ($values as $x) {
            $diff = max(
                -$delta,
                min($delta, $x - $xStar)
            );
            $sumSquares += $diff ** 2;
            $count++;
        }

        return sqrt($sumSquares / $count);
    }
}
