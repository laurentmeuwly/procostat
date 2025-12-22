<?php

namespace Procorad\Procostat\Domain\Statistics\Normality;

final class HenryLine
{
    /**
     * @param float[] $values
     * @return array<array{theoretical: float, observed: float}>
     */
    public static function compute(array $values): array
    {
        sort($values);
        $n = count($values);

        $result = [];

        for ($i = 1; $i <= $n; $i++) {
            $p = ($i - 0.5) / $n;
            $z = self::inverseNormal($p);

            $result[] = [
                'theoretical' => $z,
                'observed' => $values[$i - 1],
            ];
        }

        return $result;
    }

    /**
     * Approximation of the inverse cumulative distribution function (CDF) (Beasley-Springer)
     */
    private static function inverseNormal(float $p): float
    {
        // Abramowitz & Stegun approximation
        $a1 = -39.696830;
        $a2 = 220.946098;
        $a3 = -275.928510;
        $a4 = 138.357751;
        $a5 = -30.664798;
        $a6 = 2.506628;

        $b1 = -54.476098;
        $b2 = 161.585836;
        $b3 = -155.698979;
        $b4 = 66.801311;
        $b5 = -13.280681;

        $c1 = -0.007784894;
        $c2 = -0.322396458;
        $c3 = -2.400758;
        $c4 = -2.549732;
        $c5 = 4.374664;
        $c6 = 2.938163;

        $d1 = 0.007784695;
        $d2 = 0.322467129;
        $d3 = 2.445134;
        $d4 = 3.754408;

        $pLow = 0.02425;
        $pHigh = 1 - $pLow;

        if ($p < $pLow) {
            $q = sqrt(-2 * log($p));
            return ((((( $c1*$q + $c2)*$q + $c3)*$q + $c4)*$q + $c5)*$q + $c6)
                / (((( $d1*$q + $d2)*$q + $d3)*$q + $d4)*$q + 1);
        }

        if ($p <= $pHigh) {
            $q = $p - 0.5;
            $r = $q ** 2;
            return ((((( $a1*$r + $a2)*$r + $a3)*$r + $a4)*$r + $a5)*$r + $a6)*$q
                / ((((( $b1*$r + $b2)*$r + $b3)*$r + $b4)*$r + $b5)*$r + 1);
        }

        $q = sqrt(-2 * log(1 - $p));
        return -((((( $c1*$q + $c2)*$q + $c3)*$q + $c4)*$q + $c5)*$q + $c6)
            / (((( $d1*$q + $d2)*$q + $d3)*$q + $d4)*$q + 1);
    }
}
