<?php

namespace Fereydooni\Unixtime;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait HasTimestampEquivalents
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTimestampEquivalents(): void
    {
        // Sync on creating
        static::creating(function ($model) {
            $model->syncTimestampEquivalents();
        });

        // Sync on updating
        static::updating(function ($model) {
            $model->syncTimestampEquivalents();
        });

        // Sync on retrieved
        static::retrieved(function ($model) {
            $model->syncTimestampEquivalents();
        });

        // Sync on saved (after create or update)
        static::saved(function ($model) {
            // Re-sync to ensure consistency
            $model->syncTimestampEquivalents(false);
        });
    }

    /**
     * Get the datetime columns that should have timestamp equivalents.
     * Override this method in your model to specify custom columns.
     *
     * @return array
     */
    public function getTimestampEquivalentColumns(): array
    {
        // If the model explicitly defines columns, use those
        if (property_exists($this, 'timestampEquivalentColumns')) {
            return $this->timestampEquivalentColumns;
        }

        // Otherwise, auto-detect from database schema and casts
        return $this->getDatetimeColumnsFromDatabase();
    }

    /**
     * Get the datetime columns that should be excluded from timestamp equivalents.
     *
     * @return array
     */
    public function getExcludedTimestampColumns(): array
    {
        if (property_exists($this, 'excludedTimestampColumns')) {
            return $this->excludedTimestampColumns;
        }

        return [];
    }

    /**
     * Get the suffix to append to timestamp column names.
     *
     * @return string
     */
    public function getTimestampColumnSuffix(): string
    {
        if (property_exists($this, 'timestampColumnSuffix')) {
            return $this->timestampColumnSuffix;
        }

        return '_unix';
    }

    /**
     * Get datetime columns from database schema.
     * This is the primary source of truth - we check actual database columns.
     *
     * @return array
     */
    protected function getDatetimeColumnsFromDatabase(): array
    {
        $datetimeColumns = [];
        $table = $this->getTable();
        $connection = $this->getConnectionName();

        try {
            // Get column listing with types
            $columns = Schema::connection($connection)->getColumns($table);

            foreach ($columns as $column) {
                $columnName = $column['name'];
                $columnType = strtolower($column['type_name'] ?? $column['type'] ?? '');

                // Check if this is a datetime-related column
                if ($this->isDateTimeColumn($columnType)) {
                    // Check if it's not already a Unix timestamp column
                    if (!Str::endsWith($columnName, $this->getTimestampColumnSuffix())) {
                        $datetimeColumns[] = $columnName;
                    }
                }
            }
        } catch (\Exception $e) {
            // If we can't query the database schema, fallback to casts
            $datetimeColumns = $this->getDatetimeColumnsFromCasts();
        }

        // Merge with casts to ensure we don't miss any
        $castedColumns = $this->getDatetimeColumnsFromCasts();
        $datetimeColumns = array_unique(array_merge($datetimeColumns, $castedColumns));

        return $datetimeColumns;
    }

    /**
     * Check if a column type is a datetime-related type.
     *
     * @param string $columnType
     * @return bool
     */
    protected function isDateTimeColumn(string $columnType): bool
    {
        $dateTimeTypes = [
            'datetime',
            'timestamp',
            'date',
            'time',
        ];

        foreach ($dateTimeTypes as $type) {
            if (Str::contains($columnType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get datetime columns from model casts.
     *
     * @return array
     */
    protected function getDatetimeColumnsFromCasts(): array
    {
        $datetimeColumns = [];
        $casts = $this->getCasts();

        foreach ($casts as $column => $castType) {
            if (in_array($castType, ['datetime', 'date', 'timestamp'])) {
                $datetimeColumns[] = $column;
            }
        }

        // Add default Laravel timestamp columns if they exist
        if ($this->usesTimestamps()) {
            $datetimeColumns[] = $this->getCreatedAtColumn();
            $datetimeColumns[] = $this->getUpdatedAtColumn();
        }

        // Add deleted_at if soft deletes are enabled
        if (method_exists($this, 'getDeletedAtColumn')) {
            $datetimeColumns[] = $this->getDeletedAtColumn();
        }

        return array_unique($datetimeColumns);
    }

    /**
     * Sync timestamp equivalents for all datetime columns.
     *
     * @param bool $skipIfExists Skip if timestamp column already has a value
     */
    protected function syncTimestampEquivalents(bool $skipIfExists = true): void
    {
        $columns = array_diff(
            $this->getTimestampEquivalentColumns(),
            $this->getExcludedTimestampColumns()
        );

        foreach ($columns as $column) {
            $timestampColumn = $column . $this->getTimestampColumnSuffix();

            // Check if the timestamp column exists in the table
            if (!$this->hasTimestampColumn($timestampColumn)) {
                continue;
            }

            // Skip if timestamp already exists and we're told to skip
            if ($skipIfExists && $this->getAttribute($timestampColumn) !== null) {
                continue;
            }

            // Get the datetime value - use getAttributeValue to bypass our custom getAttribute
            $datetimeValue = $this->getAttributeValue($column);

            if ($datetimeValue) {
                // Convert to Carbon if it's not already
                if (!$datetimeValue instanceof Carbon) {
                    try {
                        $datetimeValue = Carbon::parse($datetimeValue);
                    } catch (\Exception $e) {
                        // If parsing fails, skip this column
                        continue;
                    }
                }

                // Set the Unix timestamp
                $this->attributes[$timestampColumn] = $datetimeValue->timestamp;
            } else {
                // If the datetime is null, set timestamp to null
                $this->attributes[$timestampColumn] = null;
            }
        }
    }

    /**
     * Check if a timestamp column exists in the table.
     *
     * @param string $column
     * @return bool
     */
    protected function hasTimestampColumn(string $column): bool
    {
        static $cache = [];

        $table = $this->getTable();
        $cacheKey = $table . '.' . $column;

        if (!isset($cache[$cacheKey])) {
            try {
                $cache[$cacheKey] = Schema::connection($this->getConnectionName())
                    ->hasColumn($table, $column);
            } catch (\Exception $e) {
                $cache[$cacheKey] = false;
            }
        }

        return $cache[$cacheKey];
    }

    /**
     * Get the timestamp equivalent column name for a given datetime column.
     *
     * @param string $column
     * @return string
     */
    public function getTimestampEquivalentColumnName(string $column): string
    {
        return $column . $this->getTimestampColumnSuffix();
    }

    /**
     * Get the Unix timestamp value for a datetime column.
     *
     * @param string $column
     * @return int|null
     */
    public function getUnixTimestamp(string $column): ?int
    {
        $timestampColumn = $this->getTimestampEquivalentColumnName($column);
        return $this->getAttributeValue($timestampColumn);
    }
}
