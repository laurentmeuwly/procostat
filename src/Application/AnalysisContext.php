<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Performance\EvaluationReference;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\DescriptiveStatistics;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\Domain\Trace\AnalysisTrace;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\PopulationSummary;

/**
 * Immutable-at-entry analysis context, progressively enriched
 * by the PROCOSTAT pipeline steps.
 *
 * Business-specific nullability rules:
 *   - descriptiveStatistics : always generated (all branches)
 *   - robustStatistics      : null if descriptive_only or not_exploitable
 *   - primaryIndicator      : null if not_exploitable
 *   - normalityResult       : null if n < 7 or not_exploitable
 *   - outliers              : null if normality rejected or not applicable
 */
final class AnalysisContext
{
    public function __construct(
        public readonly AnalysisDataset $dataset,
        public readonly string $thresholdStandard,

        public AnalysisTrace $trace = new AnalysisTrace(),

        public ?Population $population = null,
        public ?Population $originalPopulation = null,  // avant exclusion des aberrants (Grubbs)
        public ?PopulationStatus $populationStatus = null,

        public ?DescriptiveStatistics $descriptiveStatistics = null,

        public ?RobustStatistics $robustStatistics = null,
        public ?AssignedValue $assignedValue = null,
        public ?IndicatorType $primaryIndicator = null,

        public ?EvaluationReference $evaluationReference = null,

        public ?NormalityResult $normalityResult = null,
        /** @var array<string,string>|null */
        public ?array $outliers = null,

        // Output DTO
        public ?PopulationSummary $populationSummary = null,

        /** @var LabEvaluation[] */
        public array $labEvaluations = [],

        public ?AuditTrail $auditTrail = null,

        /**
         * Decision de l'expert (passe 2 uniquement).
         * Null en passe 1 — calcul initial sans intervention.
         */
        public ?ExpertDecision $expertDecision = null,
    ) {}

}
