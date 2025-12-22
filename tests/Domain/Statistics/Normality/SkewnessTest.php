<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Normality;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Normality\Skewness;

final class SkewnessTest extends TestCase
{
    public function test_skewness_of_symmetric_distribution_is_zero(): void
    {
        $values = [1, 2, 3, 4, 5];

        $skew = Skewness::compute($values);

        $this->assertEqualsWithDelta(0.0, $skew, 1e-6);
    }

}
