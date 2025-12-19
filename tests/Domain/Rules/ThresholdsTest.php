<?php

namespace Procorad\Procostat\Tests\Domain\Rules;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Norms\NormReference;
use Procorad\Procostat\Domain\Rules\Thresholds;

final class ThresholdsTest extends TestCase
{
    public function test_iso_13528_thresholds_are_correct(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->assertEquals(2.0, $thresholds->conformityLimit);
        $this->assertEquals(3.0, $thresholds->discussionLimit);
    }

    public function test_invalid_thresholds_throw_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Thresholds(
            conformityLimit: 3.0,
            discussionLimit: 2.0,
            normReference: NormReference::ISO_13528_2022
        );
    }
}
