<?php

namespace Procorad\Procostat\Domain\Trace;

/**
 * Trace décisionnelle complète d'une analyse PROCOSTAT.
 *
 * Produite en parallèle du ProcostatResult, elle expose chaque
 * bifurcation du workflow biologique afin de permettre la validation
 * scientifique du comportement du moteur — étape par étape.
 *
 * Usage test :
 *   expect($trace->workflowPath)->toEqual([
 *       'population_check',
 *       'normality_check',
 *       'grubbs',
 *       'robust_mean',
 *       'zscore',
 *   ]);
 */
final class AnalysisTrace
{
    // ── Population ──────────────────────────────────────────────────────────

    /**
     * Nombre de participants retenus après filtrage initial.
     */
    public int $participantCount = 0;

    /**
     * Statut de la population selon ISO 13528 :
     *   - 'not_exploitable'   : n < 3
     *   - 'descriptive_only'  : 3 ≤ n ≤ 6
     *   - 'full_evaluation'   : n ≥ 7
     */
    public string $populationStatus = '';

    // ── Valeur assignée ─────────────────────────────────────────────────────

    /**
     * Vrai si la valeur assignée est une valeur certifiée (MRC),
     * faux si elle est dérivée des participants (moyenne robuste).
     *
     * Branche gauche / droite du logigramme.
     */
    public bool $isCertifiedReference = false;

    // ── Normalité ───────────────────────────────────────────────────────────

    /**
     * Test de normalité applicable (n ≥ 7).
     */
    public bool $normalityTestApplicable = false;

    /**
     * p-valeur Shapiro-Wilk (null si non calculée).
     */
    public ?float $shapiroWilkPValue = null;

    /**
     * Hypothèse de normalité retenue par le moteur.
     */
    public bool $normalityAccepted = false;

    /**
     * Participants exclus avant robustification car distribution
     * non-normale rendait le test de Grubbs non applicable.
     *
     * @var int[]
     */
    public array $excludedBeforeRobust = [];

    // ── Détection des aberrants ──────────────────────────────────────────────

    /**
     * Test de Grubbs déclenché.
     */
    public bool $grubbsTriggered = false;

    /**
     * Statistique G de Grubbs (null si non applicable).
     */
    public ?float $grubbsStatistic = null;

    /**
     * Index dans la population du candidat Grubbs (null si non applicable).
     */
    public ?int $grubbsCandidateIndex = null;

    /**
     * Grubbs a conclu à la présence d'un aberrant.
     */
    public bool $grubbsOutlierDetected = false;

    /**
     * Test de Dixon déclenché.
     */
    public bool $dixonTriggered = false;

    /**
     * Statistique Q de Dixon (null si non applicable).
     */
    public ?float $dixonStatistic = null;

    // ── Statistiques robustes ────────────────────────────────────────────────

    /**
     * Statistiques robustes calculées sur la population.
     */
    public bool $robustStatisticsComputed = false;

    /**
     * z-score maximum dans la population (avant décision de troncature).
     * Valeur > 5 déclenche la troncature selon le logigramme.
     */
    public ?float $maxZScoreBeforeTruncation = null;

    /**
     * Troncature déclenchée car z-score > 5.
     */
    public bool $truncationTriggered = false;

    /**
     * Participants tronqués (codes laboratoires).
     *
     * @var string[]
     */
    public array $truncatedLabs = [];

    // ── Indicateur de performance ────────────────────────────────────────────

    /**
     * Indicateur principal retenu :
     *   - 'z'        : MRC disponible, valeur certifiée indépendante
     *   - 'z_prime'  : pas de MRC, valeur dérivée des participants
     *   - null       : population non exploitable
     */
    public ?string $primaryIndicator = null;

    // ── Référence d'évaluation ───────────────────────────────────────────────

    /**
     * Source retenue pour la valeur centrale de référence.
     * Null si not_exploitable.
     *
     * @var string|null  (valeur de ReferenceSource::value)
     */
    public ?string $evaluationReferenceSource = null;

    /**
     * Score de décision retenu pour la conformité.
     * Null si not_exploitable.
     *
     * @var string|null  (valeur de IndicatorType::value)
     */
    public ?string $evaluationDecisionBasis = null;

    /**
     * Valeur centrale utilisée comme référence (xRef).
     */
    public ?float $evaluationCentralValue = null;

    /**
     * σ_pt retenu (null si zeta pur).
     */
    public ?float $evaluationSigma = null;

    /**
     * u(xRef) k=1 retenu (null si pas d'incertitude sur la référence).
     */
    public ?float $evaluationURef = null;

    // ── Chemin décisionnel ───────────────────────────────────────────────────

    /**
     * Étapes franchies dans l'ordre d'exécution.
     *
     * Chaque étape est un slug parmi :
     *   'population_check', 'descriptive_only', 'normality_check',
     *   'grubbs', 'dixon', 'truncation', 'robust_mean',
     *   'zscore', 'zprime', 'not_exploitable'
     *
     * C'est la clé de validation métier : on vérifie que le moteur
     * a pris exactement les mêmes décisions que le biologiste.
     *
     * @var string[]
     */
    public array $workflowPath = [];

    /**
     * Événements structurés enregistrés pendant l'analyse.
     * Clé = nom de l'événement, valeur = payload libre.
     *
     * Exemple :
     *   $trace->record('evaluation.reference.selected', [
     *       'source'         => 'robust_mean',
     *       'decision_basis' => 'z_prime',
     *       'central_value'  => 42.1,
     *       'sigma'          => 3.8,
     *   ]);
     *
     * @var array<string, array<string, mixed>>
     */
    public array $events = [];

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function addStep(string $step): void
    {
        $this->workflowPath[] = $step;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(string $event, array $payload): void
    {
        $this->events[$event] = $payload;
    }

    public function hasEvent(string $event): bool
    {
        return isset($this->events[$event]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEvent(string $event): ?array
    {
        return $this->events[$event] ?? null;
    }

    public function usesZScore(): bool
    {
        return $this->primaryIndicator === 'z';
    }

    public function usesZPrimeScore(): bool
    {
        return $this->primaryIndicator === 'z_prime';
    }

    public function isExploitable(): bool
    {
        return $this->populationStatus !== 'not_exploitable';
    }
}
