<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Performance;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Performance\ZScore;

final class ZScoreTest extends TestCase
{
    public function test_z_score_is_computed_according_to_iso_13528(): void
    {
        $z = ZScore::compute(
            result: 115.0,
            assignedValue: 100.0,
            sigmaPt: 10.0
        );

        $this->assertEquals(1.5, $z);
    }

    public function test_z_score_can_be_negative(): void
    {
        $z = ZScore::compute(
            result: 85.0,
            assignedValue: 100.0,
            sigmaPt: 10.0
        );

        $this->assertEquals(-1.5, $z);
    }

    public function test_z_score_throws_exception_when_sigma_is_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZScore::compute(
            result: 100.0,
            assignedValue: 100.0,
            sigmaPt: 0.0
        );
    }
}
