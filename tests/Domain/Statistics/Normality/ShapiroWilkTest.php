<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Normality;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Normality\ShapiroWilk;

final class ShapiroWilkTest extends TestCase
{
    public function test_shapiro_wilk_returns_valid_pvalue(): void
{
    $values = [10, 11, 12, 13, 14];

    $result = ShapiroWilk::test($values);

    $this->assertArrayHasKey('W', $result);
    $this->assertArrayHasKey('pValue', $result);
    $this->assertGreaterThanOrEqual(0.0, $result['pValue']);
    $this->assertLessThanOrEqual(1.0, $result['pValue']);
}


}
