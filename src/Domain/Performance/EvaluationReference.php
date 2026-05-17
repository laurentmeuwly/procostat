<?php

namespace Procorad\Procostat\Domain\Performance;

/**
 * Référence d'évaluation construite pour une branche précise du workflow.
 *
 * Encapsule les trois paramètres qui varient selon la branche :
 *   - la valeur centrale (xRef) : contre quoi on calcule le biais
 *   - sigma (σ_pt)              : la dispersion de l'aptitude
 *   - uRef                      : l'incertitude de la référence (pour z' et zeta)
 *
 * Et les deux décisions qui en découlent :
 *   - decisionBasis   : l'indicateur qui décide de la conformité
 *   - referenceSource : d'où vient xRef (pour la traçabilité)
 *
 * Ce value object est construit une seule fois par BuildEvaluationReference
 * et passé tel quel à EvaluateLaboratories — qui n'a plus besoin de
 * connaître la branche du workflow.
 */
final class EvaluationReference
{
    public function __construct(
        /** Valeur centrale de référence (xRef) — utilisée pour biais et scores */
        public readonly float $centralValue,

        /**
         * Écart-type d'aptitude (σ_pt) — dénominateur de z et z'.
         * Null uniquement si decisionBasis est ZETA pur (descriptive_only) :
         * dans ce cas seule l'incertitude combinée entre en jeu.
         */
        public readonly ?float $sigma,

        /**
         * Incertitude standard (k=1) de la référence — utilisée pour z' et zeta.
         * Null si la référence n'a pas d'incertitude propre (moyenne arithmétique
         * non certifiée sans propagation).
         */
        public readonly ?float $uRef,

        /** Indicateur retenu comme score de décision (conformité) */
        public readonly IndicatorType $decisionBasis,

        /** Source de la valeur centrale — pour la traçabilité */
        public readonly ReferenceSource $referenceSource,
    ) {}

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function requiresSigma(): bool
    {
        return $this->decisionBasis === IndicatorType::Z
            || $this->decisionBasis === IndicatorType::Z_PRIME;
    }

    public function requiresURef(): bool
    {
        return $this->decisionBasis === IndicatorType::Z_PRIME
            || $this->decisionBasis === IndicatorType::ZETA;
    }

    public function toTracePayload(): array
    {
        return [
            'source'         => $this->referenceSource->value,
            'decision_basis' => $this->decisionBasis->value,
            'central_value'  => $this->centralValue,
            'sigma'          => $this->sigma,
            'u_ref'          => $this->uRef,
        ];
    }
}
