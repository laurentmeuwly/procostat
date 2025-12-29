<?php

namespace Procorad\Procostat\Domain\AssignedValue;

final class AssignedValue
{
    private function __construct(
        private readonly AssignedValueType $type,
        private readonly float $value,
        private readonly float $expandedUncertaintyK2
    ) {}

    /**
     * Named constructors
     **/

    public static function certified(
        float $value,
        float $expandedUncertaintyK2
    ): self {
        return new self(
            AssignedValueType::CERTIFIED,
            $value,
            $expandedUncertaintyK2
        );
    }

    public static function robust(
        float $value,
        float $expandedUncertaintyK2
    ): self {
        return new self(
            AssignedValueType::ROBUST_MEAN,
            $value,
            $expandedUncertaintyK2
        );
    }

    /**
     * Getters
     */
    public function type(): AssignedValueType
    {
        return $this->type;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function expandedUncertaintyK2(): float
    {
        return $this->expandedUncertaintyK2;
    }

    /**
     * Standard uncertainty (u = U / k, where k=2)
     */
    public function standardUncertainty(): float
    {
        return $this->expandedUncertaintyK2 / 2;
    }

    /**
     * A certified value is independent
     * A consensus (robust) value is not
     */
    public function isIndependent(): bool
    {
        return $this->type === AssignedValueType::CERTIFIED;
    }
}
