<?php

namespace Procorad\Procostat\Domain\Measurements;

final class Measurement
{
    public function __construct(
        public readonly string $laboratoryCode,
        public readonly float $value,
        public readonly ?Uncertainty $uncertainty = null
    ) {
    }
}
