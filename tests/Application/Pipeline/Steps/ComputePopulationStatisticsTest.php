<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\ComputePopulationStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Rules\PopulationStatus;

final class ComputePopulationStatisticsTest extends TestCase
{
    private function dataset(): AnalysisDataset
    {
        return new AnalysisDataset([
            new Measurement('LAB-1', 10.0),
            new Measurement('LAB-2', 10.2),
            new Measurement('LAB-3', 9.8),
            new Measurement('LAB-4', 10.1),
            new Measurement('LAB-5', 9.9),
            new Measurement('LAB-6', 10.3),
            new Measurement('LAB-7', 10.0),
        ]);
    }

    public function test_no_statistics_when_population_not_exploitable(): void
    {
        $step = new ComputePopulationStatistics();

        $context = $step([
            'dataset' => $this->dataset(),
            'populationStatus' => PopulationStatus::NOT_EXPLOITABLE,
        ]);

        $this->assertNull($context['assignedValue']);
        $this->assertNull($context['populationStdDev']);
    }

    public function test_statistics_are_computed_for_exploitable_population(): void
    {
        $step = new ComputePopulationStatistics();

        $context = $step([
            'dataset' => $this->dataset(),
            'populationStatus' => PopulationStatus::FULL_EVALUATION,
        ]);

        $this->assertNotNull($context['assignedValue']);
        $this->assertNotNull($context['populationStdDev']);
        $this->assertIsFloat($context['assignedValue']);
        $this->assertIsFloat($context['populationStdDev']);
    }
}
