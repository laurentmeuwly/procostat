<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use RuntimeException;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;

final class ValidateDataset
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
                'ValidateDataset requires an AnalysisDataset.'
            );
        }

        /** @var AnalysisDataset $dataset */
        $dataset = $context['dataset'];

        foreach ($dataset->measurements() as $measurement) {
            if (!$measurement instanceof Measurement) {
                throw new RuntimeException(
                    'Invalid measurement in dataset.'
                );
            }

            if (trim($measurement->laboratoryCode) === '') {
                throw new RuntimeException(
                    'Measurement requires laboratoryCode.'
                );
            }

            if (!is_finite($measurement->value)) {
                throw new RuntimeException(
                    'Measurement value must be a finite number.'
                );
            }

            if ($measurement->uncertainty !== null) {
                $u = $measurement->uncertainty->standard();
                if ($u < 0) {
                    throw new RuntimeException(
                        'Measurement uncertainty must be non-negative.'
                    );
                }
            }
        }

        return $context;
    }
}
