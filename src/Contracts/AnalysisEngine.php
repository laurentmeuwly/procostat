<?php

namespace Procorad\Procostat\Contract;

use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\ProcostatResult;

interface AnalysisEngine
{
    public function analyze(AnalysisDataset $dataset): ProcostatResult;
}
