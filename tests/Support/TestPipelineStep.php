<?php

namespace Procorad\Procostat\Tests\Support;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;

final class TestPipelineStep implements PipelineStep
{
    public function __construct(
        private string $name,
        private array &$executionOrder,
        private \Closure $effect
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        $this->executionOrder[] = $this->name;
        ($this->effect)($context);

        return $context;
    }
}
