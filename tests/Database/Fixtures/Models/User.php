<?php

namespace Heritage\Tests\Database\Fixtures\Models;

use Heritage\Foundation\Auth\User as FoundationUser;

class User extends FoundationUser
{
    protected $primaryKey = 'internal_id';
}
