<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use RuntimeException;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Rules\PopulationRules;

final class EvaluatePopulationSize
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function __invoke(array $context): array
    {
        if (
            !isset($context['dataset'])
            || !$context['dataset'] instanceof AnalysisDataset
        ) {
            throw new RuntimeException(
                'EvaluatePopulationSize requires an AnalysisDataset.'
            );
        }

        /** @var AnalysisDataset $dataset */
        $dataset = $context['dataset'];

        // Derive participant count from distinct laboratory codes
        $laboratoryCodes = [];

        foreach ($dataset->measurements() as $measurement) {
            $laboratoryCodes[$measurement->laboratoryCode] = true;
        }

        $n = count($laboratoryCodes);

        if ($n < 0) {
            // pure safety net; should never happen
            throw new RuntimeException(
                'Derived participantCount must be non-negative.'
            );
        }

        // Keep existing domain rule
        $context['participantCount'] = $n;
        $context['populationStatus'] = PopulationRules::evaluate($n);

        return $context;
    }
}
