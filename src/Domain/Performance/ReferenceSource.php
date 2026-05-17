<?php

namespace Procorad\Procostat\Domain\Performance;

/**
 * Source de la valeur centrale utilisée comme référence d'évaluation.
 *
 * Déterminée par BuildEvaluationReference selon la branche du workflow :
 *
 *   ArithmeticMean        → descriptive_only, ou full_evaluation naturel n < 12
 *                           sans troncature
 *   TrimmedArithmeticMean → full_evaluation naturel n < 12 avec Grubbs positif
 *   RobustMean            → full_evaluation n ≥ 12, ou certifié non validé
 *   CertifiedValue        → full_evaluation certifié, validé par expert
 */
enum ReferenceSource: string
{
    case ArithmeticMean        = 'arithmetic_mean';
    case TrimmedArithmeticMean = 'trimmed_arithmetic_mean';
    case RobustMean            = 'robust_mean';
    case CertifiedValue        = 'certified_value';
}
