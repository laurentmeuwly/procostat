<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

final class RobustStdDev
{
    /**
     * ISO 13528 Algorithm A — robust standard deviation
     *
     * @param  float[]  $values
     */
    public static function fromValues(array $values): float
    {
        return RobustEstimator::estimate($values)[1];
    }
}
