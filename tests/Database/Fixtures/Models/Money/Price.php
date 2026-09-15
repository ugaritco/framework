<?php

namespace Heritage\Tests\Database\Fixtures\Models\Money;

use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Model;
use Heritage\Tests\Database\Fixtures\Factories\Money\PriceFactory;

class Price extends Model
{
    /** @use HasFactory<PriceFactory> */
    use HasFactory;

    protected $table = 'prices';

    protected static string $factory = PriceFactory::class;
}
