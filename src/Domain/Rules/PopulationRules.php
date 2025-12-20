<?php

namespace Procorad\Procostat\Domain\Rules;

final class PopulationRules
{
    public static function evaluate(int $n): PopulationStatus
    {
        if ($n < 3) {
            return PopulationStatus::NOT_EXPLOITABLE;
        }

        if ($n <= 6) {
            return PopulationStatus::DESCRIPTIVE_ONLY;
        }

        return PopulationStatus::FULL_EVALUATION;
    }
}
