<?php

namespace Heritage\Support\Facades;

use Heritage\Console\Scheduling\Schedule as ConsoleSchedule;

/**
 * @method static \Heritage\Console\Scheduling\CallbackEvent call(string|callable $callback, array $parameters = [])
 * @method static \Heritage\Console\Scheduling\Event command(\Symfony\Component\Console\Command\Command|string $command, array $parameters = [])
 * @method static \Heritage\Console\Scheduling\CallbackEvent job(object|string $job, \UnitEnum|string|null $queue = null, \UnitEnum|string|null $connection = null)
 * @method static \Heritage\Console\Scheduling\Event exec(string $command, array $parameters = [])
 * @method static void group(\Closure $events)
 * @method static string compileArrayInput(string|int $key, array $value)
 * @method static bool serverShouldRun(\Heritage\Console\Scheduling\Event $event, \DateTimeInterface $time)
 * @method static \Heritage\Support\Collection dueEvents(\Heritage\Contracts\Foundation\Application $app)
 * @method static \Heritage\Console\Scheduling\Event[] events()
 * @method static \Heritage\Console\Scheduling\Event[] eventsForEnvironments(array $environments)
 * @method static \Heritage\Console\Scheduling\Schedule useCache(\UnitEnum|string $store)
 * @method static void withoutInterruptionPolling()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes withoutOverlapping(int $expiresAt = 1440, bool $releaseOnTerminationSignals = true)
 * @method static void mergeAttributes(\Heritage\Console\Scheduling\Event $event)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes user(string $user)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes environments(mixed $environments)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes evenInMaintenanceMode()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes evenWhenPaused()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes onOneServer()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes runInBackground()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes when(\Closure|bool $callback)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes skip(\Closure|bool $callback)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes name(string $description)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes description(string $description)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes withAttributes(array $attributes)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes cron(string $expression)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes between(string $startTime, string $endTime)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes unlessBetween(string $startTime, string $endTime)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everySecond()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTwoSeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFiveSeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTenSeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFifteenSeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTwentySeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyThirtySeconds()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyMinute()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTwoMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyThreeMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFourMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFiveMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTenMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFifteenMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyThirtyMinutes()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes hourly()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes hourlyAt(array|string|int|int[] $offset)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyOddHour(array|string|int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyTwoHours(array|string|int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyThreeHours(array|string|int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everyFourHours(array|string|int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes everySixHours(array|string|int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes daily()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes at(string $time)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes dailyAt(string $time)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes twiceDaily(int $first = 1, int $second = 13)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes twiceDailyAt(int $first = 1, int $second = 13, int $offset = 0)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes weekdays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes weekends()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes mondays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes tuesdays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes wednesdays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes thursdays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes fridays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes saturdays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes sundays()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes weekly()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes weeklyOn(mixed $dayOfWeek, string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes monthly()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes monthlyOn(int $dayOfMonth = 1, string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes lastDayOfMonth(string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes daysOfMonth(array|int ...$days)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes quarterly()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes quarterlyOn(int $dayOfQuarter = 1, string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes yearly()
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes yearlyOn(int $month = 1, int|string $dayOfMonth = 1, string $time = '0:0')
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes days(mixed $days)
 * @method static \Heritage\Console\Scheduling\PendingEventAttributes timezone(\UnitEnum|\DateTimeZone|string $timezone)
 *
 * @see \Heritage\Console\Scheduling\Schedule
 */
class Schedule extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleSchedule::class;
    }
}
