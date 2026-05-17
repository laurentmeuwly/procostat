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
use Procorad\Procostat\Domain\Performance\EvaluationReference;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Performance\ReferenceSource;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\LabEvaluation;

final class EvaluateLaboratoriesTest extends TestCase
{
    private function context(): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN, null, null
            ),
            campaign: '2025', sampleCode: 'XGA', radionuclide: 'Cs-137', unit: 'Bq/kg'
        );

        $context = new AnalysisContext(dataset: $dataset, thresholdStandard: 'iso13528');
        $context->population     = new Population($dataset->measurements());
        $context->assignedValue  = AssignedValue::robust(11.0, expandedUncertaintyK2: 0.6);
        $context->populationStatus = PopulationStatus::FULL_EVALUATION;

        return $context;
    }

    public function test_laboratories_are_evaluated_with_z_prime(): void
    {
        $context = $this->context();
        $context->evaluationReference = new EvaluationReference(
            centralValue:    11.0,
            sigma:           1.0,
            uRef:            0.3,
            decisionBasis:   IndicatorType::Z_PRIME,
            referenceSource: ReferenceSource::RobustMean,
        );

        $result = (new EvaluateLaboratories(new ThresholdsResolver))($context);

        $this->assertCount(3, $result->labEvaluations);

        foreach ($result->labEvaluations as $evaluation) {
            $this->assertInstanceOf(LabEvaluation::class, $evaluation);
            $this->assertNotNull($evaluation->zPrimeScore);
            $this->assertNull($evaluation->zScore);
            $this->assertSame('z_prime', $evaluation->decisionBasis);
        }
    }

    public function test_laboratories_are_evaluated_with_z(): void
    {
        $context = $this->context();
        $context->evaluationReference = new EvaluationReference(
            centralValue:    10.0,
            sigma:           1.0,
            uRef:            0.2,
            decisionBasis:   IndicatorType::Z,
            referenceSource: ReferenceSource::CertifiedValue,
        );

        $result = (new EvaluateLaboratories(new ThresholdsResolver))($context);

        foreach ($result->labEvaluations as $evaluation) {
            $this->assertNotNull($evaluation->zScore);
            $this->assertNull($evaluation->zPrimeScore);
            $this->assertSame('z', $evaluation->decisionBasis);
        }
    }

    public function test_laboratories_are_evaluated_with_zeta_only(): void
    {
        $context = $this->context();
        $context->populationStatus = PopulationStatus::DESCRIPTIVE_ONLY;
        $context->evaluationReference = new EvaluationReference(
            centralValue:    11.0,
            sigma:           null,   // pas de sigma en descriptive_only
            uRef:            null,
            decisionBasis:   IndicatorType::ZETA,
            referenceSource: ReferenceSource::ArithmeticMean,
        );

        $result = (new EvaluateLaboratories(new ThresholdsResolver))($context);

        $this->assertCount(3, $result->labEvaluations);

        foreach ($result->labEvaluations as $evaluation) {
            $this->assertNotNull($evaluation->zetaScore);
            $this->assertNull($evaluation->zScore);
            $this->assertNull($evaluation->zPrimeScore);
            $this->assertSame('zeta', $evaluation->decisionBasis);
        }
    }

    public function test_no_evaluations_when_reference_is_null(): void
    {
        $context = $this->context();
        $context->evaluationReference = null; // not_exploitable

        $result = (new EvaluateLaboratories(new ThresholdsResolver))($context);

        $this->assertEmpty($result->labEvaluations);
    }
}
