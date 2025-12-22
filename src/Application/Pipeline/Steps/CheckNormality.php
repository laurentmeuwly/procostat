<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Application\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use RuntimeException;

final class CheckNormality
{
    public function __construct(
        private readonly NormalityAdapter $normalityAdapter
    ) {}

    public function __invoke(array $context): array
    {
        if (!isset($context['dataset'], $context['populationStatus'])) {
            throw new RuntimeException(
                'CheckNormality requires dataset and populationStatus.'
            );
        }

        if (!ApplicabilityRules::canCheckNormality($context['populationStatus'])) {
            $context['normality'] = null;
            return $context;
        }

        $values = $context['dataset']->values(); // helper à prévoir

        $context['normality'] = $this->normalityAdapter->analyze($values);

        return $context;
    }
}
