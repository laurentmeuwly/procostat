<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use RuntimeException;

final class ValidateDataset
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function __invoke(array $context): array
    {
        if (!isset($context['laboratoryCode'])) {
            throw new RuntimeException(
                'ValidateDataset requires laboratoryCode.'
            );
        }

        if (!isset($context['thresholdStandard'])) {
            throw new RuntimeException(
                'ValidateDataset requires thresholdStandard.'
            );
        }

        // Placeholder: full dataset validation will be implemented later

        return $context;
    }
}
