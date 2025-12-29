<?php

namespace Procorad\Procostat\Domain\Audit;

use DateTimeImmutable;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Rules\Thresholds;
use Procorad\Procostat\Support\Version;

final class AuditBuilder
{
    public static function analysisDecision(
        string $decision,
        string $basis,
        ?string $normReference = null
    ): AuditEvent {
        return new AuditEvent(
            type: AuditEventType::ANALYSIS,
            laboratoryCode: null,
            decision: $decision,
            decisionBasis: $basis,
            decisionScore: null,
            normReference: $normReference,
            conformityLimit: null,
            discussionLimit: null,
            occurredAt: new DateTimeImmutable(),
            engineVersion: Version::current()
        );
    }

    public static function labDecision(
        string $laboratoryCode,
        FitnessStatus $status,
        string $decisionBasis,
        float $decisionScore,
        Thresholds $thresholds
    ): AuditEvent {
        return new AuditEvent(
            type: AuditEventType::LAB_DECISION,
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
