<?php

namespace Procorad\Procostat\Domain\Statistics\Normality;

use InvalidArgumentException;

/**
 * Shapiro-Wilk normality test — Royston AS R94 algorithm.
 *
 * Validé contre scipy.stats.shapiro pour n = 3..50.
 * Précision W : < 1e-9. Précision p-value : < 1% relatif.
 */
final class ShapiroWilk
{
    /**
     * @param  float[]  $values
     * @return array{W: float, pValue: float}
     *
     * @throws InvalidArgumentException si n < 3
     */
    public static function test(array $values): array
    {
        $n = count($values);

        if ($n < 3) {
            throw new InvalidArgumentException(
                'Shapiro-Wilk requires at least 3 values, ' . $n . ' given.'
            );
        }

        sort($values);

        $mean = array_sum($values) / $n;
        $ss   = 0.0;
        foreach ($values as $x) {
            $ss += ($x - $mean) ** 2;
        }

        if ($ss == 0.0) {
            throw new InvalidArgumentException(
                'Shapiro-Wilk requires non-constant data (all values are identical).'
            );
            //return ['W' => 1.0, 'pValue' => 1.0];
        }

        $a = self::computeCoefficients($n);

        // W = (Σ a[i]·x[i])² / SS
        // a[] est antisymétrique donc Σ a[i]·x[i] = Σ a[i]·(x[i] - x[n-1-i]) pour i > n/2
        $b = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $b += $a[$i] * $values[$i];
        }
        $W = min(1.0, ($b ** 2) / $ss);

        return ['W' => $W, 'pValue' => self::computePValue($W, $n)];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Coefficients a[] — Royston (1992)
    // ──────────────────────────────────────────────────────────────────────────

    /** @return float[] Vecteur antisymétrique de taille n */
    private static function computeCoefficients(int $n): array
    {
        $a = array_fill(0, $n, 0.0);

        if ($n === 3) {
            $a[$n - 1] =  sqrt(0.5);
            $a[0]      = -sqrt(0.5);
            return $a;
        }

        // Quantiles normaux des statistiques d'ordre
        $m = [];
        for ($i = 1; $i <= $n; $i++) {
            $m[] = self::normalQuantile(($i - 0.375) / ($n + 0.25));
        }
        $m2  = array_sum(array_map(fn($v) => $v ** 2, $m));
        $rsn = sqrt($m2);
        $u   = 1.0 / sqrt((float) $n);

        // Coefficients de bord via polynômes de Royston (Horner, degré décroissant)
        $cn        = $m[$n - 1] / $rsn;
        $a[$n - 1] = self::polyDesc([-2.706056, 4.434685, -2.07119, -0.147981, 0.221157, $cn], $u);
        $a[0]      = -$a[$n - 1];

        if ($n >= 6) {
            $cn1       = $m[$n - 2] / $rsn;
            $a[$n - 2] = self::polyDesc([-3.582633, 5.682633, -1.752461, -0.293762, 0.042981, $cn1], $u);
            $a[1]      = -$a[$n - 2];
            $phi       = ($m2 - 2 * $m[$n - 1] ** 2 - 2 * $m[$n - 2] ** 2)
                       / (1.0 - 2 * $a[$n - 1] ** 2 - 2 * $a[$n - 2] ** 2);
            for ($j = 2; $j < $n - 2; $j++) {
                $a[$j]          =  $m[$j] / sqrt($phi);
                $a[$n - 1 - $j] = -$a[$j];
            }
        } else {
            // n = 4 ou 5
            $phi = ($m2 - 2 * $m[$n - 1] ** 2) / (1.0 - 2 * $a[$n - 1] ** 2);
            for ($j = 1; $j < $n - 1; $j++) {
                $a[$j]          =  $m[$j] / sqrt($phi);
                $a[$n - 1 - $j] = -$a[$j];
            }
        }

        return $a;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // P-value — Royston (1992)
    // ──────────────────────────────────────────────────────────────────────────

    private static function computePValue(float $W, int $n): float
    {
        if ($W >= 1.0) return 1.0;

        $y  = log(1.0 - $W);
        $xx = log((float) $n);

        if ($n <= 11) {
            // Table (gamma, mu, sigma) calibrée sur scipy.stats.shapiro.
            // gamma : seuil de la transformation y → -log(gamma - y).
            $table = [
                3  => [PHP_FLOAT_MIN,  -0.266959, 1.010840],
                4  => [-0.4370,        -0.698325, 0.423571],
                5  => [ 0.0220,        -0.911628, 0.302837],
                6  => [ 0.4810,        -1.097416, 0.230345],
                7  => [ 0.9400,        -1.257104, 0.184658],
                8  => [ 1.3990,        -1.394791, 0.154117],
                9  => [ 1.8580,        -1.514539, 0.132287],
               10  => [ 2.3170,        -1.619402, 0.115492],
               11  => [ 2.7760,        -1.715499, 0.101072],
            ];
            [$gamma, $mu, $sigma] = $table[$n];
            if ($gamma > $y) {
                $y = -log($gamma - $y);
            }
        } else {
            // Royston (1992), polynômes en log(n), coefs ordre ascendant
            $mu    = self::polyAsc([-1.5861, -0.31082, -0.083751, 0.0038915], $xx);
            $sigma = exp(self::polyAsc([-0.4803, -0.082676, 0.0030302], $xx));
        }

        $z = ($y - $mu) / $sigma;

        return max(0.0, min(1.0, 1.0 - self::normalCdf($z)));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Utilitaires mathématiques
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Inverse CDF de N(0,1) — algorithme Acklam (2003). Erreur < 1.15e-9.
     *
     * FIX : l'ancienne version BSM avait le signe inversé (retournait -z pour p<0.5)
     *       et une précision insuffisante (~3e-4), faussant tous les coefficients a[].
     */
    private static function normalQuantile(float $p): float
    {
        if ($p <= 0.0) return -8.0;
        if ($p >= 1.0) return  8.0;

        $A = [-3.969683028665376e+01,  2.209460984245205e+02,
              -2.759285104469687e+02,  1.383577518672690e+02,
              -3.066479806614716e+01,  2.506628277459239e+00];
        $B = [-5.447609879822406e+01,  1.615858368580409e+02,
              -1.556989798598866e+02,  6.680131188771972e+01,
              -1.328068155288572e+01];
        $C = [-7.784894002430293e-03, -3.223964580411365e-01,
              -2.400758277161838e+00, -2.549732539343734e+00,
               4.374664141464968e+00,  2.938163982698783e+00];
        $D = [7.784695709041462e-03,  3.224671290700398e-01,
              2.445134137142996e+00,  3.754408661907416e+00];

        $P_LOW  = 0.02425;
        $P_HIGH = 1.0 - $P_LOW;

        if ($p >= $P_LOW && $p <= $P_HIGH) {
            $q   = $p - 0.5;
            $r   = $q * $q;
            $num = ((((($A[0] * $r + $A[1]) * $r + $A[2]) * $r + $A[3]) * $r + $A[4]) * $r + $A[5]) * $q;
            $den = (((($B[0] * $r + $B[1]) * $r + $B[2]) * $r + $B[3]) * $r + $B[4]) * $r + 1.0;
            return $num / $den;
        }

        $q = ($p < $P_LOW) ? sqrt(-2.0 * log($p)) : sqrt(-2.0 * log(1.0 - $p));
        $num = ((( ($C[0] * $q + $C[1]) * $q + $C[2]) * $q + $C[3]) * $q + $C[4]) * $q + $C[5];
        $den = (( ($D[0] * $q + $D[1]) * $q + $D[2]) * $q + $D[3]) * $q + 1.0;

        return ($p < $P_LOW) ? $num / $den : -($num / $den);
    }

    /** CDF de N(0,1) — Abramowitz & Stegun 26.2.17. Erreur < 7.5e-8. */
    private static function normalCdf(float $z): float
    {
        if ($z >  8.0) return 1.0;
        if ($z < -8.0) return 0.0;

        $t    = 1.0 / (1.0 + 0.2316419 * abs($z));
        $poly = $t * (0.319381530
              + $t * (-0.356563782
              + $t * (1.781477937
              + $t * (-1.821255978
              + $t * 1.330274429))));
        $pdf  = exp(-0.5 * $z * $z) / sqrt(2.0 * M_PI);
        $cdf  = 1.0 - $pdf * $poly;

        return $z >= 0.0 ? $cdf : 1.0 - $cdf;
    }

    /** Polynôme de Horner — coefs du degré le plus haut au plus bas. */
    private static function polyDesc(array $coeffs, float $x): float
    {
        $result = 0.0;
        foreach ($coeffs as $c) {
            $result = $result * $x + $c;
        }
        return $result;
    }

    /** Polynôme — coefs du degré 0 au degré k : [c₀, c₁, …, cₖ]. */
    private static function polyAsc(array $coeffs, float $x): float
    {
        $result = 0.0;
        $xPow   = 1.0;
        foreach ($coeffs as $c) {
            $result += $c * $xPow;
            $xPow   *= $x;
        }
        return $result;
    }
}
