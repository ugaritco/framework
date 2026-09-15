<?php

namespace Heritage\Image\Transformations;

use Heritage\Contracts\Image\Transformation;

class Scale implements Transformation
{
    /**
     * @param  positive-int|null  $width
     * @param  positive-int|null  $height
     */
    public function __construct(
        public readonly ?int $width,
        public readonly ?int $height,
    ) {
        //
    }
}
