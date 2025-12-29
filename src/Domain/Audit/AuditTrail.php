<?php

namespace Procorad\Procostat\Domain\Audit;

final class AuditTrail
{
    /** @var AuditEvent[] */
    private array $events = [];

    public function add(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    /** @return AuditEvent[] */
    public function all(): array
    {
        return $this->events;
    }

    /** @return AuditEvent[] */
    public function analysisEvents(): array
    {
        return array_filter(
            $this->events,
            fn (AuditEvent $e) => $e->type === AuditEventType::ANALYSIS
        );
    }

    /** @return AuditEvent[] */
    public function labEvents(): array
    {
        return array_filter(
            $this->events,
            fn (AuditEvent $e) => $e->type === AuditEventType::LAB_DECISION
        );
    }
}
