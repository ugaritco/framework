<?php

namespace Heritage\Tests\Foundation;

use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\PackageManifest;
use PHPUnit\Framework\TestCase;

class FoundationPackageManifestTest extends TestCase
{
    public function testAssetLoading()
    {
        @unlink(__DIR__.'/Fixtures/packages.php');
        $manifest = new PackageManifest(new Filesystem, __DIR__.'/Fixtures', __DIR__.'/Fixtures/packages.php');
        $this->assertEquals(['foo', 'bar', 'baz'], $manifest->providers());
        $this->assertEquals(['Foo' => 'Foo\\Facade'], $manifest->aliases());
        unlink(__DIR__.'/Fixtures/packages.php');
    }
}
