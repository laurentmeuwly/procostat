<?php

namespace Procorad\Procostat\Support;

final class Version
{
    /**
     * Version of the PROCOSTAT engine.
     *
     * Must be updated with each release
     * that has a scientific or normative impact.
     */
    private const VERSION = '1.1.0';

    public static function current(): string
    {
        return self::VERSION;
    }
}
