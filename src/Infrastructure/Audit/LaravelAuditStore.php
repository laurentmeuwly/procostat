<?php

namespace Procorad\Procostat\Infrastructure\Audit;

use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Domain\Audit\AuditEvent;

final class LaravelAuditStore implements AuditStore
{
    public function store(AuditEvent $event): void
    {
        // Mapping to Eloquent or Query Builder
        // To be implemented on the cient application side (i.e. Procorad)
    }
}
