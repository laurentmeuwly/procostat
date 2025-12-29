<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Contracts\AnalysisEngine;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\ProcostatResult;
use Procorad\Procostat\Support\Version;
use Procorad\Procostat\Application\Pipeline\PipelineRunner;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateDataset;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulation;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Application\Pipeline\Steps\ComputeRobustStatistics;
use Procorad\Procostat\Application\Pipeline\Steps\ResolveAssignedValue;
use Procorad\Procostat\Application\Pipeline\Steps\DecidePrimaryIndicator;
use Procorad\Procostat\Application\Pipeline\Steps\CheckNormality;
use Procorad\Procostat\Application\Pipeline\Steps\DetectOutliers;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluateLaboratories;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulationSummary;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use RuntimeException;

final class RunAnalysis implements AnalysisEngine
{
    public function __construct(
        private readonly NormalityAdapter $normalityAdapter,
        private readonly AuditStore $auditStore,
        private readonly AssignedValueResolver $assignedValueResolver,
        private readonly ThresholdsResolver $thresholdsResolver,
        private readonly string $thresholdStandard // injected via config
    ) {}

    public function analyze(AnalysisDataset $dataset): ProcostatResult
    {
        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: $this->thresholdStandard
        );

        $runner = new PipelineRunner([
            new ValidateDataset(),
            new BuildPopulation(),
            new EvaluatePopulationSize(),
            new ComputeRobustStatistics(),
            new ResolveAssignedValue($this->assignedValueResolver),
            new DecidePrimaryIndicator(),
            new CheckNormality($this->normalityAdapter),
            new DetectOutliers(),
            new EvaluateLaboratories($this->thresholdsResolver),
            new BuildPopulationSummary(),
            new RecordAuditTrail($this->auditStore),
        ]);

        $finalContext = $runner->run($context);

        if (
            $finalContext->assignedValue === null ||
            $finalContext->robustStatistics === null ||
            $finalContext->populationSummary === null ||
            $finalContext->primaryIndicator === null ||
            $finalContext->auditTrail === null
        ) {
            throw new \RuntimeException(
                'RunAnalysis pipeline did not produce a complete ProcostatResult.'
            );
        }

        return new ProcostatResult(
            assignedValue: $finalContext->assignedValue,
            robustStatistics: $finalContext->robustStatistics,
            populationSummary: $finalContext->populationSummary,
            primaryIndicator: $finalContext->primaryIndicator,
            labEvaluations: $finalContext->labEvaluations,
            auditTrail: $finalContext->auditTrail,
            engineVersion: Version::current()
        );
    }
}
