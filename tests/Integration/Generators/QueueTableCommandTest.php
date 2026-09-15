<?php

namespace Heritage\Tests\Integration\Generators;

use Heritage\Queue\Console\TableCommand;

class QueueTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->scribe(TableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Heritage\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'jobs\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'jobs\');',
        ], 'create_jobs_table.php');
    }
}
