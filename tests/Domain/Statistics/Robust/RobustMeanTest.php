<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Robust;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Robust\RobustMean;

final class RobustMeanTest extends TestCase
{
    public function test_robust_mean_is_reasonable(): void
    {
        $values = [9.8, 10.0, 10.1, 10.2, 50.0]; // intentional outlier

        $mean = RobustMean::fromValues($values);

        $this->assertGreaterThan(9.9, $mean);
        $this->assertLessThan(10.2, $mean);
    }
}
