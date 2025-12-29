<?php

namespace Procorad\Procostat\Domain\Performance;

final class IndicatorTypeDecider
{
    public static function decideZorZPrime(
        IndicatorDecisionContext $ctx
    ): IndicatorType {
        if (!$ctx->isStable) {
            return IndicatorType::Z_PRIME;
        }

        if (!$ctx->assignedValueIsIndependent) {
            return IndicatorType::Z_PRIME;
        }

        $threshold = 2 * sqrt(
            pow(1.25 * $ctx->robustStdDev / sqrt($ctx->participantCount), 2)
            + pow($ctx->referenceUncertainty, 2)
        );

        if (abs($ctx->robustMean - $ctx->referenceValue) > $threshold) {
            return IndicatorType::Z_PRIME;
        }

        return IndicatorType::Z;
    }
}
