<?php

namespace Heritage\Tests\Queue;

use Heritage\Foundation\Application;
use Heritage\Queue\Console\ListFailedCommand;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListFailedCommandTest extends TestCase
{
    public function testItDisplaysEmptyFailedJobsAsJson()
    {
        $output = $this->runCommandWithFailedJobs([], ['--json' => true]);

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testItDisplaysFailedJobsAsJson()
    {
        $output = $this->runCommandWithFailedJobs([
            (object) [
                'id' => 'failed-job-id',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode([
                    'job' => 'Heritage\Queue\CallQueuedHandler@call',
                    'data' => [
                        'command' => 'O:32:"Heritage\Tests\Queue\ExampleJob":0:{}',
                    ],
                ]),
                'exception' => 'Exception stack trace',
                'failed_at' => '2026-05-18 12:00:00',
            ],
        ], ['--json' => true]);

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString(json_encode([
            [
                'id' => 'failed-job-id',
                'connection' => 'redis',
                'queue' => 'default',
                'class' => 'Heritage\Tests\Queue\ExampleJob',
                'failed_at' => '2026-05-18 12:00:00',
            ],
        ]), $output);
    }

    protected function runCommandWithFailedJobs(array $failedJobs, array $arguments = []): string
    {
        $container = new Application;
        $failer = Mockery::mock();
        $container->instance('queue.failer', $failer);

        $failer->expects('all')->andReturn($failedJobs);

        $command = new ListFailedCommand;
        $command->setUgarit($container);

        $output = new BufferedOutput;

        $command->run(new ArrayInput($arguments), $output);

        return $output->fetch();
    }
}
