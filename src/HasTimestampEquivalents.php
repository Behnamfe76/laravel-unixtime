<?php

namespace Fereydooni\Unixtime;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

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
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder {
            /**
             * Add a basic where clause to the query.
             *
             * @param  \Closure|string|array  $column
             * @param  mixed  $operator
             * @param  mixed  $value
             * @param  string  $boolean
             * @return $this
             */
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::where($column, $operator, $value, $boolean);
            }

            /**
             * Add a "where between" statement to the query.
             *
             * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
             * @param  iterable  $values
             * @param  string  $boolean
             * @param  bool  $not
             * @return $this
             */
            public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the values to timestamps
                    if ($column !== $originalColumn) {
                        $values = array_map(function ($value) {
                            return $this->convertToUnixTimestamp($value);
                        }, is_array($values) ? $values : iterator_to_array($values));
                    }
                }

                return parent::whereBetween($column, $values, $boolean, $not);
            }

            /**
             * Add a "where not between" statement to the query.
             *
             * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
             * @param  iterable  $values
             * @param  string  $boolean
             * @return $this
             */
            public function whereNotBetween($column, iterable $values, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the values to timestamps
                    if ($column !== $originalColumn) {
                        $values = array_map(function ($value) {
                            return $this->convertToUnixTimestamp($value);
                        }, is_array($values) ? $values : iterator_to_array($values));
                    }
                }

                return parent::whereNotBetween($column, $values, $boolean);
            }

            /**
             * Add a "where date" statement to the query.
             *
             * @param  string  $column
             * @param  string  $operator
             * @param  \DateTimeInterface|string|null  $value
             * @param  string  $boolean
             * @return $this
             */
            public function whereDate($column, $operator, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::whereDate($column, $operator, $value, $boolean);
            }

            /**
             * Add a "where time" statement to the query.
             *
             * @param  string  $column
             * @param  string  $operator
             * @param  \DateTimeInterface|string|null  $value
             * @param  string  $boolean
             * @return $this
             */
            public function whereTime($column, $operator, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::whereTime($column, $operator, $value, $boolean);
            }

            /**
             * Add a "where day" statement to the query.
             *
             * @param  string  $column
             * @param  string  $operator
             * @param  \DateTimeInterface|string|int|null  $value
             * @param  string  $boolean
             * @return $this
             */
            public function whereDay($column, $operator, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::whereDay($column, $operator, $value, $boolean);
            }

            /**
             * Add a "where month" statement to the query.
             *
             * @param  string  $column
             * @param  string  $operator
             * @param  \DateTimeInterface|string|int|null  $value
             * @param  string  $boolean
             * @return $this
             */
            public function whereMonth($column, $operator, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::whereMonth($column, $operator, $value, $boolean);
            }

            /**
             * Add a "where year" statement to the query.
             *
             * @param  string  $column
             * @param  string  $operator
             * @param  \DateTimeInterface|string|int|null  $value
             * @param  string  $boolean
             * @return $this
             */
            public function whereYear($column, $operator, $value = null, $boolean = 'and')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $originalColumn = $column;
                    $column = $this->convertToUnixTimestampColumn($column);

                    // If column was converted, also convert the value to timestamp
                    if ($column !== $originalColumn && $value !== null) {
                        $value = $this->convertToUnixTimestamp($value);
                    }
                }

                return parent::whereYear($column, $operator, $value, $boolean);
            }

            /**
             * Add an "order by" clause to the query.
             *
             * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string  $column
             * @param  string  $direction
             * @return $this
             */
            public function orderBy($column, $direction = 'asc')
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $column = $this->convertToUnixTimestampColumn($column);
                }

                return parent::orderBy($column, $direction);
            }

            /**
             * Add a descending "order by" clause to the query.
             *
             * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string  $column
             * @return $this
             */
            public function orderByDesc($column)
            {
                // Only process if it's a simple column name string
                if (is_string($column) && $this->model) {
                    $column = $this->convertToUnixTimestampColumn($column);
                }

                return parent::orderByDesc($column);
            }

            /**
             * Add an "order by" clause for a timestamp to the query.
             *
             * @param  string  $column
             * @return $this
             */
            public function latest($column = null)
            {
                $column = $column ?? $this->model->getCreatedAtColumn();

                if (is_string($column) && $this->model) {
                    $column = $this->convertToUnixTimestampColumn($column);
                }

                return parent::latest($column);
            }

            /**
             * Add an "order by" clause for a timestamp to the query.
             *
             * @param  string  $column
             * @return $this
             */
            public function oldest($column = null)
            {
                $column = $column ?? $this->model->getCreatedAtColumn();

                if (is_string($column) && $this->model) {
                    $column = $this->convertToUnixTimestampColumn($column);
                }

                return parent::oldest($column);
            }

            /**
             * Convert datetime column to Unix timestamp column if applicable.
             *
             * @param  string  $column
             * @return string
             */
            protected function convertToUnixTimestampColumn(string $column): string
            {
                // Check if this column has a Unix timestamp equivalent
                if (method_exists($this->model, 'getTimestampEquivalentColumns')) {
                    $datetimeColumns = array_diff(
                        $this->model->getTimestampEquivalentColumns(),
                        $this->model->getExcludedTimestampColumns()
                    );

                    if (in_array($column, $datetimeColumns, true)) {
                        $unixColumn = $this->model->getTimestampEquivalentColumnName($column);

                        // Verify the Unix column exists in the table
                        if ($this->model->hasTimestampColumn($unixColumn)) {
                            return $unixColumn;
                        }
                    }
                }

                return $column;
            }

            /**
             * Convert a datetime value to Unix timestamp.
             *
             * @param  mixed  $value
             * @return int|null
             */
            protected function convertToUnixTimestamp($value)
            {
                // If already a timestamp (integer), return as is
                if (is_int($value)) {
                    return $value;
                }

                // If null, return null
                if ($value === null) {
                    return null;
                }

                try {
                    // If it's a Carbon instance or DateTime, get timestamp
                    if ($value instanceof \DateTimeInterface) {
                        return $value->getTimestamp();
                    }

                    // If it's a string, parse it to Carbon and get timestamp
                    if (is_string($value)) {
                        return \Carbon\Carbon::parse($value)->timestamp;
                    }
                } catch (\Exception $e) {
                    // If parsing fails, return the value as is
                    return $value;
                }

                return $value;
            }
        };
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
        $table = $this->getTable();
        $connection = $this->getConnectionName() ?: config('database.default');
        $cacheKey = 'system.schema.' . $connection . '.' . $table . '.datetime_columns';

        $cached = cache()->remember($cacheKey, now()->addDays(7), function () use ($table, $connection) {
            $datetimeColumns = [];

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

            return serialize($datetimeColumns);
        });

        return unserialize($cached);
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
    public function hasTimestampColumn(string $column): bool
    {
        $tableColumns = $this->getCachedTableColumns();
        return in_array($column, $tableColumns, true);
    }

    /**
     * Get cached table columns.
     *
     * @return array
     */
    protected function getCachedTableColumns(): array
    {
        $table = $this->getTable();
        $connection = $this->getConnectionName() ?: config('database.default');
        $cacheKey = 'system.schema.' . $connection . '.' . $table . '.columns';

        $cached = cache()->remember($cacheKey, now()->addDays(7), function () use ($table, $connection) {
            try {
                $columns = Schema::connection($connection)->getColumnListing($table);
                return serialize($columns);
            } catch (\Exception $e) {
                return serialize([]);
            }
        });

        return unserialize($cached);
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
