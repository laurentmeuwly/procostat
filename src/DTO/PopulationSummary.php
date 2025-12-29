<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;

final class PopulationSummary
{
    public function __construct(
        public readonly int $participantCount,
        public readonly PopulationStatus $populationStatus,

        public readonly ?NormalityResult $normality,
        /** @var array<string,string>|null */
        public readonly ?array $outliers,

        /** @var string[] */
        public readonly array $notes = [],
    ) {
    }
}
