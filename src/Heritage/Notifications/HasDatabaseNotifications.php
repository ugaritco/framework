<?php

namespace Heritage\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     *
     * @return \Heritage\Database\Eloquent\Relations\MorphMany<DatabaseNotification, $this>
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    /**
     * Get the entity's read notifications.
     *
     * @return \Heritage\Database\Eloquent\Relations\MorphMany<DatabaseNotification, $this>
     */
    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    /**
     * Get the entity's unread notifications.
     *
     * @return \Heritage\Database\Eloquent\Relations\MorphMany<DatabaseNotification, $this>
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }
}
