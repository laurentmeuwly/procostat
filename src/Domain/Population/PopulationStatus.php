<?php

namespace Procorad\Procostat\Domain\Population;

final class PopulationStatus
{
    public function __construct(
        private readonly int $size,
        private readonly bool $exploitable
    ) {}

    public function size(): int
    {
        return $this->size;
    }

    public function isExploitable(): bool
    {
        return $this->exploitable;
    }
}
