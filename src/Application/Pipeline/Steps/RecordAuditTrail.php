<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Domain\Audit\AuditBuilder;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\DTO\LabEvaluation;
use RuntimeException;

final class RecordAuditTrail
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver,
        private readonly AuditStore $auditStore
    ) {
    }

    /**
     * @param array{
     *   laboratoryCode: string,
     *   thresholdStandard: string,
     *   zScore?: float,
     *   zPrimeScore?: float,
     *   labEvaluation: LabEvaluation,
     *   auditTrail?: AuditTrail
     * } $context
     *
     * @return array{
     *   laboratoryCode: string,
     *   thresholdStandard: string,
     *   zScore?: float,
     *   zPrimeScore?: float,
     *   labEvaluation: LabEvaluation,
     *   auditTrail: AuditTrail
     * }
     */
    public function __invoke(array $context): array
    {
        if (!isset($context['laboratoryCode'], $context['thresholdStandard'], $context['labEvaluation'])) {
            throw new RuntimeException(
                'RecordAuditTrail requires laboratoryCode, thresholdStandard and labEvaluation.'
            );
        }

        if (!$context['labEvaluation'] instanceof LabEvaluation) {
            throw new RuntimeException('labEvaluation must be an instance of LabEvaluation.');
        }

        [$decisionScore, $decisionBasis] = $this->resolveDecisionScore($context);

        // Sanity: the audit must reflect the same basis as the decision
        if ($context['labEvaluation']->decisionBasis !== $decisionBasis) {
            throw new RuntimeException(
                'Decision basis mismatch between labEvaluation and available scores in context.'
            );
        }

        $thresholds = $this->thresholdsResolver->resolve($context['thresholdStandard']);

        $trail = $context['auditTrail'] ?? new AuditTrail();

        $event = AuditBuilder::fromDecision(
            laboratoryCode: $context['laboratoryCode'],
            status: $context['labEvaluation']->fitnessStatus,
            decisionBasis: $decisionBasis,
            decisionScore: $decisionScore,
            thresholds: $thresholds
        );

        $trail->add($event);
        $this->auditStore->store($event);

        $context['auditTrail'] = $trail;

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
            'No decision score available for audit: zPrimeScore or zScore is required.'
        );
    }
}
