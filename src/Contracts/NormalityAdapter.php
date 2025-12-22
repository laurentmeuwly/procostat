<?php

namespace Procorad\Procostat\Application\Contracts;

use Procorad\Procostat\Domain\Statistics\NormalityResult;

interface NormalityAdapter
{
    /**
     * @param float[] $values
     */
    public function analyze(array $values): NormalityResult;
}
