<?php

namespace Fereydooni\Unixtime\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TimestampEquivalentsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerBlueprintMacros();
    }

    /**
     * Register Blueprint macros for timestamp equivalents.
     */
    protected function registerBlueprintMacros(): void
    {
        /**
         * Automatically add Unix timestamp columns for all datetime columns in the table.
         * This macro should be called at the end of your migration after defining all columns.
         *
         * Usage in migration:
         * Schema::create('users', function (Blueprint $table) {
         *     $table->id();
         *     $table->timestamp('email_verified_at')->nullable();
         *     $table->timestamps();
         *     $table->softDeletes();
         *
         *     // This will automatically add _unix columns for all datetime/timestamp columns
         *     $table->timestampEquivalents();
         * });
         */
        Blueprint::macro('timestampEquivalents', function (array $options = []) {
            /** @var Blueprint $this */
            $suffix = $options['suffix'] ?? '_unix';
            $exclude = $options['exclude'] ?? [];
            $indexed = $options['indexed'] ?? true;

            // Get all columns defined in this blueprint
            $columns = $this->getColumns();

            foreach ($columns as $column) {
                $columnName = $column->get('name');
                $columnType = $column->get('type');

                // Check if this is a datetime-related column
                if (in_array($columnType, ['datetime', 'timestamp', 'date', 'dateTime', 'dateTimeTz'])) {
                    // Skip if excluded
                    if (in_array($columnName, $exclude)) {
                        continue;
                    }

                    // Skip if it already has the suffix (prevent recursion)
                    if (str_ends_with($columnName, $suffix)) {
                        continue;
                    }

                    $timestampColumnName = $columnName . $suffix;

                    // Add the Unix timestamp column
                    $timestampColumn = $this->bigInteger($timestampColumnName)
                        ->nullable()
                        ->after($columnName);

                    // Add index if requested
                    if ($indexed) {
                        $timestampColumn->index();
                    }
                }
            }

            return $this;
        });

        /**
         * Add a Unix timestamp column for a specific datetime column.
         *
         * Usage:
         * $table->timestamp('verified_at')->nullable();
         * $table->timestampEquivalent('verified_at');
         */
        Blueprint::macro('timestampEquivalent', function (string $columnName, array $options = []) {
            /** @var Blueprint $this */
            $suffix = $options['suffix'] ?? '_unix';
            $indexed = $options['indexed'] ?? true;
            $nullable = $options['nullable'] ?? true;

            $timestampColumnName = $columnName . $suffix;

            // Add the Unix timestamp column
            $column = $this->bigInteger($timestampColumnName);

            if ($nullable) {
                $column->nullable();
            }

            // Position after the original column if it exists
            try {
                $column->after($columnName);
            } catch (\Exception $e) {
                // If 'after' fails, just add it normally
            }

            // Add index if requested
            if ($indexed) {
                $column->index();
            }

            return $this;
        });

        /**
         * Drop Unix timestamp equivalent columns.
         *
         * Usage:
         * $table->dropTimestampEquivalents(['created_at', 'updated_at']);
         */
        Blueprint::macro('dropTimestampEquivalents', function (array $columns = [], string $suffix = '_unix') {
            /** @var Blueprint $this */
            foreach ($columns as $column) {
                $timestampColumn = $column . $suffix;
                if (Schema::hasColumn($this->getTable(), $timestampColumn)) {
                    $this->dropColumn($timestampColumn);
                }
            }

            return $this;
        });
    }
}
