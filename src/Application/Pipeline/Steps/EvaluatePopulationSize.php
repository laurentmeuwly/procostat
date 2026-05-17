<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\PopulationRules;
use Procorad\Procostat\Domain\Rules\PopulationStatus;
use RuntimeException;

final class EvaluatePopulationSize implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'EvaluatePopulationSize requires an existing Population.'
            );
        }

        $n = $context->population->count();

        $context->populationStatus = PopulationRules::evaluate($n);

        // Trace
        $context->trace->participantCount  = $n;
        $context->trace->populationStatus  = $context->populationStatus->value;
        $context->trace->addStep('population_check');

        if ($context->populationStatus === PopulationStatus::NOT_EXPLOITABLE) {
            $context->trace->addStep('not_exploitable');
        } elseif ($context->populationStatus === PopulationStatus::DESCRIPTIVE_ONLY) {
            $context->trace->addStep('descriptive_only');
        }
        // End trace

        return $context;
    }
}
