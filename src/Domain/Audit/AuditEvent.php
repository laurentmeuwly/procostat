<?php

namespace Procorad\Procostat\Domain\Audit;

use DateTimeImmutable;

final class AuditEvent
{
    public function __construct(
        public readonly string $laboratoryCode,
        public readonly string $decision,
        public readonly string $decisionBasis,
        public readonly float $decisionScore,
        public readonly string $normReference,
        public readonly float $conformityLimit,
        public readonly float $discussionLimit,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $engineVersion
    ) {
    }
}
