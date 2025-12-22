<?php

namespace Procorad\Procostat\Tests\Domain\Statistics\Normality;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Statistics\Normality\Kurtosis;

final class KurtosisTest extends TestCase
{
    public function test_kurtosis_of_normal_like_distribution(): void
    {
        $values = [1, 2, 3, 4, 5, 6];

        $kurt = Kurtosis::compute($values);

        $this->assertIsFloat($kurt);
    }

}
