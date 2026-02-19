<?php

namespace Procorad\Procostat\Tests\Support;

use Procorad\Procostat\DTO\ProcostatResult;
use Procorad\Procostat\DTO\LabEvaluation;

final class ProcostatResultView
{
    public static function forDiscussion(ProcostatResult $result): array
    {
        return [
            'assigned_value' => [
                'type' => $result->assignedValue->type()->value,
                'value' => $result->assignedValue->value(),
                'uncertainty_k2' => $result->assignedValue->expandedUncertaintyK2(),
            ],

            'robust_statistics' => [
                'mean' => round($result->robustStatistics->mean(), 3),
                'std_dev' => round($result->robustStatistics->stdDev(), 3),
            ],

            'indicator' => $result->primaryIndicator->value,

            'laboratories' => array_map(
                static fn (LabEvaluation $eval) => [
                    'lab_code' => $eval->laboratoryCode,
                    'z' => $eval->zScore !== null ? round($eval->zScore, 2) : null,
                    'z_prime' => $eval->zPrimeScore !== null ? round($eval->zPrimeScore, 2) : null,
                    'zeta' => $eval->zetaScore !== null ? round($eval->zetaScore, 2) : null,
                    'bias_percent' => round($eval->biasPercent, 1),
                    'decision' => $eval->fitnessStatus->value,
                    'decision_basis' => $eval->decisionBasis,
                ],
                $result->labEvaluations()
            ),
        ];
    }
}
