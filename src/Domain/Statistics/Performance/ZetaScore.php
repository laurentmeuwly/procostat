<?php

namespace Procorad\Procostat\Domain\Statistics\Performance;

final class ZetaScore
{
    /**
     * Compute ζ-score using standard uncertainties (u, k=1)
     *
     * ISO 13528:
     * ζ = (x - x_ref) / sqrt(u_x^2 + u_ref^2)
     */
    public static function compute(
        float $result,
        float $assignedValue,
        float $uResult,     // standard uncertainty (k=1)
        float $uAssigned    // standard uncertainty (k=1)
    ): float {
        if ($uResult < 0.0 || $uAssigned < 0.0) {
            throw new \InvalidArgumentException('Uncertainties must be non-negative.');
        }

        $denominator = sqrt(
            ($uResult ** 2) + ($uAssigned ** 2)
        );

        if ($denominator === 0.0) {
            throw new \InvalidArgumentException('Combined uncertainty must be strictly positive.');
        }

        return ($result - $assignedValue) / $denominator;
    }
}
