<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use Procorad\Procostat\Domain\Rules\OutliersRules;
use Procorad\Procostat\Domain\Statistics\Outliers\Dixon;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;
use RuntimeException;

final class DetectOutliers implements PipelineStep
{
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
            true, //$context->normalityResult->isNormal
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

        $n = count($values);

        // Dixon : applicable uniquement pour 3 ≤ n ≤ 25 (limite de sa table de critiques)
        // Grubbs : pas de limite supérieure
        $grubbsResult = Grubbs::compute($values);
        $dixonResult  = ($n >= 3 && $n <= 25) ? Dixon::compute($values) : null;

        $context->outliers = [
            'dixon'  => $dixonResult,
            'grubbs' => $grubbsResult,
        ];

        // Seuil Grubbs ISO 13528 tabulé par n (α = 5%)
        $grubbsOutlierDetected = OutliersRules::isSuspiciousGrubbs(
            $grubbsResult['G'],
            count($values),
        );

        // Trace Grubbs
        $context->trace->grubbsTriggered        = true;
        $context->trace->grubbsStatistic        = $grubbsResult['G'];
        $context->trace->grubbsCandidateIndex   = $grubbsResult['index'];
        $context->trace->grubbsOutlierDetected  = $grubbsOutlierDetected;
        $context->trace->addStep('grubbs');
        // End trace Grubbs

        // Si Grubbs détecte un aberrant → exclure la mesure du contexte
        // afin que ComputeRobustStatistics et EvaluateLaboratories travaillent
        // sur la population nettoyée.
        if ($grubbsOutlierDetected && $grubbsResult['index'] !== -1) {
            $measurements = $context->population->measurements();
            $excludedCode = $measurements[$grubbsResult['index']]->laboratoryCode();

            // Snapshot avant exclusion
            $context->originalPopulation = $context->population;

            $context->population = $context->population->withoutIndex(
                $grubbsResult['index'],
            );

            $context->trace->grubbsExcludedLab = $excludedCode;
            $context->trace->addStep('grubbs_exclusion');
        }

        // Trace Dixon
        if ($dixonResult !== null) {
            $context->trace->dixonTriggered  = true;
            $context->trace->dixonStatistic  = $dixonResult['Q'] ?? null;
            $context->trace->addStep('dixon');
        }
        // End Trace Dixon

        return $context;
    }
}
