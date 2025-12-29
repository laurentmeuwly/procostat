<?php

namespace Procorad\Procostat\Tests\Domain\Measurements;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use RuntimeException;

final class MeasurementTest extends TestCase
{
    public function test_valid_measurement_with_uncertainty_and_ld(): void
    {
        $measurement = new Measurement(
            laboratoryCode: 'LAB01',
            value: 12.5,
            uncertainty: new Uncertainty(0.8),
            limitOfDetection: 0.5
        );

        $this->assertSame('LAB01', $measurement->laboratoryCode());
        $this->assertSame(12.5, $measurement->value());
        $this->assertTrue($measurement->hasUncertainty());
        $this->assertTrue($measurement->hasLimitOfDetection());
        $this->assertSame(0.5, $measurement->limitOfDetection());
    }

    public function test_valid_measurement_without_uncertainty_and_ld(): void
    {
        $measurement = new Measurement(
            laboratoryCode: 'LAB02',
            value: 5.0
        );

        $this->assertSame('LAB02', $measurement->laboratoryCode());
        $this->assertSame(5.0, $measurement->value());
        $this->assertFalse($measurement->hasUncertainty());
        $this->assertFalse($measurement->hasLimitOfDetection());
        $this->assertNull($measurement->uncertainty());
        $this->assertNull($measurement->limitOfDetection());
    }

    public function test_empty_laboratory_code_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        new Measurement(
            laboratoryCode: '   ',
            value: 10.0
        );
    }

    public function test_non_finite_value_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        new Measurement(
            laboratoryCode: 'LAB03',
            value: INF
        );
    }

    public function test_negative_limit_of_detection_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        new Measurement(
            laboratoryCode: 'LAB04',
            value: 3.2,
            uncertainty: null,
            limitOfDetection: -0.1
        );
    }

    public function test_negative_uncertainty_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        new Measurement(
            laboratoryCode: 'LAB05',
            value: 7.5,
            uncertainty: new Uncertainty(-0.2)
        );
    }
}
