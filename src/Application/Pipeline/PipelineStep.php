<?php

namespace Procorad\Procostat\Application\Pipeline;

use Procorad\Procostat\Application\AnalysisContext;

interface PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext;
}
