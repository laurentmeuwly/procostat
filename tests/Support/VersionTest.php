<?php

namespace Procorad\Procostat\Tests\Support;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Support\Version;

final class VersionTest extends TestCase
{
    public function test_version_is_not_empty(): void
    {
        $this->assertNotEmpty(Version::current());
    }
}
