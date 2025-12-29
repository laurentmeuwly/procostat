<?php
namespace Procorad\Procostat\Domain\AssignedValue;

enum AssignedValueType: string
{
    case CERTIFIED = 'certified_value';
    case ROBUST_MEAN = 'robust_mean';
}
