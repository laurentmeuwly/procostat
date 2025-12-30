<?php

namespace Procorad\Procostat\Tests\Application\Pipeline;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineRunner;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Tests\Support\TestPipelineStep;

final class PipelineRunnerTest extends TestCase
{
    private function makeContext(): AnalysisContext
    {
        $dataset = new AnalysisDataset(
            measurements: [
                new Measurement(
                    laboratoryCode: 'TEST',
                    value: 1.0,
                    uncertainty: Uncertainty::fromStandard(0.1)
                ),
            ],
            assignedValueSpec: new AssignedValueSpecification(
                type: AssignedValueType::ROBUST_MEAN,
                value: null,
                expandedUncertaintyK2: null
            ),
            campaign: 'TEST',
            sampleCode: 'SAMPLE',
            radionuclide: 'Cs-137',
            unit: 'Bq'
        );

        return new AnalysisContext(
            dataset: $dataset,
            thresholdStandard: 'iso13528'
        );
    }

    public function test_pipeline_executes_steps_in_order(): void
    {
        $executionOrder = [];

        $steps = [
            new TestPipelineStep(
                'step1',
                $executionOrder,
                fn (AnalysisContext $ctx) => $ctx->populationStatus = PopulationStatus::FULL_EVALUATION
            ),
            new TestPipelineStep(
                'step2',
                $executionOrder,
                fn (AnalysisContext $ctx) => $ctx->primaryIndicator = IndicatorType::Z
            ),
        ];

        $runner = new PipelineRunner($steps);

        $context = $this->makeContext();
        $result = $runner->run($context);

        // Execution order
        $this->assertSame(['step1', 'step2'], $executionOrder);

        // Effects on the context
        $this->assertSame(
            PopulationStatus::FULL_EVALUATION,
            $result->populationStatus
        );

        $this->assertSame(
            IndicatorType::Z,
            $result->primaryIndicator
        );
    }

    public function test_pipeline_throws_if_step_is_not_callable(): void
    {
        $this->expectException(\RuntimeException::class);

        $runner = new PipelineRunner([new \stdClass]);
        $context = $this->makeContext();
        $runner->run($context);
    }
}
