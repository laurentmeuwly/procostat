<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class LabEvaluation
{
    public function __construct(
        public readonly string $laboratoryCode, // anonymous code
        public readonly ?float $zScore,
        public readonly ?float $zPrimeScore,
        public readonly ?float $zetaScore,
        public readonly ?float $biasPercent,
        public readonly FitnessStatus $fitnessStatus,
        public readonly string $decisionBasis
    ) {
    }
}
