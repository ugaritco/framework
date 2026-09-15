<?php

namespace Heritage\Tests\Queue;

use Exception;
use Heritage\Contracts\Queue\Job;
use Heritage\Queue\InteractsWithQueue;
use Mockery;
use PHPUnit\Framework\TestCase;

class InteractsWithQueueTest extends TestCase
{
    public function testCreatesAnExceptionFromString()
    {
        $queueJob = Mockery::mock(Job::class);
        $queueJob->expects('fail')->withArgs(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertSame('Whoops!', $e->getMessage());

            return true;
        });

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->fail('Whoops!');
    }
}
