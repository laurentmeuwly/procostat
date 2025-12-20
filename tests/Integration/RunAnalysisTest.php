<?php

namespace Procorad\Procostat\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Norms\NormReference;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;

final class RunAnalysisTest extends TestCase
{
    public function test_run_analysis_produces_lab_evaluation_and_audit(): void
    {
        $useCase = new RunAnalysis(
            thresholdsResolver: new ThresholdsResolver(),
            auditStore: new NullAuditStore()
        );

        $resultContext = $useCase([
            'laboratoryCode' => 'LAB-042',
            'zPrimeScore' => 1.7,
            'zetaScore' => 1.3,
            'biasPercent' => 4.2,
            'thresholdStandard' => 'iso13528',
        ]);

        // 1. LabEvaluation exists
        $this->assertArrayHasKey('labEvaluation', $resultContext);
        $this->assertInstanceOf(
            LabEvaluation::class,
            $resultContext['labEvaluation']
        );

        $evaluation = $resultContext['labEvaluation'];

        // 2. Correct decision
        $this->assertSame(
            FitnessStatus::CONFORME,
            $evaluation->fitnessStatus
        );

        $this->assertSame(
            'z_prime',
            $evaluation->decisionBasis
        );

        // 3. AuditTrail exists
        $this->assertArrayHasKey('auditTrail', $resultContext);
        $this->assertInstanceOf(
            AuditTrail::class,
            $resultContext['auditTrail']
        );

        $events = $resultContext['auditTrail']->all();
        $this->assertCount(1, $events);

        $event = $events[0];

        // 4. AuditEvent is consistent
        $this->assertSame('LAB-042', $event->laboratoryCode);
        $this->assertSame('conforme', $event->decision);
        $this->assertSame('z_prime', $event->decisionBasis);
        $this->assertEquals(1.7, $event->decisionScore);
        $this->assertSame(
            NormReference::ISO_13528_2022,
            $event->normReference
        );

        // 5. Engine version traceability
        $this->assertNotEmpty($event->engineVersion);
    }
}
