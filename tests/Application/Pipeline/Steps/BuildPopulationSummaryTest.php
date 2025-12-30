<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\BuildPopulationSummary;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\DTO\PopulationSummary;

final class BuildPopulationSummaryTest extends TestCase
{
    private function context(): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
            ],
            assignedValueSpec: new \Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification(
                \Procorad\Procostat\Domain\AssignedValue\AssignedValueType::ROBUST_MEAN,
                null,
                null
            ),
            campaign: '2025',
            sampleCode: 'XGA',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );

        $context->population = new Population($dataset->measurements());
        $context->populationStatus = PopulationStatus::FULL_EVALUATION;
        $context->robustStatistics = new RobustStatistics(11.0, 1.0);
        $context->assignedValue = AssignedValue::robust(11.0);
        $context->normalityResult = new NormalityResult(
            isNormal: true,
            shapiroWilkPValue: 0.12,
            skewness: 0.01,
            kurtosis: -0.05,
            conclusion: 'Normal',
            henryLine: null
        );
        $context->outliers = [
            'dixon' => ['LAB03'],
            'grubbs' => [],
        ];

        return $context;
    }

    public function test_population_summary_is_built(): void
    {
        $context = $this->context();

        $result = (new BuildPopulationSummary)($context);

        $this->assertInstanceOf(
            PopulationSummary::class,
            $result->populationSummary
        );

        $summary = $result->populationSummary;

        $this->assertSame(3, $summary->participantCount);
        $this->assertSame(
            PopulationStatus::FULL_EVALUATION,
            $summary->populationStatus
        );

        $this->assertSame(11.0, $summary->assignedValue);
        $this->assertNull($summary->assignedUncertainty);
        $this->assertSame(1.0, $summary->populationStdDev);

        $this->assertNotNull($summary->normality);
        $this->assertIsArray($summary->outliers);
    }
}
