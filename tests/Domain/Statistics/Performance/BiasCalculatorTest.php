<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Performance;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Performance\BiasCalculator;

final class BiasCalculatorTest extends TestCase
{
    public function test_bias_is_computed_as_relative_percentage(): void
    {
        $bias = BiasCalculator::compute(
            result: 110.0,
            referenceValue: 100.0
        );

        $this->assertEquals(10.0, $bias);
    }

    public function test_bias_can_be_negative(): void
    {
        $bias = BiasCalculator::compute(
            result: 75.0,
            referenceValue: 100.0
        );

        $this->assertEquals(-25.0, $bias);
    }

    public function test_bias_throws_exception_when_reference_value_is_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BiasCalculator::compute(
            result: 100.0,
            referenceValue: 0.0
        );
    }
}
