<?php

namespace Procorad\Procostat\Domain\Statistics\Outliers;

use RuntimeException;

final class Grubbs
{
    /**
     * Grubbs test (single outlier)
     *
     * @param float[] $values
     * @return array{G: float, index: int}
     */
    public static function compute(array $values): array
    {
        $n = count($values);
        if ($n < 3) {
            throw new RuntimeException(
                'Grubbs test requires at least 3 values.'
            );
        }

        $mean = array_sum($values) / $n;

        $variance = 0.0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= ($n - 1);
        $stdDev = sqrt($variance);

        if ($stdDev == 0.0) {
            return ['G' => 0.0, 'index' => -1];
        }

        $maxDeviation = 0.0;
        $index = -1;

        foreach ($values as $i => $v) {
            $dev = abs($v - $mean);
            if ($dev > $maxDeviation) {
                $maxDeviation = $dev;
                $index = $i;
            }
        }

        $G = $maxDeviation / $stdDev;

        return ['G' => $G, 'index' => $index];
    }
}
