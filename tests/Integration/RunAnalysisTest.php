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
    private function engine(NormalityAdapter $normalityAdapter, AuditStore $auditStore): RunAnalysis
    {
        return new RunAnalysis(
            normalityAdapter: $normalityAdapter,
            assignedValueResolver: new AssignedValueResolver,
            thresholdsResolver: new ThresholdsResolver,
            auditStore: $auditStore,
            thresholdStandard: 'iso13528'
        );
    }

    // ── Scénario full_evaluation (n=7) ───────────────────────────────────────

    public function test_full_evaluation_produces_robust_statistics(): void
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
                new Measurement('LAB04', 10.5, new Uncertainty(0.5)),
                new Measurement('LAB05', 11.5, new Uncertainty(0.5)),
                new Measurement('LAB06', 10.2, new Uncertainty(0.5)),
                new Measurement('LAB07', 11.8, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN, null, null
            ),
            campaign: '2025', sampleCode: 'XGA', radionuclide: 'Cs-137', unit: 'Bq/kg'
        );

        $normalityAdapter = $this->createMock(NormalityAdapter::class);
        $normalityAdapter->method('analyze')->willReturn(new NormalityResult(
            isNormal: true, shapiroWilkPValue: 0.15,
            skewness: 0.1, kurtosis: -0.2, conclusion: 'Normal', henryLine: null
        ));

        $auditStore = $this->createMock(AuditStore::class);
        $auditStore->expects($this->exactly(7))->method('store');

        $result = $this->engine($normalityAdapter, $auditStore)->analyze($dataset);

        $this->assertInstanceOf(ProcostatResult::class, $result);

        // DescriptiveStatistics : toujours présentes
        $this->assertNotNull($result->descriptiveStatistics);
        $this->assertSame(7, $result->descriptiveStatistics->count);

        // RobustStatistics : présentes pour full_evaluation
        $this->assertTrue($result->hasRobustStatistics());
        $this->assertNotNull($result->robustStatistics);

        // Indicateur : présent (z_prime car robust_mean)
        $this->assertTrue($result->hasPerformanceIndicator());
        $this->assertNotNull($result->primaryIndicator);

        $this->assertCount(7, $result->labEvaluations);
    }

    // ── Scénario descriptive_only (n=5) ─────────────────────────────────────

    public function test_descriptive_only_produces_no_robust_statistics(): void
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
                new Measurement('LAB04', 10.5, new Uncertainty(0.5)),
                new Measurement('LAB05', 11.5, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::CERTIFIED, 11.0, 0.5
            ),
            campaign: '2025', sampleCode: 'SML', radionuclide: 'Cs-137', unit: 'Bq/kg'
        );

        $normalityAdapter = $this->createMock(NormalityAdapter::class);
        $normalityAdapter->expects($this->never())->method('analyze'); // non appelé pour n ≤ 6

        $auditStore = $this->createMock(AuditStore::class);

        $result = $this->engine($normalityAdapter, $auditStore)->analyze($dataset);

        // DescriptiveStatistics : toujours présentes
        $this->assertNotNull($result->descriptiveStatistics);
        $this->assertSame(5, $result->descriptiveStatistics->count);
        $this->assertNotNull($result->descriptiveStatistics->minimum);
        $this->assertNotNull($result->descriptiveStatistics->maximum);
        $this->assertNotNull($result->descriptiveStatistics->median);

        // RobustStatistics : NULL pour descriptive_only (métier correct)
        $this->assertFalse($result->hasRobustStatistics());
        $this->assertNull($result->robustStatistics);

        // PopulationSummary : normalité null, outliers null
        $this->assertNull($result->populationSummary->normality);
        $this->assertNull($result->populationSummary->outliers);
        $this->assertFalse($result->populationSummary->isFullyExploitable());
        $this->assertTrue($result->populationSummary->isExploitable());
    }
}
