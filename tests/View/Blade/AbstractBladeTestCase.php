<?php

namespace Heritage\Tests\View\Blade;

use Heritage\Container\Container;
use Heritage\Filesystem\Filesystem;
use Heritage\View\Compilers\BladeCompiler;
use Heritage\View\Component;
use Mockery;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    /**
     * @var \Heritage\View\Compilers\BladeCompiler
     */
    protected $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BladeCompiler($this->getFiles(), __DIR__);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();
    }

    protected function getFiles()
    {
        return Mockery::mock(Filesystem::class);
    }
}
