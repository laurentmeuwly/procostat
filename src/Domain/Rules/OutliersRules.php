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
        // Seuils critiques ISO 13528 / ASTM E178 (α = 5%, unilatéral)
        // Source : table G_crit pour le test de Grubbs (valeur unique aberrante)
        $criticalValues = [
            3  => 1.155, 4  => 1.481, 5  => 1.715, 6  => 1.887,
            7  => 2.020, 8  => 2.126, 9  => 2.215, 10 => 2.290,
            11 => 2.355, 12 => 2.412, 13 => 2.462, 14 => 2.507,
            15 => 2.549, 16 => 2.585, 17 => 2.620, 18 => 2.651,
            19 => 2.681, 20 => 2.709, 21 => 2.733, 22 => 2.758,
            23 => 2.781, 24 => 2.802, 25 => 2.822,
        ];

        if ($n < 3) {
            return false;
        }

        // Pour n > 25 : approximation conservative (Grubbs ~ Z-score à α=5%)
        $critical = $criticalValues[$n] ?? 2.822;

        return $G > $critical;

    }
}
