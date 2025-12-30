<?php

namespace Procorad\Procostat\Tests\Support;

use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;
use Procorad\Procostat\Infrastructure\Normality\PhpNormalityAdapter;

final class TestAnalysisEngineFactory
{
    public static function createIso13528Engine(): RunAnalysis
    {
        return new RunAnalysis(
            normalityAdapter: new PhpNormalityAdapter(),
            auditStore: new NullAuditStore(),
            assignedValueResolver: new AssignedValueResolver(),
            thresholdsResolver: new ThresholdsResolver(),
            thresholdStandard: 'iso13528'
        );
    }
}
