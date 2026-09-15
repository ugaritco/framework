<?php

namespace Heritage\Notifications;

trait Notifiable
{
    use HasDatabaseNotifications, RoutesNotifications;
}
