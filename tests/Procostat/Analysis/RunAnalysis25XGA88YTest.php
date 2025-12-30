<?php

namespace Procorad\Procostat\Tests\Procostat\Analysis;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25XGA88Y;
use Procorad\Procostat\Tests\Procostat\Oracle\Oracle25XGA88Y;
use Procorad\Procostat\Tests\Support\TestAnalysisEngineFactory;

final class RunAnalysis25XGA88YTest extends TestCase
{
    public function test_25_xg_a_88_y_matches_oracle(): void
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

        foreach (Oracle25XGA88Y::NON_CONFORM_LABS as $labCode) {
            $evaluation = $result->labEvaluationFor($labCode);

            self::assertNotNull($evaluation, "Missing evaluation for lab {$labCode}");
            self::assertSame(
                FitnessStatus::NON_CONFORME,
                $evaluation->fitnessStatus,
                "Lab {$labCode} should be non conform"
            );
        }

        foreach (Oracle25XGA88Y::WARNING_LABS as $labCode) {
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
