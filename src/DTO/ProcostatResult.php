<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\DTO\PopulationSummary;

final class ProcostatResult
{
    /**
     * @param array<string, LabEvaluation> $labEvaluations
     */
    public function __construct(
        public readonly AssignedValue $assignedValue,
        public readonly RobustStatistics $robustStatistics,
        public readonly PopulationSummary $populationSummary,
        public readonly IndicatorType $primaryIndicator,
        public readonly array $labEvaluations,
        public readonly AuditTrail $auditTrail,
        public readonly string $engineVersion
    ) {
    }

    /** @return array<string, LabEvaluation> */
    public function labEvaluations(): array
    {
        return $this->labEvaluations;
    }

    public function labEvaluationFor(int|string $labCode): ?LabEvaluation
    {
        return $this->labEvaluations[(string) $labCode] ?? null;
    }
}
