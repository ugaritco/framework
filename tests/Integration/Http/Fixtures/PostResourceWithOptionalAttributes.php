<?php

namespace Heritage\Tests\Integration\Http\Fixtures;

use Heritage\Http\Resources\Json\JsonResource;

class PostResourceWithOptionalAttributes extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->whenNotNull($this->id),
            'title' => $this->whenNotNull($this->title, 'no title'),
        ];
    }
}
