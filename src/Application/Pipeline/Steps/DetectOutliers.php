<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use Procorad\Procostat\Domain\Statistics\Outliers\Dixon;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;
use RuntimeException;

final class DetectOutliers implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'DetectOutliers requires an existing Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'DetectOutliers requires PopulationStatus.'
            );
        }

        // Default: no outliers detected / not applicable
        $context->outliers = null;

        if ($context->normalityResult === null) {
            return $context;
        }

        if (! ApplicabilityRules::canDetectOutliers(
            $context->populationStatus,
            $context->normalityResult->isNormal
        )) {
            return $context;
        }

        $values = array_map(
            static fn ($measurement) => $measurement->value(),
            $context->population->measurements()
        );

        $context->outliers = [
            'dixon' => Dixon::compute($values),
            'grubbs' => Grubbs::compute($values),
        ];

        return $context;
    }
}
