<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Robust;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Robust\RobustStdDev;

final class RobustStdDevTest extends TestCase
{
    public function test_internal_dataset(): void
    {
        $values = [9.8, 10.0, 10.1, 10.2, 50.0]; // intentional outlier

        $stdDev = RobustStdDev::fromValues($values);

        $this->assertEqualsWithDelta(0.4879, $stdDev, 1e-4);
    }

    public function test_real_procorad_dataset(): void
    {
        $values = [
            10.63, 13.83, 14.90, 15.11, 16.54, 17.00,
            17.63, 19.90, 21.90, 22.50, 27.30, 34.70, 62.00
        ];

        $stdDev = RobustStdDev::fromValues($values);

        $this->assertEqualsWithDelta(7.38, $stdDev, 0.01);
    }

    public function test_iso_13528_example(): void
    {
        $values = [
            0.0400, 0.0550, 0.1780, 0.2020, 0.2060, 0.2270, 0.2280,
            0.2300, 0.2300, 0.2350, 0.2360, 0.2370, 0.2430, 0.2440,
            0.2450, 0.2555, 0.2600, 0.2640, 0.2670, 0.2700, 0.2730,
            0.2740, 0.2740, 0.2780, 0.2810, 0.2870, 0.2870, 0.2880,
            0.2890, 0.2950, 0.2960, 0.3110, 0.3310, 0.4246
        ];

        $stdDev = RobustStdDev::fromValues($values);

        $this->assertEqualsWithDelta(0.0395, $stdDev, 1e-5);
    }

    public function test_constant_values(): void
    {
        $values = [10, 10, 10, 10, 10];

        $stdDev = RobustStdDev::fromValues($values);

        $this->assertEquals(0.0, $stdDev);
    }

    public function test_order_does_not_change_result(): void
    {
        $values1 = [9.8, 10.0, 10.1, 10.2, 50.0];
        $values2 = [50.0, 10.2, 9.8, 10.1, 10.0];

        $this->assertEqualsWithDelta(
            RobustStdDev::fromValues($values1),
            RobustStdDev::fromValues($values2),
            1e-10
        );
    }
}
