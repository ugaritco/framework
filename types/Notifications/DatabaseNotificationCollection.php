<?php

use Heritage\Notifications\DatabaseNotification;
use Heritage\Notifications\DatabaseNotificationCollection;

use function PHPStan\Testing\assertType;

class CustomNotification extends DatabaseNotification
{
    //
}

/**
 * @extends DatabaseNotificationCollection<int, CustomNotification>
 */
class CustomNotificationCollection extends DatabaseNotificationCollection
{
    //
}

$databaseNotificationsCollection = DatabaseNotification::all();
assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Notifications\DatabaseNotification>', $databaseNotificationsCollection);

$customNotificationsCollection = CustomNotification::all();
assertType('Heritage\Database\Eloquent\Collection<int, CustomNotification>', $customNotificationsCollection);
