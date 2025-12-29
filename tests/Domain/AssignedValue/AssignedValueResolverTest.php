<?php

namespace Procorad\Procostat\Tests\Domain\AssignedValue;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Statistics\RobustStatisticsInterface;

final class AssignedValueResolverTest extends TestCase
{
    private AssignedValueResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AssignedValueResolver();
    }

    public function test_resolves_certified_assigned_value(): void
    {
        $spec = new AssignedValueSpecification(
            AssignedValueType::CERTIFIED,
            13.8,
            0.5
        );

        $robustStats = new FakeRobustStatistics(
            mean: 14.0,
            stdDev: 0.6
        );

        $assignedValue = $this->resolver->resolve($spec, $robustStats, 12);

        $this->assertInstanceOf(AssignedValue::class, $assignedValue);
        $this->assertSame(AssignedValueType::CERTIFIED, $assignedValue->type());
        $this->assertSame(13.8, $assignedValue->value());
        $this->assertSame(0.5, $assignedValue->expandedUncertaintyK2());
        $this->assertTrue($assignedValue->isIndependent());
    }

    public function test_resolves_robust_mean_assigned_value(): void
    {
        $spec = new AssignedValueSpecification(
            AssignedValueType::ROBUST_MEAN,
            null,
            null
        );

        $robustStats = new FakeRobustStatistics(
            mean: 8000.0,
            stdDev: 432.0
        );

        $assignedValue = $this->resolver->resolve($spec, $robustStats, 46);

        $this->assertSame(AssignedValueType::ROBUST_MEAN, $assignedValue->type());
        $this->assertSame(8000.0, $assignedValue->value());
        $this->assertEqualsWithDelta(
            2 * (1.25 * 432 / sqrt(46)),
            $assignedValue->expandedUncertaintyK2(),
            1e-6
        );
    }
}

/**
 * Local test — RobustStatistics is NOT tested here
 */
final class FakeRobustStatistics implements RobustStatisticsInterface
{
    public function __construct(
        private float $mean,
        private float $stdDev
    ) {}

    public function mean(): float
    {
        return $this->mean;
    }

    public function stdDev(): float
    {
        return $this->stdDev;
    }
}
