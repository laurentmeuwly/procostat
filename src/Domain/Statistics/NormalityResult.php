<?php

namespace Procorad\Procostat\Domain\Statistics;

final class NormalityResult
{
    public function __construct(
        public readonly bool $isNormal,
        public readonly ?float $shapiroWilkPValue,
        public readonly ?float $skewness,
        public readonly ?float $kurtosis,
        public readonly string $conclusion,
        public readonly ?array $henryLine
    ) {}
}
