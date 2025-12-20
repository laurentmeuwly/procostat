<?php

namespace Procorad\Procostat\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\Domain\Decision\EvaluationValidity;
use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class LabEvaluationTest extends TestCase
{
    public function test_lab_evaluation_can_be_instantiated_with_partial_indicators(): void
    {
        $evaluation = new LabEvaluation(
            laboratoryCode: 'LAB-042',
            zScore: null,
            zPrimeScore: 1.45,
            zetaScore: 1.10,
            biasPercent: 8.5,
            fitnessStatus: FitnessStatus::CONFORME,
            decisionBasis: 'z_prime',
            evaluationValidity: EvaluationValidity::OFFICIAL
        );

        $this->assertSame('LAB-042', $evaluation->laboratoryCode);
        $this->assertNull($evaluation->zScore);
        $this->assertEquals(1.45, $evaluation->zPrimeScore);
        $this->assertSame(FitnessStatus::CONFORME, $evaluation->fitnessStatus);
        $this->assertSame('z_prime', $evaluation->decisionBasis);
        $this->assertSame(EvaluationValidity::OFFICIAL, $evaluation->evaluationValidity);
    }
}
