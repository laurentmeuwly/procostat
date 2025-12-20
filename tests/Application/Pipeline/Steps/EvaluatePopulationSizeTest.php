<?php

namespace Procorad\Procostat\Tests\Application\Pipeline\Steps;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\Steps\EvaluatePopulationSize;
use Procorad\Procostat\Domain\Rules\PopulationStatus;

final class EvaluatePopulationSizeTest extends TestCase
{
    public function test_population_is_not_exploitable_when_less_than_3(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'participantCount' => 2,
        ]);

        $this->assertSame(
            PopulationStatus::NOT_EXPLOITABLE,
            $context['populationStatus']
        );
    }

    public function test_population_is_descriptive_only_between_3_and_6(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'participantCount' => 5,
        ]);

        $this->assertSame(
            PopulationStatus::DESCRIPTIVE_ONLY,
            $context['populationStatus']
        );
    }

    public function test_population_is_fully_exploitable_from_7(): void
    {
        $step = new EvaluatePopulationSize();

        $context = $step([
            'participantCount' => 10,
        ]);

        $this->assertSame(
            PopulationStatus::FULL_EVALUATION,
            $context['populationStatus']
        );
    }
}
