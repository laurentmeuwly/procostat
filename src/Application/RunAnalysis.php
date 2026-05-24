<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Application\Pipeline\PipelineRunner;
use Procorad\Procostat\Application\Pipeline\Steps\BuildDescriptiveStatistics;
use Procorad\Procostat\Application\Pipeline\Steps\BuildEvaluationReference;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulation;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulationSummary;
use Procorad\Procostat\Application\Pipeline\Steps\CheckNormality;
use Procorad\Procostat\Application\Pipeline\Steps\ComputeRobustStatistics;
use Procorad\Procostat\Application\Pipeline\Steps\DecidePrimaryIndicator;
use Procorad\Procostat\Application\Pipeline\Steps\DetectOutliers;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluateLaboratories;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Application\Pipeline\Steps\ResolveAssignedValue;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateCertifiedValue;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateDataset;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Contracts\AnalysisEngine;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\Rules\PopulationThresholds;
use Procorad\Procostat\Domain\Trace\AnalysisTrace;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\AnalysisOutput;
use Procorad\Procostat\DTO\ProcostatResult;
use Procorad\Procostat\Support\Version;

final class RunAnalysis implements AnalysisEngine
{
    public PopulationThresholds $populationThresholds;

    public function __construct(
        private readonly NormalityAdapter $normalityAdapter,
        private readonly AuditStore $auditStore,
        private readonly AssignedValueResolver $assignedValueResolver,
        private readonly ThresholdsResolver $thresholdsResolver,
        private readonly string $thresholdStandard // injected via config
    ) {
        // PROCORAD utilise minFullEvaluation = 12 (plan statistique §3 et logigramme §11)
        // ISO 13528 strict utiliserait 7 — configurable via withPopulationThresholds().
        $this->populationThresholds = PopulationThresholds::procorad();
    }

    /**
     * API publique principale
     */
    public function analyze(AnalysisDataset $dataset): ProcostatResult
    {
        return $this->run($dataset)->result;
    }

    /**
     * API de validation scientifique — retourne résultat + trace.
     *
     * Usage test :
     *   $output = $engine->analyzeWithTrace($dataset);
     *   expect($output->trace->workflowPath)->toEqual([...]);
     */
    public function analyzeWithTrace(AnalysisDataset $dataset): AnalysisOutput
    {
        return $this->run($dataset);
    }

    public function withPopulationThresholds(PopulationThresholds $thresholds): static
    {
        $clone = clone $this;
        $clone->populationThresholds = $thresholds;
        return $clone;
    }

    private function run(AnalysisDataset $dataset): AnalysisOutput
    {
        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: $this->thresholdStandard
        );

        $runner = new PipelineRunner([
            new ValidateDataset,
            new BuildPopulation,
            new EvaluatePopulationSize($this->populationThresholds),
            new CheckNormality($this->normalityAdapter),
            new DetectOutliers,
            new ComputeRobustStatistics,        // peut modifier population + populationStatus
            new BuildDescriptiveStatistics,     // apres : reflète la population finale
            new ResolveAssignedValue($this->assignedValueResolver),
            new ValidateCertifiedValue,
            new DecidePrimaryIndicator,
            new BuildEvaluationReference,
            new EvaluateLaboratories($this->thresholdsResolver),
            new BuildPopulationSummary,
            new RecordAuditTrail($this->auditStore),
        ]);

        $finalContext = $runner->run($context);

        // Integrity guards
        // descriptiveStatistics : always required
        if ($finalContext->descriptiveStatistics === null) {
            throw new \RuntimeException(
                'RunAnalysis pipeline did not produce DescriptiveStatistics.'
            );
        }

        if (
            $finalContext->assignedValue === null ||
            $finalContext->populationSummary === null ||
            $finalContext->auditTrail === null
        ) {
            throw new \RuntimeException(
                'RunAnalysis pipeline did not produce a complete ProcostatResult.'
            );
        }

        // Stats robustes avant troncature — reconstituées depuis la trace si troncature a eu lieu
        $robustBeforeTruncation = null;
        if ($finalContext->trace->truncationTriggered
            && $finalContext->trace->robustMeanBeforeTruncation !== null
        ) {
            $robustBeforeTruncation = new \Procorad\Procostat\Domain\Statistics\RobustStatistics(
                mean:   $finalContext->trace->robustMeanBeforeTruncation,
                stdDev: $finalContext->trace->robustStdDevBeforeTruncation ?? 0.0,
            );
        }

        $result = new ProcostatResult(
            assignedValue: $finalContext->assignedValue,
            descriptiveStatistics: $finalContext->descriptiveStatistics,
            robustStatistics: $finalContext->robustStatistics,      // tronquée si z>5
            robustStatisticsBeforeTruncation: $robustBeforeTruncation,  // complète (null si pas de troncature)
            populationSummary: $finalContext->populationSummary,
            primaryIndicator: $finalContext->primaryIndicator,      // nullable
            labEvaluations: $finalContext->labEvaluations,
            auditTrail: $finalContext->auditTrail,
            engineVersion: Version::current(),
            expertValidationRequired:          $finalContext->trace->expertValidationRequired,
            certifiedValueValidationGap:       $finalContext->trace->certifiedValueValidationGap,
            certifiedValueValidationThreshold: $finalContext->trace->certifiedValueValidationThreshold,
        );

        return new AnalysisOutput(
            result: $result,
            trace: $finalContext->trace
        );
    }
}
