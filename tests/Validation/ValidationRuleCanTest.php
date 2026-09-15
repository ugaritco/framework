<?php

namespace Heritage\Tests\Validation;

use Heritage\Auth\Access\Gate;
use Heritage\Container\Container;
use Heritage\Contracts\Auth\Access\Gate as GateContract;
use Heritage\Support\Facades\Facade;
use Heritage\Translation\ArrayLoader;
use Heritage\Translation\Translator;
use Heritage\Validation\Rules\Can;
use Heritage\Validation\ValidationServiceProvider;
use Heritage\Validation\Validator;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationRuleCanTest extends TestCase
{
    protected $container;
    protected $user;
    protected $router;

    protected function setUp(): void
    {
        $this->user = new stdClass;

        Container::setInstance($this->container = new Container);

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($this->container);

        (new ValidationServiceProvider($this->container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }

    public function testValidationFails()
    {
        $this->gate()->define('update-company', function ($user, $value) {
            $this->assertSame('1', $value);

            return false;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company')]
        );

        $this->assertTrue($v->fails());
    }

    public function testValidationPasses()
    {
        $this->gate()->define('update-company', function ($user, $class, $model, $value) {
            $this->assertEquals(\App\Models\Company::class, $class);
            $this->assertInstanceOf(stdClass::class, $model);
            $this->assertSame('1', $value);

            return true;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company', [\App\Models\Company::class, new stdClass])]
        );

        $this->assertTrue($v->passes());
    }

    public function testCustomMessageUsingDotNotationAndFqcnWorks()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'company' => '1',
                'company_fqcn' => '1',
            ],
            [
                'company' => new Can('update-company', [\App\Models\Company::class, new stdClass]),
                'company_fqcn' => new Can('update-company', [\App\Models\Company::class, new stdClass]),
            ],
            [
                'company.can' => 'You dont have permission (dot notation)',
                'company_fqcn.Heritage\Validation\Rules\Can' => 'You dont have permission (fqcn)',
            ]
        );

        $this->assertTrue($v->fails());

        $this->assertSame([
            'You dont have permission (dot notation)',
            'You dont have permission (fqcn)',
        ], $v->messages()->all());
    }

    /**
     * Get the Gate instance from the container.
     *
     * @return \Heritage\Auth\Access\Gate
     */
    protected function gate()
    {
        return $this->container->make(GateContract::class);
    }
}
