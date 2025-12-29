<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\Steps\ResolveAssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Population\Population;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Statistics\RobustStatistics;
use RuntimeException;

final class ResolveAssignedValueTest extends TestCase
{
    private function context(): AnalysisContext
    {
        $dataset = new \Procorad\Procostat\DTO\AnalysisDataset(
            measurements: [
                new Measurement('LAB01', 10.0, new Uncertainty(0.5)),
                new Measurement('LAB02', 11.0, new Uncertainty(0.5)),
                new Measurement('LAB03', 12.0, new Uncertainty(0.5)),
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
        $context->robustStatistics = new RobustStatistics(11.0, 1.0);

        return $context;
    }

    public function test_assigned_value_is_resolved(): void
    {
        $resolver = new AssignedValueResolver();
        $step = new ResolveAssignedValue($resolver);

        $context = $this->context();

        $result = $step($context);

        $this->assertInstanceOf(
            AssignedValue::class,
            $result->assignedValue
        );
    }

    public function test_missing_population_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $resolver = new AssignedValueResolver();
        $step = new ResolveAssignedValue($resolver);

        $context = $this->context();
        $context->population = null;

        $step($context);
    }

    public function test_missing_statistics_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $resolver = new AssignedValueResolver();
        $step = new ResolveAssignedValue($resolver);

        $context = $this->context();
        $context->robustStatistics = null;

        $step($context);
    }
}
