<?php

namespace Procorad\Procostat\Domain\Audit;

use DateTimeImmutable;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Rules\Thresholds;
use Procorad\Procostat\Support\Version;

final class AuditBuilder
{
    public static function fromDecision(
        string $laboratoryCode,
        FitnessStatus $status,
        string $decisionBasis,
        float $decisionScore,
        Thresholds $thresholds
    ): AuditEvent {
        return new AuditEvent(
            laboratoryCode: $laboratoryCode,
            decision: $status->value,
            decisionBasis: $decisionBasis,
            decisionScore: $decisionScore,
            normReference: $thresholds->normReference,
            conformityLimit: $thresholds->conformityLimit,
            discussionLimit: $thresholds->discussionLimit,
            occurredAt: new DateTimeImmutable(),
            engineVersion: Version::current()
        );
    }
}
