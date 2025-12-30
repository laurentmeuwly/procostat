<?php

namespace Procorad\Procostat\Domain\Statistics\Robust;

final class AssignedValueCalculator
{
    /**
     * Compute assigned value according to ISO 13528
     *
     * @param  float[]  $values
     */
    public static function fromValues(array $values): float
    {
        return RobustMean::fromValues($values);
    }
}
