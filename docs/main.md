# Main

The `Main` class provides a simplified, WordPress-friendly wrapper around the Store API. It automatically handles error conversion to `WP_Error` and provides convenient static methods for common database operations.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Main.php`

## Description

The Main class abstracts interactions with the database, providing a cleaner API that's more familiar to WordPress developers. It automatically converts exceptions to `WP_Error` objects and provides a singleton pattern for accessing stores.

## Key Features

- **Singleton Pattern**: One instance per store namespace
- **Automatic Error Handling**: Converts Store_Exception to WP_Error
- **WordPress-Friendly**: Uses WP_Error instead of exceptions
- **Query Optimization**: Automatically optimizes queries based on return type
- **Convenience Methods**: Simplified API for common operations

## Main Methods

### `instance( $store_name )`

Gets or creates a Main instance for the specified store.

**Parameters:**
- `$store_name` (string) - The store namespace

**Returns:** `Main` - The Main instance

**Example:**
```php
use Hizzle\Store\Main;

$db = Main::instance('my_store');
```

### `init_store( $collections )`

Initializes a store with collections.

**Parameters:**
- `$collections` (array) - Array of collection configurations

**Returns:** `Store` - The initialized store

**Example:**
```php
$db = Main::instance('my_store');

$store = $db->init_store(
    array(
        'orders' => array(
            'object'        => 'Order',
            'singular_name' => 'order',
            'props'         => array(
                'id' => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Order ID',
                ),
                // ... more props
            ),
            'keys'          => array(
                'primary' => array( 'id' ),
            ),
        ),
    )
);
```

### `get( $record_id, $collection_name )`

Retrieves a record from the database.

**Parameters:**
- `$record_id` (Record|int) - The record ID or Record object
- `$collection_name` (string) - The collection name

**Returns:** `Record|WP_Error` - Record object if found, WP_Error on failure

**Example:**
```php
$db = Main::instance('my_store');
$order = $db->get('orders', 123);

if (is_wp_error($order)) {
    error_log($order->get_error_message());
} else {
    echo $order->get('total');
}
```

### `get_id_by_prop( $prop, $value, $collection_name )`

Retrieves an ID by a given property value.

**Parameters:**
- `$prop` (string) - The property to search by
- `$value` (int|string|float) - The value to search for
- `$collection_name` (string) - The collection name

**Returns:** `int|false` - The ID if found, false otherwise

**Example:**
```php
$db = Main::instance('my_store');
$order_id = $db->get_id_by_prop('order_number', 'ORD-12345', 'orders');

if ($order_id) {
    $order = $db->get('orders', $order_id);
}
```

### `delete_where( $where, $collection_name )`

Deletes all records matching the criteria.

**Parameters:**
- `$where` (array) - Array of field => value pairs
- `$collection_name` (string) - The collection name

**Returns:** `int|false` - Number of rows deleted, or false on error

**Example:**
```php
$db = Main::instance('my_store');

// Delete all pending orders older than 30 days
$deleted = $db->delete_where(
    array(
        'status' => 'pending',
        'created_at_before' => date('Y-m-d', strtotime('-30 days')),
    ),
    'orders'
);

echo "Deleted {$deleted} orders";
```

### `delete_all( $collection_name )`

Deletes all records from a collection.

**Parameters:**
- `$collection_name` (string) - The collection name

**Returns:** `int|false` - Number of rows deleted, or false on error

**Example:**
```php
$db = Main::instance('my_store');

// Use with extreme caution!
$deleted = $db->delete_all('temporary_data');
```

### `query( $collection_name, $args, $to_return )`

Queries records from the database.

**Parameters:**
- `$collection_name` (string) - The collection name
- `$args` (array) - Query arguments
- `$to_return` (string) - Return type: 'results', 'count', 'aggregate', or 'query'

**Returns:** `int|array|Record[]|Query|WP_Error` - Results based on return type

**Example:**
```php
$db = Main::instance('my_store');

// Get results (default)
$orders = $db->query('orders', array(
    'status' => 'completed',
    'per_page' => 10,
));

// Get count
$count = $db->query('orders', array(
    'status' => 'completed',
), 'count');

// Get aggregate results
$stats = $db->query('orders', array(
    'aggregate' => array(
        'total' => array('SUM', 'AVG'),
    ),
    'groupby' => 'status',
), 'aggregate');

// Get Query object for more control
$query = $db->query('orders', array(
    'status' => 'completed',
), 'query');

$orders = $query->get_results();
$total = $query->get_total();
```

### Metadata Methods

#### `get_record_meta( $record_id, $meta_key, $single, $collection_name )`

Retrieves metadata for a record.

**Example:**
```php
$db = Main::instance('my_store');
$notes = $db->get_record_meta(123, 'customer_notes', true, 'orders');
```

#### `add_record_meta( $record_id, $meta_key, $meta_value, $unique, $collection_name )`

Adds metadata for a record.

**Example:**
```php
$db = Main::instance('my_store');
$db->add_record_meta(123, 'gift_wrap', '1', false, 'orders');
```

#### `update_record_meta( $record_id, $meta_key, $meta_value, $prev_value, $collection_name )`

Updates metadata for a record.

**Example:**
```php
$db = Main::instance('my_store');
$db->update_record_meta(123, 'priority', 'high', '', 'orders');
```

#### `delete_record_meta( $record_id, $meta_key, $meta_value, $collection_name )`

Deletes metadata for a record.

**Example:**
```php
$db = Main::instance('my_store');
$db->delete_record_meta(123, 'temporary_flag', '', 'orders');
```

#### `delete_all_record_meta( $record_id, $collection_name )`

Deletes all metadata for a record.

**Example:**
```php
$db = Main::instance('my_store');
$db->delete_all_record_meta(123, 'orders');
```

#### `delete_all_meta_by_key( $meta_key, $collection_name )`

Deletes all metadata with a specific key across all records.

**Example:**
```php
$db = Main::instance('my_store');
$db->delete_all_meta_by_key('deprecated_field', 'orders');
```

#### `record_meta_exists( $record_id, $meta_key, $collection_name )`

Checks if metadata exists for a record.

**Example:**
```php
$db = Main::instance('my_store');

if ($db->record_meta_exists(123, 'gift_wrap', 'orders')) {
    // Apply gift wrap charges
}
```

## Usage Examples

### Complete Example

```php
use Hizzle\Store\Main;

// Get database instance
$db = Main::instance('shop');

// Initialize store (usually done once during plugin activation)
$db->init_store(
    array(
        'orders' => array(
            'object'        => 'Order',
            'singular_name' => 'order',
            'props'         => array(
                'id'          => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Order ID',
                ),
                'customer_id' => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'description' => 'Customer ID',
                ),
                'total'       => array(
                    'type'        => 'DECIMAL',
                    'length'      => '10,2',
                    'nullable'    => false,
                    'description' => 'Order total',
                ),
                'status'      => array(
                    'type'        => 'VARCHAR',
                    'length'      => 20,
                    'default'     => 'pending',
                    'description' => 'Order status',
                ),
            ),
            'keys'          => array(
                'primary'     => array( 'id' ),
                'customer_id' => array( 'customer_id' ),
                'status'      => array( 'status' ),
            ),
        ),
    )
);

// Create order via collection
$collection = Store::instance('shop')->get('orders');
$order = $collection->create(array(
    'customer_id' => 456,
    'total' => 99.99,
    'status' => 'pending',
));

$order_id = $order->get_id();

// Get order
$order = $db->get('orders', $order_id);

if (is_wp_error($order)) {
    wp_die($order->get_error_message());
}

// Update order
$order->set('status', 'completed');
$order->save();

// Add metadata
$db->add_record_meta($order_id, 'shipping_carrier', 'UPS', false, 'orders');

// Query orders
$completed_orders = $db->query('orders', array(
    'status' => 'completed',
    'customer_id' => 456,
    'orderby' => 'id',
    'order' => 'DESC',
));

foreach ($completed_orders as $order) {
    echo "Order #{$order->get('id')}: ${$order->get('total')}\n";
}

// Get order statistics
$stats = $db->query('orders', array(
    'aggregate' => array(
        'total' => array('SUM', 'AVG', 'COUNT'),
    ),
    'groupby' => 'status',
), 'aggregate');

foreach ($stats as $stat) {
    echo "{$stat['status']}: Total=${$stat['total_sum']}, Avg=${$stat['total_avg']}\n";
}

// Delete old pending orders
$deleted = $db->delete_where(
    array(
        'status' => 'pending',
        'created_at_before' => date('Y-m-d', strtotime('-7 days')),
    ),
    'orders'
);
```

### Error Handling Pattern

```php
$db = Main::instance('shop');

// Get a record
$order = $db->get('orders', $order_id);

if (is_wp_error($order)) {
    // Log error
    error_log('Failed to get order: ' . $order->get_error_message());
    
    // Show user-friendly message
    wp_send_json_error(array(
        'message' => __('Order not found.', 'textdomain'),
    ));
    
    return;
}

// Work with the order
echo $order->get('total');
```

### Query Optimization

```php
$db = Main::instance('shop');

// When you only need the count, use 'count' return type
// This skips fetching records and only returns the count
$total_orders = $db->query('orders', array(
    'status' => 'completed',
), 'count');

// When you only need results, the query is optimized to skip the count query
$orders = $db->query('orders', array(
    'status' => 'pending',
    'per_page' => 10,
)); // Returns results only, skips total count

// When you need both results and total, use 'query' return type
$query = $db->query('orders', array(
    'status' => 'pending',
    'per_page' => 10,
), 'query');

$orders = $query->get_results();
$total = $query->get_total();
```

## Benefits Over Direct Store/Collection Usage

1. **Automatic Error Handling**: No need for try/catch blocks
2. **WP_Error Integration**: Works seamlessly with WordPress error handling
3. **Singleton Pattern**: Consistent instance across your codebase
4. **Simplified API**: Fewer method calls for common operations
5. **Query Optimization**: Automatically optimizes based on what you need

## See Also

- [Store](store.md) - Direct store management
- [Collection](collection.md) - Collection operations
- [Query](query.md) - Query builder details
- [Record](record.md) - Working with records
