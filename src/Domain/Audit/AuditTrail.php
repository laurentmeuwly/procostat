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
}
