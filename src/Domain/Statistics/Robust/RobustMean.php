<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

final class RobustMean
{
    /**
     * ISO 13528 Algorithm A — robust mean
     *
     * @param  float[]  $values
     */
    public static function fromValues(array $values): float
    {
        return RobustEstimator::estimate($values)[0];
    }
}
