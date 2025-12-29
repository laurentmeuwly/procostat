<?php

namespace Procorad\Procostat\Domain\Performance;

final class IndicatorDecisionContext
{
    public function __construct(
        public readonly bool $isStable,
        public readonly bool $assignedValueIsIndependent,
        public readonly float $robustMean,
        public readonly float $referenceValue,
        public readonly float $robustStdDev,
        public readonly int $participantCount,
        public readonly float $referenceUncertainty,
    ) {}
}
