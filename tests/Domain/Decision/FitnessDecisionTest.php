<?php

namespace Procorad\Procostat\Tests\Domain\Decision;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Decision\FitnessDecision;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Rules\Thresholds;

final class FitnessDecisionTest extends TestCase
{
    public function test_conforme_with_iso_thresholds(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->assertSame(
            FitnessStatus::CONFORME,
            FitnessDecision::decideFromScore(1.99, $thresholds)
        );
    }

    public function test_discutable_with_iso_thresholds(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->assertSame(
            FitnessStatus::DISCUTABLE,
            FitnessDecision::decideFromScore(2.5, $thresholds)
        );
    }

    public function test_non_conforme_with_iso_thresholds(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->assertSame(
            FitnessStatus::NON_CONFORME,
            FitnessDecision::decideFromScore(3.01, $thresholds)
        );
    }

    public function test_decision_is_symmetric(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->assertSame(
            FitnessStatus::NON_CONFORME,
            FitnessDecision::decideFromScore(-3.5, $thresholds)
        );
    }

    public function test_invalid_score_throws_exception(): void
    {
        $thresholds = Thresholds::iso13528();

        $this->expectException(\InvalidArgumentException::class);

        FitnessDecision::decideFromScore(INF, $thresholds);
    }
}
