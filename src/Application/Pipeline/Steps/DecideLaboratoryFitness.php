<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Audit\AuditBuilder;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\DTO\LabEvaluation;
use RuntimeException;

final class DecideLaboratoryFitness
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver
    ) {
    }

    /**
     * @param array{
     *   laboratoryCode: string,
     *   thresholdStandard: string,
     *   zScore?: float,
     *   zPrimeScore?: float,
     *   zetaScore?: float,
     *   biasPercent?: float
     * } $context
     *
     * @return array{
     *   laboratoryCode: string,
     *   thresholdStandard: string,
     *   zScore?: float,
     *   zPrimeScore?: float,
     *   zetaScore?: float,
     *   biasPercent?: float,
     *   labEvaluation: LabEvaluation
     * }
     */
    public function __invoke(array $context): array
    {
        if (!isset($context['laboratoryCode'], $context['thresholdStandard'])) {
            throw new RuntimeException(
                'DecideLaboratoryFitness requires laboratoryCode and thresholdStandard.'
            );
        }

        [$decisionScore, $decisionBasis] = $this->resolveDecisionScore($context);

        $thresholds = $this->thresholdsResolver
            ->resolve($context['thresholdStandard']);

        $fitnessStatus = FitnessDecision::decideFromScore(
            $decisionScore,
            $thresholds
        );

        $context['labEvaluation'] = new LabEvaluation(
            laboratoryCode: $context['laboratoryCode'],
            zScore: $context['zScore'] ?? null,
            zPrimeScore: $context['zPrimeScore'] ?? null,
            zetaScore: $context['zetaScore'] ?? null,
            biasPercent: $context['biasPercent'] ?? null,
            fitnessStatus: $fitnessStatus,
            decisionBasis: $decisionBasis
        );

        $context['auditTrail'] ??= new AuditTrail();

        $context['auditTrail']->add(
        AuditBuilder::fromDecision(
            laboratoryCode: $context['laboratoryCode'],
            status: $fitnessStatus,
            decisionBasis: $decisionBasis,
            decisionScore: $decisionScore,
            thresholds: $thresholds
        )
    );

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     * @return array{0: float, 1: string}
     */
    private function resolveDecisionScore(array $context): array
    {
        if (isset($context['zPrimeScore'])) {
            return [$context['zPrimeScore'], 'z_prime'];
        }

        if (isset($context['zScore'])) {
            return [$context['zScore'], 'z'];
        }

        throw new RuntimeException(
            'No decision score available: zPrimeScore or zScore is required.'
        );
    }
}
