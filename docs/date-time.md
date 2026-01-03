# Date_Time

The `Date_Time` class is an enhanced DateTime implementation that adds WordPress timezone support and convenient formatting methods.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Date_Time.php`  
**Extends:** `DateTime`

## Description

A wrapper for PHP's DateTime class that adds support for GMT/UTC offset when a timezone is absent, integrates with WordPress timezone settings, and provides convenient formatting methods for WordPress applications.

## Key Features

- WordPress timezone integration
- Offset timestamp support
- Localized date formatting
- Context-aware display formatting
- ISO 8601 compliance

## Usage Examples

### Creating Date_Time Objects

```php
use Hizzle\Store\Date_Time;

// From string
$date = new Date_Time('2024-01-15 10:30:00');

// From timestamp
$date = new Date_Time('@' . time());

// Current time
$date = new Date_Time('now');

// Relative dates
$date = new Date_Time('+1 day');
$date = new Date_Time('-1 week');
$date = new Date_Time('next Monday');
```

### Basic Methods

#### `__toString()`

Returns ISO 8601 formatted date string.

```php
$date = new Date_Time('2024-01-15 10:30:00');
echo $date; // Outputs: 2024-01-15T10:30:00+00:00
```

#### `getOffsetTimestamp()`

Gets the timestamp with WordPress timezone offset added or subtracted.

```php
$date = new Date_Time('2024-01-15 10:30:00');
$offset_timestamp = $date->getOffsetTimestamp();
```

### Formatting Methods

#### `utc( $format = 'Y-m-d H:i:s' )`

Format date based on UTC timestamp.

```php
$date = new Date_Time('2024-01-15 10:30:00');

echo $date->utc(); // Outputs: 2024-01-15 10:30:00
echo $date->utc('Y-m-d'); // Outputs: 2024-01-15
echo $date->utc('H:i:s'); // Outputs: 10:30:00
```

#### `date( $format = 'Y-m-d H:i:s' )`

Format date based on offset timestamp (WordPress timezone).

```php
$date = new Date_Time('2024-01-15 10:30:00');

echo $date->date(); // Outputs: 2024-01-15 10:30:00 (with timezone offset)
echo $date->date('F j, Y'); // Outputs: January 15, 2024
```

#### `date_i18n( $format = null, $gmt = false )`

Returns a localized date using WordPress's `date_i18n()` function.

```php
$date = new Date_Time('2024-01-15 10:30:00');

// Use WordPress date format setting
echo $date->date_i18n(); // Outputs: January 15, 2024 (based on WordPress settings)

// Custom format
echo $date->date_i18n('F j, Y'); // Outputs: January 15, 2024

// GMT/UTC time
echo $date->date_i18n(null, true); // Outputs: date in GMT
```

#### `context( $context = 'view' )`

Formats a date for display or storage based on context.

**Contexts:**
- `view` - Human-readable format with date and time
- `view_day` - Date only, no time
- `db` - Database format (Y-m-d H:i:s)
- `raw` - ISO 8601 format

```php
$date = new Date_Time('2024-01-15 10:30:00');

// View context (for display)
echo $date->context('view'); // Outputs: January 15, 2024 @ 10:30 am

// Day view (date only)
echo $date->context('view_day'); // Outputs: January 15, 2024

// Database context
echo $date->context('db'); // Outputs: 2024-01-15 10:30:00

// Raw context
echo $date->context('raw'); // Outputs: 2024-01-15T10:30:00+00:00
```

## Practical Examples

### Storing Dates in Database

```php
use Hizzle\Store\Date_Time;

$customer = $collection->get($id);

// Save current date/time
$customer->set('created_at', (new Date_Time('now'))->context('db'));
$customer->save();

// Save specific date
$date = new Date_Time('2024-01-15 10:30:00');
$customer->set('registered_at', $date->context('db'));
$customer->save();
```

### Displaying Dates

```php
// Get date from record
$customer = $collection->get($id);
$created_raw = $customer->get('created_at');

// Convert to Date_Time
$created = new Date_Time($created_raw);

// Display in different formats
echo "Created: " . $created->context('view'); // January 15, 2024 @ 10:30 am
echo "Date: " . $created->context('view_day'); // January 15, 2024
echo "Raw: " . $created; // 2024-01-15T10:30:00+00:00
```

### Date Comparisons

```php
$date1 = new Date_Time('2024-01-15');
$date2 = new Date_Time('2024-01-20');

if ($date1 < $date2) {
    echo "Date 1 is earlier";
}

if ($date1 == $date2) {
    echo "Dates are equal";
}

// Get difference
$diff = $date1->diff($date2);
echo $diff->days . ' days difference';
```

### Date Calculations

```php
// Add time
$date = new Date_Time('2024-01-15');
$date->modify('+1 day');
echo $date->date('Y-m-d'); // 2024-01-16

$date->modify('+1 week');
echo $date->date('Y-m-d'); // 2024-01-23

// Subtract time
$date->modify('-1 month');
echo $date->date('Y-m-d'); // 2023-12-23
```

### Relative Date Display

```php
function get_relative_time($date_string) {
    $date = new Date_Time($date_string);
    $now = new Date_Time('now');
    
    $diff = $now->getTimestamp() - $date->getTimestamp();
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return floor($diff / 86400) . ' days ago';
    }
}

echo get_relative_time('2024-01-15 10:30:00');
```

### Working with Timezones

```php
use DateTimeZone;

// Create with specific timezone
$date = new Date_Time('2024-01-15 10:30:00', new DateTimeZone('America/New_York'));

// Convert timezone
$date->setTimezone(new DateTimeZone('UTC'));
echo $date->format('Y-m-d H:i:s'); // UTC time

// Get timezone offset
$offset = $date->getOffset();
echo "Offset: " . ($offset / 3600) . " hours";
```

### Date Ranges

```php
function get_records_in_date_range($collection, $start, $end) {
    $start_date = new Date_Time($start);
    $end_date = new Date_Time($end);
    
    return $collection->query(array(
        'where' => array(
            array('created_at', '>=', $start_date->context('db')),
            array('created_at', '<=', $end_date->context('db')),
        ),
    ))->get_results();
}

// Get records from last 7 days
$start = new Date_Time('-7 days');
$end = new Date_Time('now');
$records = get_records_in_date_range($collection, $start, $end);
```

### Midnight Detection

```php
$date = new Date_Time('2024-01-15 00:00:00');

if ($date->format('H:i:s') === '00:00:00') {
    // Time is midnight, show only date
    echo $date->context('view_day'); // January 15, 2024
} else {
    // Show date and time
    echo $date->context('view'); // January 15, 2024 @ 10:30 am
}
```

### Format Reference

Common format strings:

```php
$date = new Date_Time('2024-01-15 10:30:00');

// Date formats
echo $date->format('Y-m-d');           // 2024-01-15
echo $date->format('m/d/Y');           // 01/15/2024
echo $date->format('F j, Y');          // January 15, 2024
echo $date->format('l, F j, Y');       // Monday, January 15, 2024

// Time formats
echo $date->format('H:i:s');           // 10:30:00
echo $date->format('g:i a');           // 10:30 am
echo $date->format('h:i:s A');         // 10:30:00 AM

// Combined
echo $date->format('Y-m-d H:i:s');     // 2024-01-15 10:30:00
echo $date->format('c');               // ISO 8601: 2024-01-15T10:30:00+00:00
echo $date->format('r');               // RFC 2822: Mon, 15 Jan 2024 10:30:00 +0000
```

## WordPress Integration

### Using WordPress Date/Time Settings

```php
$date = new Date_Time('2024-01-15 10:30:00');

// Format using WordPress settings
$date_format = get_option('date_format', 'F j, Y');
$time_format = get_option('time_format', 'g:i a');

echo $date->date_i18n($date_format); // Uses WP date format
echo $date->date_i18n($time_format); // Uses WP time format
echo $date->date_i18n($date_format . ' ' . $time_format); // Both
```

### Localization

The `date_i18n()` method automatically handles localization:

```php
// If WordPress is in French
$date = new Date_Time('2024-01-15 10:30:00');
echo $date->date_i18n('F j, Y'); // "janvier 15, 2024"

// If WordPress is in Spanish
echo $date->date_i18n('F j, Y'); // "enero 15, 2024"
```

## Best Practices

1. **Use `context()` for display** - It handles midnight detection automatically
2. **Use `context('db')` for storage** - Consistent database format
3. **Use `date_i18n()` for localization** - Respects WordPress language settings
4. **Store dates in UTC** - Convert to local time only for display
5. **Use relative formats when appropriate** - "2 hours ago" is often more useful

## See Also

- [Record](record.md) - Storing dates in records
- [Collection](collection.md) - Date field definitions
- [Prop](prop.md) - DateTime property types
