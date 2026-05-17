<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\ComputeRobustStatistics;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use RuntimeException;

final class ComputeRobustStatisticsTest extends TestCase
{
    private function population(): Population
    {
        return new Population([
            new Measurement('LAB-1', 10.0, new Uncertainty(0.5)),
            new Measurement('LAB-2', 10.2, new Uncertainty(0.5)),
            new Measurement('LAB-3', 9.8, new Uncertainty(0.5)),
            new Measurement('LAB-4', 10.1, new Uncertainty(0.5)),
            new Measurement('LAB-5', 9.9, new Uncertainty(0.5)),
            new Measurement('LAB-6', 10.3, new Uncertainty(0.5)),
            new Measurement('LAB-7', 10.0, new Uncertainty(0.5)),
        ]);
    }

    private function dummyDataset(): AnalysisDataset
    {
        return new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement('DUMMY', 1.0, new Uncertainty(0.1)),
            ],
            assignedValueSpec: new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
                \Procorad\Procostat\Domain\AssignedValue\AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'TEST',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );
    }

    private function contextWithPopulation(
        PopulationStatus $status = PopulationStatus::FULL_EVALUATION
    ): AnalysisContext {
        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528',
            population: $this->population()
        );
        $context->populationStatus = $status;

        return $context;
    }

    public function test_robust_statistics_are_computed(): void
    {
        $result = (new ComputeRobustStatistics)($this->contextWithPopulation());

        $this->assertInstanceOf(RobustStatistics::class, $result->robustStatistics);
    }

    public function test_null_for_descriptive_only(): void
    {
        $result = (new ComputeRobustStatistics)(
            $this->contextWithPopulation(PopulationStatus::DESCRIPTIVE_ONLY)
        );

        $this->assertNull($result->robustStatistics);
    }

    public function test_null_for_not_exploitable(): void
    {
        $result = (new ComputeRobustStatistics)(
            $this->contextWithPopulation(PopulationStatus::NOT_EXPLOITABLE)
        );

        $this->assertNull($result->robustStatistics);
    }

    public function test_missing_population_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        (new ComputeRobustStatistics)($context);
    }

    public function test_missing_population_status_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528',
            population: $this->population()
            // populationStatus intentionnellement absent
        );

        (new ComputeRobustStatistics)($context);
    }
}
