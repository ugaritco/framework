<?php

namespace Heritage\Mail;

use Heritage\Contracts\Support\DeferrableProvider;
use Heritage\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHeritageMailer();
        $this->registerMarkdownRenderer();
    }

    /**
     * Register the Heritage mailer instance.
     *
     * @return void
     */
    protected function registerHeritageMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }

    /**
     * Register the Markdown renderer instance.
     *
     * @return void
     */
    protected function registerMarkdownRenderer()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/mail'),
            ], 'ugarit-mail');
        }

        $this->app->singleton(Markdown::class, function ($app) {
            $config = $app->make('config');

            return new Markdown($app->make('view'), [
                'theme' => $config->get('mail.markdown.theme', 'default'),
                'paths' => $config->get('mail.markdown.paths', []),
                'extensions' => $config->get('mail.markdown.extensions', []),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mail.manager',
            'mailer',
            Markdown::class,
        ];
    }
}
