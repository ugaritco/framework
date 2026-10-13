<?php

declare(strict_types=1);

namespace Heritage\Translation\Console;

use Heritage\Console\Command;
use Heritage\Console\ConfirmableTrait;
use Heritage\Translation\Locales;
use Heritage\Translation\LocaleStorageConverter;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'translations:convert-locale-storage')]
class ConvertLocaleStorageCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:convert-locale-storage
                    {--to= : Target storage: "locale_id" (i18n artifact) or "locale" (locale code)}
                    {--table=* : Only convert these translation tables}
                    {--database= : The database connection to use}
                    {--dry-run : Show the conversion plan without changing anything}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert translation tables between locale code ("locale") and locale_id storage';

    public function handle(): int
    {
        $to = (string) $this->option('to');

        if (! in_array($to, ['locale', 'locale_id'], true)) {
            $this->components->error('Pass --to=locale_id (adopting the i18n artifact) or --to=locale (leaving it).');

            return self::FAILURE;
        }

        $converter = new LocaleStorageConverter(
            $this->ugarit['db']->connection($this->option('database')),
            Locales::resolver(),
        );

        $tables = $this->option('table') ?: $converter->translationTables();

        if ($tables === []) {
            $this->components->info('No translation tables found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->confirmToProceed('Translation tables will be altered. Back up the database first.')) {
            return self::FAILURE;
        }

        $rows = [];

        try {
            foreach ($tables as $table) {
                $report = $converter->convert($table, $to, $dryRun);

                $rows[] = [
                    $report['table'],
                    $report['skipped'] ?? "{$report['from']} → {$report['to']}",
                    $report['rows'],
                    implode(', ', array_map(fn ($old, $new) => "{$old}→{$new}", array_keys($report['mapping']), $report['mapping'])),
                    implode(', ', $report['unmapped']) ?: '-',
                ];
            }
        } catch (RuntimeException $e) {
            $this->table(['Table', 'Change', 'Rows', 'Mapping', 'Unmapped'], $rows);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Table', 'Change', 'Rows', 'Mapping', 'Unmapped'], $rows);

        $dryRun
            ? $this->components->info('Dry run: nothing was changed.')
            : $this->components->info('Translation tables converted to '.$to.' storage.');

        return self::SUCCESS;
    }
}
