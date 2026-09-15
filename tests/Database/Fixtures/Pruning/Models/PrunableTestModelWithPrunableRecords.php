<?php

declare(strict_types=1);

namespace Heritage\Tests\Database\Fixtures\Pruning\Models;

use Heritage\Database\Eloquent\MassPrunable;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Events\ModelsPruned;

class PrunableTestModelWithPrunableRecords extends Model
{
    use MassPrunable;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function pruneAll()
    {
        event(new ModelsPruned(static::class, 10));
        event(new ModelsPruned(static::class, 20));

        return 20;
    }

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}
