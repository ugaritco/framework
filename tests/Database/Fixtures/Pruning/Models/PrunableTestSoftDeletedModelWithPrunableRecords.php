<?php

declare(strict_types=1);

namespace Heritage\Tests\Database\Fixtures\Pruning\Models;

use Heritage\Database\Eloquent\MassPrunable;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\SoftDeletes;

class PrunableTestSoftDeletedModelWithPrunableRecords extends Model
{
    use MassPrunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}
