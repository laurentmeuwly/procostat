<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

final class ComputePerformanceIndicators
{
    /**
     * Placeholder implementation.
     *
     * Performance indicators (z, z', zeta, bias)
     * are assumed to be already present in the context.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function __invoke(array $context): array
    {
        // No computation at this stage
        return $context;
    }
}
