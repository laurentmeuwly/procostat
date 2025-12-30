<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;

final class PopulationSummary
{
    public function __construct(
        public readonly int $participantCount,
        public readonly PopulationStatus $populationStatus,

        /** Assigned value finally retained for the analysis */
        public readonly ?float $assignedValue,

        /** Expanded uncertainty (k=2) of the assigned value, if available */
        public readonly ?float $assignedUncertainty,

        /** Robust population standard deviation (s*) */
        public readonly ?float $populationStdDev,

        /** Normality diagnostic (if applicable) */
        public readonly ?NormalityResult $normality,

        /** @var array<string,string>|null code => reason */
        public readonly ?array $outliers,

        /** @var string[] */
        public readonly array $notes = [],
    ) {}
}
