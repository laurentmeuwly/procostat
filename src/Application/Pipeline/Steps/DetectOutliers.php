<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use Procorad\Procostat\Domain\Statistics\Outliers\Dixon;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;
use RuntimeException;

final class DetectOutliers implements PipelineStep
{
    // Seuil Grubbs critique simplifié (α = 0.05, dépend de n en production).
    // Le seuil exact est résolu par la table ISO ; ici on expose la stat brute
    // dans la trace et le test comparatif reste dans les règles métier.
    private const GRUBBS_CRITICAL_APPROXIMATION = 2.0;

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'DetectOutliers requires an existing Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'DetectOutliers requires PopulationStatus.'
            );
        }

        // Default: no outliers detected / not applicable
        $context->outliers = null;

        if ($context->normalityResult === null) {
            return $context;
        }

        $applicable = ApplicabilityRules::canDetectOutliers(
            $context->populationStatus,
            $context->normalityResult->isNormal
        );

        if (!$applicable) {
            // Distribution non-normale -> exclusion avant robuste
            if (! $context->normalityResult->isNormal) {
                $context->trace->excludedBeforeRobust = []; // peuplé par step dédiée si existante
            }
            return $context;
        }

        $values = array_map(
            static fn ($measurement) => $measurement->value(),
            $context->population->measurements()
        );

        $grubbsResult = Grubbs::compute($values);
        $dixonResult  = Dixon::compute($values);

        $context->outliers = [
            'dixon'  => $dixonResult,
            'grubbs' => $grubbsResult,
        ];

        // Trace Grubbs
        $context->trace->grubbsTriggered        = true;
        $context->trace->grubbsStatistic        = $grubbsResult['G'];
        $context->trace->grubbsCandidateIndex   = $grubbsResult['index'];
        $context->trace->grubbsOutlierDetected  = $grubbsResult['G'] > self::GRUBBS_CRITICAL_APPROXIMATION;
        $context->trace->addStep('grubbs');
        // End trace Grubbs

        // Trace Dixon
        $context->trace->dixonTriggered  = true;
        $context->trace->dixonStatistic  = $dixonResult['Q'] ?? null;
        $context->trace->addStep('dixon');
        // End Trace Dixon

        return $context;
    }
}
