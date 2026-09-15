<?php

namespace Heritage\Image\Transformations;

use Heritage\Contracts\Image\Transformation;

class Blur implements Transformation
{
    /**
     * @param  positive-int  $amount
     */
    public function __construct(public readonly int $amount)
    {
        //
    }
}
