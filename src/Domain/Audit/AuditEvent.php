<?php

namespace Procorad\Procostat\Domain\Audit;

use DateTimeImmutable;

final class AuditEvent
{
    public function __construct(
        public readonly AuditEventType $type,

        // Context
        public readonly ?string $laboratoryCode,

        // Decision
        public readonly string $decision,
        public readonly string $decisionBasis,
        public readonly ?float $decisionScore,

        // Normative references
        public readonly ?string $normReference,
        public readonly ?float $conformityLimit,
        public readonly ?float $discussionLimit,

        // Metatdata
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $engineVersion
    ) {
    }
}
