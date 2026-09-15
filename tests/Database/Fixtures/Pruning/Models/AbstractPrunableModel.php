<?php

declare(strict_types=1);

namespace Heritage\Tests\Database\Fixtures\Pruning\Models;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Prunable;

abstract class AbstractPrunableModel extends Model
{
    use Prunable;
}
