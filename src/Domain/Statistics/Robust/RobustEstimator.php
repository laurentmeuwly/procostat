<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

use RuntimeException;

final class RobustEstimator
{
    /**
     * ISO 13528 Algorithm A — robust mean and robust standard deviation
     *
     * @param  float[]  $values
     */

    public static function estimate(array $values): array
    {
        if (count($values) < 3) {
            throw new RuntimeException('Requires at least 3 values.');
        }

        sort($values);

        $xStar = self::median($values);
        $sStar = self::initialScale($values, $xStar);

        $maxIter = 20;
        $minIter = 5;

        for ($i = 0; $i < $maxIter; $i++) {

            $prevX = $xStar;
            $prevS = $sStar;

            $delta = 1.5 * $sStar;

            $sumSquares = 0.0;
            $n = count($values);

            $truncatedValues = [];
            foreach ($values as $x) {
                if ($x < $xStar - $delta) {
                    $xiStar = $xStar - $delta;
                } elseif ($x > $xStar + $delta) {
                    $xiStar = $xStar + $delta;
                } else {
                    $xiStar = $x;
                }

                $truncatedValues[] = $xiStar;
            }

            // Calculate new xStar
            $newX = array_sum($truncatedValues) / $n;

            // Calculate new sStar, based on new xStar
            foreach ($truncatedValues as $xiStar) {
                $sumSquares += ($xiStar - $newX) ** 2;
            }

            $newS = 1.134 * sqrt($sumSquares / ($n-1));

            $xStar = $newX;
            $sStar = $newS;

            if ($i + 1 >= $minIter
                && self::same3SigFigs($prevX, $xStar)
                && self::same3SigFigs($prevS, $sStar)
            ) {
                break;
            }
        }

        return [$xStar, $sStar];
    }

    /**
     * ISO 13528 Algorithm A — version debug.
     *
     * Retourne les mêmes x* et s* que estimate(), plus le détail de chaque
     * itération pour validation et test.
     *
     * NE PAS utiliser en production — réservé aux tests unitaires et à l'audit
     * du comportement de l'algorithme.
     *
     * @param  float[]  $values
     * @return array{
     *     xStar: float,
     *     sStar: float,
     *     iterations: int,
     *     converged: bool,
     *     steps: list<array{i: int, xStar: float, sStar: float, delta: float, converged: bool}>
     * }
     */
    public static function estimateWithTrace(array $values): array
    {
        if (count($values) < 3) {
            throw new RuntimeException('Requires at least 3 values.');
        }

        sort($values);

        $xStar = self::median($values);
        $sStar = self::initialScale($values, $xStar);

        $maxIter  = 20;
        $minIter  = 5;
        $steps    = [];
        $converged = false;

        $steps[] = [
            'i'         => 0,
            'xStar'     => $xStar,
            'sStar'     => $sStar,
            'delta'     => 0.0,
            'converged' => false,
            'note'      => 'initial (median + MAD)',
        ];

        for ($i = 0; $i < $maxIter; $i++) {
            $prevX = $xStar;
            $prevS = $sStar;
            $delta = 1.5 * $sStar;
            $n     = count($values);

            $truncatedValues = [];
            foreach ($values as $x) {
                if ($x < $xStar - $delta) {
                    $truncatedValues[] = $xStar - $delta;
                } elseif ($x > $xStar + $delta) {
                    $truncatedValues[] = $xStar + $delta;
                } else {
                    $truncatedValues[] = $x;
                }
            }

            $newX = array_sum($truncatedValues) / $n;

            $sumSquares = 0.0;
            foreach ($truncatedValues as $xiStar) {
                $sumSquares += ($xiStar - $newX) ** 2;
            }
            $newS = 1.134 * sqrt($sumSquares / ($n - 1));

            $xStar = $newX;
            $sStar = $newS;

            $iterConverged = ($i + 1 >= $minIter)
                && self::same3SigFigs($prevX, $xStar)
                && self::same3SigFigs($prevS, $sStar);

            $steps[] = [
                'i'         => $i + 1,
                'xStar'     => $xStar,
                'sStar'     => $sStar,
                'delta'     => $delta,
                'converged' => $iterConverged,
            ];

            if ($iterConverged) {
                $converged = true;
                break;
            }
        }

        return [
            'xStar'      => $xStar,
            'sStar'      => $sStar,
            'iterations' => count($steps) - 1,   // hors étape initiale
            'converged'  => $converged,
            'steps'      => $steps,
        ];
    }


    /**
     * @param  float[]  $values
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
     * @param float[]   $values
     * @param float     $xStar  Robust mean estimate (median)
     */
    private static function initialScale(array $values, float $xStar): float
    {
        $absDeviations = array_map(
            fn(float $x) => abs($x - $xStar),
            $values
        );

        sort($absDeviations);

        return 1.483 * self::median($absDeviations);
    }

    private static function same3SigFigs(float $a, float $b): bool
    {
        return self::roundSig($a, 3) === self::roundSig($b, 3);
    }

    private static function roundSig(float $x, int $sig): float
    {
        if ($x == 0.0) return 0.0;

        $p = floor(log10(abs($x)));
        $scale = 10 ** ($sig - 1 - $p);

        return round($x * $scale) / $scale;
    }
}
