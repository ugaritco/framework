<?php

namespace Heritage\Contracts\Auth;

use Heritage\Contracts\Auth\Access\Authorizable;

/**
 * Whoever performs an action: the authenticated entity a policy decides for. Policies take an
 * Actor instead of a concrete user model, so an artifact's policies do not depend on the
 * identity artifact; `Heritage\Foundation\Auth\User` is an Actor.
 */
interface Actor extends Authenticatable, Authorizable
{
    //
}
