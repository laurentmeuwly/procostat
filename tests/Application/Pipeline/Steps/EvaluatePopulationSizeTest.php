<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationRules;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

final class EvaluatePopulationSizeTest extends TestCase
{
    private function populationOfSize(int $n): Population
    {
        $measurements = [];

        for ($i = 1; $i <= $n; $i++) {
            $measurements[] = new Measurement(
                laboratoryCode: 'LAB' . $i,
                value: (float) $i,
                uncertainty: new Uncertainty(0.5),
                limitOfDetection: null
            );
        }

        return new Population($measurements);
    }

    public function test_population_status_is_computed(): void
    {
        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        $context->population = $this->populationOfSize(10);

        $step = new EvaluatePopulationSize();
        $result = $step($context);

        $this->assertNotNull($result->populationStatus);
        $this->assertSame(
            PopulationRules::evaluate(10)->isExploitable(),
            $result->populationStatus->isExploitable()
        );
    }

    public function test_population_is_not_exploitable_when_less_than_3(): void
    {
        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        $context->population = $this->populationOfSize(2);

        $step = new EvaluatePopulationSize();
        $result = $step($context);

        $this->assertNotNull($result->populationStatus);

        $this->assertSame(
            PopulationStatus::NOT_EXPLOITABLE,
            $result->populationStatus
        );
    }

    public function test_population_is_descriptive_only_between_3_and_6(): void
    {
        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        $context->population = $this->populationOfSize(5);

        $step = new EvaluatePopulationSize();
        $result = $step($context);

        $this->assertNotNull($result->populationStatus);

        $this->assertSame(
            PopulationStatus::DESCRIPTIVE_ONLY,
            $result->populationStatus
        );
    }

    public function test_population_is_fully_exploitable_from_7(): void
    {
        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        $context->population = $this->populationOfSize(7);

        $step = new EvaluatePopulationSize();
        $result = $step($context);

        $this->assertNotNull($result->populationStatus);

        $this->assertSame(
            PopulationStatus::FULL_EVALUATION,
            $result->populationStatus
        );
    }

    public function test_missing_population_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $context = new AnalysisContext(
            dataset: $this->dummyDataset(),
            thresholdStandard: 'iso13528'
        );

        (new EvaluatePopulationSize())($context);
    }

    private function dummyDataset(): \Procorad\Procostat\DTO\AnalysisDataset
    {
        return new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement(
                    laboratoryCode: 'DUMMY',
                    value: 1.0,
                    uncertainty: new Uncertainty(0.1),
                    limitOfDetection: null
                )
            ],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'TEST',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );
    }
}
