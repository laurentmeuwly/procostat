<?php

namespace Procorad\Procostat\Infrastructure\Audit;

use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Domain\Audit\AuditEvent;

final class NullAuditStore implements AuditStore
{
    public function store(AuditEvent $event): void
    {
        // intentionally do nothing
    }
}
