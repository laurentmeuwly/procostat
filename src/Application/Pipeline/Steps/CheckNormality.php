<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use RuntimeException;

final class CheckNormality implements PipelineStep
{
    public function __construct(
        private readonly NormalityAdapter $normalityAdapter
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'CheckNormality requires an existing Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'CheckNormality requires PopulationStatus.'
            );
        }

        // Default: no normality result
        $context->normalityResult = null;

        if (! ApplicabilityRules::canCheckNormality($context->populationStatus)) {
            return $context;
        }

        $values = array_map(
            static fn ($measurement) => $measurement->value(),
            $context->population->measurements()
        );

        $context->normalityResult = $this->normalityAdapter->analyze($values);

        return $context;
    }
}
