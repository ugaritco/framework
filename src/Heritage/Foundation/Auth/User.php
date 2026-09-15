<?php

namespace Heritage\Foundation\Auth;

use Heritage\Auth\Authenticatable;
use Heritage\Auth\MustVerifyEmail;
use Heritage\Auth\Passwords\CanResetPassword;
use Heritage\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Heritage\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Heritage\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Heritage\Database\Eloquent\Model;
use Heritage\Foundation\Auth\Access\Authorizable;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
}
