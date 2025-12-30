<?php

namespace Procorad\Procostat\Application\Resolvers;

use Procorad\Procostat\Domain\Decision\EvaluationValidity;
use Procorad\Procostat\Domain\Rules\PopulationStatus;

final class EvaluationValidityResolver
{
    public static function resolve(PopulationStatus $status): EvaluationValidity
    {
        return match ($status) {
            PopulationStatus::FULL_EVALUATION => EvaluationValidity::OFFICIAL,
            PopulationStatus::DESCRIPTIVE_ONLY => EvaluationValidity::INFORMATIVE,
            PopulationStatus::NOT_EXPLOITABLE => EvaluationValidity::NOT_VALID,
        };
    }
}
