<?php

namespace Procorad\Procostat\Contracts;

use Procorad\Procostat\Domain\Audit\AuditEvent;

interface AuditStore
{
    public function store(AuditEvent $event): void;
}
