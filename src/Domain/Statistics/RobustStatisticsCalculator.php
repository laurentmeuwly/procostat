<?php

namespace Procorad\Procostat\Domain\Statistics;

use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Statistics\Robust\RobustMean;
use Procorad\Procostat\Domain\Statistics\Robust\RobustStdDev;

final class RobustStatisticsCalculator
{
    public static function compute(Population $population): RobustStatistics
    {
        $values = [];

        foreach ($population->measurements() as $measurement) {
            $values[] = $measurement->value();
        }

        $mean = RobustMean::fromValues($values);
        $stdDev = RobustStdDev::fromValues($values);

        return new RobustStatistics($mean, $stdDev);
    }
}
