<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\BuildEvaluationReference;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Performance\ReferenceSource;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\DescriptiveStatistics;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;

final class BuildEvaluationReferenceTest extends TestCase
{
    private function baseContext(PopulationStatus $status): AnalysisContext
    {
        $measurements = [
            new Measurement('L1', 10.0, new Uncertainty(0.5)),
            new Measurement('L2', 11.0, new Uncertainty(0.5)),
            new Measurement('L3', 12.0, new Uncertainty(0.5)),
        ];

        $dataset = new AnalysisDataset(
            measurements: $measurements,
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN, null, null
            ),
            campaign: '2025', sampleCode: 'X', radionuclide: 'Cs137', unit: 'Bq/kg'
        );

        $ctx = new AnalysisContext(dataset: $dataset, thresholdStandard: 'iso13528');
        $ctx->population       = new Population($measurements);
        $ctx->populationStatus = $status;

        $ctx->descriptiveStatistics = new DescriptiveStatistics(
            count: 3, minimum: 10.0, maximum: 12.0,
            median: 11.0, mean: 11.0, standardDeviation: 1.0,
            medianAbsoluteDeviation: 1.0
        );

        return $ctx;
    }

    // ── not_exploitable ───────────────────────────────────────────────────────

    public function test_not_exploitable_produces_null_reference(): void
    {
        $ctx = $this->baseContext(PopulationStatus::NOT_EXPLOITABLE);
        $ctx->assignedValue = AssignedValue::robust(11.0);

        $result = (new BuildEvaluationReference)($ctx);

        $this->assertNull($result->evaluationReference);
    }

    // ── descriptive_only ──────────────────────────────────────────────────────

    public function test_descriptive_only_uses_arithmetic_mean_and_zeta(): void
    {
        $ctx = $this->baseContext(PopulationStatus::DESCRIPTIVE_ONLY);
        $ctx->assignedValue = AssignedValue::certified(10.0, 0.4);

        $result = (new BuildEvaluationReference)($ctx);
        $ref    = $result->evaluationReference;

        $this->assertNotNull($ref);
        $this->assertSame(ReferenceSource::ArithmeticMean, $ref->referenceSource);
        $this->assertSame(IndicatorType::ZETA, $ref->decisionBasis);
        $this->assertSame(11.0, $ref->centralValue); // moyenne arithmétique
        $this->assertNull($ref->sigma);              // pas de sigma d'aptitude
        $this->assertNull($ref->uRef);
    }

    public function test_descriptive_only_traces_correctly(): void
    {
        $ctx = $this->baseContext(PopulationStatus::DESCRIPTIVE_ONLY);
        $ctx->assignedValue = AssignedValue::robust(11.0);

        $result = (new BuildEvaluationReference)($ctx);

        $this->assertSame('arithmetic_mean', $result->trace->evaluationReferenceSource);
        $this->assertSame('zeta', $result->trace->evaluationDecisionBasis);
        $this->assertTrue($result->trace->hasEvent('evaluation.reference.selected'));

        $event = $result->trace->getEvent('evaluation.reference.selected');
        $this->assertSame('arithmetic_mean', $event['source']);
        $this->assertSame('zeta', $event['decision_basis']);
    }

    // ── full_evaluation, robust_mean ─────────────────────────────────────────

    public function test_full_evaluation_robust_mean_uses_robust_reference(): void
    {
        $ctx = $this->baseContext(PopulationStatus::FULL_EVALUATION);
        $ctx->assignedValue      = AssignedValue::robust(11.0, expandedUncertaintyK2: 0.6);
        $ctx->primaryIndicator   = IndicatorType::Z_PRIME;
        $ctx->robustStatistics   = new RobustStatistics(11.0, 0.8);

        $result = (new BuildEvaluationReference)($ctx);
        $ref    = $result->evaluationReference;

        $this->assertNotNull($ref);
        $this->assertSame(ReferenceSource::RobustMean, $ref->referenceSource);
        $this->assertSame(IndicatorType::Z_PRIME, $ref->decisionBasis);
        $this->assertSame(11.0, $ref->centralValue); // moyenne robuste
        $this->assertSame(0.8, $ref->sigma);         // s* robuste
        $this->assertEqualsWithDelta(0.3, $ref->uRef, 0.001); // 0.6 / 2
    }

    // ── full_evaluation, certified ────────────────────────────────────────────

    public function test_full_evaluation_certified_uses_certified_reference(): void
    {
        $ctx = $this->baseContext(PopulationStatus::FULL_EVALUATION);
        $ctx->assignedValue    = AssignedValue::certified(10.0, expandedUncertaintyK2: 0.4);
        $ctx->primaryIndicator = IndicatorType::Z; // valeur certifiée indépendante
        $ctx->robustStatistics = new RobustStatistics(10.1, 0.9);

        $result = (new BuildEvaluationReference)($ctx);
        $ref    = $result->evaluationReference;

        $this->assertNotNull($ref);
        $this->assertSame(ReferenceSource::CertifiedValue, $ref->referenceSource);
        $this->assertSame(IndicatorType::Z_PRIME, $ref->decisionBasis);
        $this->assertSame(10.0, $ref->centralValue);  // valeur certifiée
        $this->assertSame(0.9, $ref->sigma);           // s* robuste
        $this->assertEqualsWithDelta(0.2, $ref->uRef, 0.001); // 0.4 / 2
    }

    public function test_full_evaluation_certified_traces_correctly(): void
    {
        $ctx = $this->baseContext(PopulationStatus::FULL_EVALUATION);
        $ctx->assignedValue    = AssignedValue::certified(10.0, 0.4);
        $ctx->primaryIndicator = IndicatorType::Z;
        $ctx->robustStatistics = new RobustStatistics(10.1, 0.9);

        $result = (new BuildEvaluationReference)($ctx);

        $this->assertSame('certified_value', $result->trace->evaluationReferenceSource);
        $this->assertSame('z_prime', $result->trace->evaluationDecisionBasis);
        $this->assertSame(10.0, $result->trace->evaluationCentralValue);
        $this->assertSame(0.9, $result->trace->evaluationSigma);

        $this->assertContains(
            'evaluation_reference_certified_value',
            $result->trace->workflowPath
        );
    }

    // ── toTracePayload ────────────────────────────────────────────────────────

    public function test_trace_payload_is_complete(): void
    {
        $ctx = $this->baseContext(PopulationStatus::FULL_EVALUATION);
        $ctx->assignedValue    = AssignedValue::robust(11.0, 0.6);
        $ctx->primaryIndicator = IndicatorType::Z_PRIME;
        $ctx->robustStatistics = new RobustStatistics(11.0, 0.8);

        (new BuildEvaluationReference)($ctx);

        $event = $ctx->trace->getEvent('evaluation.reference.selected');

        $this->assertArrayHasKey('source', $event);
        $this->assertArrayHasKey('decision_basis', $event);
        $this->assertArrayHasKey('central_value', $event);
        $this->assertArrayHasKey('sigma', $event);
        $this->assertArrayHasKey('u_ref', $event);
    }
}
