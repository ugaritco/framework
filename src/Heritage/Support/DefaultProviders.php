<?php

namespace Heritage\Support;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array<class-string>
     */
    protected $providers;

    /**
     * Create a new default provider collection.
     *
     * @param  array<class-string>|null  $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            \Heritage\Auth\AuthServiceProvider::class,
            \Heritage\Broadcasting\BroadcastServiceProvider::class,
            \Heritage\Bus\BusServiceProvider::class,
            \Heritage\Cache\CacheServiceProvider::class,
            \Heritage\Foundation\Providers\ConsoleSupportServiceProvider::class,
            \Heritage\Concurrency\ConcurrencyServiceProvider::class,
            \Heritage\Cookie\CookieServiceProvider::class,
            \Heritage\Database\DatabaseServiceProvider::class,
            \Heritage\Encryption\EncryptionServiceProvider::class,
            \Heritage\Filesystem\FilesystemServiceProvider::class,
            \Heritage\Image\ImageServiceProvider::class,
            \Heritage\Foundation\Providers\FoundationServiceProvider::class,
            \Heritage\Hashing\HashServiceProvider::class,
            \Heritage\Mail\MailServiceProvider::class,
            \Heritage\Notifications\NotificationServiceProvider::class,
            \Heritage\Pagination\PaginationServiceProvider::class,
            \Heritage\Auth\Passwords\PasswordResetServiceProvider::class,
            \Heritage\Pipeline\PipelineServiceProvider::class,
            \Heritage\Queue\QueueServiceProvider::class,
            \Heritage\Redis\RedisServiceProvider::class,
            \Heritage\Session\SessionServiceProvider::class,
            \Heritage\Translation\TranslationServiceProvider::class,
            \Heritage\Validation\ValidationServiceProvider::class,
            \Heritage\View\ViewServiceProvider::class,
        ];
    }

    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array<class-string>  $providers
     * @return static
     */
    public function merge(array $providers)
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
     * Replace the given providers with other providers.
     *
     * @param  array<class-string, class-string>  $replacements
     * @return static
     */
    public function replace(array $replacements)
    {
        $current = new Collection($this->providers);

        foreach ($replacements as $from => $to) {
            $key = $current->search($from);

            $current = is_int($key) ? $current->replace([$key => $to]) : $current;
        }

        return new static($current->values()->toArray());
    }

    /**
     * Disable the given providers.
     *
     * @param  array<class-string>  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static((new Collection($this->providers))
            ->diff($providers)
            ->values()
            ->toArray());
    }

    /**
     * Convert the provider collection to an array.
     *
     * @return array<class-string>
     */
    public function toArray()
    {
        return $this->providers;
    }
}
