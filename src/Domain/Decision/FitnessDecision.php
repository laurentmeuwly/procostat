<?php

namespace Procorad\Procostat\Domain\Decision;

use InvalidArgumentException;

final class FitnessDecision
{
    public static function decideFromScore(float $score): FitnessStatus
    {
        if (!is_finite($score)) {
            throw new InvalidArgumentException('Score must be a finite number.');
        }

        $abs = abs($score);

        return match (true) {
            $abs < 2.0  => FitnessStatus::CONFORME,
            $abs <= 3.0 => FitnessStatus::DISCUTABLE,
            default     => FitnessStatus::NON_CONFORME,
        };
    }
}
