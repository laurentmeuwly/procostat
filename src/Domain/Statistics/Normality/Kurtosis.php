<?php

namespace Procorad\Procostat\Domain\Statistics\Normality;

use RuntimeException;

final class Kurtosis
{
    /**
     * Excess kurtosis (normal distribution = 0)
     *
     * @param float[] $values
     */
    public static function compute(array $values): float
    {
        $n = count($values);
        if ($n < 4) {
            throw new RuntimeException('Kurtosis requires at least 4 values.');
        }

        $mean = array_sum($values) / $n;

        $m2 = 0.0;
        $m4 = 0.0;

        foreach ($values as $x) {
            $d = $x - $mean;
            $m2 += $d ** 2;
            $m4 += $d ** 4;
        }

        $m2 /= $n;
        $m4 /= $n;

        return ($m4 / ($m2 ** 2)) - 3.0;
    }
}
