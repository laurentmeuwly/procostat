<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Normality;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Normality\HenryLine;

final class HenryLineTest extends TestCase
{
    public function test_henri_line_returns_same_number_of_points(): void
    {
        $values = [10, 12, 14, 16, 18];

        $points = HenryLine::compute($values);

        $this->assertCount(count($values), $points);
    }
}
