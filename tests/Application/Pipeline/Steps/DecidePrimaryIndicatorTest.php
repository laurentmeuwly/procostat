<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\DecidePrimaryIndicator;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;

final class DecidePrimaryIndicatorTest extends TestCase
{
    private function contextWith(
        PopulationStatus $status,
        AssignedValue $assignedValue
    ): AnalysisContext {
        $dataset = new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'X',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );
        $context->population = new Population($dataset->measurements());
        $context->robustStatistics = new RobustStatistics(11.0, 1.0);
        $context->populationStatus = $status;
        $context->assignedValue = $assignedValue;

        return $context;
    }

    public function test_not_exploitable_population_has_no_indicator(): void
    {
        $assignedValue = AssignedValue::robust(
            value: 11.0,
            expandedUncertaintyK2: null
        );

        $context = $this->contextWith(
            PopulationStatus::NOT_EXPLOITABLE,
            $assignedValue
        );

        $result = (new DecidePrimaryIndicator())($context);

        $this->assertNull($result->primaryIndicator);
    }

    public function test_independent_assigned_value_uses_z(): void
    {
        $assignedValue = AssignedValue::certified(
            value: 10.5,
            expandedUncertaintyK2: 0.5
        );

        $context = $this->contextWith(
            PopulationStatus::FULL_EVALUATION,
            $assignedValue
        );

        $result = (new DecidePrimaryIndicator())($context);

        $this->assertSame(
            IndicatorType::Z,
            $result->primaryIndicator
        );
    }

    public function test_population_based_assigned_value_uses_z_prime(): void
    {
        $assignedValue = AssignedValue::robust(
            value: 11.0,
            expandedUncertaintyK2: null
        );

        $context = $this->contextWith(
            PopulationStatus::FULL_EVALUATION,
            $assignedValue
        );

        $result = (new DecidePrimaryIndicator())($context);

        $this->assertSame(
            IndicatorType::Z_PRIME,
            $result->primaryIndicator
        );
    }
}
