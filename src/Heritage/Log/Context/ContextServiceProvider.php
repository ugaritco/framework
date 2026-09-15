<?php

namespace Heritage\Log\Context;

use Heritage\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use Heritage\Queue\Events\JobProcessing;
use Heritage\Queue\Queue;
use Heritage\Support\Env;
use Heritage\Support\Facades\Context;
use Heritage\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(Repository::class);

        if ($this->app->runningInConsole()) {
            $this->app->resolving(Repository::class, function (Repository $repository) {
                $context = Env::get('__UGARIT_CONTEXT');

                if ($context && $context = json_decode($context, associative: true)) {
                    $repository->hydrate($context);
                }
            });
        }

        $this->app->bind(ContextLogProcessorContract::class, fn () => new ContextLogProcessor());
    }

    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            /** @phpstan-ignore staticMethod.notFound */
            $context = Context::dehydrate();

            return $context === null ? $payload : [
                ...$payload,
                'heritage:log:context' => $context,
            ];
        });

        $this->app['events']->listen(function (JobProcessing $event) {
            /** @phpstan-ignore staticMethod.notFound */
            Context::hydrate($event->job->payload()['heritage:log:context'] ?? null);
        });
    }
}
