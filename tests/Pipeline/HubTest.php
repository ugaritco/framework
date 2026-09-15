<?php

namespace Heritage\Tests\Pipeline;

use Heritage\Container\Container;
use Heritage\Pipeline\Hub;
use Heritage\Pipeline\Pipeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HubTest extends TestCase
{
    private Hub $hub;

    protected function setUp(): void
    {
        $this->hub = new Hub(new Container);
    }

    public function testPipeSendsObjectThroughDefaultPipeline(): void
    {
        $this->hub->defaults(function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $this->hub->pipe('foo'));
    }

    public function testPipeSendsObjectThroughNamedPipeline(): void
    {
        $this->hub->pipeline('named', function (Pipeline $pipeline, $object) {
            return $pipeline->send($object)->through([])->thenReturn();
        });

        $this->assertSame('foo', $this->hub->pipe('foo', 'named'));
    }

    public function testPipeThrowsExceptionForUndefinedPipeline(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Pipeline [missing] is not defined.'));

        $this->hub->pipe('foo', 'missing');
    }
}
