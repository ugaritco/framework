<?php

namespace Heritage\Tests\Integration\Http;

use Heritage\Http\Client\Events\RequestSending;
use Heritage\Http\Client\Pool;
use Heritage\Http\Client\Request;
use Heritage\Http\Client\Response;
use Heritage\Support\Collection;
use Heritage\Support\Facades\Event;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class HttpClientTest extends TestCase
{
    public function testGlobalMiddlewarePersistsBeforeWeDispatchEvent(): void
    {
        Event::fake();
        Http::fake();

        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Facade/1.0'));

        Http::get('ugarit.com');

        Event::assertDispatched(RequestSending::class, function (RequestSending $event) {
            return (new Collection($event->request->header('User-Agent')))->contains('Facade/1.0');
        });
    }

    public function testGlobalMiddlewarePersistsAfterFacadeFlush(): void
    {
        Http::macro('getGlobalMiddleware', fn () => $this->globalMiddleware);
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));

        $this->assertCount(2, Http::getGlobalMiddleware());

        Facade::clearResolvedInstances();

        $this->assertCount(2, Http::getGlobalMiddleware());
    }

    public function testPoolCanForwardToUnderlyingPromise()
    {
        Http::fake([
            'https://ugarit.com*' => Http::response('Ugarit'),
            'https://forge.ugarit.com*' => Http::response('Forge'),
            'https://nightwatch.ugarit.com*' => Http::response('Tim n Jess'),
        ]);

        $responses = Http::pool(function (Pool $pool) {
            $pool->as('ugarit')->get('https://ugarit.com');

            $pool->as('forge')
                ->get('https://forge.ugarit.com')
                ->then(function (Response $response): int {
                    return strlen($response->getBody());
                });

            $pool->as('nightwatch')
                ->get('https://nightwatch.ugarit.com')
                ->then(fn (): int => 1)
                ->then(fn ($i): int => $i + 199);
        }, 3);

        $this->assertInstanceOf(Response::class, $responses['ugarit']);
        $this->assertEquals(5, $responses['forge']);
        $this->assertEquals(200, $responses['nightwatch']);

        $this->assertCount(3, Http::recorded());
    }

    public function testForwardsCallsToPromise()
    {
        Http::fake(['*' => Http::response('faked response')]);

        $myFakedResponse = null;
        $r = Http::async()
            ->get('https://ugarit.com')
            ->then(function (Response $response) use (&$myFakedResponse): string {
                $myFakedResponse = $response->getBody();

                return 'stub';
            })
            ->wait();

        $this->assertSame('faked response', (string) $myFakedResponse);
        $this->assertSame('stub', $r);
    }

    public function testCanSetRequestAttributes()
    {
        Http::fake([
            '*' => fn (Request $request) => match ($request->attributes()['name'] ?? null) {
                'first' => Http::response('first response'),
                'second' => Http::response('second response'),
                default => Http::response('unnamed')
            },
        ]);

        $response1 = Http::withAttributes(['name' => 'first'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response2 = Http::withAttributes(['name' => 'second'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response3 = Http::get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response4 = Http::withAttributes(['name' => 'fourth'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');

        $this->assertSame('first response', $response1->body());
        $this->assertSame('second response', $response2->body());
        $this->assertSame('unnamed', $response3->body());
        $this->assertSame('unnamed', $response4->body());
    }

    public function testAsyncCanHandleThrownException()
    {
        Http::fake(
            ['*' => Http::response(['luke' => 'kuzmish'])]
        );

        $thrown = new RuntimeException();
        $actual = Http::async()
            ->afterResponse(
                fn (Response $response) => $response->json('luke') === 'kuzmish'
                    ? throw $thrown
                    : null
            )->get('https://cosmastech.com')
            ->wait();

        $this->assertSame($thrown, $actual);
    }
}
