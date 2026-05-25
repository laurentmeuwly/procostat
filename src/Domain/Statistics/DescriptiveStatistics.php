<?php

namespace Procorad\Procostat\Domain\Statistics;

final class DescriptiveStatistics
{
    public function __construct(
        public readonly int $count,

        public readonly ?float $minimum,
        public readonly ?float $maximum,

        public readonly ?float $median,

        public readonly ?float $mean,
        public readonly ?float $standardDeviation,

        /** Median Absolute Deviation */
        public readonly ?float $medianAbsoluteDeviation,

        /** Optional trimmed count */
        public readonly ?int $trimmedCount = null,

        // U(x̄, k=2) = 2 × s / √p — calculé à la construction
        public readonly ?float  $uArithK2 = null,
    ) {}
}
