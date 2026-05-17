<?php

namespace Procorad\Procostat\Tests\Procostat\Analysis;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25OBTH3H;
use Procorad\Procostat\Tests\Procostat\Oracle\Oracle25OBTH3H;
use Procorad\Procostat\Tests\Support\TestAnalysisEngineFactory;

final class RunAnalysis25OBTH_3HTest extends TestCase
{
    public function test_25_obth_3h_matches_oracle(): void
    {
        $dataset = Dataset25OBTH3H::create();
        $engine  = TestAnalysisEngineFactory::createIso13528Engine();
        $result  = $engine->analyze($dataset);

        // ── Valeur assignée ───────────────────────────────────────────────────
        $assignedValue = $result->assignedValue;

        $this->assertSame(Oracle25OBTH3H::ASSIGNED_VALUE, $assignedValue->value());
        $this->assertSame(
            Oracle25OBTH3H::ASSIGNED_UNCERTAINTY_K2,
            $assignedValue->expandedUncertaintyK2()
        );
        $this->assertTrue(
            $assignedValue->isIndependent(),
            'Valeur certifiée doit être indépendante des participants'
        );

        // ── Indicateur ────────────────────────────────────────────────────────
        // Valeur certifiée indépendante → primaryIndicator = Z
        // (le score effectif est z' car u_ref ≠ 0, mais la décision de branche est Z)
        $this->assertSame(IndicatorType::Z, $result->primaryIndicator);

        // ── Statistiques robustes ─────────────────────────────────────────────
        $this->assertTrue(
            $result->hasRobustStatistics(),
            'n=13 → full_evaluation, stats robustes attendues'
        );

        $this->assertEqualsWithDelta(
            Oracle25OBTH3H::ROBUST_MEAN,
            $result->robustStatistics->mean(),
            0.1,
            'Moyenne robuste'
        );

        $this->assertEqualsWithDelta(
            Oracle25OBTH3H::ROBUST_STD_DEV,
            $result->robustStatistics->stdDev(),
            0.1,
            'Écart-type robuste'
        );

        // ── Labs non conformes ────────────────────────────────────────────────
        foreach (Oracle25OBTH3H::NON_CONFORM_LABS as $labCode) {
            $evaluation = $result->labEvaluationFor($labCode);

            $this->assertNotNull($evaluation, "Évaluation manquante pour lab {$labCode}");
            $this->assertSame(
                FitnessStatus::NON_CONFORME,
                $evaluation->fitnessStatus,
                "Lab {$labCode} (valeur=62.0) devrait être NON_CONFORME (z'≈6.6)"
            );
        }

        // ── Scores numériques ─────────────────────────────────────────────────
        $lab6 = $result->labEvaluationFor(6);
        $this->assertSame('z_prime', $lab6->decisionBasis);
        $this->assertGreaterThan(3.0, abs($lab6->zPrimeScore));

        /*$lab52 = $result->labEvaluationFor(52);
        $this->assertNotNull($lab52->zPrimeScore);
        $this->assertGreaterThan(2.0, abs($lab52->zPrimeScore));
        $this->assertLessThanOrEqual(3.0, abs($lab52->zPrimeScore));*/
    }
}
