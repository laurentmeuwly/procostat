<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\ComputeRobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
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
                new Measurement('DUMMY', 1.0, new Uncertainty(0.1))
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

    private function contextWithPopulation(): AnalysisContext
    {
        return new AnalysisContext(
            dataset: $this->dummyDataset(),
            population: $this->population()
        );
    }

    public function test_robust_statistics_are_computed(): void
    {
        $context = $this->contextWithPopulation();

        $result = (new ComputeRobustStatistics())($context);

        $this->assertInstanceOf(
            RobustStatistics::class,
            $result->robustStatistics
        );
    }

    public function test_missing_population_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $context = new AnalysisContext(
            dataset: $this->dummyDataset()
        );

        (new ComputeRobustStatistics())($context);
    }
}
