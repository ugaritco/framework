<?php

namespace Heritage\Foundation\Testing\Concerns;

use Carbon\CarbonImmutable;
use Heritage\Console\Application as Scribe;
use Heritage\Cookie\Middleware\EncryptCookies;
use Heritage\Database\Eloquent\Factories\Factory;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Migrations\Migrator;
use Heritage\Foundation\Bootstrap\HandleExceptions;
use Heritage\Foundation\Bootstrap\RegisterProviders;
use Heritage\Foundation\Console\AboutCommand;
use Heritage\Foundation\Http\FormRequest;
use Heritage\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Heritage\Foundation\Http\Middleware\PreventRequestForgery;
use Heritage\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Heritage\Foundation\Http\Middleware\TrimStrings;
use Heritage\Foundation\Testing\Attributes\SetUp;
use Heritage\Foundation\Testing\Attributes\TearDown;
use Heritage\Foundation\Testing\DatabaseMigrations;
use Heritage\Foundation\Testing\DatabaseTransactions;
use Heritage\Foundation\Testing\DatabaseTruncation;
use Heritage\Foundation\Testing\RefreshDatabase;
use Heritage\Foundation\Testing\WithFaker;
use Heritage\Foundation\Testing\WithoutMiddleware;
use Heritage\Http\Client\Response;
use Heritage\Http\Middleware\HandleCors;
use Heritage\Http\Middleware\TrustHosts;
use Heritage\Http\Middleware\TrustProxies;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\JsonApi\JsonApiResource;
use Heritage\Mail\Markdown;
use Heritage\Queue\Console\WorkCommand;
use Heritage\Queue\Queue;
use Heritage\Support\Carbon;
use Heritage\Support\EncodedHtmlString;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\ParallelTesting;
use Heritage\Support\Lottery;
use Heritage\Support\Once;
use Heritage\Support\Sleep;
use Heritage\Support\Str;
use Heritage\Validation\Validator;
use Heritage\View\Component;
use Mockery;
use Mockery\Exception\InvalidCountException;
use PHPUnit\Metadata\Annotation\Parser\Registry as PHPUnitRegistry;
use ReflectionClass;
use Throwable;

trait InteractsWithTestCaseLifecycle
{
    /**
     * The Heritage application instance.
     *
     * @var \Heritage\Foundation\Application
     */
    protected $app;

    /**
     * The callbacks that should be run after the application is created.
     *
     * @var array
     */
    protected $afterApplicationCreatedCallbacks = [];

    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * @var array
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * The exception thrown while running an application destruction callback.
     *
     * @var \Throwable
     */
    protected $callbackException;

    /**
     * Indicates if we have made it through the base setUp function.
     *
     * @var bool
     */
    protected $setUpHasRun = false;

    /**
     * Setup the test environment.
     *
     * @internal
     *
     * @return void
     */
    protected function setUpTheTestEnvironment(): void
    {
        Facade::clearResolvedInstances();

        if (! $this->app) {
            $this->refreshApplication();

            ParallelTesting::callSetUpTestCaseCallbacks($this);
        }

        $this->setUpTraits();

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            $callback();
        }

        Model::setEventDispatcher($this->app['events']);

        $this->setUpHasRun = true;
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @internal
     *
     * @return void
     *
     * @throws \Throwable
     */
    protected function tearDownTheTestEnvironment(): void
    {
        if ($this->app) {
            $this->callBeforeApplicationDestroyedCallbacks();

            ParallelTesting::callTearDownTestCaseCallbacks($this);

            $this->app->flush();

            $this->app = null;
        }

        $this->setUpHasRun = false;

        if (property_exists($this, 'serverVariables')) {
            $this->serverVariables = [];
        }

        if (property_exists($this, 'defaultHeaders')) {
            $this->defaultHeaders = [];
        }

        if (class_exists('Mockery')) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            try {
                Mockery::close();
            } catch (InvalidCountException $e) {
                if (! Str::contains($e->getMethodName(), ['doWrite', 'askQuestion'])) {
                    throw $e;
                }
            }
        }

        if (class_exists(Carbon::class)) {
            Carbon::setTestNow();
        }

        if (class_exists(CarbonImmutable::class)) {
            CarbonImmutable::setTestNow();
        }

        $this->afterApplicationCreatedCallbacks = [];
        $this->beforeApplicationDestroyedCallbacks = [];

        if (property_exists($this, 'originalExceptionHandler')) {
            $this->originalExceptionHandler = null;
        }

        if (property_exists($this, 'originalDeprecationHandler')) {
            $this->originalDeprecationHandler = null;
        }

        $this->flushState();

        if ($this->callbackException) {
            throw $this->callbackException;
        }
    }

    /**
     * Reset static state between test executions.
     */
    protected function flushState(): void
    {
        AboutCommand::flushState();
        Scribe::forgetBootstrappers();
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();
        ConvertEmptyStringsToNull::flushState();
        Factory::flushState();
        FormRequest::flushState();
        EncodedHtmlString::flushState();
        EncryptCookies::flushState();
        HandleCors::flushState();
        HandleExceptions::flushState($this);
        JsonApiResource::flushState();
        JsonResource::flushState();
        Lottery::determineResultsNormally();
        Markdown::flushState();
        Migrator::withoutMigrations([]);
        Once::flush();
        PreventRequestsDuringMaintenance::flushState();
        Queue::createPayloadUsing(null);
        RegisterProviders::flushState();
        Response::flushState();
        Sleep::fake(false);
        Str::resetFactoryState();
        TrimStrings::flushState();
        TrustProxies::flushState();
        TrustHosts::flushState();
        PreventRequestForgery::flushState();
        Validator::flushState();
        WorkCommand::flushState();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $uses = $this->traitsUsedByTest ?? class_uses_recursive(static::class);

        if (isset($uses[RefreshDatabase::class])) {
            $this->refreshDatabase();
        }

        if (isset($uses[DatabaseMigrations::class])) {
            $this->runDatabaseMigrations();
        }

        if (isset($uses[DatabaseTruncation::class])) {
            $this->truncateDatabaseTables();
        }

        if (isset($uses[DatabaseTransactions::class])) {
            $this->beginDatabaseTransaction();
        }

        if (isset($uses[WithoutMiddleware::class])) {
            $this->disableMiddlewareForAllTests();
        }

        if (isset($uses[WithFaker::class])) {
            $this->setUpFaker();
        }

        foreach ($uses as $trait) {
            if (method_exists($this, $method = 'setUp'.class_basename($trait))) {
                $this->{$method}();
            }

            if (method_exists($this, $method = 'tearDown'.class_basename($trait))) {
                $this->beforeApplicationDestroyed(fn () => $this->{$method}());
            }

            foreach ((new ReflectionClass($trait))->getMethods() as $method) {
                if ($method->getAttributes(SetUp::class) !== []) {
                    $this->{$method->getName()}();
                }

                if ($method->getAttributes(TearDown::class) !== []) {
                    $this->beforeApplicationDestroyed(fn () => $this->{$method->getName()}());
                }
            }
        }

        return $uses;
    }

    /**
     * Clean up the testing environment before the next test case.
     *
     * @internal
     *
     * @return void
     */
    public static function tearDownAfterClassUsingTestCase()
    {
        if (class_exists(PHPUnitRegistry::class)) {
            (function () {
                $this->classDocBlocks = [];
                $this->methodDocBlocks = [];
            })->call(PHPUnitRegistry::getInstance());
        }
    }

    /**
     * Register a callback to be run after the application is created.
     *
     * @param  callable  $callback
     * @return void
     */
    public function afterApplicationCreated(callable $callback)
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            $callback();
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable  $callback
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback)
    {
        $this->beforeApplicationDestroyedCallbacks[] = $callback;
    }

    /**
     * Execute the application's pre-destruction callbacks.
     *
     * @return void
     */
    protected function callBeforeApplicationDestroyedCallbacks()
    {
        foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
            try {
                $callback();
            } catch (Throwable $e) {
                if (! $this->callbackException) {
                    $this->callbackException = $e;
                }
            }
        }
    }
}
