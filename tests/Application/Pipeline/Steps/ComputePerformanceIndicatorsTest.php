<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\ComputePerformanceIndicators;
use Procorad\Procostat\Domain\Performance\PerformanceStatus;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Tests\Support\Procostat\TestContextFactory;


final class ComputePerformanceIndicatorsTest extends TestCase
{
    private ComputePerformanceIndicators $step;

    protected function setUp(): void
    {
        $this->step = new ComputePerformanceIndicators();
    }

    public function test_uses_zprime_when_mrc_and_iso_condition_satisfied(): void
    {
        $context = TestContextFactory::withSingleLab(
            isMrc: true,
            n: 20,
            xStar: 100,
            sStar: 5,
            xRef: 100,
            uRef: 1,
            xLab: 110,
            uLab: 2,
            sigmaPt: 10
        );

        $result = ($this->step)($context);

        $indicators = $result['performance_indicators']['LAB1'];

        $this->assertSame(IndicatorType::Z_PRIME, $indicators->declaredIndicator);
        $this->assertNotNull($indicators->zPrime);
        $this->assertNull($indicators->z);
    }

    public function test_uses_z_when_reference_is_not_mrc(): void
    {
        $context = TestContextFactory::withSingleLab(
            isMrc: false,
            n: 20,
            xStar: 100,
            sStar: 5,
            xRef: 100,
            uRef: 1,
            xLab: 110,
            uLab: 2,
            sigmaPt: 10
        );

        $result = ($this->step)($context);

        $indicators = $result['performance_indicators']['LAB1'];

        $this->assertSame(IndicatorType::Z, $indicators->declaredIndicator);
        $this->assertNotNull($indicators->z);
        $this->assertNull($indicators->zPrime);
    }

    public function test_zeta_is_always_computed(): void
    {
        $context = TestContextFactory::withSingleLab();

        $result = ($this->step)($context);

        $zeta = $result['performance_indicators']['LAB1']->zeta;

        $this->assertIsFloat($zeta);
    }

    public function test_performance_status_thresholds(): void
    {
        $context = TestContextFactory::withDeclaredValue(1.99);
        $status = ($this->step)($context)['performance_indicators']['LAB1']->status;
        $this->assertSame(PerformanceStatus::CONFORME, $status);

        $context = TestContextFactory::withDeclaredValue(2.00);
        $status = ($this->step)($context)['performance_indicators']['LAB1']->status;
        $this->assertSame(PerformanceStatus::DISCUTABLE, $status);

        $context = TestContextFactory::withDeclaredValue(3.00);
        $status = ($this->step)($context)['performance_indicators']['LAB1']->status;
        $this->assertSame(PerformanceStatus::DISCUTABLE, $status);

        $context = TestContextFactory::withDeclaredValue(3.01);
        $status = ($this->step)($context)['performance_indicators']['LAB1']->status;
        $this->assertSame(PerformanceStatus::NON_CONFORME, $status);
    }

    public function test_n_lower_than_seven_does_not_break_computation(): void
    {
        $context = TestContextFactory::withSingleLab(
            n: 5,
            isMrc: true
        );

        $result = ($this->step)($context);

        $indicators = $result['performance_indicators']['LAB1'];

        $this->assertNotNull($indicators->declaredValue());
        $this->assertInstanceOf(PerformanceStatus::class, $indicators->status);
    }

    public function test_performance_indicators_are_immutable(): void
    {
        $context = TestContextFactory::withSingleLab();

        $result = ($this->step)($context);

        $indicators = $result['performance_indicators']['LAB1'];

        $this->expectException(\Error::class);
        $indicators->z = 999;
    }

}
