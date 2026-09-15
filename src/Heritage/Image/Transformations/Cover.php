<?php

namespace Heritage\Image\Transformations;

use Heritage\Contracts\Image\Transformation;

class Cover implements Transformation
{
    /**
     * @param  positive-int  $width
     * @param  positive-int  $height
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
        //
    }
}
