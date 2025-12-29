<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\DTO\PopulationSummary;

final class InterlaboratoryAnalysisResult
{
    /**
     * @param array<string, LabEvaluation> $labEvaluations
     */
    public function __construct(
        public readonly PopulationSummary $populationSummary,
        public readonly array $labEvaluations,
        public readonly AuditTrail $auditTrail,
        public readonly string $engineVersion
    ) {
    }
}
