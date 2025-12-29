<?php

namespace Procorad\Procostat\DTO;

use Procorad\Procostat\Domain\AssignedValue\AssignedValueSpecification;
use Procorad\Procostat\Domain\Measurements\Measurement;
use RuntimeException;

final class AnalysisDataset
{
    /** @var Measurement[] */
    private array $measurements;

    /**
     * @param Measurement[] $measurements
     */
    public function __construct(
        array $measurements,
        public readonly AssignedValueSpecification $assignedValueSpec,
    ) {
        if ($measurements === []) {
            throw new RuntimeException(
                'AnalysisDataset cannot be empty.'
            );
        }

        foreach ($measurements as $measurement) {
            if (!$measurement instanceof Measurement) {
                throw new RuntimeException(
                    'AnalysisDataset expects only Measurement instances.'
                );
            }
        }

        $this->measurements = array_values($measurements);
    }

    /**
     * Number of participants (laboratories)
     */
    public function count(): int
    {
        return count($this->measurements);
    }

    /**
     * @return Measurement[]
     */
    public function measurements(): array
    {
        return $this->measurements;
    }

    /**
     * Extract numeric values only (for statistics)
     *
     * @return float[]
     */
    public function values(): array
    {
        return array_map(
            static fn (Measurement $m) => $m->value,
            $this->measurements
        );
    }

    /**
     * Map measurements by laboratory code
     *
     * @return array<string, Measurement>
     */
    public function byLaboratory(): array
    {
        $map = [];

        foreach ($this->measurements as $measurement) {
            $code = $measurement->laboratoryCode;

            if (isset($map[$code])) {
                throw new RuntimeException(
                    "Duplicate laboratory code [$code] in AnalysisDataset."
                );
            }

            $map[$code] = $measurement;
        }

        return $map;
    }
}
