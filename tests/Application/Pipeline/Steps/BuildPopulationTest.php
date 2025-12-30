<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulation;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\DTO\AnalysisDataset;
use RuntimeException;

final class BuildPopulationTest extends TestCase
{
    private function validMeasurement(string $lab, float $value): Measurement
    {
        return new Measurement(
            laboratoryCode: $lab,
            value: $value,
            uncertainty: new Uncertainty(0.5),
            limitOfDetection: null
        );
    }

    private function validContext(array $measurements): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: $measurements,
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

        return new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );
    }

    public function test_population_is_built_from_measurements(): void
    {
        $context = $this->validContext([
            $this->validMeasurement('LAB01', 10.0),
            $this->validMeasurement('LAB02', 12.5),
        ]);

        $step = new BuildPopulation;

        $result = $step($context);

        $this->assertNotNull($result->population);
        $this->assertSame(2, $result->population->count());
        $this->assertSame(
            ['LAB01', 'LAB02'],
            $result->population->laboratoryCodes()
        );
    }

    public function test_empty_dataset_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $context = $this->validContext([]);

        (new BuildPopulation)($context);
    }
}
