<?php

namespace Procorad\Procostat\Tests\Procostat\Dataset;

use Procorad\Procostat\DTO\AnalysisDataset;
use Procorad\Procostat\Domain\Measurements\Measurement;
use Procorad\Procostat\Domain\Measurements\Uncertainty;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\AssignedValue\AssignedValue;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueType;

final class Dataset25XGA88Y
{
    public static function create(): AnalysisDataset
    {
        $json = json_decode(
            file_get_contents(__DIR__ . '/fixtures/25XGA_88Y.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $measurements = array_map(
            static fn (array $row) => new Measurement(
                laboratoryCode: $row['lab_code'],
                value: $row['value'],
                uncertainty: Uncertainty::fromExpanded(
                    U: (float) $row['uncertainty_k2'],
                    k: 2.0
                ),
                limitOfDetection: $row['lod'],
            ),
            $json['measurements']
        );

        $assignedValueSpec = new AssignedValueSpecification(
            type: AssignedValueType::CERTIFIED,
            value: $json['assigned_value']['value'],
            expandedUncertaintyK2: $json['assigned_value']['expanded_uncertainty_k2']
        );

        return new AnalysisDataset(
            measurements: $measurements,
            assignedValueSpec: $assignedValueSpec,
            campaign: (string) $json['campaign'],
            sampleCode: $json['sample_code'],
            radionuclide: $json['radionuclide'],
            unit: $json['unit'],
            isStable: true
        );
    }
}
