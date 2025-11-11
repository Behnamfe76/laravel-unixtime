# Quick Start Guide - HasTimestampEquivalents

## What This Does

Automatically creates and maintains Unix timestamp columns (e.g., `created_at_unix`) for all your datetime columns (e.g., `created_at`).

## Setup (3 Steps)

### 1. Add Trait to Your Model

```php
use App\Traits\HasTimestampEquivalents;

class Customer extends Model
{
    use HasTimestampEquivalents;
}
```

### 2. Add Database Columns

Run this command:

```bash
php artisan timestamp-equivalents:sync --model="Fereydooni\Shopping\app\Models\Customer" --backfill
```

Or add to your migration:

```php
Schema::table('customers', function (Blueprint $table) {
    $table->timestampEquivalents();
});
```

### 3. Use It!

```php
$customer = Customer::find(1);
echo $customer->created_at_unix;      // 1731341349
echo $customer->last_order_date_unix; // 1731341349
```

## That's It!

Everything is automatic. No manual updates needed.

## Optional Configuration

```php
class Customer extends Model
{
    use HasTimestampEquivalents;
    
    // Exclude specific columns
    protected $excludedTimestampColumns = ['date_of_birth'];
}
```

## More Info

See `app/Traits/HasTimestampEquivalents_README.md` for complete documentation.
