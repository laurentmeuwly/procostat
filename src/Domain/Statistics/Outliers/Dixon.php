<?php

namespace Procorad\Procostat\Domain\Statistics\Outliers;

use RuntimeException;

final class Dixon
{
    /**
     * Dixon Q test (single extreme value)
     *
     * @param  float[]  $values
     * @return array{Q: float, position: 'min'|'max'}
     */
    public static function compute(array $values): array
    {
        $n = count($values);
        if ($n < 3 || $n > 25) {
            throw new RuntimeException(
                'Dixon test requires 3 to 25 values.'
            );
        }

        sort($values);

        $range = $values[$n - 1] - $values[0];
        if ($range == 0.0) {
            return ['Q' => 0.0, 'position' => 'none'];
        }

        $Qmin = ($values[1] - $values[0]) / $range;
        $Qmax = ($values[$n - 1] - $values[$n - 2]) / $range;

        if ($Qmin > $Qmax) {
            return ['Q' => $Qmin, 'position' => 'min'];
        }

        return ['Q' => $Qmax, 'position' => 'max'];
    }
}
