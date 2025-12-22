<?php

namespace Procorad\Procostat\Domain\Rules;

final class OutliersRules
{
    /**
     * ISO 13528 – indicative thresholds
     */
    public static function isSuspiciousDixon(float $Q, int $n): bool
    {
        // Conservative generic threshold (α ≈ 5%)
        return $Q > 0.41 && $n <= 25;
    }

    public static function isSuspiciousGrubbs(float $G, int $n): bool
    {
        // Conservative screening threshold
        return $G > 2.0 && $n >= 3;
    }
}
