<?php

namespace Procorad\Procostat\Infrastructure\Normality;

use Procorad\Procostat\Application\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\Statistics\NormalityResult;
use RuntimeException;

final class PhpNormalityAdapter implements NormalityAdapter
{
    public function analyze(array $values): NormalityResult
    {
        if (count($values) < 3) {
            throw new RuntimeException(
                'Normality analysis requires at least 3 values.'
            );
        }

        // TODO:
        // - Implement Shapiro-Wilk test (PHP)
        // - Compute skewness
        // - Compute kurtosis
        // - Determine normality according to ISO 13528

        throw new RuntimeException(
            'PhpNormalityAdapter not implemented yet.'
        );
    }
}
