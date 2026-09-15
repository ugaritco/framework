<?php

namespace Heritage\Log\Context\Events;

class ContextDehydrating
{
    /**
     * The context instance.
     *
     * @var \Heritage\Log\Context\Repository
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Log\Context\Repository  $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }
}
