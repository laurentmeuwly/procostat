<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;

final class EvaluatePopulationSizeTest extends TestCase
{
    public function test_population_is_not_exploitable_when_less_than_3(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'dataset' => $this->datasetWithNParticipants(2),
        ]);

        $this->assertSame(
            PopulationStatus::NOT_EXPLOITABLE,
            $context['populationStatus']
        );
    }

    public function test_population_is_descriptive_only_between_3_and_6(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'dataset' => $this->datasetWithNParticipants(5),
        ]);

        $this->assertSame(
            PopulationStatus::DESCRIPTIVE_ONLY,
            $context['populationStatus']
        );
    }

    public function test_population_is_fully_exploitable_from_7(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'dataset' => $this->datasetWithNParticipants(10),
        ]);

        $this->assertSame(
            PopulationStatus::FULL_EVALUATION,
            $context['populationStatus']
        );
    }

    /**
     * Helper: create a dataset with N distinct laboratory codes.
     */
    private function datasetWithNParticipants(int $n): AnalysisDataset
    {
        $measurements = [];

        for ($i = 1; $i <= $n; $i++) {
            $measurements[] = new Measurement(
                laboratoryCode: 'LAB-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                value: 100.0,
                uncertainty: null
            );
        }

        return new AnalysisDataset($measurements);
    }
}
