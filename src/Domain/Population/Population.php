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
}
