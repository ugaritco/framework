<?php

namespace Heritage\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \Heritage\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
