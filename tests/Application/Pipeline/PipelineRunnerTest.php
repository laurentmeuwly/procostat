<?php

namespace Procorad\Procostat\Tests\Application\Pipeline;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\Pipeline\PipelineRunner;

final class PipelineRunnerTest extends TestCase
{
    public function test_pipeline_executes_steps_in_order(): void
    {
        $steps = [
            fn(array $ctx) => array_merge($ctx, ['a' => 1]),
            fn(array $ctx) => array_merge($ctx, ['b' => 2]),
        ];

        $runner = new PipelineRunner($steps);

        $result = $runner->run([]);

        $this->assertSame(1, $result['a']);
        $this->assertSame(2, $result['b']);
    }

    public function test_pipeline_throws_if_step_is_not_callable(): void
    {
        $this->expectException(\RuntimeException::class);

        $runner = new PipelineRunner([new \stdClass()]);
        $runner->run([]);
    }
}
