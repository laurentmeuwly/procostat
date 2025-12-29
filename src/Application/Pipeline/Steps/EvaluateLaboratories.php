<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\DTO\LabEvaluation;
use RuntimeException;

final class EvaluateLaboratories implements PipelineStep
{
    public function __construct(
        private readonly ThresholdsResolver $thresholdsResolver
    ) {}

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if (
            $context->population === null ||
            $context->assignedValue === null ||
            $context->robustStatistics === null ||
            $context->primaryIndicator === null
        ) {
            throw new RuntimeException(
                'EvaluateLaboratories requires population, assignedValue, robustStatistics and primaryIndicator.'
            );
        }

        $thresholds = $this->thresholdsResolver->resolve(
            $context->thresholdStandard
        );

        foreach ($context->population->measurements() as $measurement) {
            $context->labEvaluations[] = $this->evaluateLab(
                $measurement,
                $context->assignedValue,
                $context->robustStatistics,
                $context->primaryIndicator,
                $thresholds
            );
        }

        return $context;
    }

    private function evaluateLab(
        $measurement,
        $assignedValue,
        $robustStats,
        IndicatorType $indicatorType,
        $thresholds
    ): LabEvaluation {
        $xLab = $measurement->value();
        $uLab = $measurement->uncertainty()?->standard();

        $xRef = $assignedValue->value();
        $uRef = $assignedValue->standardUncertainty();

        $z = null;
        $zPrime = null;
        $zeta = null;

        if ($indicatorType === IndicatorType::Z) {
            $z = ($xLab - $xRef) / $robustStats->stdDev();
            $decisionScore = $z;
            $decisionBasis = 'z';
        } else {
            $zPrime = ($xLab - $xRef)
                / sqrt($robustStats->stdDev() ** 2 + ($uRef ?? 0.0) ** 2);
            $decisionScore = $zPrime;
            $decisionBasis = 'z_prime';
        }

        if ($uLab !== null && $uRef !== null) {
            $zeta = ($xLab - $xRef) / sqrt($uLab ** 2 + $uRef ** 2);
        }

        $fitnessStatus = FitnessDecision::decideFromScore(
            $decisionScore,
            $thresholds
        );

        return new LabEvaluation(
            laboratoryCode: $measurement->laboratoryCode(),
            zScore: $z,
            zPrimeScore: $zPrime,
            zetaScore: $zeta,
            biasPercent: ($xLab - $xRef) / $xRef * 100,
            fitnessStatus: $fitnessStatus,
            decisionBasis: $decisionBasis
        );
    }
}
