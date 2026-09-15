<?php

namespace Heritage\Tests\Mail\Fixtures;

use Heritage\Mail\Mailable;

class GreetingMailable extends Mailable
{
    public function build()
    {
        return $this->view('greeting');
    }
}
