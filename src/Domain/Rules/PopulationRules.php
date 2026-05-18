<?php

namespace Procorad\Procostat\Domain\Rules;

final class PopulationRules
{
    public static function evaluate(
        int $n,
        PopulationThresholds $thresholds = new PopulationThresholds(),
    ): PopulationStatus {
        if ($n < $thresholds->minExploitable) {
            return PopulationStatus::NOT_EXPLOITABLE;
        }

        if ($n < $thresholds->minFullEvaluation) {
            return PopulationStatus::DESCRIPTIVE_ONLY;
        }

        return PopulationStatus::FULL_EVALUATION;
    }
}
