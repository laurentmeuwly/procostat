<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Performance\EvaluationReference;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Performance\ReferenceSource;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

/**
 * Construit la référence d'évaluation selon la branche active du workflow.
 *
 * C'est ici que se matérialise la décision du logigramme :
 * quelle valeur centrale, quel sigma, quel indicateur de décision ?
 *
 * ── Table de décision ────────────────────────────────────────────────────────
 *
 *  Statut          │ Assignée    │ Résultat    │ Référence              │ Décision
 *  ────────────────┼─────────────┼─────────────┼────────────────────────┼─────────
 *  not_exploitable │ —           │ —           │ null (pas d'évaluation)│ —
 *  descriptive_only│ toute       │ —           │ ArithmeticMean         │ ZETA
 *  full_evaluation │ robust_mean │ —           │ RobustMean             │ Z_PRIME
 *  full_evaluation │ certified   │ validé      │ CertifiedValue         │ Z_PRIME
 *  full_evaluation │ certified   │ non validé  │ RobustMean             │ Z_PRIME
 *
 *  Note : la validation expert (certified vs robust_mean pour full_eval certifié)
 *  est portée par AssignedValue::isIndependent() → primaryIndicator = Z.
 *  Ici on traduit ce Z en référence concrète.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
final class BuildEvaluationReference implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'BuildEvaluationReference requires PopulationStatus.'
            );
        }

        if ($context->assignedValue === null) {
            throw new RuntimeException(
                'BuildEvaluationReference requires AssignedValue.'
            );
        }

        // ── not_exploitable : pas d'évaluation individuelle ──────────────────
        if ($context->populationStatus === PopulationStatus::NOT_EXPLOITABLE) {
            $context->evaluationReference = null;
            return $context;
        }

        // ── descriptive_only : zeta sur moyenne arithmétique ─────────────────
        if ($context->populationStatus === PopulationStatus::DESCRIPTIVE_ONLY) {
            $context->evaluationReference = $this->buildDescriptiveReference($context);
            $this->traceReference($context);
            return $context;
        }

        // ── full_evaluation ───────────────────────────────────────────────────
        $context->evaluationReference = $this->buildFullEvaluationReference($context);
        $this->traceReference($context);

        return $context;
    }

    // ── Builders par branche ─────────────────────────────────────────────────

    private function buildDescriptiveReference(AnalysisContext $context): EvaluationReference
    {
        $descriptive = $context->descriptiveStatistics
            ?? throw new RuntimeException(
                'BuildEvaluationReference requires DescriptiveStatistics for descriptive_only.'
            );

        if ($descriptive->mean === null) {
            throw new RuntimeException(
                'DescriptiveStatistics::mean is required for descriptive_only evaluation.'
            );
        }

        // Pour descriptive_only : pas de sigma d'aptitude (pas de z ni z'),
        // uniquement le zeta basé sur les incertitudes individuelles.
        // uRef = null car la moyenne arithmétique n'a pas d'incertitude certifiée.
        return new EvaluationReference(
            centralValue:    $descriptive->mean,
            sigma:           null,
            uRef:            null,
            decisionBasis:   IndicatorType::ZETA,
            referenceSource: ReferenceSource::ArithmeticMean,
        );
    }

    private function buildFullEvaluationReference(AnalysisContext $context): EvaluationReference
    {
        $robustStats = $context->robustStatistics
            ?? throw new RuntimeException(
                'BuildEvaluationReference requires RobustStatistics for full_evaluation.'
            );

        $assignedValue = $context->assignedValue;

        // Branche certifiée : primaryIndicator = Z → référence = valeur certifiée
        if ($context->primaryIndicator === IndicatorType::Z) {
            return new EvaluationReference(
                centralValue:    $assignedValue->value(),
                sigma:           $robustStats->stdDev(),
                uRef:            $assignedValue->standardUncertainty(),
                decisionBasis:   IndicatorType::Z_PRIME,
                referenceSource: ReferenceSource::CertifiedValue,
            );
        }

        // Branche robuste : primaryIndicator = Z_PRIME → référence = moyenne robuste
        return new EvaluationReference(
            centralValue:    $robustStats->mean(),
            sigma:           $robustStats->stdDev(),
            uRef:            $assignedValue->standardUncertainty(),
            decisionBasis:   IndicatorType::Z_PRIME,
            referenceSource: ReferenceSource::RobustMean,
        );
    }

    // ── Trace ────────────────────────────────────────────────────────────────

    private function traceReference(AnalysisContext $context): void
    {
        $ref = $context->evaluationReference;
        if ($ref === null) {
            return;
        }

        // Propriétés plates pour assertions directes en test
        $context->trace->evaluationReferenceSource = $ref->referenceSource->value;
        $context->trace->evaluationDecisionBasis   = $ref->decisionBasis->value;
        $context->trace->evaluationCentralValue    = $ref->centralValue;
        $context->trace->evaluationSigma           = $ref->sigma;
        $context->trace->evaluationURef            = $ref->uRef;

        // Événement structuré pour validation payload complet
        $context->trace->record(
            'evaluation.reference.selected',
            $ref->toTracePayload()
        );

        $context->trace->addStep('evaluation_reference_' . $ref->referenceSource->value);
    }
}
