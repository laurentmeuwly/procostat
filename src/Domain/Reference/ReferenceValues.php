<?php

namespace Procorad\Procostat\Domain\Reference;

final class ReferenceValues
{
    public function __construct(
        private readonly float $value,
        private readonly float $uncertainty,
        private readonly bool $fromMrc
    ) {}

    /**
     * Assigned value x_ref
     */
    public function value(): float
    {
        return $this->value;
    }

    /**
     * Standard uncertainty u(x_ref)
     */
    public function uncertainty(): float
    {
        return $this->uncertainty;
    }

    /**
     * True if reference comes from a certified reference material (MRC/MR)
     */
    public function isFromMRC(): bool
    {
        return $this->fromMrc;
    }
}
