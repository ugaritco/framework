<?php

namespace Heritage\Tests\Notifications\Fixtures\Models;

use Heritage\Database\Eloquent\Model;
use Heritage\Notifications\Notifiable;

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}
