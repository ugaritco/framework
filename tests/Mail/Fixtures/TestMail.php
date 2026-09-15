<?php

namespace Heritage\Tests\Mail\Fixtures;

use Heritage\Mail\Mailable;

class TestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}
