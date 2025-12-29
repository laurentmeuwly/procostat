<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use RuntimeException;

final class ResolveAssignedValue implements PipelineStep
{
    public function __construct(
        private readonly AssignedValueResolver $resolver
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'ResolveAssignedValue requires an existing Population.'
            );
        }

        if ($context->robustStatistics === null) {
            throw new RuntimeException(
                'ResolveAssignedValue requires existing RobustStatistics.'
            );
        }

        $context->assignedValue = $this->resolver->resolve(
            $context->dataset->assignedValueSpec,
            $context->robustStatistics,
            $context->population->count()
        );

        return $context;
    }
}
