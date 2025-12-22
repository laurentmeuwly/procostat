<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Domain\Performance\PerformanceIndicators;
use Procorad\Procostat\Domain\Performance\PerformanceStatus;
use Procorad\Procostat\Domain\Performance\IndicatorType;

final class ComputePerformanceIndicators
{
    /**
     * Placeholder implementation.
     *
     * Performance indicators (z, z', zeta, bias)
     * are assumed to be already present in the context.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function __invoke(array $context): array
    {
        $dataset        = $context['dataset'];
        $population     = $context['population'];
        $robustStats    = $context['robust_statistics'];
        $reference      = $context['reference'];
        $sigmaPt        = $context['sigma_pt'];
        $labResults     = $context['laboratory_results'];

        $indicators = [];

        foreach ($labResults as $labResult) {
            $indicators[$labResult->laboratoryCode()] = $this->computeForLab(
                $labResult,
                $dataset,
                $population,
                $robustStats,
                $reference,
                $sigmaPt
            );
        }

        $context['performance_indicators'] = $indicators;

        return $context;
    }

     private function computeForLab(
        $labResult,
        $dataset,
        $population,
        $robustStats,
        $reference,
        $sigmaPt
    ): PerformanceIndicators {
        $xLab = $labResult->value();
        $uLab = $labResult->uncertainty();

        $xRef = $reference->value();
        $uRef = $reference->uncertainty();

        $bias = ($xLab - $xRef) / $xRef;

        $useZPrime = $this->shouldUseZPrime(
            $reference,
            $robustStats,
            $population->size()
        );

        $zPrime = null;
        $z      = null;

        if ($useZPrime) {
            $zPrime = ($xLab - $xRef)
                / sqrt($sigmaPt->value() ** 2 + $uRef ** 2);
        } else {
            $z = ($xLab - $xRef) / $sigmaPt->value();
        }

        $zeta = ($xLab - $xRef)
            / sqrt($uLab ** 2 + $uRef ** 2);

        $declaredValue = $useZPrime ? $zPrime : $z;

        $status = match (true) {
            abs($declaredValue) < 2  => PerformanceStatus::CONFORME,
            abs($declaredValue) <= 3 => PerformanceStatus::DISCUTABLE,
            default                  => PerformanceStatus::NON_CONFORME,
        };

        return new PerformanceIndicators(
            z: $z,
            zPrime: $zPrime,
            zeta: $zeta,
            bias: $bias,
            status: $status,
            declaredIndicator: $useZPrime
                ? IndicatorType::Z_PRIME
                : IndicatorType::Z
        );
    }

    private function shouldUseZPrime(
        $reference,
        $robustStats,
        int $n
    ): bool {
        if (!$reference->isFromMRC()) {
            return false;
        }

        $lhs = abs($robustStats->mean() - $reference->value());

        $rhs = 2 * sqrt(
            (1.25 * $robustStats->stdDev() / sqrt($n)) ** 2
            + $reference->uncertainty() ** 2
        );

        return $lhs <= $rhs;
    }
}
