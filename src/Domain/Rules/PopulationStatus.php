<?php

namespace Procorad\Procostat\Domain\Rules;

enum PopulationStatus: string
{
    case NOT_EXPLOITABLE = 'not_exploitable';   // n < 3
    case DESCRIPTIVE_ONLY = 'descriptive_only'; // 3 ≤ n ≤ 6
    case FULL_EVALUATION = 'full_evaluation';   // n ≥ 7

    /**
     * ISO 13528: full statistical evaluation allowed.
     */
    public function isFullyExploitable(): bool
    {
        return $this === self::FULL_EVALUATION;
    }

    /**
     * ISO 13528: exploitable for robust statistics.
     */
    public function isExploitable(): bool
    {
        return $this !== self::NOT_EXPLOITABLE;
    }

    /**
     * ISO 13528: descriptive statistics only.
     */
    public function isDescriptiveOnly(): bool
    {
        return $this === self::DESCRIPTIVE_ONLY;
    }
}
