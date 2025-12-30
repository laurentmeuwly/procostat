<?php

namespace Procorad\Procostat\Tests\Procostat\Analysis;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25XGA88Y;
use Procorad\Procostat\Tests\Procostat\Oracle\Oracle25XGA88Y;
use Procorad\Procostat\Tests\Support\TestAnalysisEngineFactory;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class RunAnalysis25XGA88YTest extends TestCase
{
    public function test_25XGA_88Y_matches_oracle(): void
    {
        $dataset = Dataset25XGA88Y::create();

        $engine = TestAnalysisEngineFactory::createIso13528Engine();

        $result = $engine->analyze($dataset);

        $assignedValue = $result->assignedValue;

        $this->assertSame(
            Oracle25XGA88Y::ASSIGNED_VALUE,
            $assignedValue->value()
        );

        $this->assertSame(
            Oracle25XGA88Y::ASSIGNED_UNCERTAINTY_K2,
            $assignedValue->expandedUncertaintyK2()
        );

        self::assertTrue(
            $assignedValue->isIndependent()
        );

        /*$this->assertSame(
            Oracle25XGA88Y::PERFORMANCE_INDICATOR,
            $result->performanceIndicator()
        );

        // --- σ aptitude
        $this->assertEqualsWithDelta(
            Oracle25XGA88Y::ROBUST_STD_DEV,
            $result->robustStdDev(),
            1e-6
        );*/

        //dd($result->labEvaluations());

        foreach (Oracle25XGA88Y::NON_CONFORM_LABS as $labCode) {
            $evaluation = $result->labEvaluationFor($labCode);

            self::assertNotNull($evaluation, "Missing evaluation for lab {$labCode}");
            self::assertSame(
                FitnessStatus::NON_CONFORME,
                $evaluation->fitnessStatus,
                "Lab {$labCode} should be non conform"
            );

            /*$this->assertSame(
                'non_conform',
                $evaluation->decision()
            );*/
        }

        foreach (Oracle25XGA88Y::WARNING_LABS as $labCode) {

            self::assertNotNull($evaluation, "Missing evaluation for lab {$labCode}");
            self::assertSame(
                FitnessStatus::DISCUTABLE,
                $evaluation->fitnessStatus,
                "Lab {$labCode} should be warning"
            );

            /*$this->assertSame(
                'warning',
                $result->labEvaluation($lab)->decision()
            );*/
        }
    }
}
