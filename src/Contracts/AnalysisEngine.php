<?php

namespace Procorad\Procostat\Contracts;

use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\AnalysisOutput;
use Procorad\Procostat\DTO\ExpertDecision;
use Procorad\Procostat\DTO\ProcostatResult;

interface AnalysisEngine
{
    public function analyze(AnalysisDataset $dataset, ?ExpertDecision $expertDecision = null): ProcostatResult;

    public function analyzeWithTrace(AnalysisDataset $dataset, ?ExpertDecision $expertDecision = null): AnalysisOutput;
}
