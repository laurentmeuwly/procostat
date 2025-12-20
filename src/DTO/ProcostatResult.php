<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\DTO\LabEvaluation;

final class ProcostatResult
{
    public function __construct(
        public readonly LabEvaluation $labEvaluation,
        public readonly AuditTrail $auditTrail,
        public readonly string $engineVersion
    ) {
    }
}
