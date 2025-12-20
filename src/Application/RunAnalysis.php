<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Application\Pipeline\PipelineRunner;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateDataset;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Application\Pipeline\Steps\ComputePerformanceIndicators;
use Procorad\Procostat\Application\Pipeline\Steps\DecideLaboratoryFitness;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Application\Resolvers\EvaluationValidityResolver;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\DTO\ProcostatResult;
use Procorad\Procostat\Support\Version;

final class RunAnalysis
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver,
        private readonly AuditStore $auditStore
    ) {
    }

    public function __invoke(array $input): ProcostatResult
    {
        $runner = new PipelineRunner([
            new ValidateDataset(),
            new EvaluatePopulationSize(),
            new ComputePerformanceIndicators(),
            new DecideLaboratoryFitness($this->thresholdsResolver),
            new RecordAuditTrail($this->thresholdsResolver, $this->auditStore),
        ]);

        $context = $runner->run($input);

        if (!isset(
                $context['labEvaluation'],
                $context['auditTrail'],
                $context['populationStatus']
            )
        ) {
            throw new \RuntimeException(
                'Pipeline did not produce a complete ProcostatResult.'
            );
        }

        $finalEvaluation = $context['labEvaluation']
            ->withEvaluationValidity(
                EvaluationValidityResolver::resolve(
                    $context['populationStatus']
                )
            );

        return new ProcostatResult(
            labEvaluation: $finalEvaluation,
            auditTrail: $context['auditTrail'],
            engineVersion: Version::current()
        );
    }
}
