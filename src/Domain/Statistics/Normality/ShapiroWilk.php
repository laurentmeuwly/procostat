<?php

namespace Procorad\Procostat\Domain\Statistics\Normality;

use RuntimeException;

final class ShapiroWilk
{
    /**
     * @param float[] $values
     * @return array{W: float, pValue: float}
     */
    public static function test(array $values): array
    {
        $n = count($values);
        if ($n < 3) {
            throw new RuntimeException('Shapiro-Wilk requires at least 3 values.');
        }

        sort($values);

        $mean = array_sum($values) / $n;

        $s2 = 0.0;
        foreach ($values as $x) {
            $s2 += ($x - $mean) ** 2;
        }

        if ($s2 == 0.0) {
            return ['W' => 1.0, 'pValue' => 1.0];
        }

        // Simplified approximation: W based on correlation
        $expected = [];
        for ($i = 1; $i <= $n; $i++) {
            $expected[] = HenryLine::compute($values)[$i - 1]['theoretical'];
        }

        $num = 0.0;
        $den = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $num += $expected[$i] * ($values[$i] - $mean);
            $den += ($values[$i] - $mean) ** 2;
        }

        $W = ($num ** 2) / $den;

        // Crude p-value approximation (acceptable for screening)
        $pValue = exp(-5 * (1 - $W));

        return [
            'W' => max(0.0, min(1.0, $W)),
            'pValue' => max(0.0, min(1.0, $pValue)),
        ];
    }
}
