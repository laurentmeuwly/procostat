<?php

namespace Procorad\Procostat\Domain\Population;

use Procorad\Procostat\Domain\Measurements\Measurement;

final class Population
{
    /** @var Measurement[] */
    private array $measurements;

    /**
     * @param  Measurement[]  $measurements
     */
    public function __construct(array $measurements)
    {
        if ($measurements === []) {
            throw new \RuntimeException('Population cannot be empty.');
        }

        $this->measurements = $measurements;
    }

    /** @return Measurement[] */
    public function measurements(): array
    {
        return $this->measurements;
    }

    public function count(): int
    {
        return count($this->measurements);
    }

    /**
     * @return string[] laboratory codes
     */
    public function laboratoryCodes(): array
    {
        return array_map(
            fn (Measurement $m) => $m->laboratoryCode(),
            $this->measurements
        );
    }

    /**
     * Retourne une nouvelle Population sans la mesure à l'index donné.
     * Utilisé par DetectOutliers pour exclure un aberrant Grubbs.
     *
     * @throws \RuntimeException si la population résultante devait être vide
     */
    public function withoutIndex(int $index): self
    {
        $filtered = array_values(
            array_filter(
                $this->measurements,
                fn ($_, $i) => $i !== $index,
                ARRAY_FILTER_USE_BOTH,
            )
        );

        if ($filtered === []) {
            throw new \RuntimeException(
                'Cannot exclude the only measurement from Population.'
            );
        }

        return new self($filtered);
    }

    /**
     * Retourne une nouvelle Population sans la mesure identifiée par son code labo.
     */
    public function withoutLaboratory(string $laboratoryCode): self
    {
        $filtered = array_values(
            array_filter(
                $this->measurements,
                fn (Measurement $m) => $m->laboratoryCode() !== $laboratoryCode,
            )
        );

        if ($filtered === []) {
            throw new \RuntimeException(
                "Cannot exclude laboratory '{$laboratoryCode}': would leave Population empty."
            );
        }

        return new self($filtered);
    }

}
