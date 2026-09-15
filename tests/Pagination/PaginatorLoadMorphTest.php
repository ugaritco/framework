<?php

namespace Heritage\Tests\Pagination;

use Heritage\Database\Eloquent\Collection;
use Heritage\Pagination\AbstractPaginator;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaginatorLoadMorphTest extends TestCase
{
    public function testCollectionLoadMorphCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Mockery::mock(Collection::class);
        $items->expects('loadMorph')->with('parentable', $relations);

        $p = (new class extends AbstractPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorph('parentable', $relations));
    }
}
