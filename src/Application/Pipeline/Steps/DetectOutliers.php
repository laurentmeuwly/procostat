<?php

namespace Procorad\Procostat\Application\Pipeline\Steps;

use Procorad\Procostat\Domain\Rules\ApplicabilityRules;
use Procorad\Procostat\Domain\Statistics\Outliers\Dixon;
use Procorad\Procostat\Domain\Statistics\Outliers\Grubbs;
use RuntimeException;

final class DetectOutliers
{
    public function __invoke(array $context): array
    {
        if (!isset(
            $context['dataset'],
            $context['populationStatus'],
            $context['normality']
        )) {
            throw new RuntimeException(
                'DetectOutliers requires dataset, populationStatus and normality.'
            );
        }

        if (!ApplicabilityRules::canDetectOutliers(
            $context['populationStatus'],
            $context['normality']->isNormal
        )) {
            $context['outliers'] = null;
            return $context;
        }

        $values = $context['dataset']->values();

        $context['outliers'] = [
            'dixon'  => Dixon::compute($values),
            'grubbs' => Grubbs::compute($values),
        ];

        return $context;
    }
}
