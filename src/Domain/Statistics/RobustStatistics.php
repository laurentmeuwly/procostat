<?php

namespace Procorad\Procostat\Domain\Statistics;

use Procorad\Procostat\Domain\Statistics\RobustStatisticsInterface;

final class RobustStatistics implements RobustStatisticsInterface
{
    public function __construct(
        private readonly float $mean,
        private readonly float $stdDev
    ) {}

    public function mean(): float
    {
        return $this->mean;
    }

    public function stdDev(): float
    {
        return $this->stdDev;
    }
}
