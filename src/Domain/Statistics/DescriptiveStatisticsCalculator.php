<?php

namespace Procorad\Procostat\Domain\Statistics;

use Procorad\Procostat\Domain\Population\Population;

/**
 * Calculates descriptive statistics for a population.
 *
 * These statistics are generated for ALL workflow branches
 * (not_exploitable, descriptive_only, full_evaluation): they are
 * always included in ProcostatResult, unlike robust statistics,
 * which are conditional.
 */
final class DescriptiveStatisticsCalculator
{
    public static function compute(
        Population $population,
        ?Population $trimmedPopulation = null
    ): DescriptiveStatistics {
        $values = array_map(
            static fn ($m) => $m->value(),
            $population->measurements()
        );

        sort($values);
        $n = count($values);

        $trimmedCount = $trimmedPopulation !== null
            ? $trimmedPopulation->count()
            : null;

        return new DescriptiveStatistics(
            count: $n,
            minimum: $values[0],
            maximum: $values[$n - 1],
            median: self::median($values),
            mean: self::mean($values),
            standardDeviation: self::stdDev($values),
            medianAbsoluteDeviation: self::mad($values),
            trimmedCount: $trimmedCount,
        );
    }

    // Elementary statistics

    /** @param float[] $sorted valeurs déjà triées */
    private static function median(array $sorted): float
    {
        $n = count($sorted);
        $mid = intdiv($n, 2);

        return ($n % 2 === 0)
            ? ($sorted[$mid - 1] + $sorted[$mid]) / 2.0
            : $sorted[$mid];
    }

    /** @param float[] $values */
    private static function mean(array $values): float
    {
        return array_sum($values) / count($values);
    }

    /**
     * Sample standard deviation (n-1 divisor, Bessel).
     *
     * @param float[] $values
     */
    private static function stdDev(array $values): ?float
    {
        $n = count($values);
        if ($n < 2) {
            return null;
        }

        $mean = self::mean($values);
        $variance = array_sum(
            array_map(static fn (float $v): float => ($v - $mean) ** 2, $values)
        ) / ($n - 1);

        return sqrt($variance);
    }

    /**
     * MADe = 1.483 * median(|x_i - median(x)|)
     *
     * Le facteur 1.483 rend le MAD coherent avec l'ecart-type
     * pour une distribution normale (estimateur robuste de sigma).
     * Reference : plan statistique PROCORAD 2026, paragraphe 9.1.
     *
     * @param float[] $sorted valeurs deja triees
     */
    private static function mad(array $sorted): float
    {
        $med = self::median($sorted);

        $deviations = array_map(
            static fn (float $v): float => abs($v - $med),
            $sorted
        );

        sort($deviations);

        return 1.483 * self::median($deviations);
    }
}
