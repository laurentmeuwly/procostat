<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use Procorad\Procostat\Domain\Statistics\RobustStatisticsCalculator;
use RuntimeException;

/**
 * Calculates robust statistics (x*, s*, MAD).
 *
 * Depends on the population status:
 *   - not_exploitable  → robustStatistics remains null  (no calculation)
 *   - descriptive_only → robustStatistics remains null  (ISO 13528: n too small)
 *   - full_evaluation  → robustStatistics calculated
 *
 * The nullable property of robustStatistics in AnalysisContext and ProcostatResult
 * is therefore a business constraint, not an oversight.
 */
final class ComputeRobustStatistics implements PipelineStep
{
    /**
     * Seuil de troncature selon le logigramme PROCOSTAT (z > 5 → troncature).
     */
    private const TRUNCATION_THRESHOLD = 5.0;
    //private $threshold = config('procostat.workflow.truncation_z_threshold');

    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'ComputeRobustStatistics requires an existing Population.'
            );
        }

        if ($context->populationStatus === null) {
            throw new RuntimeException(
                'ComputeRobustStatistics requires PopulationStatus.'
            );
        }

        // not_exploitable or descriptive_only branch -> no robust stats
        //if (! ApplicabilityRules::canComputeRobustStatistics($context->populationStatus)) {
        if (! $context->populationStatus->isFullyExploitable()) {
            $context->robustStatistics = null;

            // Trace
            $context->trace->robustStatisticsComputed = false;

            return $context;
        }

        $context->robustStatistics =
            RobustStatisticsCalculator::compute($context->population);

        // Trace : z-score maximum / troncation
        $context->trace->robustStatisticsComputed = true;
        $context->trace->addStep('robust_mean');

        if ($context->robustStatistics !== null) {
            $mean   = $context->robustStatistics->mean();
            $stdDev = $context->robustStatistics->stdDev();

            if ($stdDev > 0) {
                $values = array_map(
                    static fn ($m) => $m->value(),
                    $context->population->measurements()
                );

                $zScores = array_map(
                    static fn (float $v): float => abs($v - $mean) / $stdDev,
                    $values
                );

                $maxZ = max($zScores);
                $context->trace->maxZScoreBeforeTruncation = $maxZ;

                if ($maxZ > self::TRUNCATION_THRESHOLD) {
                    $context->trace->truncationTriggered = true;
                    $context->trace->addStep('truncation');

                    // Identifier les laboratoires dont z > 5 et les exclure
                    $measurements    = $context->population->measurements();
                    $truncatedCodes  = [];

                    foreach ($measurements as $i => $measurement) {
                        if ($zScores[$i] > self::TRUNCATION_THRESHOLD) {
                            $truncatedCodes[] = (string) $measurement->laboratoryCode();
                            $context->trace->truncatedLabs[] = (string) $measurement->laboratoryCode();
                        }
                    }

                    // Recalculer x* et s* sur la population tronquée
                    // C'est la population qui alimente EvaluateLaboratories ensuite
                    $truncatedPopulation = $context->population;
                    foreach ($truncatedCodes as $code) {
                        $truncatedPopulation = $truncatedPopulation->withoutLaboratory($code);
                    }

                    $context->population       = $truncatedPopulation;
                    $context->robustStatistics = RobustStatisticsCalculator::compute($truncatedPopulation);

                    $context->trace->addStep('truncation_recompute');
                }
            }
        }
        // End trace

        return $context;
    }
}
