<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Application\Pipeline\PipelineRunner;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateDataset;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Application\Pipeline\Steps\ComputePerformanceIndicators;
use Procorad\Procostat\Application\Pipeline\Steps\DecideLaboratoryFitness;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;

final class RunAnalysis
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        $runner = new PipelineRunner([
            //new ValidateDataset(),
            //new EvaluatePopulationSize(),
            //new ComputePerformanceIndicators(),
            new DecideLaboratoryFitness($this->thresholdsResolver),
            new RecordAuditTrail($this->thresholdsResolver, new NullAuditStore()),
        ]);

        return $runner->run($input);
    }
}
