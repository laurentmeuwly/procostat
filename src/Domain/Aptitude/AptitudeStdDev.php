<?php

namespace Procorad\Procostat\Domain\Aptitude;

final class AptitudeStdDev
{
    public function __construct(
        private readonly float $value
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Aptitude standard deviation must be positive.');
        }
    }

    public function value(): float
    {
        return $this->value;
    }
}
