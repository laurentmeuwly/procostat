<?php

namespace Procorad\Procostat\Domain\Rules;

enum PopulationStatus: string
{
    case NOT_EXPLOITABLE = 'not_exploitable';   // n < 3
    case DESCRIPTIVE_ONLY = 'descriptive_only'; // 3 ≤ n ≤ 6
    case FULL_EVALUATION = 'full_evaluation';   // n ≥ 7
}
