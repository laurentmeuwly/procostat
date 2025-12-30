<?php

namespace Procorad\Procostat\Application\Pipeline;

use Procorad\Procostat\Application\AnalysisContext;
use RuntimeException;

final class PipelineRunner
{
    /**
     * @param  iterable<PipelineStep>  $steps
     */
    public function __construct(
        private readonly iterable $steps
    ) {}

    public function run(AnalysisContext $context): AnalysisContext
    {
        foreach ($this->steps as $step) {
            if (! $step instanceof PipelineStep) {
                throw new RuntimeException('Pipeline step must implement PipelineStep.');
            }

            $context = $step($context);
        }

        return $context;
    }
}
