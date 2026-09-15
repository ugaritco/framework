<?php

namespace Heritage\Tests\Integration\Http;

use Heritage\Contracts\Support\Jsonable;
use Heritage\Http\JsonResponse;
use Heritage\Support\Facades\Route;
use InvalidArgumentException;
use JsonSerializable;
use Orchestra\Testbench\TestCase;

class JsonResponseTest extends TestCase
{
    public function testResponseWithInvalidJsonThrowsException()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Malformed UTF-8 characters, possibly incorrectly encoded'));

        Route::get('/response', function () {
            return new JsonResponse(new class implements JsonSerializable
            {
                public function jsonSerialize(): string
                {
                    return "\xB1\x31";
                }
            });
        });

        $this->withoutExceptionHandling();

        $this->get('/response');
    }

    public function testResponseSetDataPassesWithPriorJsonErrors()
    {
        $response = new JsonResponse();

        // Trigger json_last_error() to have a non-zero value...
        json_encode(['a' => acos(2)]);

        $response->setData(new class implements Jsonable
        {
            public function toJson($options = 0): string
            {
                return '{}';
            }
        });

        $this->assertJson($response->getContent());
    }
}
