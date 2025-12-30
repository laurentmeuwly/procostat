<?php

namespace Procorad\Procostat\Tests\Procostat\Oracle;

final class Oracle25XGA88Y
{
    public const ASSIGNED_VALUE = 11.7;
    public const ASSIGNED_UNCERTAINTY_K2 = 0.5;

    public const ROBUST_MEAN = 12.0;
    public const ROBUST_STD_DEV = 0.9;

    public const PERFORMANCE_INDICATOR = 'z_score';

    /** @var int[] */
    public const NON_CONFORM_LABS = [56];

    /** @var int[] */
    public const WARNING_LABS = [35, 41, 49];
}
