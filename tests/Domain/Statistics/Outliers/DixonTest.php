<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Outliers;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Outliers\Dixon;

final class DixonTest extends TestCase
{
    public function test_dixon_detects_high_outlier(): void
    {
        $values = [10, 11, 12, 13, 30];

        $result = Dixon::compute($values);

        $this->assertSame('max', $result['position']);
        $this->assertGreaterThan(0.4, $result['Q']);
    }
}
