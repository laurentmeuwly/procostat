<?php

namespace Procorad\Procostat\Tests\Support\Procostat;

use Procorad\Procostat\Domain\Aptitude\AptitudeStdDev;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Population\PopulationStatus;
use Procorad\Procostat\Domain\Reference\ReferenceValues;
use Procorad\Procostat\Domain\Results\LaboratoryResult;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;

/**
 * Factory for building consistent pipeline contexts
 * for ComputePerformanceIndicators tests.
 */
final class TestContextFactory
{
    /**
     * Base scenario: one laboratory, coherent statistics.
     */
    public static function withSingleLab(
        bool $isMrc = true,
        int $n = 20,
        float $xStar = 100,
        float $sStar = 5,
        float $xRef = 100,
        float $uRef = 1,
        float $xLab = 110,
        float $uLab = 2,
        float $sigmaPt = 10
    ): array {
        return [
            'dataset' => self::dataset(),
            'population' => new PopulationStatus(
                size: $n,
                exploitable: true
            ),
            'robust_statistics' => new RobustStatistics(
                mean: $xStar,
                stdDev: $sStar
            ),
            'reference' => new ReferenceValues(
                value: $xRef,
                uncertainty: $uRef,
                fromMrc: $isMrc
            ),
            'sigma_pt' => new AptitudeStdDev($sigmaPt),
            'laboratory_results' => [
                'LAB1' => new LaboratoryResult(
                    laboratoryCode: 'LAB1',
                    value: $xLab,
                    uncertainty: $uLab
                ),
            ],
        ];
    }

    /**
     * Forces a given declared z or z′ value by adjusting sigma_pt.
     *
     * Useful for testing thresholds 2 / 3 exactly.
     */
    public static function withDeclaredValue(
        float $declaredValue,
        bool $useZPrime = false
    ): array {
        $xRef = 100.0;
        $xLab = $xRef + $declaredValue;

        $sigmaPt = abs($declaredValue) > 0
            ? abs(($xLab - $xRef) / $declaredValue)
            : 1.0;

        return self::withSingleLab(
            isMrc: $useZPrime,
            xRef: $xRef,
            xLab: $xLab,
            sigmaPt: $sigmaPt
        );
    }

    /**
     * Minimal dataset stub.
     *
     * Dataset content is irrelevant for this step;
     * it just needs to exist.
     */
    private static function dataset(): AnalysisDataset
    {
        return new AnalysisDataset([
            new Measurement(
                laboratoryCode: 'LAB1',
                value: 100.0,
                uncertainty: null
            ),
        ]);
    }
}
