<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\PopulationSummary;

/**
 * Immutable-at-entry analysis context, progressively enriched
 * by the PROCOSTAT pipeline steps.
 */
final class AnalysisContext
{
    public function __construct(
        public readonly AnalysisDataset $dataset,
        public readonly string $thresholdStandard,

        public ?Population $population = null,
        public ?RobustStatistics $robustStatistics = null,
        public ?AssignedValue $assignedValue = null,
        public ?IndicatorType $primaryIndicator = null,

        public ?NormalityResult $normalityResult = null,
        /** @var array<string,string>|null */
        public ?array $outliers = null,

        public ?PopulationStatus $populationStatus = null,
        public ?PopulationSummary $populationSummary = null,

        /** @var LabEvaluation[] */
        public array $labEvaluations = [],

        public ?AuditTrail $auditTrail = null
    ) {}

}
