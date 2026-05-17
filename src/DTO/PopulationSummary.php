<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Rules\PopulationStatus;
use Procorad\Procostat\Domain\Statistics\NormalityResult;

final class PopulationSummary
{
    public function __construct(
        public readonly int $participantCount,
        public readonly PopulationStatus $populationStatus,

        /** Normality diagnostic (if applicable) */
        public readonly ?NormalityResult $normality,

        /** @var array<string,string>|null code => reason */
        public readonly ?array $outliers,

        /** @var string[] */
        public readonly array $notes = [],
    ) {}

    // Decisionnals helpers

    public function isExploitable(): bool
    {
        return $this->populationStatus->isExploitable();
    }

    public function isFullyExploitable(): bool
    {
        return $this->populationStatus->isFullyExploitable();
    }

    public function normalityAccepted(): bool
    {
        return $this->normality?->isNormal ?? false;
    }
}
