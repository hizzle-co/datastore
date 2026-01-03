# Prop

The `Prop` class manages individual property definitions within a collection. It handles property metadata, validation rules, and formatting.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Prop.php`

## Description

Manages the definition and behavior of a single property (field) in a collection. Properties define the schema, validation rules, display labels, and other metadata for data fields.

## Key Properties

- `$collection` (string) - The collection this property belongs to
- `$name` (string) - The property name (e.g., "first_name")
- `$label` (string) - Human-readable label for the property
- `$description` (string) - Description of the property
- `$type` (string) - Data type (varchar, int, decimal, text, etc.)
- `$length` (string|int) - Length constraint for the data type
- `$default` (mixed) - Default value for the property
- `$nullable` (bool) - Whether NULL values are allowed
- `$enum` (array) - Array of allowed values
- `$auto_increment` (bool) - Whether to auto-increment (for int types)

## Data Types

Supported data types include:

- **varchar** - Variable-length string (requires length)
- **text** - Long text field
- **int** - Integer number
- **bigint** - Large integer
- **decimal** - Decimal number (requires length like "10,2")
- **float** - Floating-point number
- **datetime** - Date and time
- **date** - Date only
- **time** - Time only
- **bool** - Boolean (true/false)
- **json** - JSON data

## Usage Examples

### Basic Property Definition

```php
// In collection configuration
'props' => array(
    'email' => array(
        'type' => 'varchar',
        'length' => 255,
        'nullable' => false,
        'label' => 'Email Address',
        'description' => 'Customer email address',
    ),
)
```

### Integer Property

```php
'id' => array(
    'type' => 'int',
    'length' => 20,
    'nullable' => false,
    'auto_increment' => true,
    'label' => 'ID',
),
```

### Decimal Property

```php
'price' => array(
    'type' => 'decimal',
    'length' => '10,2', // 10 digits total, 2 after decimal
    'default' => '0.00',
    'nullable' => false,
    'label' => 'Price',
    'description' => 'Product price in USD',
),
```

### Text Property

```php
'description' => array(
    'type' => 'text',
    'nullable' => true,
    'label' => 'Description',
    'description' => 'Full product description',
),
```

### DateTime Property

```php
'created_at' => array(
    'type' => 'datetime',
    'nullable' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'label' => 'Created At',
),
```

### Enum Property

```php
'status' => array(
    'type' => 'varchar',
    'length' => 20,
    'default' => 'draft',
    'enum' => array('draft', 'published', 'archived'),
    'label' => 'Status',
    'description' => 'Publication status',
),
```

### Boolean Property

```php
'is_featured' => array(
    'type' => 'bool',
    'default' => false,
    'label' => 'Featured',
    'description' => 'Whether this item is featured',
),
```

### JSON Property

```php
'metadata' => array(
    'type' => 'json',
    'nullable' => true,
    'label' => 'Metadata',
    'description' => 'Additional data stored as JSON',
),
```

## Advanced Property Features

### Default Values

```php
'count' => array(
    'type' => 'int',
    'default' => 0,
),

'created_at' => array(
    'type' => 'datetime',
    'default' => 'CURRENT_TIMESTAMP',
),
```

### Nullable Fields

```php
'middle_name' => array(
    'type' => 'varchar',
    'length' => 100,
    'nullable' => true, // Allows NULL values
),
```

### Validation with Enum

```php
'priority' => array(
    'type' => 'varchar',
    'length' => 10,
    'enum' => array('low', 'medium', 'high', 'urgent'),
    'default' => 'medium',
),
```

### Custom Validation

Properties support custom validation through collection hooks:

```php
add_filter('shop_products_validate_price', function($valid, $value, $prop) {
    if ($value < 0) {
        throw new Store_Exception(
            'invalid_price',
            'Price cannot be negative',
            400
        );
    }
    return $valid;
}, 10, 3);
```

## Property Configuration Options

### Core Options

| Option | Type | Description |
|--------|------|-------------|
| `type` | string | Data type (required) |
| `length` | int/string | Field length constraint |
| `nullable` | bool | Allow NULL values (default: true) |
| `default` | mixed | Default value |
| `label` | string | Human-readable label |
| `description` | string | Property description |

### Validation Options

| Option | Type | Description |
|--------|------|-------------|
| `enum` | array | Allowed values |
| `min` | int/float | Minimum value (for numbers) |
| `max` | int/float | Maximum value (for numbers) |
| `pattern` | string | Regex pattern for validation |

### Database Options

| Option | Type | Description |
|--------|------|-------------|
| `auto_increment` | bool | Auto-increment integer |
| `unique` | bool | Unique constraint |
| `index` | bool | Add index for this field |

### Display Options

| Option | Type | Description |
|--------|------|-------------|
| `sortable` | bool | Can be sorted in list tables |
| `searchable` | bool | Included in search queries |
| `hidden` | bool | Hidden in REST API responses |

## Complete Property Examples

### Email Field

```php
'email' => array(
    'type' => 'varchar',
    'length' => 255,
    'nullable' => false,
    'unique' => true,
    'label' => 'Email Address',
    'description' => 'Customer email address',
    'searchable' => true,
    'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
),
```

### Price Field

```php
'price' => array(
    'type' => 'decimal',
    'length' => '10,2',
    'nullable' => false,
    'default' => '0.00',
    'min' => 0,
    'label' => 'Price',
    'description' => 'Product price in USD',
    'sortable' => true,
),
```

### Status Field with Enum

```php
'status' => array(
    'type' => 'varchar',
    'length' => 20,
    'nullable' => false,
    'default' => 'pending',
    'enum' => array('pending', 'active', 'inactive', 'deleted'),
    'label' => 'Status',
    'description' => 'Account status',
    'sortable' => true,
    'index' => true,
),
```

### Timestamp Fields

```php
'created_at' => array(
    'type' => 'datetime',
    'nullable' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'label' => 'Created',
    'sortable' => true,
),

'updated_at' => array(
    'type' => 'datetime',
    'nullable' => true,
    'label' => 'Last Updated',
    'sortable' => true,
),
```

## Property Methods

### `get_label()`

Returns the human-readable label.

```php
$prop = $collection->get_prop('email');
echo $prop->get_label(); // "Email Address"
```

### `get_default()`

Returns the default value.

```php
$default = $prop->get_default();
```

### `is_nullable()`

Checks if the property allows NULL values.

```php
if ($prop->is_nullable()) {
    // Allow NULL
}
```

### `validate( $value )`

Validates a value against the property rules.

```php
try {
    $prop->validate($value);
} catch (Store_Exception $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

## Property Groups

You can organize properties into logical groups for better UI organization:

```php
'props' => array(
    // Personal Information
    'first_name' => array(/* ... */),
    'last_name' => array(/* ... */),
    'email' => array(/* ... */),
    
    // Address Information
    'street' => array(/* ... */),
    'city' => array(/* ... */),
    'country' => array(/* ... */),
    
    // System Fields
    'created_at' => array(/* ... */),
    'updated_at' => array(/* ... */),
),
```

## Best Practices

1. **Always specify type and nullable**: Be explicit about data requirements
2. **Use appropriate lengths**: Don't over-allocate (e.g., varchar(1000) for a name)
3. **Set sensible defaults**: Provide default values where appropriate
4. **Use enums for fixed choices**: Better than free-text fields
5. **Add labels and descriptions**: Helps with auto-generated UIs
6. **Index frequently queried fields**: Improve query performance
7. **Make unique fields unique**: Use `unique` constraint for emails, usernames, etc.

## See Also

- [Collection](collection.md) - Using properties in collections
- [Record](record.md) - Working with property values
- [Query](query.md) - Querying by properties
