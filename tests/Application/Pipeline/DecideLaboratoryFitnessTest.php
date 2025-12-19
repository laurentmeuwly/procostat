<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\DecideLaboratoryFitness;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class DecideLaboratoryFitnessTest extends TestCase
{
    public function test_decision_is_made_using_z_prime_when_available(): void
    {
        $step = new DecideLaboratoryFitness(new ThresholdsResolver());

        $evaluation = $step([
            'laboratoryCode' => 'LAB-007',
            'zPrimeScore' => 1.8,
            'zScore' => 2.5,
            'zetaScore' => 1.2,
            'biasPercent' => 5.0,
            'thresholdStandard' => 'iso13528',
        ]);

        $this->assertSame(FitnessStatus::CONFORME, $evaluation->fitnessStatus);
        $this->assertSame('z_prime', $evaluation->decisionBasis);
    }

    public function test_decision_falls_back_to_z_score(): void
    {
        $step = new DecideLaboratoryFitness(new ThresholdsResolver());

        $evaluation = $step([
            'laboratoryCode' => 'LAB-008',
            'zScore' => 2.6,
            'thresholdStandard' => 'iso13528',
        ]);

        $this->assertSame(FitnessStatus::DISCUTABLE, $evaluation->fitnessStatus);
        $this->assertSame('z', $evaluation->decisionBasis);
    }
}
