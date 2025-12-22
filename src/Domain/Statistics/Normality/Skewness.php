<?php

namespace Procorad\Procostat\Domain\Statistics\Normality;

use RuntimeException;

final class Skewness
{
    /**
     * @param float[] $values
     */
    public static function compute(array $values): float
    {
        $n = count($values);
        if ($n < 3) {
            throw new RuntimeException('Skewness requires at least 3 values.');
        }

        $mean = array_sum($values) / $n;

        $m2 = 0.0;
        $m3 = 0.0;

        foreach ($values as $x) {
            $d = $x - $mean;
            $m2 += $d ** 2;
            $m3 += $d ** 3;
        }

        $m2 /= $n;
        $m3 /= $n;

        return $m3 / ($m2 ** 1.5);
    }
}
