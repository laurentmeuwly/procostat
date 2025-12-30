<?php

namespace Procorad\Procostat\Tests\Application;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\ProcostatResult;

final class RunAnalysisTest extends TestCase
{
    public function test_run_analysis_produces_final_result(): void
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
                new Measurement('LAB04', 13.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'XGA',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $normalityAdapter = $this->createMock(NormalityAdapter::class);
        $normalityAdapter
            ->method('analyze')
            ->willReturn(new NormalityResult(
                isNormal: true,
                shapiroWilkPValue: 0.15,
                skewness: 0.1,
                kurtosis: -0.2,
                conclusion: 'Normal',
                henryLine: null
            ));

        $auditStore = $this->createMock(AuditStore::class);
        $auditStore
            ->expects($this->exactly(4))
            ->method('store');

        $assignedValueResolver = new AssignedValueResolver;
        $thresholdsResolver = new ThresholdsResolver;

        $engine = new RunAnalysis(
            normalityAdapter: $normalityAdapter,
            assignedValueResolver: $assignedValueResolver,
            thresholdsResolver: $thresholdsResolver,
            auditStore: $auditStore,
            thresholdStandard: 'iso13528'
        );

        $result = $engine->analyze(
            dataset: $dataset
        );

        $this->assertInstanceOf(
            ProcostatResult::class,
            $result
        );

        $this->assertNotNull($result->assignedValue);
        $this->assertNotNull($result->robustStatistics);
        $this->assertNotNull($result->populationSummary);
        $this->assertNotNull($result->primaryIndicator);
        $this->assertNotNull($result->auditTrail);
        $this->assertCount(4, $result->labEvaluations);
        $this->assertCount(4, $result->auditTrail->all());
    }
}
