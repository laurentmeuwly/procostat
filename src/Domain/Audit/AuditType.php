<?php

namespace Procorad\Procostat\Domain\Audit;

enum AuditEventType: string
{
    case ANALYSIS = 'analysis';
    case LAB_DECISION = 'lab_decision';
}
