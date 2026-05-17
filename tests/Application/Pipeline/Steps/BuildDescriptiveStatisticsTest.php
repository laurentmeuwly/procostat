<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\BuildDescriptiveStatistics;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Statistics\DescriptiveStatistics;
use Procorad\Procostat\DTO\AnalysisDataset;

final class BuildDescriptiveStatisticsTest extends TestCase
{
    private function contextWithPopulation(array $values): AnalysisContext
    {
        $measurements = array_map(
            static fn (int $i, float $v) => new Measurement("LAB{$i}", $v, new Uncertainty(0.5)),
            array_keys($values),
            $values
        );

        $dataset = new AnalysisDataset(
            measurements: $measurements,
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN, null, null
            ),
            campaign: '2025',
            sampleCode: 'TEST',
            radionuclide: 'Cs-137',
            unit: 'Bq/kg'
        );

        $context = new AnalysisContext(dataset: $dataset, thresholdStandard: 'iso13528');
        $context->population = new Population($measurements);

        return $context;
    }

    public function test_produces_descriptive_statistics(): void
    {
        $context = $this->contextWithPopulation([10.0, 20.0, 30.0, 40.0, 50.0]);

        $result = (new BuildDescriptiveStatistics)($context);

        $this->assertInstanceOf(DescriptiveStatistics::class, $result->descriptiveStatistics);
    }

    public function test_correct_min_max(): void
    {
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([15.0, 5.0, 25.0, 10.0, 20.0])
        )->descriptiveStatistics;

        $this->assertSame(5.0, $stats->minimum);
        $this->assertSame(25.0, $stats->maximum);
    }

    public function test_correct_median_odd_count(): void
    {
        // [5, 10, 15, 20, 25] → médiane = 15
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([15.0, 5.0, 25.0, 10.0, 20.0])
        )->descriptiveStatistics;

        $this->assertSame(15.0, $stats->median);
    }

    public function test_correct_median_even_count(): void
    {
        // [10, 20, 30, 40] → médiane = (20+30)/2 = 25
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([10.0, 20.0, 30.0, 40.0])
        )->descriptiveStatistics;

        $this->assertSame(25.0, $stats->median);
    }

    public function test_correct_mean(): void
    {
        // [10, 20, 30] → moyenne = 20
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([10.0, 20.0, 30.0])
        )->descriptiveStatistics;

        $this->assertEqualsWithDelta(20.0, $stats->mean, 0.001);
    }

    public function test_standard_deviation_uses_bessel_correction(): void
    {
        // [10, 20, 30] → std échantillon = sqrt(((−10)²+(0)²+(10)²)/2) = sqrt(100) = 10
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([10.0, 20.0, 30.0])
        )->descriptiveStatistics;

        $this->assertEqualsWithDelta(10.0, $stats->standardDeviation, 0.001);
    }

    public function test_mad_computed(): void
    {
        // [1, 2, 3, 4, 5] → médiane=3, |déviation|=[2,1,0,1,2], MAD=médiane([0,1,1,2,2])=1
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([1.0, 2.0, 3.0, 4.0, 5.0])
        )->descriptiveStatistics;

        $this->assertEqualsWithDelta(1.0, $stats->medianAbsoluteDeviation, 0.001);
    }

    public function test_produced_unconditionally_for_small_population(): void
    {
        // n=2 -> not_exploitable, but descriptives stats should still be calculated
        $stats = (new BuildDescriptiveStatistics)(
            $this->contextWithPopulation([10.0, 20.0])
        )->descriptiveStatistics;

        $this->assertSame(2, $stats->count);
        $this->assertSame(10.0, $stats->minimum);
        $this->assertSame(20.0, $stats->maximum);
        $this->assertEqualsWithDelta(7.071, $stats->standardDeviation, 0.001);
    }

    public function test_requires_population(): void
    {
        $this->expectException(\RuntimeException::class);

        $dataset = new AnalysisDataset(
            measurements: [new Measurement('L1', 1.0, new Uncertainty(0.1))],
            assignedValueSpec: new AssignedValueSpecification(
                AssignedValueType::ROBUST_MEAN, null, null
            ),
            campaign: '2025', sampleCode: 'X', radionuclide: 'Cs', unit: 'Bq/kg'
        );

        (new BuildDescriptiveStatistics)(
            new AnalysisContext(dataset: $dataset, thresholdStandard: 'iso13528')
        );
    }
}
