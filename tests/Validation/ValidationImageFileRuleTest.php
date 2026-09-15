<?php

namespace Heritage\Tests\Validation;

use Heritage\Container\Container;
use Heritage\Http\UploadedFile;
use Heritage\Support\Arr;
use Heritage\Support\Facades\Facade;
use Heritage\Translation\ArrayLoader;
use Heritage\Translation\Translator;
use Heritage\Validation\Rule;
use Heritage\Validation\Rules\File;
use Heritage\Validation\ValidationServiceProvider;
use Heritage\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationImageFileRuleTest extends TestCase
{
    public function testDimensions()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            UploadedFile::fake()->image('foo.png', 101, 101),
            ['validation.dimensions'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );
    }

    public function testDimensionsWithCustomImageSizeMethod()
    {
        $stream = tmpfile(); // To prevent PHP from deleting the temp file early.
        $path = stream_get_meta_data($stream)['uri'];

        $this->fails(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            new UploadedFileWithCustomImageSizeMethod($path, 'foo.png'),
            ['validation.dimensions'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->width(200)->height(200)),
            new UploadedFileWithCustomImageSizeMethod($path, 'foo.png'),
        );
    }

    public function testDimensionWithTheRatioMethod()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->ratio(1)),
            UploadedFile::fake()->image('foo.png', 105, 100),
            ['validation.dimensions'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->ratio(1)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );
    }

    public function testDimensionWithTheMinRatioMethod()
    {
        $this->passes(
            File::image()->dimensions(Rule::dimensions()->minRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->minRatio(2 / 3)),
            UploadedFile::fake()->image('foo.png', 200, 300),
        );

        $this->fails(
            File::image()->dimensions(Rule::dimensions()->minRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 300),
            ['validation.dimensions'],
        );
    }

    public function testDimensionWithTheMaxRatioMethod()
    {
        $this->passes(
            File::image()->dimensions(Rule::dimensions()->maxRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 300),
            ['validation.dimensions'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->maxRatio(1 / 3)),
            UploadedFile::fake()->image('foo.png', 100, 300),
        );

        $this->fails(
            File::image()->dimensions(Rule::dimensions()->maxRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 100),
            ['validation.dimensions'],
        );
    }

    public function testDimensionWithTheRatioBetweenMethod()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->ratioBetween(1 / 3, 1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 100),
            ['validation.dimensions'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->ratioBetween(1 / 3, 1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 200),
        );
    }

    protected function fails($rule, $values, $messages)
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules($rule, $values, $result, $messages)
    {
        $values = Arr::wrap($values);

        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_file' => $value],
                ['my_file' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_file' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}

class UploadedFileWithCustomImageSizeMethod extends UploadedFile
{
    public function isValid(): bool
    {
        return true;
    }

    public function guessExtension(): string
    {
        return 'png';
    }

    public function dimensions()
    {
        return [200, 200];
    }
}
