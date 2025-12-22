<?php

namespace Procorad\Procostat\Domain\Results;

final class LaboratoryResult
{
    public function __construct(
        private readonly string $laboratoryCode,
        private readonly float $value,
        private readonly float $uncertainty
    ) {}

    public function laboratoryCode(): string
    {
        return $this->laboratoryCode;
    }

    public function value(): float
    {
        return $this->value;
    }

    /**
     * Standard uncertainty (u), not k=2
     */
    public function uncertainty(): float
    {
        return $this->uncertainty;
    }
}
