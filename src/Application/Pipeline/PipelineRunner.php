<?php

namespace Procorad\Procostat\Application\Pipeline;

use RuntimeException;

final class PipelineRunner
{
    /**
     * @param iterable<callable> $steps
     */
    public function __construct(
        private readonly iterable $steps
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function run(array $context): array
    {
        foreach ($this->steps as $step) {
            if (!is_callable($step)) {
                throw new RuntimeException('Pipeline step is not callable.');
            }

            $context = $step($context);
        }

        return $context;
    }
}
