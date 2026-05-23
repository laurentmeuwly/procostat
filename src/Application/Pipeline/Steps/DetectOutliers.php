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
    /**
     * Seuil Procorad : Grubbs uniquement si n <= 12.
     * Au-delà, la population est suffisamment grande pour que
     * les stats robustes absorbent les valeurs extrêmes sans exclusion.
     */
    private const GRUBBS_MAX_N = 12;

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException('DetectOutliers requires an existing Population.');
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException('DetectOutliers requires PopulationStatus.');
        }

        $context->outliers = null;

        if ($context->normalityResult === null) {
            return $context;
        }

        $applicable = ApplicabilityRules::canDetectOutliers(
            $context->populationStatus,
            true,
        );

        if (! $applicable) {
            if (! $context->normalityResult->isNormal) {
                $context->trace->excludedBeforeRobust = [];
            }
            return $context;
        }

        $values = array_map(
            static fn ($measurement) => $measurement->value(),
            $context->population->measurements()
        );

        $n = count($values);

        // ── Grubbs : Procorad n <= 12 uniquement ───────────────────────────
        $grubbsResult = ($n <= self::GRUBBS_MAX_N)
            ? Grubbs::compute($values)
            : null;

        // ── Dixon : 3 ≤ n ≤ 25 (limite de sa table de critiques) ──────────
        $dixonResult = ($n >= 3 && $n <= 25)
            ? Dixon::compute($values)
            : null;

        $context->outliers = [
            'grubbs' => $grubbsResult,
            'dixon'  => $dixonResult,
        ];

        // ── Traitement Grubbs ──────────────────────────────────────────────
        if ($grubbsResult !== null) {
            $grubbsOutlierDetected = OutliersRules::isSuspiciousGrubbs(
                $grubbsResult['G'],
                $n,
            );

            $context->trace->grubbsTriggered       = true;
            $context->trace->grubbsStatistic       = $grubbsResult['G'];
            $context->trace->grubbsCandidateIndex  = $grubbsResult['index'];
            $context->trace->grubbsOutlierDetected = $grubbsOutlierDetected;
            $context->trace->addStep('grubbs');

            // Aberrant confirmé et index valide → exclure de la population
            if ($grubbsOutlierDetected && $grubbsResult['index'] !== -1) {
                $measurements = $context->population->measurements();
                $index        = $grubbsResult['index'];

                if (! isset($measurements[$index])) {
                    throw new RuntimeException(
                        "Grubbs: index {$index} hors limites (population size: {$n})."
                    );
                }

                $excludedCode = $measurements[$index]->laboratoryCode();

                $context->originalPopulation = $context->population;
                $context->population         = $context->population->withoutIndex($index);

                $context->trace->grubbsExcludedLab = $excludedCode;
                $context->trace->addStep('grubbs_exclusion');
            }
        }

        // ── Traitement Dixon ───────────────────────────────────────────────
        if ($dixonResult !== null) {
            $context->trace->dixonTriggered = true;
            $context->trace->dixonStatistic = $dixonResult['Q'] ?? null;
            $context->trace->addStep('dixon');
        }

        return $context;
    }
}
