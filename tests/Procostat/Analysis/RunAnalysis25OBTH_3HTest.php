<?php

namespace Procorad\Procostat\Tests\Procostat\Analysis;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25OBTH3H;
//use Procorad\Procostat\Tests\Procostat\Oracle\Oracle25XGA88Y;
use Procorad\Procostat\Tests\Support\TestAnalysisEngineFactory;

final class RunAnalysis25OBTH_3HTest extends TestCase
{
    public function test_it_computes_z_prime_scores_correctly_for_certified_value(): void
    {
        $dataset = Dataset25OBTH3H::create();

        $engine = TestAnalysisEngineFactory::createIso13528Engine();

        $result = $engine->analyze($dataset);

        //$assignedValue = $result->assignedValue;

        dump($result);


    }
}
