<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Domain\Rules\PopulationRules;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

final class EvaluatePopulationSize
{
    /**
     * @param array{
     *   participantCount: int
     * } $context
     *
     * @return array{
     *   participantCount: int,
     *   populationStatus: PopulationStatus
     * }
     */
    public function __invoke(array $context): array
    {
        if (!isset($context['participantCount'])) {
            throw new RuntimeException(
                'EvaluatePopulationSize requires participantCount.'
            );
        }

        $n = $context['participantCount'];

        if (!is_int($n) || $n < 0) {
            throw new RuntimeException(
                'participantCount must be a non-negative integer.'
            );
        }

        $context['populationStatus'] = PopulationRules::evaluate($n);

        return $context;
    }
}
