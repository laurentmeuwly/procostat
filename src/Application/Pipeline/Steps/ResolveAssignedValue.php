<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
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

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'ResolveAssignedValue requires PopulationStatus.'
            );
        }

        $spec = $context->dataset->assignedValueSpec;

        // RobustStatistics are required only if the assigned value depends on them
        if (
            $spec->type === AssignedValueType::ROBUST_MEAN
            && $context->robustStatistics === null
        ) {
            if ($context->descriptiveStatistics === null || $context->descriptiveStatistics->mean === null) {
                throw new RuntimeException(
                    'ResolveAssignedValue requires DescriptiveStatistics for ROBUST_MEAN '
                    . 'fallback in descriptive_only branch.'
                );
            }

            // Incertitude de la moyenne arithmétique : U = 2 * s / sqrt(n)
            $n   = $context->population->count();
            $s   = $context->descriptiveStatistics->standardDeviation;
            $u   = ($s !== null && $n > 1)
                ? 2.0 * $s / sqrt($n)
                : null;

            $context->assignedValue = AssignedValue::robust(
                value:                 $context->descriptiveStatistics->mean,
                expandedUncertaintyK2: $u,
            );

            return $context;
        }

        // Standard case (CERTIFIED or ROBUST_MEAN with robusts stats)
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
