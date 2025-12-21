<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

use RuntimeException;

final class RobustMean
{
    /**
     * ISO 13528 Algorithm A — robust mean
     *
     * @param float[] $values
     */
    public static function fromValues(array $values): float
    {
        if (count($values) < 3) {
            throw new RuntimeException(
                'Robust mean requires at least 3 values.'
            );
        }

        sort($values);

        // Initial estimates
        $xStar = self::median($values);
        $sStar = self::initialScale($values);

        // Algorithm A parameters
        $delta = 1.5 * $sStar;

        // Iterative refinement (fixed number of iterations per ISO)
        for ($i = 0; $i < 10; $i++) {
            $sum = 0.0;
            $count = 0;

            foreach ($values as $x) {
                $weight = max(
                    -$delta,
                    min($delta, $x - $xStar)
                );
                $sum += $xStar + $weight;
                $count++;
            }

            $xStar = $sum / $count;
        }

        return $xStar;
    }

    /**
     * @param float[] $values
     */
    private static function median(array $values): float
    {
        $n = count($values);
        $mid = intdiv($n, 2);

        return ($n % 2 === 0)
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    /**
     * Initial robust scale estimate (ISO 13528)
     *
     * @param float[] $values
     */
    private static function initialScale(array $values): float
    {
        $n = count($values);
        return 1.483 * (
            abs($values[intdiv($n * 3, 4)] - $values[intdiv($n, 4)])
        );
    }

    public static function initialScaleForStdDev(array $values): float
    {
        $n = count($values);
        return 1.483 * (
            abs($values[intdiv($n * 3, 4)] - $values[intdiv($n, 4)])
        );
    }
}
