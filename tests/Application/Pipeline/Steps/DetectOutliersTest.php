<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\DetectOutliers;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;

final class DetectOutliersTest extends TestCase
{
    private function context(
        PopulationStatus $status,
        ?NormalityResult $normality
    ): AnalysisContext {
        $dataset = new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 50.0, new Uncertainty(0.5)), // outlier candidate
                new Measurement('LAB04', 12.0, new Uncertainty(0.5)),
                new Measurement('LAB05', 13.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'X',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'ISO_13528'
        );
        $context->population = new Population($dataset->measurements());
        $context->populationStatus = $status;
        $context->normalityResult = $normality;

        return $context;
    }

    public function test_outliers_not_detected_when_no_normality(): void
    {
        $context = $this->context(
            PopulationStatus::FULL_EVALUATION,
            null
        );

        $result = (new DetectOutliers)($context);

        $this->assertNull($result->outliers);
    }

    public function test_outliers_not_detected_when_not_applicable(): void
    {
        $normality = new NormalityResult(
            isNormal: false,
            shapiroWilkPValue: 0.01,
            skewness: 1.2,
            kurtosis: 3.4,
            conclusion: 'Distribution not normal.',
            henryLine: null
        );

        $context = $this->context(
            PopulationStatus::DESCRIPTIVE_ONLY,
            $normality
        );

        $result = (new DetectOutliers)($context);

        $this->assertNull($result->outliers);
    }

    public function test_outliers_detected_when_applicable(): void
    {
        $normality = new NormalityResult(
            isNormal: true,
            shapiroWilkPValue: 0.12,
            skewness: 0.05,
            kurtosis: -0.10,
            conclusion: 'Distribution compatible with normality.',
            henryLine: null
        );

        $context = $this->context(
            PopulationStatus::FULL_EVALUATION,
            $normality
        );

        $result = (new DetectOutliers)($context);

        $this->assertIsArray($result->outliers);
        $this->assertArrayHasKey('dixon', $result->outliers);
        $this->assertArrayHasKey('grubbs', $result->outliers);
    }
}
