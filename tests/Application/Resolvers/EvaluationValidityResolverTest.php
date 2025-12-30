<?php

namespace Procorad\Procostat\Tests\Application\Resolvers;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Resolvers\EvaluationValidityResolver;
use Procorad\Procostat\Domain\Decision\EvaluationValidity;
use Procorad\Procostat\Domain\Rules\PopulationStatus;

final class EvaluationValidityResolverTest extends TestCase
{
    public function test_population_status_is_mapped_to_evaluation_validity(): void
    {
        $this->assertSame(
            EvaluationValidity::OFFICIAL,
            EvaluationValidityResolver::resolve(PopulationStatus::FULL_EVALUATION)
        );

        $this->assertSame(
            EvaluationValidity::INFORMATIVE,
            EvaluationValidityResolver::resolve(PopulationStatus::DESCRIPTIVE_ONLY)
        );

        $this->assertSame(
            EvaluationValidity::NOT_VALID,
            EvaluationValidityResolver::resolve(PopulationStatus::NOT_EXPLOITABLE)
        );
    }
}
