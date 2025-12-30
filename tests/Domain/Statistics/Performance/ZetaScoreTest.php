<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Performance;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Performance\ZetaScore;

final class ZetaScoreTest extends TestCase
{
    public function test_zeta_score_is_computed_according_to_iso_13528(): void
    {
        $zeta = ZetaScore::compute(
            result: 105.0,
            assignedValue: 100.0,
            uResult: 2.0,
            uAssigned: 3.0
        );

        $this->assertEqualsWithDelta(1.39, $zeta, 0.01);
    }

    public function test_zeta_score_throws_exception_when_combined_uncertainty_is_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZetaScore::compute(
            result: 100.0,
            assignedValue: 100.0,
            uResult: 0.0,
            uAssigned: 0.0
        );
    }
}
