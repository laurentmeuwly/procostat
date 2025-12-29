<?php

namespace Procorad\Procostat\Infrastructure\Normality;

use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use Procorad\Procostat\Domain\Statistics\Normality\ShapiroWilk;
use Procorad\Procostat\Domain\Statistics\Normality\Skewness;
use Procorad\Procostat\Domain\Statistics\Normality\Kurtosis;
use Procorad\Procostat\Domain\Statistics\Normality\HenryLine;
use RuntimeException;

final class PhpNormalityAdapter implements NormalityAdapter
{
    /**
     * @param float[] $values
     */
    public function analyze(array $values): NormalityResult
    {
        if (count($values) < 3) {
            throw new RuntimeException(
                'Normality analysis requires at least 3 values.'
            );
        }

        // --- Statistical indicators ---
        $shapiro = ShapiroWilk::test($values);
        $skewness = Skewness::compute($values);
        $kurtosis = Kurtosis::compute($values);
        $henryLine = HenryLine::compute($values);

        // --- ISO-like conservative decision ---
        $isNormal =
            $shapiro['pValue'] >= 0.05
            && abs($skewness) <= 1.0
            && abs($kurtosis) <= 1.0;

        // --- Human-readable conclusion ---
        $conclusion = $isNormal
            ? 'Distribution compatible with normality (ISO screening)'
            : 'Distribution not compatible with normality (ISO screening)';

        return new NormalityResult(
            isNormal: $isNormal,
            shapiroWilkPValue: $shapiro['pValue'],
            skewness: $skewness,
            kurtosis: $kurtosis,
            conclusion: $conclusion,
            henryLine: $henryLine
        );
    }
}
