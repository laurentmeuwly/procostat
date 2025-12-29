<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluateLaboratories;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Rules\Thresholds;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\LabEvaluation;

final class EvaluateLaboratoriesTest extends TestCase
{
    private function context(IndicatorType $indicator): AnalysisContext
    {
        $dataset = new AnalysisDataset(
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
            sampleCode: 'XGA',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );

        $context->population = new Population($dataset->measurements());
        $context->robustStatistics = new RobustStatistics(11.0, 1.0);
        $context->assignedValue = AssignedValue::robust(11.0);
        $context->populationStatus = PopulationStatus::FULL_EVALUATION;
        $context->primaryIndicator = $indicator;

        return $context;
    }

    public function test_laboratories_are_evaluated(): void
    {
        $context = $this->context(IndicatorType::Z_PRIME);

        $step = new EvaluateLaboratories(
            new ThresholdsResolver()
        );

        $result = $step($context);

        $this->assertCount(
            3,
            $result->labEvaluations
        );

        foreach ($result->labEvaluations as $evaluation) {
            $this->assertInstanceOf(
                LabEvaluation::class,
                $evaluation
            );

            $this->assertNotNull($evaluation->fitnessStatus);
            $this->assertSame(
                'z_prime',
                $evaluation->decisionBasis
            );
        }
    }

    public function test_z_indicator_is_used_when_selected(): void
    {
        $context = $this->context(IndicatorType::Z);

        $step = new EvaluateLaboratories(
            new ThresholdsResolver()
        );

        $result = $step($context);

        foreach ($result->labEvaluations as $evaluation) {
            $this->assertNotNull($evaluation->zScore);
            $this->assertNull($evaluation->zPrimeScore);
            $this->assertSame('z', $evaluation->decisionBasis);
        }
    }
}
