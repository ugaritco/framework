<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Relations\Pivot;

class Membership extends Pivot
{
    protected $table = 'team_user';
}
