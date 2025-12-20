<?php

namespace Procorad\Procostat\Facades;

use Illuminate\Support\Facades\Facade;
use Procorad\Procostat\Application\RunAnalysis;

/**
 * @method static \Procorad\Procostat\DTO\ProcostatResult run(array $input)
 */
final class Procostat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RunAnalysis::class;
    }
}
