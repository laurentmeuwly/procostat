<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\CheckNormality;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;

final class CheckNormalityTest extends TestCase
{
    private function contextWithPopulation(
        PopulationStatus $status
    ): AnalysisContext {
        $dataset = new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
                new Measurement('LAB04', 13.0, new Uncertainty(0.5)),
                new Measurement('LAB05', 14.0, new Uncertainty(0.5)),
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
            thresholdStandard: 'iso13528'
        );
        $context->population = new Population($dataset->measurements());
        $context->populationStatus = $status;

        return $context;
    }

    public function test_normality_is_not_checked_when_not_applicable(): void
    {
        $adapter = $this->createMock(NormalityAdapter::class);
        $adapter->expects($this->never())->method('analyze');

        $step = new CheckNormality($adapter);

        $context = $this->contextWithPopulation(
            PopulationStatus::DESCRIPTIVE_ONLY
        );

        $result = $step($context);

        $this->assertNull($result->normalityResult);
    }

    public function test_normality_is_checked_when_applicable(): void
    {
        $normalityResult = new NormalityResult(
            isNormal: true,
            shapiroWilkPValue: 0.12,
            skewness: 0.05,
            kurtosis: -0.10,
            conclusion: 'Distribution compatible with normality.',
            henryLine: null
        );

        $adapter = $this->createMock(NormalityAdapter::class);
        $adapter
            ->expects($this->once())
            ->method('analyze')
            ->willReturn($normalityResult);

        $step = new CheckNormality($adapter);

        $context = $this->contextWithPopulation(
            PopulationStatus::FULL_EVALUATION
        );

        $result = $step($context);

        $this->assertSame(
            $normalityResult,
            $result->normalityResult
        );
    }
}
