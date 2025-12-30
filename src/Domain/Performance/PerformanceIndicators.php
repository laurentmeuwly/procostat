<?php

namespace Procorad\Procostat\Domain\Performance;

final class PerformanceIndicators
{
    public function __construct(
        public readonly ?float $z,
        public readonly ?float $zPrime,
        public readonly float $zeta,
        public readonly float $bias,
        public readonly PerformanceStatus $status,
        public readonly IndicatorType $declaredIndicator
    ) {
        if ($this->z !== null && $this->zPrime !== null) {
            throw new \LogicException('z and zPrime cannot both be defined.');
        }
    }

    public function declaredValue(): float
    {
        return match ($this->declaredIndicator) {
            IndicatorType::Z => $this->z,
            IndicatorType::Z_PRIME => $this->zPrime,
            default => throw new \LogicException('Invalid declared indicator'),
        };
    }
}
