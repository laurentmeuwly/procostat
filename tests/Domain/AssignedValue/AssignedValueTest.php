<?php

namespace Procorad\Procostat\Tests\Domain\AssignedValue;

use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use PHPUnit\Framework\TestCase;

final class AssignedValueTest extends TestCase
{
    public function test_certified_assigned_value_is_independent(): void
    {
        $value = AssignedValue::certified(13.8, 0.5);

        $this->assertSame(AssignedValueType::CERTIFIED, $value->type());
        $this->assertSame(13.8, $value->value());
        $this->assertSame(0.5, $value->expandedUncertaintyK2());
        $this->assertSame(0.25, $value->standardUncertainty());
        $this->assertTrue($value->isIndependent());
    }

    public function test_robust_assigned_value_is_not_independent(): void
    {
        $value = AssignedValue::robust(8000, 159);

        $this->assertSame(AssignedValueType::ROBUST_MEAN, $value->type());
        $this->assertFalse($value->isIndependent());
    }
}
