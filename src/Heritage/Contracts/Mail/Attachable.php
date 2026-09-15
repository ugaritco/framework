<?php

namespace Heritage\Contracts\Mail;

interface Attachable
{
    /**
     * Get an attachment instance for this entity.
     *
     * @return \Heritage\Mail\Attachment
     */
    public function toMailAttachment();
}
