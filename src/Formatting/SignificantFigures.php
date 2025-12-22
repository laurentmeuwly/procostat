<?php

namespace Procorad\Procostat\Formatting;

use RuntimeException;

final class SignificantFigures
{
    /**
     * Round a value to a given number of significant figures.
     */
    public static function roundToSignificantFigures(
        float $value,
        int $figures
    ): float {
        if ($figures <= 0) {
            throw new RuntimeException(
                'Number of significant figures must be positive.'
            );
        }

        if ($value == 0.0) {
            return 0.0;
        }

        $magnitude = floor(log10(abs($value)));
        $scale = pow(10, $figures - 1 - $magnitude);

        return round($value * $scale) / $scale;
    }

    /**
     * Round a value according to its uncertainty.
     *
     * Example:
     *  value = 10.1234
     *  uncertainty = 0.045
     *  => decimals = 2
     *  => result = 10.12
     */
    public static function roundToUncertainty(
        float $value,
        float $uncertainty,
        int $uncertaintyFigures = 2
    ): float {
        if ($uncertainty <= 0) {
            throw new RuntimeException(
                'Uncertainty must be positive.'
            );
        }

        $decimals = self::decimalsFromUncertainty(
            $uncertainty,
            $uncertaintyFigures
        );

        return round($value, $decimals);
    }

    /**
     * Determine the number of decimals implied by an uncertainty.
     *
     * Example:
     *  uncertainty = 0.045
     *  figures = 2
     *  => decimals = 2
     */
    public static function decimalsFromUncertainty(
        float $uncertainty,
        int $figures = 2
    ): int {
        if ($uncertainty <= 0) {
            throw new RuntimeException(
                'Uncertainty must be positive.'
            );
        }

        if ($figures <= 0) {
            throw new RuntimeException(
                'Uncertainty figures must be positive.'
            );
        }

        $magnitude = floor(log10(abs($uncertainty)));

        return max(0, (int)-$magnitude + $figures - 2);
    }
}
