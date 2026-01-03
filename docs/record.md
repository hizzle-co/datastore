# Record

The `Record` class handles CRUD operations on a single object/record within a collection. It provides an object-oriented interface for working with individual data records, tracking changes, and managing metadata.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Record.php`

## Description

Manages a single data record with built-in change tracking, validation, and metadata support. Records are typically created and managed through Collections, but can also be extended for custom object types.

## Key Properties

- `$id` (int) - Unique identifier for this record
- `$data` (array) - Core data for this object (name => value pairs)
- `$changes` (array) - Tracks changes made to the data
- `$object_read` (bool) - Whether the object has been loaded from database
- `$collection_name` (string) - The collection this record belongs to
- `$object_type` (string) - The object type name (for hooks)

## Main Methods

### Core CRUD Operations

#### `get_id()`

Returns the record's ID.

**Returns:** `int` - The record ID

**Example:**
```php
$id = $record->get_id();
```

#### `get( $prop )`

Retrieves a property value.

**Parameters:**
- `$prop` (string) - Property name

**Returns:** `mixed` - The property value

**Example:**
```php
$name = $record->get('name');
$email = $record->get('email');
```

#### `set( $prop, $value )`

Sets a property value (tracked as a change).

**Parameters:**
- `$prop` (string) - Property name
- `$value` (mixed) - Property value

**Example:**
```php
$record->set('name', 'Jane Doe');
$record->set('email', 'jane@example.com');
```

#### `save()`

Saves the record to the database (creates or updates).

**Returns:** `bool|int` - Record ID on success, false on failure

**Example:**
```php
$record->set('name', 'Updated Name');
$record->save();
```

#### `delete()`

Deletes the record from the database.

**Returns:** `bool` - True on success

**Example:**
```php
$record->delete();
```

### Data Access

#### `get_data()`

Returns all record data.

**Returns:** `array` - All data as associative array

**Example:**
```php
$data = $record->get_data();
// Returns: array('id' => 1, 'name' => 'John', 'email' => 'john@example.com')
```

#### `get_changes()`

Returns all changes made to the record.

**Returns:** `array` - Changed data

**Example:**
```php
$record->set('name', 'New Name');
$changes = $record->get_changes();
// Returns: array('name' => 'New Name')
```

#### `set_data( $data )`

Sets multiple properties at once.

**Parameters:**
- `$data` (array) - Property => value pairs

**Example:**
```php
$record->set_data(array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active',
));
```

#### `exists()`

Checks if the record exists in the database.

**Returns:** `bool` - True if record exists

**Example:**
```php
if ($record->exists()) {
    // Record is in database
}
```

### Metadata Operations

#### `get_meta( $key, $single = true )`

Retrieves metadata for the record.

**Parameters:**
- `$key` (string) - Meta key
- `$single` (bool) - Whether to return single value or array

**Returns:** `mixed` - Meta value(s)

**Example:**
```php
$custom_field = $record->get_meta('custom_field');
$all_tags = $record->get_meta('tags', false);
```

#### `update_meta( $key, $value )`

Updates or creates metadata.

**Parameters:**
- `$key` (string) - Meta key
- `$value` (mixed) - Meta value

**Example:**
```php
$record->update_meta('custom_field', 'value');
$record->update_meta('preferences', array('theme' => 'dark'));
```

#### `add_meta( $key, $value )`

Adds metadata (allows multiple values for same key).

**Parameters:**
- `$key` (string) - Meta key
- `$value` (mixed) - Meta value

**Example:**
```php
$record->add_meta('tag', 'featured');
$record->add_meta('tag', 'popular');
```

#### `delete_meta( $key, $value = '' )`

Deletes metadata.

**Parameters:**
- `$key` (string) - Meta key
- `$value` (mixed) - Optional specific value to delete

**Example:**
```php
// Delete all meta with this key
$record->delete_meta('custom_field');

// Delete specific value
$record->delete_meta('tag', 'featured');
```

### Validation

#### `validate( $data )`

Validates data against collection schema.

**Parameters:**
- `$data` (array) - Data to validate

**Returns:** `bool` - True if valid

**Throws:** `Store_Exception` - If validation fails

**Example:**
```php
try {
    $record->validate(array(
        'email' => 'invalid-email',
    ));
} catch (Store_Exception $e) {
    echo $e->getMessage();
}
```

## Usage Examples

### Creating a New Record

```php
// Get collection
$customers = Store::instance('shop')->get_collection('customers');

// Create new record
$customer = $customers->create(array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active',
));

// Or using Record directly (less common)
$record = new Record();
$record->set_data(array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
));
$record->save();
```

### Updating a Record

```php
// Load existing record
$customer = $customers->get(123);

// Update properties
$customer->set('email', 'newemail@example.com');
$customer->set('status', 'premium');

// Save changes
$customer->save();
```

### Working with Changes

```php
$customer = $customers->get(123);

// Make changes
$customer->set('name', 'Jane Doe');
$customer->set('email', 'jane@example.com');

// Check what changed
$changes = $customer->get_changes();
// Returns: array('name' => 'Jane Doe', 'email' => 'jane@example.com')

// Save only changed fields
$customer->save();
```

### Using Metadata

```php
$customer = $customers->get(123);

// Add custom metadata
$customer->update_meta('vip_level', 'gold');
$customer->update_meta('preferences', array(
    'newsletter' => true,
    'notifications' => false,
));

// Retrieve metadata
$vip = $customer->get_meta('vip_level');
$prefs = $customer->get_meta('preferences');

// Add multiple values for same key
$customer->add_meta('interest', 'electronics');
$customer->add_meta('interest', 'books');
$interests = $customer->get_meta('interest', false);
// Returns: array('electronics', 'books')
```

### Custom Record Classes

You can extend the Record class for custom behavior:

```php
namespace MyApp;

use Hizzle\Store\Record;

class Customer extends Record {
    
    /**
     * Get customer's full name
     */
    public function get_full_name() {
        return $this->get('first_name') . ' ' . $this->get('last_name');
    }
    
    /**
     * Check if customer is VIP
     */
    public function is_vip() {
        return $this->get_meta('vip_level') === 'gold';
    }
    
    /**
     * Send welcome email
     */
    public function send_welcome() {
        // Custom logic
        wp_mail($this->get('email'), 'Welcome!', 'Thanks for joining!');
    }
}

// Use custom class in collection definition
'customers' => array(
    'name' => 'customers',
    'object' => '\MyApp\Customer',
    // ... other config
)
```

### Conditional Operations

```php
$customer = $customers->get(123);

// Only update if record exists
if ($customer && $customer->exists()) {
    $customer->set('last_login', current_time('mysql'));
    $customer->save();
}

// Check for specific conditions
if ($customer->get('status') === 'active') {
    $customer->send_notification();
}
```

### Bulk Operations

```php
// Update multiple records
$query = $customers->query(array(
    'where' => array(
        array('status', '=', 'pending'),
    ),
));

foreach ($query->get_results() as $customer) {
    $customer->set('status', 'active');
    $customer->set('activated_at', current_time('mysql'));
    $customer->save();
}
```

## Property Access Patterns

The Record class supports multiple ways to access data:

```php
// Using get() method (recommended)
$name = $record->get('name');

// Using set() method (recommended)
$record->set('name', 'New Name');

// Checking if property exists
if (isset($record->data['name'])) {
    // Property exists
}
```

## Hooks and Filters

Records trigger various WordPress hooks during their lifecycle:

- `{namespace}_{collection}_before_save` - Before saving
- `{namespace}_{collection}_after_save` - After saving
- `{namespace}_{collection}_created` - After creating new record
- `{namespace}_{collection}_updated` - After updating existing record
- `{namespace}_{collection}_before_delete` - Before deletion
- `{namespace}_{collection}_after_delete` - After deletion
- `{namespace}_{collection}_{property}_changed` - When a property value changes

## Error Handling

```php
use Hizzle\Store\Store_Exception;

try {
    $customer = $customers->create(array(
        'email' => 'invalid', // Invalid email format
    ));
} catch (Store_Exception $e) {
    error_log('Failed to create customer: ' . $e->getMessage());
    // Handle error
}
```

## See Also

- [Collection](collection.md) - Managing collections of records
- [Query](query.md) - Querying records
- [Store_Exception](store-exception.md) - Exception handling
