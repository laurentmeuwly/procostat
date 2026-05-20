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
        $this->populationThresholds = new PopulationThresholds();
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
            new BuildDescriptiveStatistics,
            new ComputeRobustStatistics,
            new ResolveAssignedValue($this->assignedValueResolver),
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

        $result = new ProcostatResult(
            assignedValue: $finalContext->assignedValue,
            descriptiveStatistics: $finalContext->descriptiveStatistics,
            robustStatistics: $finalContext->robustStatistics,      // nullable
            populationSummary: $finalContext->populationSummary,
            primaryIndicator: $finalContext->primaryIndicator,      // nullable
            labEvaluations: $finalContext->labEvaluations,
            auditTrail: $finalContext->auditTrail,
            engineVersion: Version::current()
        );

        return new AnalysisOutput(
            result: $result,
            trace: $finalContext->trace
        );
    }
}
