<?php

namespace Heritage\Tests\Integration\Generators;

use Heritage\Notifications\Console\NotificationTableCommand;

class NotificationTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->scribe(NotificationTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Heritage\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'notifications\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'notifications\');',
        ], 'create_notifications_table.php');
    }
}
