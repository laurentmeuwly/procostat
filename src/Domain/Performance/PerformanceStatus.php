<?php

namespace Procorad\Procostat\Domain\Performance;

enum PerformanceStatus: string
{
    case CONFORME = 'conforme';
    case DISCUTABLE = 'discutable';
    case NON_CONFORME = 'non_conforme';

    public static function fromAbsoluteValue(float $value): self
    {
        $abs = abs($value);

        return match (true) {
            $abs < 2 => self::CONFORME,
            $abs <= 3 => self::DISCUTABLE,
            default => self::NON_CONFORME,
        };
    }
}
