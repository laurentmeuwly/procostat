<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\DTO\AnalysisDataset;
use RuntimeException;

final class ValidateDatasetTest extends TestCase
{
    private function validMeasurement(string $labCode, float $value): Measurement
    {
        return new Measurement(
            laboratoryCode: $labCode,
            value: $value,
            uncertainty: new Uncertainty(0.5),
            limitOfDetection: null
        );
    }

    private function validContext(array $measurements): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: $measurements,
            assignedValueSpec: $this->dummyAssignedValueSpec(),
            campaign: '2025',
            sampleCode: 'X',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        return new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );
    }

    private function dummyAssignedValueSpec()
    {
        return new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
            \Procorad\Procostat\Domain\AssignedValue\AssignedValueType::ROBUST_MEAN,
            null,
            null
        );
    }

    public function test_valid_dataset_passes_validation(): void
    {
        $context = $this->validContext([
            $this->validMeasurement('LAB01', 10.0),
            $this->validMeasurement('LAB02', 11.2),
        ]);

        $step = new ValidateDataset;

        $result = $step($context);

        $this->assertSame($context, $result);
    }

    public function test_duplicate_laboratory_code_throws(): void
    {
        $context = $this->validContext([
            $this->validMeasurement('LAB01', 10.0),
            $this->validMeasurement('LAB01', 12.0),
        ]);

        $this->expectException(RuntimeException::class);

        (new ValidateDataset)($context);
    }

    public function test_empty_laboratory_code_throws(): void
    {
        $this->expectException(RuntimeException::class);

        $context = $this->validContext([
            $this->validMeasurement(' ', 10.0),
        ]);

        (new ValidateDataset)($context);
    }

    public function test_non_finite_value_throws(): void
    {
        $this->expectException(RuntimeException::class);

        $context = $this->validContext([
            $this->validMeasurement('LAB01', INF),
        ]);

        (new ValidateDataset)($context);
    }

    public function test_negative_uncertainty_throws(): void
    {
        $this->expectException(RuntimeException::class);

        $measurement = new Measurement(
            laboratoryCode: 'LAB01',
            value: 10.0,
            uncertainty: new Uncertainty(-0.1),
            limitOfDetection: null
        );

        $context = $this->validContext([$measurement]);

        (new ValidateDataset)($context);
    }
}
