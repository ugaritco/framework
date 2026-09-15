<?php

namespace Heritage\Tests\Pagination;

use Heritage\Database\Eloquent\Collection;
use Heritage\Pagination\AbstractCursorPaginator;
use Mockery;
use PHPUnit\Framework\TestCase;

class CursorPaginatorLoadMorphTest extends TestCase
{
    public function testCollectionLoadMorphCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Mockery::mock(Collection::class);
        $items->expects('loadMorph')->with('parentable', $relations);

        $p = (new class extends AbstractCursorPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorph('parentable', $relations));
    }
}
