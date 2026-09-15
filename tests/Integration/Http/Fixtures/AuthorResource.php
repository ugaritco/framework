<?php

namespace Heritage\Tests\Integration\Http\Fixtures;

use Heritage\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray($request)
    {
        return ['name' => $this->name];
    }
}
