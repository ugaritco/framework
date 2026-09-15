<?php

namespace Heritage\Tests\Queue;

use Heritage\Queue\Listener;
use Heritage\Queue\ListenerOptions;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function Heritage\Support\scribe_binary;
use function Heritage\Support\php_binary;

class QueueListenerTest extends TestCase
{
    public function testRunProcessCallsProcess()
    {
        $process = Mockery::mock(Process::class)->makePartial();
        $process->expects('run');
        $listener = Mockery::mock(Listener::class)->makePartial();
        $listener->expects('memoryExceeded')->with(1)->andReturn(false);

        $listener->runProcess($process, 1);
    }

    public function testListenerStopsWhenMemoryIsExceeded()
    {
        $process = Mockery::mock(Process::class)->makePartial();
        $process->expects('run');
        $listener = Mockery::mock(Listener::class)->makePartial();
        $listener->expects('memoryExceeded')->with(1)->andReturn(true);
        $listener->expects('stop');

        $listener->runProcess($process, 1);
    }

    public function testMakeProcessCorrectlyFormatsCommandLine()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions;
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = $escapeMsys = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        if (windows_os()) {
            $escapeMsys = '"';
        }

        $scribeBinary = scribe_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$scribeBinary}{$escape} {$escape}queue:work{$escape} {$escape}connection{$escape} {$escape}--once{$escape} {$escapeMsys}--name=default{$escapeMsys} {$escapeMsys}--queue=queue{$escapeMsys} {$escapeMsys}--backoff=1{$escapeMsys} {$escapeMsys}--memory=2{$escapeMsys} {$escapeMsys}--sleep=3{$escapeMsys} {$escapeMsys}--tries=1{$escapeMsys}", $process->getCommandLine());
    }

    public function testMakeProcessCorrectlyFormatsCommandLineWithAnEnvironmentSpecified()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions('default', 'test');
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = $escapeMsys = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        if (windows_os()) {
            $escapeMsys = '"';
        }

        $scribeBinary = scribe_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$scribeBinary}{$escape} {$escape}queue:work{$escape} {$escape}connection{$escape} {$escape}--once{$escape} {$escapeMsys}--name=default{$escapeMsys} {$escapeMsys}--queue=queue{$escapeMsys} {$escapeMsys}--backoff=1{$escapeMsys} {$escapeMsys}--memory=2{$escapeMsys} {$escapeMsys}--sleep=3{$escapeMsys} {$escapeMsys}--tries=1{$escapeMsys} {$escapeMsys}--env=test{$escapeMsys}", $process->getCommandLine());
    }

    public function testMakeProcessCorrectlyFormatsCommandLineWhenTheConnectionIsNotSpecified()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions('default', 'test');
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess(null, 'queue', $options);
        $escape = $escapeMsys = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        if (windows_os()) {
            $escapeMsys = '"';
        }

        $scribeBinary = scribe_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$scribeBinary}{$escape} {$escape}queue:work{$escape} {$escape}--once{$escape} {$escapeMsys}--name=default{$escapeMsys} {$escapeMsys}--queue=queue{$escapeMsys} {$escapeMsys}--backoff=1{$escapeMsys} {$escapeMsys}--memory=2{$escapeMsys} {$escapeMsys}--sleep=3{$escapeMsys} {$escapeMsys}--tries=1{$escapeMsys} {$escapeMsys}--env=test{$escapeMsys}", $process->getCommandLine());
    }
}
