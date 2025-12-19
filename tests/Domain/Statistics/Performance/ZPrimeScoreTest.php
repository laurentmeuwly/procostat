<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Performance;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Performance\ZPrimeScore;

final class ZPrimeScoreTest extends TestCase
{
    public function test_z_prime_score_is_computed_according_to_iso_13528(): void
    {
        $zPrime = ZPrimeScore::compute(
            result: 115.0,
            assignedValue: 100.0,
            sigmaPt: 10.0,
            uAssigned: 5.0
        );

        $this->assertEqualsWithDelta(1.34, $zPrime, 0.01);
    }

    public function test_z_prime_score_reduces_to_z_score_when_uncertainty_is_zero(): void
    {
        $zPrime = ZPrimeScore::compute(
            result: 115.0,
            assignedValue: 100.0,
            sigmaPt: 10.0,
            uAssigned: 0.0
        );

        $this->assertEquals(1.5, $zPrime);
    }

    public function test_z_prime_score_throws_exception_when_sigma_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZPrimeScore::compute(
            result: 100.0,
            assignedValue: 100.0,
            sigmaPt: 0.0,
            uAssigned: 1.0
        );
    }
}
