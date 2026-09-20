<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Console\Migrations\MigrateMakeCommand;
use Heritage\Database\Migrations\MigrationCreator;
use Heritage\Foundation\Application;
use Heritage\Support\Composer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMakeCommandTest extends TestCase
{
    public function testBasicCreateDumpsAutoload()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $composer = Mockery::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setUgarit($app);
        $app->setBasePath('/home/ugarit');
        $creator->expects('create')
            ->with('create_foo', '/home/ugarit/vendor/ugarit-package/migrations', 'users', true)
            ->andReturn('/home/ugarit/vendor/ugarit-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/ugarit-package/migrations', '--create' => 'users']);
    }

    public function testTranslatableOptionGivesCreatorProperArguments()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_posts_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'posts', true, true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_posts_table.php');

        $this->runCommand($command, ['name' => 'create_posts_table', '--trans' => true]);
    }

    public function testTranslatableShortOptionGivesCreatorProperArguments()
    {
        $creator = Mockery::mock(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Mockery::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setUgarit($app);
        $creator->expects('create')
            ->with('create_posts_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'posts', true, true)
            ->andReturn(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_posts_table.php');

        $this->runCommand($command, ['name' => 'create_posts_table', '--trans' => true]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

