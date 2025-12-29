<?php

namespace Procorad\Procostat\Application;

use Procorad\Procostat\Domain\Audit\AuditBuilder;
use Procorad\Procostat\Domain\Population\PopulationContext;
use Procorad\Procostat\DTO\AnalysisDataset;

final class AnalysisContext
{
    public function __construct(
        public readonly AnalysisDataset $dataset,

        // Population
        public ?PopulationContext $population = null,

        // Intermediate population data
        public array $usableValues = [],
        public array $excludedLaboratoryCodes = [],

        // Results
        public array $labEvaluations = [],

        // Audit
        public AuditBuilder $audit,
    ) {
    }
}
