<?php

namespace Heritage\Tests\Integration\Generators;

use Heritage\Session\Console\SessionTableCommand;

class SessionTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->scribe(SessionTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Heritage\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'sessions\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'sessions\');',
        ], 'create_sessions_table.php');
    }
}
