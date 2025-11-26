# Laravel UnixTime

Automatically keep Unix timestamp mirrors (e.g. `created_at_unix`) for every datetime column in your Laravel models. The package detects relevant columns, creates the `_unix` columns for you, keeps them in sync any time the model is saved/retrieved, and even rewrites `orderBy`, `latest`, and `oldest` calls so they transparently use the faster integer columns.  

> Why it exists: while building large datasets I found the ORM constantly ordering and filtering on datetime columns, which meant string comparisons or expensive casting. Integer indexes (`*_unix`) make those same range/ordering queries consistently faster, so this package automates the boring part—adding, filling, and maintaining the mirrored integer fields.

## Features
- 🔁 Keeps Unix timestamp equivalents in sync for all datetime/timestamp/date columns.
- ⚙️ Artisan command that scans your app, adds the missing `_unix` columns, and can backfill legacy data.
- 🧱 Schema Builder macros to add/drop Unix columns directly in migrations.
- 🔍 Smart auto-detection of datetime fields from the schema (casts are only a fallback).
- 🎛️ Per-model configuration for suffixes, explicit include/exclude lists, and helper methods like `getUnixTimestamp()`.

## Installation

```bash
composer require fereydooni/unixtime
```

The service provider is auto-discovered, so no manual registration is required.

## Quick Start

1. **Use the trait on your model**
    ```php
    use Fereydooni\Unixtime\HasTimestampEquivalents;

    class Customer extends Model
    {
        use HasTimestampEquivalents;
    }
    ```

2. **Add the Unix columns**
   - **Existing tables:** run the sync command (see below).
   - **New migrations:** call `$table->timestampEquivalents();` at the end of the migration.

3. **Enjoy the new attributes**
    ```php
    $customer = Customer::find(1);
    echo $customer->created_at_unix;        // 1731341349
    echo $customer->last_order_date_unix;   // Also managed automatically
    ```

## Adding Columns for Existing Tables

Use the included Artisan command to scan all models that use the trait and create any missing `_unix` columns.

```bash
php artisan timestamp-equivalents:sync
```

Common options:

| Option | Description |
| --- | --- |
| `--model="App\\Models\\Customer"` | Limit the sync to one or more fully-qualified models (option may be repeated). |
| `--dry-run` | Show the pending changes without touching the database. |
| `--backfill` | After adding the columns, backfill existing rows using the database’s native Unix conversion functions. |

Example (specific model with backfill):
```bash
php artisan timestamp-equivalents:sync --model="App\\Models\\Customer" --backfill
```

## Adding Columns in New Migrations

The service provider registers Schema Builder macros so you can add Unix columns directly in migrations.

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('orders', function (Blueprint $table) {
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Adds confirmed_at_unix, created_at_unix, updated_at_unix, deleted_at_unix
    $table->timestampEquivalents();
});
```

Need only one column?

```php
$table->timestamp('verified_at')->nullable();
$table->timestampEquivalent('verified_at'); // Adds verified_at_unix (nullable, indexed)
```

Cleanup helper:

```php
$table->dropTimestampEquivalents(['verified_at', 'created_at']);
```

## Model Configuration

Everything works out-of-the-box, but you can fine-tune behavior with optional properties:

```php
class Customer extends Model
{
    use HasTimestampEquivalents;

    // Only manage these columns (otherwise they are auto-discovered from the DB)
    protected $timestampEquivalentColumns = ['created_at', 'last_order_date'];

    // Skip columns even if they look like datetimes
    protected $excludedTimestampColumns = ['date_of_birth'];

    // Customize suffix (default: '_unix')
    protected $timestampColumnSuffix = '_timestamp';
}
```

Helper methods:

```php
$customer->getUnixTimestamp('created_at'); // => 1731341349
$customer->hasTimestampColumn('created_at_unix'); // true/false
```

## Query Builder Enhancements

The trait swaps in a custom Eloquent builder so ordering helpers automatically use the `_unix` columns whenever they exist. This keeps your queries fast without changing call sites.

```php
// Uses created_at_unix behind the scenes
Customer::latest()->take(50)->get();

// orderByDesc('shipped_at') becomes shipped_at_unix when available
Order::orderByDesc('shipped_at')->paginate();
```

## Testing

Run the package test suite from your app:

```bash
php artisan test --filter=HasTimestampEquivalentsTest
```

## License

The Laravel UnixTime package is open-sourced software licensed under the [MIT license](LICENSE).
