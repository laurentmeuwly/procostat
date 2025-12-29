<?php

namespace Procorad\Procostat\Tests\Domain\AssignedValue;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;

final class AssignedValueSpecificationTest extends TestCase
{
    public function test_certified_assigned_value_is_valid(): void
    {
        $spec = new AssignedValueSpecification(
            AssignedValueType::CERTIFIED,
            13.8,
            0.5
        );

        $this->assertSame(AssignedValueType::CERTIFIED, $spec->type);
        $this->assertSame(13.8, $spec->value);
        $this->assertSame(0.5, $spec->expandedUncertaintyK2);
    }

    public function test_robust_mean_assigned_value_is_valid(): void
    {
        $spec = new AssignedValueSpecification(
            AssignedValueType::ROBUST_MEAN,
            null,
            null
        );

        $this->assertSame(AssignedValueType::ROBUST_MEAN, $spec->type);
        $this->assertNull($spec->value);
        $this->assertNull($spec->expandedUncertaintyK2);
    }

    public function test_certified_without_value_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssignedValueSpecification(
            AssignedValueType::CERTIFIED,
            null,
            null
        );
    }

    public function test_certified_without_uncertainty_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssignedValueSpecification(
            AssignedValueType::CERTIFIED,
            13.8,
            null
        );
    }

    public function test_robust_mean_with_value_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssignedValueSpecification(
            AssignedValueType::ROBUST_MEAN,
            8000.0,
            null
        );
    }

    public function test_robust_mean_with_uncertainty_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssignedValueSpecification(
            AssignedValueType::ROBUST_MEAN,
            null,
            159.0
        );
    }
}
