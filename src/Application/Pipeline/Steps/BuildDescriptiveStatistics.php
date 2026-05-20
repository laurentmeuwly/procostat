<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\AnalysisContext;
use Procorad\Procostat\Application\Pipeline\PipelineStep;
use Procorad\Procostat\Domain\Statistics\DescriptiveStatisticsCalculator;
use RuntimeException;

/**
 * Calculates descriptive statistics for the population.
 *
 * This step ALWAYS runs, regardless of the workflow branch
 * (not_exploitable, descriptive_only, full_evaluation).
 * Min, max, median, arithmetic mean, standard deviation, and MAD
 * are always produced. Tthey appear in all branches of the flowchart.
 *
 * Position in the pipeline: immediately after BuildPopulation,
 * before EvaluatePopulationSize.
 */
final class BuildDescriptiveStatistics implements PipelineStep
{
    public function __invoke(AnalysisContext $context): AnalysisContext
    {
        if ($context->population === null) {
            throw new RuntimeException(
                'BuildDescriptiveStatistics requires an existing Population.'
            );
        }

        // La population originale (avant Grubbs) est stockée dans le contexte
        // pour que trimmedCount reflète l'exclusion effectuée par DetectOutliers.
        $context->descriptiveStatistics = DescriptiveStatisticsCalculator::compute(
            population:        $context->population,
            trimmedPopulation: $context->originalPopulation,
        );

        return $context;
    }
}
