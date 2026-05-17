<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
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

        $spec = $context->dataset->assignedValueSpec;

        // RobustStatistics are required only if the assigned value depends on them
        if ($spec->type === AssignedValueType::ROBUST_MEAN && $context->robustStatistics === null) {
            throw new RuntimeException(
                'ResolveAssignedValue requires RobustStatistics for ROBUST_MEAN assigned value.'
            );
        }

        $context->assignedValue = $this->resolver->resolve(
            $spec,
            $context->robustStatistics,
            $context->population->count()
        );

        return $context;
    }
}
