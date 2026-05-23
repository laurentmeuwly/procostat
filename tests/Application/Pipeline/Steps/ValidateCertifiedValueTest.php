<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\ValidateCertifiedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;

/**
 * Tests unitaires pour ValidateCertifiedValue (§9.2.2 plan statistique PROCORAD 2026).
 *
 * Critere : |Xref - X*| <= 2 * sqrt[ u2(ref) + (1.25 * s_star/sqrt(p))^2 ]
 */
final class ValidateCertifiedValueTest extends TestCase
{
    private ValidateCertifiedValue $step;

    protected function setUp(): void
    {
        $this->step = new ValidateCertifiedValue();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Construit un contexte full_evaluation avec valeur certifiée et stats robustes.
     */
    private function makeContext(
        float $xRef,
        float $uRefK2,
        float $xStar,
        float $sStar,
        int   $p,
        PopulationStatus $status = PopulationStatus::FULL_EVALUATION,
        AssignedValueType $type  = AssignedValueType::CERTIFIED,
    ): AnalysisContext {
        // Mesures fictives (p participants à xStar pour simplifier)
        $measurements = array_map(
            fn (int $i) => new Measurement("L$i", $xStar, new Uncertainty(0.5)),
            range(1, $p)
        );

        $dataset = new AnalysisDataset(
            measurements: $measurements,
            assignedValueSpec: new AssignedValueSpecification(
                $type,
                $type === AssignedValueType::CERTIFIED ? $xRef : null,
                $type === AssignedValueType::CERTIFIED ? $uRefK2 : null,
            ),
            campaign: '2026', sampleCode: 'X', radionuclide: 'Cs137', unit: 'Bq/L'
        );

        $ctx = new AnalysisContext(dataset: $dataset, thresholdStandard: 'iso13528');
        $ctx->population       = new Population($measurements);
        $ctx->populationStatus = $status;
        $ctx->robustStatistics = new RobustStatistics(mean: $xStar, stdDev: $sStar);

        $ctx->assignedValue = $type === AssignedValueType::CERTIFIED
            ? AssignedValue::certified($xRef, $uRefK2)
            : AssignedValue::robust($xStar, 2.0 * (1.25 * $sStar / sqrt($p)));

        return $ctx;
    }

    // ── Cas : step ignoré si non full_evaluation ─────────────────────────────

    public function test_skips_if_descriptive_only(): void
    {
        $ctx = $this->makeContext(
            xRef: 100.0, uRefK2: 10.0,
            xStar: 98.0, sStar: 5.0, p: 6,
            status: PopulationStatus::DESCRIPTIVE_ONLY,
        );

        $result = ($this->step)($ctx);

        $this->assertNull($result->trace->certifiedValueValidated);
        $this->assertFalse($result->trace->expertValidationRequired);
    }

    public function test_skips_if_assigned_value_is_robust_mean(): void
    {
        $ctx = $this->makeContext(
            xRef: 100.0, uRefK2: 10.0,
            xStar: 98.0, sStar: 5.0, p: 20,
            type: AssignedValueType::ROBUST_MEAN,
        );

        $result = ($this->step)($ctx);

        $this->assertNull($result->trace->certifiedValueValidated);
        $this->assertFalse($result->trace->expertValidationRequired);
    }

    // ── Cas : critère respecté -> validation OK ───────────────────────────────

    /**
     * Données :
     *   Xref = 2200, U(ref) k=2 = 88  -> u(ref) k=1 = 44
     *   X*   = 2175, s*   = 120, p = 40
     *   u_consensus = 1.25 * 120 / sqrt40 = 23.717…
     *   seuil = 2 * sqrt(442 + 23.7172) = 2 * sqrt(1936 + 562.50) ≈ 2 * 49.97 ≈ 99.94
     *   gap = |2200 - 2175| = 25  ->  25 <= 99.94 ✓
     */
    public function test_validates_when_gap_within_threshold(): void
    {
        $ctx = $this->makeContext(
            xRef: 2200.0, uRefK2: 88.0,
            xStar: 2175.0, sStar: 120.0, p: 40,
        );

        $result = ($this->step)($ctx);

        $this->assertTrue($result->trace->certifiedValueValidated, 'Doit être validé');
        $this->assertFalse($result->trace->expertValidationRequired);

        // La valeur assignée reste CERTIFIED
        $this->assertSame(AssignedValueType::CERTIFIED, $result->assignedValue->type());
        $this->assertEqualsWithDelta(2200.0, $result->assignedValue->value(), 0.001);

        // Événement et chemin
        $this->assertTrue($result->trace->hasEvent('certified_value.validation'));
        $this->assertContains('certified_value_validated', $result->trace->workflowPath);
        $this->assertNotContains('certified_value_rejected', $result->trace->workflowPath);
        $this->assertNotContains('certified_value_fallback_to_robust', $result->trace->workflowPath);
    }

    // ── Cas : critère NON respecté -> substitution + alerte expert ────────────

    /**
     * Données :
     *   Xref = 2200, U(ref) k=2 = 88  -> u(ref) k=1 = 44
     *   X*   = 1900, s*   = 60,  p = 40
     *   u_consensus = 1.25 * 60 / sqrt40 = 11.858…
     *   seuil = 2 * sqrt(442 + 11.8582) = 2 * sqrt(1936 + 140.61) ≈ 2 * 45.58 ≈ 91.16
     *   gap = |2200 - 1900| = 300  ->  300 > 91.16 ✗
     */
    public function test_rejects_when_gap_exceeds_threshold(): void
    {
        $ctx = $this->makeContext(
            xRef: 2200.0, uRefK2: 88.0,
            xStar: 1900.0, sStar: 60.0, p: 40,
        );

        $result = ($this->step)($ctx);

        $this->assertFalse($result->trace->certifiedValueValidated, 'Ne doit pas être validé');
        $this->assertTrue($result->trace->expertValidationRequired, 'Expert requis');

        // Substitution automatique sur la moyenne robuste
        $this->assertSame(AssignedValueType::ROBUST_MEAN, $result->assignedValue->type());
        $this->assertEqualsWithDelta(1900.0, $result->assignedValue->value(), 0.001);

        // U(X*) k=2 = 2 * u_consensus k=1
        $uConsensus = 1.25 * 60.0 / sqrt(40);
        $this->assertEqualsWithDelta(
            2.0 * $uConsensus,
            $result->assignedValue->expandedUncertaintyK2(),
            0.001
        );

        // Chemin décisionnel
        $this->assertContains('certified_value_rejected', $result->trace->workflowPath);
        $this->assertContains('certified_value_fallback_to_robust', $result->trace->workflowPath);
        $this->assertNotContains('certified_value_validated', $result->trace->workflowPath);

        // Événement
        $event = $result->trace->getEvent('certified_value.validation');
        $this->assertNotNull($event);
        $this->assertFalse($event['validated']);
        $this->assertTrue($event['expert_required']);
    }

    // ── Cas limite : gap == seuil -> valide (<= strict) ────────────────────────

    /**
     * On construit un cas où gap = seuil exactement :
     *   u_ref k=1 = 30, u_consensus k=1 = 40
     *   seuil = 2 * sqrt(900 + 1600) = 2 * 50 = 100
     *   gap   = 100  -> validé (<= seuil)
     *
     *   u_consensus = 1.25 * s_star / sqrt(p) = 40  ->  pour p = 25 : s_star = 40 * sqrt(25) / 1.25 = 160
     */
    public function test_validates_when_gap_equals_threshold_exactly(): void
    {
        $ctx = $this->makeContext(
            xRef: 2100.0, uRefK2: 60.0,   // u_ref k=1 = 30
            xStar: 2000.0,                 // gap = 100
            sStar: 160.0, p: 25,
        );

        $result = ($this->step)($ctx);

        $this->assertTrue($result->trace->certifiedValueValidated);
        $this->assertFalse($result->trace->expertValidationRequired);
        $this->assertSame(AssignedValueType::CERTIFIED, $result->assignedValue->type());
    }

    // ── Vérification des valeurs numériques dans la trace ─────────────────────

    public function test_trace_records_correct_gap_and_threshold(): void
    {
        $ctx = $this->makeContext(
            xRef: 2200.0, uRefK2: 88.0,
            xStar: 2175.0, sStar: 120.0, p: 40,
        );

        $result = ($this->step)($ctx);

        $uRef       = 44.0;         // 88 / 2
        $uConsensus = 1.25 * 120.0 / sqrt(40);
        $expected   = 2.0 * sqrt($uRef ** 2 + $uConsensus ** 2);

        $this->assertEqualsWithDelta(25.0, $result->trace->certifiedValueValidationGap, 0.001);
        $this->assertEqualsWithDelta($expected, $result->trace->certifiedValueValidationThreshold, 0.001);
    }

    // ── Vérification du payload de l'événement structuré ─────────────────────

    public function test_event_payload_contains_all_fields(): void
    {
        $ctx = $this->makeContext(
            xRef: 2200.0, uRefK2: 88.0,
            xStar: 2175.0, sStar: 120.0, p: 40,
        );

        ($this->step)($ctx);

        $event = $ctx->trace->getEvent('certified_value.validation');

        $this->assertArrayHasKey('x_ref', $event);
        $this->assertArrayHasKey('x_star', $event);
        $this->assertArrayHasKey('u_ref_k1', $event);
        $this->assertArrayHasKey('u_consensus_k1', $event);
        $this->assertArrayHasKey('gap', $event);
        $this->assertArrayHasKey('threshold', $event);
        $this->assertArrayHasKey('validated', $event);
        $this->assertArrayHasKey('expert_required', $event);

        $this->assertEqualsWithDelta(2200.0, $event['x_ref'], 0.001);
        $this->assertEqualsWithDelta(2175.0, $event['x_star'], 0.001);
        $this->assertEqualsWithDelta(44.0, $event['u_ref_k1'], 0.001);
    }
}
