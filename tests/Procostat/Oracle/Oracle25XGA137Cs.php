<?php

namespace Procorad\Procostat\Tests\Procostat\Oracle;

final class Oracle25XGA137Cs
{
    public const ASSIGNED_VALUE = 13.8;

    public const ASSIGNED_UNCERTAINTY_K2 = 0.5;

    public const ROBUST_MEAN = 13.9;

    public const ROBUST_STD_DEV = 0.6;

    public const PERFORMANCE_INDICATOR = 'z_score';

    // Note: Lab 41 is classified as WARNING in official report
    // but exceeds |z| > 3 with ISO thresholds → NON_CONFORM in engine
    public const NON_CONFORM_LABS = [41, 56, 72];

    public const WARNING_LABS = [14];
}
