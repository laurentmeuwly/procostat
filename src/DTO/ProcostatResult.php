<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Statistics\DescriptiveStatistics;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;

/**
 * Résultat complet d'une analyse PROCOSTAT.
 *
 * Règles de nullabilité métier :
 *
 *   descriptiveStatistics : JAMAIS null — calculées pour toutes les branches,
 *                           y compris not_exploitable et descriptive_only.
 *
 *   robustStatistics      : null si not_exploitable (n < 3) ou
 *                           descriptive_only (3 ≤ n ≤ 6).
 *                           Présente uniquement si full_evaluation (n ≥ 7).
 *
 *   primaryIndicator      : null si not_exploitable. Présent sinon (z ou z').
 *
 * Ces nullables sont des contraintes métier, pas des états d'erreur.
 * Le consommateur DOIT vérifier populationSummary.populationStatus
 * avant d'accéder à robustStatistics ou primaryIndicator.
 */
final class ProcostatResult
{
    /**
     * @param  array<string, LabEvaluation>  $labEvaluations
     */
    public function __construct(
        public readonly AssignedValue $assignedValue,

        /** Toujours présentes — min, max, médiane, moyenne, s, MAD */
        public readonly DescriptiveStatistics $descriptiveStatistics,

        /**
         * Null si n < 7 (not_exploitable ou descriptive_only).
         * Contient x* et s* si full_evaluation.
         * Si troncature (z>5), il s'agit des stats SUR LA POPULATION TRONQUÉE.
         */
        public readonly ?RobustStatistics $robustStatistics,

        /**
         * Stats robustes calculées sur la population COMPLÈTE avant troncature.
         * Null si aucune troncature n'a eu lieu (robustStatistics = version initiale).
         * Présent uniquement si truncationTriggered = true dans la trace.
         */
        public readonly ?RobustStatistics $robustStatisticsBeforeTruncation,

        public readonly PopulationSummary $populationSummary,

        /**
         * Null si not_exploitable.
         * 'z' si MRC certifiée, 'z_prime' si valeur dérivée des participants.
         */
        public readonly ?IndicatorType $primaryIndicator,

        /** @var array<string, LabEvaluation> */
        public readonly array $labEvaluations,

        public readonly AuditTrail $auditTrail,
        public readonly string $engineVersion
    ) {}

    /** @return array<string, LabEvaluation> */
    public function labEvaluations(): array
    {
        return $this->labEvaluations;
    }

    public function labEvaluationFor(int|string $labCode): ?LabEvaluation
    {
        return $this->labEvaluations[(string) $labCode] ?? null;
    }

    // Business helpers

    /**
     * Vrai si des statistiques robustes ont été calculées.
     * Utiliser avant tout accès à robustStatistics.
     */
    public function hasRobustStatistics(): bool
    {
        return $this->robustStatistics !== null;
    }

    /**
     * Vrai si un indicateur de performance a été calculé.
     * Utiliser avant tout accès à primaryIndicator ou labEvaluations.
     */
    public function hasPerformanceIndicator(): bool
    {
        return $this->primaryIndicator !== null;
    }
}
