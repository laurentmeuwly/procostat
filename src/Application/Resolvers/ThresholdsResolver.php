<?php

namespace Procorad\Procostat\Application\Resolvers;

use Procorad\Procostat\Domain\Rules\Thresholds;
use RuntimeException;

final class ThresholdsResolver
{
    public static function resolve(string $standard): Thresholds
    {
        return match ($standard) {
            'iso13528',
            'iso13528_2022' => Thresholds::iso13528_2022(),

            default => throw new RuntimeException(
                "Unknown threshold standard [$standard]"
            ),
        };
    }
}
