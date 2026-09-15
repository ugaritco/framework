<?php

namespace Heritage\Tests\Integration\Generators;

use Heritage\Queue\Console\FailedTableCommand;

class QueueFailedTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->scribe(FailedTableCommand::class)->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Heritage\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'failed_jobs\', function (Blueprint $table) {',
            '$table->string(\'connection\');',
            '$table->string(\'queue\');',
            '$table->index([\'connection\', \'queue\', \'failed_at\']);',
            'Schema::dropIfExists(\'failed_jobs\');',
        ], 'create_failed_jobs_table.php');
    }
}
