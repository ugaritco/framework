<?php

namespace Heritage\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Heritage\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue();
}
