<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Domain\Audit\AuditBuilder;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\DTO\LabEvaluation;
use RuntimeException;

final class RecordAuditTrail implements PipelineStep
{
    public function __construct(
        private readonly AuditStore $auditStore
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->populationSummary === null) {
            throw new RuntimeException(
                'RecordAuditTrail requires PopulationSummary.'
            );
        }

        if (empty($context->labEvaluations)) {
            throw new RuntimeException(
                'RecordAuditTrail requires lab evaluations.'
            );
        }

        $thresholds = ThresholdsResolver::resolve(
            $context->thresholdStandard
        );

        $trail = new AuditTrail;

        foreach ($context->labEvaluations as $evaluation) {
            $decisionScore = $this->decisionScoreFromEvaluation($evaluation);

            $event = AuditBuilder::labDecision(
                laboratoryCode: $evaluation->laboratoryCode,
                status: $evaluation->fitnessStatus,
                decisionBasis: $evaluation->decisionBasis,
                decisionScore: $decisionScore,
                thresholds: $thresholds
            );

            $trail->add($event);
            $this->auditStore->store($event);
        }

        $context->auditTrail = $trail;

        return $context;
    }

    private function decisionScoreFromEvaluation(LabEvaluation $evaluation): float
    {
        return match ($evaluation->decisionBasis) {
            'z' => $evaluation->zScore
                ?? throw new RuntimeException('Audit requires zScore when decisionBasis is "z".'),
            'z_prime' => $evaluation->zPrimeScore
                ?? throw new RuntimeException('Audit requires zPrimeScore when decisionBasis is "z_prime".'),
            default => throw new RuntimeException(
                "Unknown decisionBasis [{$evaluation->decisionBasis}] in LabEvaluation."
            ),
        };
    }
}
