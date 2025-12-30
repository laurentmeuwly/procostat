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

    /** Create a standard uncertainty (u, k=1) */
    public static function fromStandard(float $u): self
    {
        return new self(
            value: $u,
            coverageFactor: 1.0,
            type: 'standard'
        );
    }

    /** Create an expanded uncertainty (U, k>1, typically k=2) */
    public static function fromExpanded(float $U, float $k = 2.0): self
    {
        return new self(
            value: $U,
            coverageFactor: $k,
            type: 'expanded'
        );
    }

    /**
     * Convert to standard uncertainty (u)
     */
    public function toStandard(): float
    {
        return $this->type === 'expanded'
            ? $this->value / $this->coverageFactor
            : $this->value;
    }

    /**
     * Convert to expanded uncertainty (U)
     */
    public function toExpanded(): float
    {
        return $this->type === 'standard'
            ? $this->value * $this->coverageFactor
            : $this->value;
    }
}
