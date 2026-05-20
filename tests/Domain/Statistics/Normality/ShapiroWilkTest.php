<?php

namespace Tests\Unit\Statistics\NormalityTests;

use Procorad\Procostat\Domain\Statistics\Normality\ShapiroWilk;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShapiroWilkTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Catégorie 1 — Valeurs de référence connues (vérifiées via scipy.stats)
    // -------------------------------------------------------------------------

    /**
     * Référence Python :
     *   x = [0.08250, 0.00334, 0.00382, 0.00233, 0.00366, 0.00277]
     *   scipy.stats.shapiro(x) → W = 0.5131, p = 3.54e-05
     *
     * Distribution très non-normale (un outlier massif à 0.0825).
     */
    public function test_known_values_match_python_reference(): void
    {
        $values = [0.08250, 0.00334, 0.00382, 0.00233, 0.00366, 0.00277];
        $result = ShapiroWilk::test($values);

        $this->assertArrayHasKey('W', $result);
        $this->assertArrayHasKey('pValue', $result);

        // W très faible → distribution clairement non-normale
        $this->assertEqualsWithDelta(0.5131, $result['W'], 0.0001,
            'W doit correspondre à la valeur scipy à 4 décimales près'
        );

        // p très faible → on rejette H0 (normalité)
        $this->assertLessThan(0.001, $result['pValue'],
            'La pValue doit être < 0.001 pour cette distribution non-normale'
        );
        $this->assertEqualsWithDelta(3.54e-05, $result['pValue'], 1e-06,
            'La pValue doit correspondre à la valeur scipy'
        );
    }

    /**
     * Données linéaires parfaites [10..14] : W doit être très proche de 1.
     * Référence scipy : W ≈ 0.9868, p ≈ 0.958
     */
    public function test_normal_sequential_data_has_high_w_and_pvalue(): void
    {
        $values = [10, 11, 12, 13, 14];
        $result = ShapiroWilk::test($values);

        $this->assertGreaterThan(0.9, $result['W'],
            'Des données linéaires doivent produire W proche de 1'
        );
        $this->assertGreaterThan(0.05, $result['pValue'],
            'On ne doit pas rejeter H0 pour des données linéaires'
        );
    }

    // -------------------------------------------------------------------------
    // Catégorie 2 — Détection de non-normalité
    // -------------------------------------------------------------------------

    /**
     * Distribution bimodale → doit être détectée comme non-normale.
     * scipy : W ≈ 0.75, p < 0.01
     */
    public function test_bimodal_distribution_detected_as_non_normal(): void
    {
        $values = [1.0, 1.1, 1.2, 9.8, 9.9, 10.0, 1.05, 9.95];
        $result = ShapiroWilk::test($values);

        $this->assertLessThan(0.9, $result['W'],
            'Une distribution bimodale doit avoir un W faible'
        );
        $this->assertLessThan(0.05, $result['pValue'],
            'Une distribution bimodale doit rejeter H0'
        );
    }

    // -------------------------------------------------------------------------
    // Catégorie 3 — Cas limites
    // -------------------------------------------------------------------------

    /**
     * n = 3 est le minimum théorique pour Shapiro-Wilk.
     * Le test doit s'exécuter sans erreur et retourner des valeurs valides.
     */
    public function test_minimum_sample_size_of_three_is_accepted(): void
    {
        $values = [1.0, 2.0, 3.0];
        $result = ShapiroWilk::test($values);

        $this->assertArrayHasKey('W', $result);
        $this->assertArrayHasKey('pValue', $result);
        $this->assertGreaterThanOrEqual(0.0, $result['W']);
        $this->assertLessThanOrEqual(1.0, $result['W']);
        $this->assertGreaterThanOrEqual(0.0, $result['pValue']);
        $this->assertLessThanOrEqual(1.0, $result['pValue']);
    }

    /**
     * Valeurs identiques → variance nulle.
     * Comportement attendu : exception.
     */
    public function test_identical_values_throws_or_returns_degenerate(): void
    {
        $values = [5.0, 5.0, 5.0, 5.0, 5.0];

        $this->expectException(InvalidArgumentException::class);
        ShapiroWilk::test($values);
    }

    // -------------------------------------------------------------------------
    // Catégorie 4 — Invariants et contrat de l'API
    // -------------------------------------------------------------------------

    /**
     * W doit toujours être dans [0, 1] quelle que soit l'entrée.
     * pValue doit toujours être dans [0, 1].
     */
    public function test_w_and_pvalue_are_always_within_valid_range(): void
    {
        $datasets = [
            [0.08250, 0.00334, 0.00382, 0.00233, 0.00366, 0.00277], // non-normal
            [10, 11, 12, 13, 14],                                     // linéaire
            [1.0, 1.2, 0.9, 1.1, 0.95, 1.05, 1.15, 0.85],           // ~ normal
            [-3.2, -1.1, 0.0, 1.1, 3.2],                             // symétrique
        ];

        foreach ($datasets as $values) {
            $result = ShapiroWilk::test($values);

            $this->assertGreaterThanOrEqual(0.0, $result['W'],
                'W doit être ≥ 0 pour : ' . implode(', ', $values)
            );
            $this->assertLessThanOrEqual(1.0, $result['W'],
                'W doit être ≤ 1 pour : ' . implode(', ', $values)
            );
            $this->assertGreaterThanOrEqual(0.0, $result['pValue'],
                'pValue doit être ≥ 0 pour : ' . implode(', ', $values)
            );
            $this->assertLessThanOrEqual(1.0, $result['pValue'],
                'pValue doit être ≤ 1 pour : ' . implode(', ', $values)
            );
        }
    }

    /**
     * n < 3 doit lever une exception (Shapiro-Wilk non défini).
     */
    public function test_sample_smaller_than_3_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ShapiroWilk::test([1.0, 2.0]);
    }
}
