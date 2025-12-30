<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\RecordAuditTrail;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\DTO\PopulationSummary;

final class RecordAuditTrailTest extends TestCase
{
    private function context(): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
                \Procorad\Procostat\Domain\AssignedValue\AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'XGA',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );

        $context->populationSummary = new PopulationSummary(
            participantCount: 2,
            populationStatus: PopulationStatus::FULL_EVALUATION,
            assignedValue: 10.5,
            assignedUncertainty: null,
            populationStdDev: 1.0,
            normality: null,
            outliers: null
        );

        $context->labEvaluations = [
            new LabEvaluation(
                laboratoryCode: 'LAB01',
                zScore: -0.5,
                zPrimeScore: null,
                zetaScore: null,
                biasPercent: -5.0,
                fitnessStatus: FitnessStatus::CONFORME,
                decisionBasis: 'z'
            ),
            new LabEvaluation(
                laboratoryCode: 'LAB02',
                zScore: 0.5,
                zPrimeScore: null,
                zetaScore: null,
                biasPercent: 5.0,
                fitnessStatus: FitnessStatus::CONFORME,
                decisionBasis: 'z'
            ),
        ];

        return $context;
    }

    public function test_audit_trail_is_recorded(): void
    {
        $store = $this->createMock(AuditStore::class);
        $store->expects($this->exactly(2))
            ->method('store');

        $step = new RecordAuditTrail($store);

        $context = $this->context();

        $result = $step($context);

        $this->assertInstanceOf(
            AuditTrail::class,
            $result->auditTrail
        );

        $this->assertCount(
            2,
            $result->auditTrail->all()
        );
    }
}
