<?php

namespace Procorad\Procostat\Domain\Statistics;

interface RobustStatisticsInterface
{
    public function mean(): float;

    public function stdDev(): float;
}
