<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\Trace\AnalysisTrace;

/**
 * Enveloppe retournée par RunAnalysis::analyzeWithTrace().
 *
 * Regroupe le résultat exploitable en production (ProcostatResult)
 * et la trace décisionnelle pour validation scientifique (AnalysisTrace).
 */
final readonly class AnalysisOutput
{
    public function __construct(
        public ProcostatResult $result,
        public AnalysisTrace $trace,
    ) {}
}
