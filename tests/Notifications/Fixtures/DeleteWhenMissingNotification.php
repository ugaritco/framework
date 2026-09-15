<?php

namespace Heritage\Tests\Notifications\Fixtures;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Notifications\Notification;
use Heritage\Queue\Attributes\DeleteWhenMissingModels;
use Heritage\Queue\SerializesModels;
use Heritage\Tests\Integration\Queue\DeleteNotificationTestModel;

#[DeleteWhenMissingModels]
class DeleteWhenMissingNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public static bool $sent = false;

    public function __construct(public DeleteNotificationTestModel $model)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        static::$sent = true;

        return new \Heritage\Notifications\Messages\MailMessage;
    }
}
