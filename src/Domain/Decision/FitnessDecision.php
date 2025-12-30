<?php

namespace Procorad\Procostat\Domain\Decision;

use InvalidArgumentException;
use Procorad\Procostat\Domain\Rules\Thresholds;

final class FitnessDecision
{
    public static function decideFromScore(
        float $score,
        Thresholds $thresholds
    ): FitnessStatus {
        if (! is_finite($score)) {
            throw new InvalidArgumentException('Score must be a finite number.');
        }

        $abs = abs($score);

        return match (true) {
            $abs < $thresholds->conformityLimit => FitnessStatus::CONFORME,

            $abs <= $thresholds->discussionLimit => FitnessStatus::DISCUTABLE,

            default => FitnessStatus::NON_CONFORME,
        };
    }
}
