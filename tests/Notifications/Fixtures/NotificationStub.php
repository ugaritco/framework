<?php

namespace Heritage\Tests\Notifications\Fixtures;

use Heritage\Notifications\Notification;

class NotificationStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}
