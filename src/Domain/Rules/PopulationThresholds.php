<?php

namespace Procorad\Procostat\Domain\Rules;

/**
 * Seuils de population configurables.
 *
 * Les valeurs par défaut correspondent à ISO 13528.
 * Le client peut surcharger via RunAnalysis::withPopulationThresholds().
 *
 * PROCORAD utilise minFullEvaluation = 12 au lieu de 7.
 */
final class PopulationThresholds
{
    public function __construct(
        /** n minimum pour être exploitable (en-dessous → not_exploitable) */
        public readonly int $minExploitable    = 3,

        /** n minimum pour full_evaluation (en-dessous → descriptive_only) */
        public readonly int $minFullEvaluation = 7,
    ) {}

    public static function iso13528(): self
    {
        return new self(minExploitable: 3, minFullEvaluation: 7);
    }

    public static function procorad(): self
    {
        return new self(minExploitable: 3, minFullEvaluation: 12);
    }
}
