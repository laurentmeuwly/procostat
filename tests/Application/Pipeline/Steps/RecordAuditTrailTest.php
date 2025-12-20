<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Norms\NormReference;
use Procorad\Procostat\Domain\Decision\EvaluationValidity;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;

final class RecordAuditTrailTest extends TestCase
{
    public function test_it_creates_audit_trail_if_missing_and_appends_event(): void
    {
        $step = new RecordAuditTrail(
            new ThresholdsResolver(),
            new NullAuditStore()
        );

        $context = $step([
            'laboratoryCode' => 'LAB-007',
            'thresholdStandard' => 'iso13528',
            'zPrimeScore' => 1.8,
            'labEvaluation' => new LabEvaluation(
                laboratoryCode: 'LAB-007',
                zScore: 2.5,
                zPrimeScore: 1.8,
                zetaScore: 1.2,
                biasPercent: 5.0,
                fitnessStatus: FitnessStatus::CONFORME,
                evaluationValidity: EvaluationValidity::OFFICIAL,
                decisionBasis: 'z_prime'
            ),
        ]);

        $this->assertArrayHasKey('auditTrail', $context);
        $this->assertInstanceOf(AuditTrail::class, $context['auditTrail']);

        $events = $context['auditTrail']->all();
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertSame('LAB-007', $event->laboratoryCode);
        $this->assertSame('conforme', $event->decision);
        $this->assertSame('z_prime', $event->decisionBasis);
        $this->assertEquals(1.8, $event->decisionScore);
        $this->assertSame(NormReference::ISO_13528_2022, $event->normReference);
    }

    public function test_it_appends_to_existing_audit_trail(): void
    {
        $step = new RecordAuditTrail(
            new ThresholdsResolver(),
            new NullAuditStore()
        );

        $trail = new AuditTrail();

        $context = $step([
            'laboratoryCode' => 'LAB-008',
            'thresholdStandard' => 'iso13528',
            'zScore' => 2.6,
            'auditTrail' => $trail,
            'labEvaluation' => new LabEvaluation(
                laboratoryCode: 'LAB-008',
                zScore: 2.6,
                zPrimeScore: null,
                zetaScore: null,
                biasPercent: null,
                fitnessStatus: FitnessStatus::DISCUTABLE,
                evaluationValidity: EvaluationValidity::INFORMATIVE,
                decisionBasis: 'z'
            ),
        ]);

        $this->assertSame($trail, $context['auditTrail']);
        $this->assertCount(1, $context['auditTrail']->all());
    }
}
