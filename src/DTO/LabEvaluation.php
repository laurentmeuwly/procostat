<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Decision\EvaluationValidity;
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
        public readonly string $decisionBasis,
        public readonly ?EvaluationValidity $evaluationValidity = null,
        // Labo exclu du calcul de la population (z > 5 troncature)
        // Les scores sont calculés à titre informatif uniquement.
        public readonly bool $isExcluded = false,
        // Raison de l'exclusion : 'outlier_grubbs' | 'truncation_z5' | null
        public readonly ?string $exclusionReason = null
    ) {}

    public function withEvaluationValidity(
        EvaluationValidity $validity
    ): self {
        return new self(
            laboratoryCode: $this->laboratoryCode,
            zScore: $this->zScore,
            zPrimeScore: $this->zPrimeScore,
            zetaScore: $this->zetaScore,
            biasPercent: $this->biasPercent,
            fitnessStatus: $this->fitnessStatus,
            decisionBasis: $this->decisionBasis,
            evaluationValidity: $validity
        );
    }
}
