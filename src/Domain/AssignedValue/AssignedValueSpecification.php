<?php

namespace Procorad\Procostat\Domain\AssignedValue;

use InvalidArgumentException;

final class AssignedValueSpecification
{
    public function __construct(
        public readonly AssignedValueType $type,
        public readonly ?float $value,
        public readonly ?float $expandedUncertaintyK2,
    ) {
        // Contractual lock-in
        if ($this->type === AssignedValueType::CERTIFIED) {
            if ($this->value === null || $this->expandedUncertaintyK2 === null) {
                throw new InvalidArgumentException(
                    'CERTIFIED assigned value requires value and expanded uncertainty.'
                );
            }
        }

        if ($this->type === AssignedValueType::ROBUST_MEAN) {
            if ($this->value !== null || $this->expandedUncertaintyK2 !== null) {
                throw new InvalidArgumentException(
                    'ROBUST_MEAN assigned value must not provide value or uncertainty.'
                );
            }
        }
    }
}
