<?php

namespace Fereydooni\Unixtime\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Fereydooni\Unixtime\Traits\HasTimestampEquivalents;
use ReflectionClass;

use function config;
use function app_path;
use function base_path;

class SyncTimestampEquivalents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timestamp-equivalents:sync
                            {--model=* : Specific model(s) to sync}
                            {--dry-run : Show what would be done without actually doing it}
                            {--backfill : Backfill existing data with Unix timestamps}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically add Unix timestamp columns for models using HasTimestampEquivalents trait';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $shouldBackfill = $this->option('backfill');
        $specificModels = $this->option('model');

        $this->info('🔍 Scanning for models with HasTimestampEquivalents trait...');

        $models = $this->getModelsWithTrait($specificModels);

        if (empty($models)) {
            $this->warn('No models found with HasTimestampEquivalents trait.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d model(s) to process.', count($models)));
        $this->newLine();

        foreach ($models as $modelClass) {
            $this->processModel($modelClass, $isDryRun, $shouldBackfill);
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info('✅ Dry run completed. Use without --dry-run to apply changes.');
        } else {
            $this->info('✅ All timestamp equivalent columns have been synced!');
        }

        return Command::SUCCESS;
    }

    /**
     * Process a single model.
     */
    protected function processModel(string $modelClass, bool $isDryRun, bool $shouldBackfill): void
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $connection = $model->getConnectionName() ?? config('database.default');

        $this->line(sprintf('<fg=cyan>Processing:</> %s (<fg=yellow>%s</>)', $modelClass, $table));

        // Get datetime columns that need Unix equivalents
        $datetimeColumns = array_diff(
            $model->getTimestampEquivalentColumns(),
            $model->getExcludedTimestampColumns()
        );

        if (empty($datetimeColumns)) {
            $this->line('  <fg=gray>→ No datetime columns found or all excluded.</>');
            return;
        }

        $suffix = $model->getTimestampColumnSuffix();
        $addedColumns = [];
        $existingColumns = [];

        foreach ($datetimeColumns as $column) {
            $timestampColumn = $column . $suffix;

            // Check if the column already exists
            if (Schema::connection($connection)->hasColumn($table, $timestampColumn)) {
                $existingColumns[] = $timestampColumn;
                continue;
            }

            $addedColumns[] = $timestampColumn;

            if (!$isDryRun) {
                // Add the column
                Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($column, $timestampColumn) {
                    $blueprint->bigInteger($timestampColumn)
                        ->nullable()
                        ->index()
                        ->after($column);
                });

                $this->line(sprintf('  <fg=green>✓</> Added column: %s', $timestampColumn));
            } else {
                $this->line(sprintf('  <fg=yellow>+</> Would add column: %s', $timestampColumn));
            }
        }

        // Show summary for existing columns
        if (!empty($existingColumns)) {
            $this->line(sprintf('  <fg=gray>→ %d column(s) already exist: %s</>',
                count($existingColumns),
                implode(', ', $existingColumns)
            ));
        }

        // Backfill data if requested
        if ($shouldBackfill && !$isDryRun && !empty($addedColumns)) {
            $this->backfillData($model, $datetimeColumns, $connection, $table, $suffix);
        }

        $this->newLine();
    }

    /**
     * Backfill existing data with Unix timestamps.
     */
    protected function backfillData($model, array $datetimeColumns, string $connection, string $table, string $suffix): void
    {
        $this->line('  <fg=magenta>⟳</> Backfilling data...');

        foreach ($datetimeColumns as $column) {
            $timestampColumn = $column . $suffix;

            // Check if column exists in the table
            if (!Schema::connection($connection)->hasColumn($table, $column)) {
                continue;
            }

            // Use database-specific function to convert datetime to Unix timestamp
            $driver = DB::connection($connection)->getDriverName();

            $updateQuery = match($driver) {
                'mysql' => "UPDATE `{$table}` SET `{$timestampColumn}` = UNIX_TIMESTAMP(`{$column}`) WHERE `{$column}` IS NOT NULL AND `{$timestampColumn}` IS NULL",
                'pgsql' => "UPDATE \"{$table}\" SET \"{$timestampColumn}\" = EXTRACT(EPOCH FROM \"{$column}\") WHERE \"{$column}\" IS NOT NULL AND \"{$timestampColumn}\" IS NULL",
                'sqlite' => "UPDATE \"{$table}\" SET \"{$timestampColumn}\" = CAST(strftime('%s', \"{$column}\") AS INTEGER) WHERE \"{$column}\" IS NOT NULL AND \"{$timestampColumn}\" IS NULL",
                default => null,
            };

            if ($updateQuery) {
                $affected = DB::connection($connection)->update($updateQuery);
                if ($affected > 0) {
                    $this->line(sprintf('    <fg=green>✓</> Backfilled %d row(s) for %s', $affected, $column));
                }
            }
        }
    }

    /**
     * Get all models that use the HasTimestampEquivalents trait.
     */
    protected function getModelsWithTrait(array $specificModels = []): array
    {
        $models = [];

        // If specific models are provided, use them
        if (!empty($specificModels)) {
            foreach ($specificModels as $modelClass) {
                if (!class_exists($modelClass)) {
                    $this->warn(sprintf('Model class not found: %s', $modelClass));
                    continue;
                }

                if ($this->usesTrait($modelClass, HasTimestampEquivalents::class)) {
                    $models[] = $modelClass;
                } else {
                    $this->warn(sprintf('Model does not use HasTimestampEquivalents trait: %s', $modelClass));
                }
            }

            return $models;
        }

        // Otherwise, scan all models in the app
        $modelPaths = [
            app_path('Models'),
            base_path('packages/shopping/src/app/Models'),
        ];

        foreach ($modelPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $models = array_merge($models, $this->scanDirectoryForModels($path));
        }

        return array_unique($models);
    }

    /**
     * Scan a directory for models that use the trait.
     */
    protected function scanDirectoryForModels(string $directory): array
    {
        $models = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Try to extract namespace and class name
            if (preg_match('/namespace\s+(.+?);/', $content, $namespaceMatch) &&
                preg_match('/class\s+(\w+)/', $content, $classMatch)) {

                $namespace = $namespaceMatch[1];
                $className = $classMatch[1];
                $fullClassName = $namespace . '\\' . $className;

                if (class_exists($fullClassName) && $this->usesTrait($fullClassName, HasTimestampEquivalents::class)) {
                    $models[] = $fullClassName;
                }
            }
        }

        return $models;
    }

    /**
     * Check if a class uses a specific trait.
     */
    protected function usesTrait(string $class, string $trait): bool
    {
        $reflection = new ReflectionClass($class);
        $traits = $reflection->getTraitNames();

        return in_array($trait, $traits);
    }
}
