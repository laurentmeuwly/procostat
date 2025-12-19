<?php

namespace Procorad\Procostat\Tests\Domain\Decision;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\Domain\Decision\FitnessStatus;

final class FitnessDecisionTest extends TestCase
{
    public function test_conforme_when_score_is_less_than_2(): void
    {
        $this->assertSame(
            FitnessStatus::CONFORME,
            FitnessDecision::decideFromScore(1.99)
        );
    }

    public function test_discutable_when_score_is_between_2_and_3(): void
    {
        $this->assertSame(
            FitnessStatus::DISCUTABLE,
            FitnessDecision::decideFromScore(2.5)
        );
    }

    public function test_non_conforme_when_score_is_greater_than_3(): void
    {
        $this->assertSame(
            FitnessStatus::NON_CONFORME,
            FitnessDecision::decideFromScore(3.01)
        );
    }

    public function test_decision_is_symmetric(): void
    {
        $this->assertSame(
            FitnessStatus::NON_CONFORME,
            FitnessDecision::decideFromScore(-3.5)
        );
    }

    public function test_invalid_score_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FitnessDecision::decideFromScore(INF);
    }
}
