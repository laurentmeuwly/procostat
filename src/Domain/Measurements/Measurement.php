<?php

namespace Procorad\Procostat\Domain\Measurements;

use RuntimeException;

final class Measurement
{
    public function __construct(
        public readonly string $laboratoryCode,
        public readonly float $value,
        public readonly ?Uncertainty $uncertainty = null,
        public readonly ?float $limitOfDetection = null
    ) {
        if (trim($laboratoryCode) === '') {
            throw new RuntimeException('Measurement requires a non-empty laboratory code.');
        }

        if (! is_finite($value)) {
            throw new RuntimeException('Measurement value must be a finite number.');
        }

        if ($limitOfDetection !== null && $limitOfDetection < 0) {
            throw new RuntimeException('Limit of detection must be non-negative.');
        }

        if ($uncertainty !== null && $uncertainty->toStandard() < 0) {
            throw new RuntimeException('Measurement uncertainty must be non-negative.');
        }
    }

    public function laboratoryCode(): string
    {
        return $this->laboratoryCode;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function uncertainty(): ?Uncertainty
    {
        return $this->uncertainty;
    }

    public function hasUncertainty(): bool
    {
        return $this->uncertainty !== null;
    }

    public function limitOfDetection(): ?float
    {
        return $this->limitOfDetection;
    }

    public function hasLimitOfDetection(): bool
    {
        return $this->limitOfDetection !== null;
    }
}
