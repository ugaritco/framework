<?php

declare(strict_types=1);

namespace Heritage\Tests\Database\Fixtures\Pruning\Models;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Prunable;

class PrunableTestModelWithoutPrunableRecords extends Model
{
    use Prunable;

    public function pruneAll()
    {
        return 0;
    }
}
