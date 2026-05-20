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

    public function test_grubbs_detects_outlier_2(): void
    {
        $values = [0.0825, 0.00334, 0.00382, 0.00233, 0.00366, 0.00277];

        $result = Grubbs::compute($values);

        // The outlier value must be 0.0825
        $this->assertSame(0.0825, $values[$result['index']]);

        // G statistic ~ 2.04
        $this->assertEqualsWithDelta(2.04, $result['G'], 0.001);
    }
}
