<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Measurements\Measurement;
use RuntimeException;

final class ValidateDataset implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        $dataset = $context->dataset;

        if ($dataset->measurements() === []) {
            throw new RuntimeException(
                'AnalysisDataset must contain at least one measurement.'
            );
        }

        $seenLaboratoryCodes = [];

        foreach ($dataset->measurements() as $measurement) {
            if (!$measurement instanceof Measurement) {
                throw new RuntimeException(
                    'Invalid measurement in dataset.'
                );
            }

            $labCode = trim($measurement->laboratoryCode());

            if ($labCode === '') {
                throw new RuntimeException(
                    'Measurement requires a non-empty laboratory code.'
                );
            }

            if (isset($seenLaboratoryCodes[$labCode])) {
                throw new RuntimeException(
                    sprintf('Duplicate laboratory code detected: %s', $labCode)
                );
            }

            $seenLaboratoryCodes[$labCode] = true;

            if (!is_finite($measurement->value())) {
                throw new RuntimeException(
                    'Measurement value must be a finite number.'
                );
            }

            if ($measurement->uncertainty() !== null) {
                $u = $measurement->uncertainty()->standard();

                if ($u < 0) {
                    throw new RuntimeException(
                        'Measurement uncertainty must be non-negative.'
                    );
                }
            }

            if ($measurement->limitOfDetection() !== null) {
                if ($measurement->limitOfDetection() < 0) {
                    throw new RuntimeException(
                        'Limit of detection must be non-negative.'
                    );
                }
            }
        }

        return $context;
    }
}
