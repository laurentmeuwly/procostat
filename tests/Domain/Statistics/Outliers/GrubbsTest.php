<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Outliers;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;

final class GrubbsTest extends TestCase
{
    public function test_grubbs_detects_outlier(): void
    {
        $values = [10, 11, 30, 13, 12];

        $result = Grubbs::compute($values);

        // The outlier value must be 30
        $this->assertSame(30, $values[$result['index']]);

        // G statistic ~ 1.77
        $this->assertEqualsWithDelta(1.77, $result['G'], 0.05);
    }
}
