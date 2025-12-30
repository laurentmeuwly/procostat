<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Rules\Thresholds;
use Procorad\Procostat\Domain\Statistics\Performance\ZetaScore;
use Procorad\Procostat\Domain\Statistics\Performance\ZPrimeScore;
use Procorad\Procostat\Domain\Statistics\Performance\ZScore;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
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
            $labCode = (string) $measurement->laboratoryCode();
            $context->labEvaluations[$labCode] = $this->evaluateLab(
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
        Measurement $measurement,
        AssignedValue $assignedValue,
        RobustStatistics $robustStats,
        IndicatorType $indicatorType,
        Thresholds $thresholds
    ): LabEvaluation {
        $xLab = $measurement->value();
        $uLab = $measurement->uncertainty()?->toStandard();   // k=1

        $xRef = $assignedValue->value();
        $uRef = $assignedValue->standardUncertainty();  // k=1

        $z = null;
        $zPrime = null;
        $zeta = null;

        $sigma = $robustStats->stdDev();
        if ($sigma <= 0.0) {
            throw new \LogicException('Standard deviation must be strictly positive.');
        }

        if ($indicatorType === IndicatorType::Z) {
            $z = ZScore::compute(
                result: $xLab,
                assignedValue: $xRef,
                sigmaPt: $sigma
            );
            $decisionScore = $z;
            $decisionBasis = 'z';
        } else {
            $zPrime = ZPrimeScore::compute(
                result: $xLab,
                assignedValue: $xRef,
                sigmaPt: $sigma,
                uAssigned: $uRef
            );
            $decisionScore = $zPrime;
            $decisionBasis = 'z_prime';
        }

        if ($uLab !== null && $uRef !== null) {
            $zeta = ZetaScore::compute(
                result: $xLab,
                assignedValue: $xRef,
                uResult: $uLab,     // standard uncertainty (k=1)
                uAssigned: $uRef   // standard uncertainty (k=1)
            );
        }

        $fitnessStatus = FitnessDecision::decideFromScore(
            $decisionScore,
            $thresholds
        );

        return new LabEvaluation(
            laboratoryCode: (string) $measurement->laboratoryCode(),
            zScore: $z,
            zPrimeScore: $zPrime,
            zetaScore: $zeta,
            biasPercent: ($xLab - $xRef) / $xRef * 100,
            fitnessStatus: $fitnessStatus,
            decisionBasis: $decisionBasis
        );
    }
}
