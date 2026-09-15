<?php

declare(strict_types=1);

namespace Heritage\Tests\Database\Fixtures\Pruning\Models;

use Heritage\Database\Eloquent\Prunable;

trait NonPrunableTrait
{
    use Prunable;
}
