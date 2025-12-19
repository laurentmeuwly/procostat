<?php

namespace Procorad\Procostat\Domain\Rules;

use Procorad\Procostat\Domain\Norms\NormReference;

final class Thresholds
{
    public function __construct(
        public readonly float $conformityLimit,
        public readonly float $discussionLimit,
        public readonly string $normReference
    ) {
        if ($conformityLimit <= 0.0) {
            throw new \InvalidArgumentException('Conformity limit must be strictly positive.');
        }

        if ($discussionLimit <= $conformityLimit) {
            throw new \InvalidArgumentException(
                'Discussion limit must be greater than conformity limit.'
            );
        }
    }

    public static function iso13528(): self
    {
        return Thresholds::iso13528_2022();
    }

    public static function iso13528_2022(): self
    {
        return new self(
            conformityLimit: 2.0,
            discussionLimit: 3.0,
            normReference: NormReference::ISO_13528_2022,
        );
    }

    public static function iso13528_2015(): self
    {
        return new self(
            conformityLimit: 2.0,
            discussionLimit: 3.0,
            normReference: NormReference::ISO_13528_2015,
        );
    }
}
