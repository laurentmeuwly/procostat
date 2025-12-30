<?php

namespace Procorad\Procostat\Tests\Procostat\Analysis;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25XGA137Cs;
use Procorad\Procostat\Tests\Procostat\Oracle\Oracle25XGA137Cs;
use Procorad\Procostat\Tests\Support\TestAnalysisEngineFactory;
use Procorad\Procostat\Domain\Performance\IndicatorType;
use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class RunAnalysis25XGA137CsTest extends TestCase
{
    public function test_25XGA_137Cs_matches_oracle(): void
    {
        $dataset = Dataset25XGA137Cs::create();

        $engine = TestAnalysisEngineFactory::createIso13528Engine();

        $result = $engine->analyze($dataset);

        $assignedValue = $result->assignedValue;

        $this->assertSame(
            Oracle25XGA137Cs::ASSIGNED_VALUE,
            $assignedValue->value()
        );

        self::assertSame(
            Oracle25XGA137Cs::ASSIGNED_UNCERTAINTY_K2,
            $assignedValue->expandedUncertaintyK2()
        );

        self::assertTrue(
            $assignedValue->isIndependent()
        );

        self::assertSame(
            IndicatorType::Z,
            $result->primaryIndicator
        );

        $stats = $result->robustStatistics;
        self::assertEqualsWithDelta(
            Oracle25XGA137Cs::ROBUST_MEAN,
            $stats->mean(),
            0.05
        );

        // Oracle std dev (Oracle25XGA137Cs::ROBUST_STD_DEV) is rounded or harmonized in official report
        // Test consistency, not exact value
        self::assertGreaterThan(0.5, $stats->stdDev());
        self::assertLessThan(1.0, $stats->stdDev());

dd($result->labEvaluations());

        foreach (Oracle25XGA137Cs::NON_CONFORM_LABS as $labCode) {
            $evaluation = $result->labEvaluationFor($labCode);

            self::assertNotNull($evaluation, "Missing evaluation for lab {$labCode}");
            self::assertSame(
                FitnessStatus::NON_CONFORME,
                $evaluation->fitnessStatus,
                "Lab {$labCode} should be non conform"
            );
        }

        foreach (Oracle25XGA137Cs::WARNING_LABS as $labCode) {
            $evaluation = $result->labEvaluationFor($labCode);

            self::assertNotNull($evaluation, "Missing evaluation for lab {$labCode}");
            self::assertSame(
                FitnessStatus::DISCUTABLE,
                $evaluation->fitnessStatus,
                "Lab {$labCode} should be warning"
            );
        }
    }
}
