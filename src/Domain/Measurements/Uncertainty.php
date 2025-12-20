<?php

namespace Procorad\Procostat\Domain\Measurements;

use RuntimeException;

final class Uncertainty
{
    public function __construct(
        public readonly float $value,
        public readonly float $coverageFactor = 1.0,
        public readonly string $type = 'standard' // 'standard' | 'expanded'
    ) {
        if ($value < 0) {
            throw new RuntimeException('Uncertainty value must be non-negative.');
        }

        if ($coverageFactor <= 0) {
            throw new RuntimeException('Coverage factor must be positive.');
        }
    }

    /**
     * Standard uncertainty (u)
     */
    public function standard(): float
    {
        return $this->type === 'expanded'
            ? $this->value / $this->coverageFactor
            : $this->value;
    }

    /**
     * Expanded uncertainty (U)
     */
    public function expanded(): float
    {
        return $this->type === 'standard'
            ? $this->value * $this->coverageFactor
            : $this->value;
    }
}
