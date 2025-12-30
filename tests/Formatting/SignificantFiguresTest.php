<?php

namespace Procorad\Procostat\Tests\Formatting;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Formatting\SignificantFigures;

final class SignificantFiguresTest extends TestCase
{
    public function test_round_to_significant_figures(): void
    {
        $this->assertSame(
            10.1,
            SignificantFigures::roundToSignificantFigures(10.1234, 3)
        );

        $this->assertSame(
            0.0123,
            SignificantFigures::roundToSignificantFigures(0.012345, 3)
        );
    }

    public function test_decimals_from_uncertainty(): void
    {
        $this->assertSame(
            2,
            SignificantFigures::decimalsFromUncertainty(0.045, 2)
        );

        $this->assertSame(
            3,
            SignificantFigures::decimalsFromUncertainty(0.0045, 2)
        );
    }

    public function test_round_to_uncertainty(): void
    {
        $this->assertSame(
            10.12,
            SignificantFigures::roundToUncertainty(10.1234, 0.045)
        );
    }
}
