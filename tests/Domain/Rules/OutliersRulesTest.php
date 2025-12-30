<?php

namespace Procorad\Procostat\Tests\Domain\Rules;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Rules\OutliersRules;

final class OutliersRulesTest extends TestCase
{
    public function test_dixon_rule_flags_suspicious(): void
    {
        $this->assertTrue(
            OutliersRules::isSuspiciousDixon(0.45, 10)
        );
    }

    public function test_grubbs_rule_flags_suspicious(): void
    {
        $this->assertTrue(
            OutliersRules::isSuspiciousGrubbs(2.3, 12)
        );
    }
}
