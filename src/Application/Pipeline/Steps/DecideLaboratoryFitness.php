<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
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
     *   zScore?: float,
     *   zPrimeScore?: float,
     *   zetaScore?: float,
     *   biasPercent?: float,
     *   thresholdStandard: string
     * } $data
     */
    public function __invoke(array $data): LabEvaluation
    {
        $decisionScore = null;
        $decisionBasis = null;

        if (isset($data['zPrimeScore'])) {
            $decisionScore = $data['zPrimeScore'];
            $decisionBasis = 'z_prime';
        } elseif (isset($data['zScore'])) {
            $decisionScore = $data['zScore'];
            $decisionBasis = 'z';
        }

        if ($decisionScore === null) {
            throw new RuntimeException(
                'No decision score available (zPrimeScore or zScore required).'
            );
        }

        $thresholds = $this->thresholdsResolver
            ->resolve($data['thresholdStandard']);

        $fitnessStatus = FitnessDecision::decideFromScore(
            $decisionScore,
            $thresholds
        );

        return new LabEvaluation(
            laboratoryCode: $data['laboratoryCode'],
            zScore: $data['zScore'] ?? null,
            zPrimeScore: $data['zPrimeScore'] ?? null,
            zetaScore: $data['zetaScore'] ?? null,
            biasPercent: $data['biasPercent'] ?? null,
            fitnessStatus: $fitnessStatus,
            decisionBasis: $decisionBasis
        );
    }
}
