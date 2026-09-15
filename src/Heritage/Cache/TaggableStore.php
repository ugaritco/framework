<?php

namespace Heritage\Cache;

use Heritage\Contracts\Cache\Store;

abstract class TaggableStore implements Store
{
    /**
     * Begin executing a new tags operation.
     *
     * @param  mixed  $names
     * @return \Heritage\Cache\TaggedCache
     */
    public function tags($names)
    {
        return new TaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }
}
