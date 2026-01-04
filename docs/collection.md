# Collection

The `Collection` class handles CRUD (Create, Read, Update, Delete) operations on a single collection of data. It manages the database schema, validation, and provides a fluent interface for querying and manipulating records.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Collection.php`

## Description

Manages a single collection of data including its schema, properties, and all CRUD operations. Collections are the core building blocks of the datastore system, representing a logical grouping of similar records (similar to database tables).

## Key Properties

- `$namespace` (string) - The collection's namespace
- `$name` (string) - The collection's name (e.g., "subscribers")
- `$singular_name` (string) - The collection's singular name (e.g., "subscriber")
- `$props` (Prop[]) - Array of property definitions
- `$object` (string) - CRUD class that should extend Record
- `$use_meta_table` (bool) - Whether to use a custom meta table
- `$post_type` (string) - Connected WordPress post type (if applicable)
- `$capabillity` (string) - WordPress capability required to manage (default: 'manage_options')

## Main Methods

### Schema Management

#### `get_table_name()`

Returns the database table name for this collection.

**Returns:** `string` - The table name

**Example:**
```php
$table = $collection->get_table_name();
// Returns: wp_my_store_customers
```

#### `table_exists()`

Checks if the collection's table exists in the database.

**Returns:** `bool` - True if table exists

#### `create_table()`

Creates the database table for this collection.

**Example:**
```php
$collection->create_table();
```

#### `get_prop( $name )`

Retrieves a property definition.

**Parameters:**
- `$name` (string) - Property name

**Returns:** `Prop|null` - The property object or null

**Example:**
```php
$email_prop = $collection->get_prop('email');
```

### CRUD Operations

#### `create( $data )`

Creates a new record in the collection.

**Parameters:**
- `$data` (array) - Record data

**Returns:** `Record|int` - The created record or record ID

**Example:**
```php
$customer = $collection->create(array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
));
```

#### `get( $id )`

Retrieves a record by its ID.

**Parameters:**
- `$id` (int) - Record ID

**Returns:** `Record|null` - The record or null if not found

**Example:**
```php
$customer = $collection->get(123);
if ($customer) {
    echo $customer->get('name');
}
```

#### `update( $id, $data )`

Updates an existing record.

**Parameters:**
- `$id` (int) - Record ID
- `$data` (array) - Data to update

**Returns:** `bool|Record` - Success status or updated record

**Example:**
```php
$collection->update(123, array(
    'name' => 'Jane Doe',
));
```

#### `delete( $id )`

Deletes a record.

**Parameters:**
- `$id` (int) - Record ID

**Returns:** `bool` - True on success

**Example:**
```php
$collection->delete(123);
```

### Querying

#### `query( $args = array() )`

Creates a new query for this collection.

**Parameters:**
- `$args` (array) - Query arguments

**Returns:** `Query` - A Query object

**Example:**
```php
// Query with field filtering
$query = $collection->query(array(
    'email' => 'john@example.com',
    'status' => 'active',
    'per_page' => 10,
));

$results = $query->get_results();
```

#### `count( $args = array() )`

Counts records matching the query.

**Parameters:**
- `$args` (array) - Query arguments

**Returns:** `int` - Number of matching records

**Example:**
```php
$total = $collection->count(array(
    'status' => 'active',
));
```

### Aggregate Functions

#### `aggregate( $args )`

Performs aggregate operations (SUM, AVG, COUNT, MIN, MAX).

**Parameters:**
- `$args` (array) - Aggregate query arguments

**Returns:** `array|int|float` - Aggregate results

**Example:**
```php
// Get sum and count of amounts
$result = $collection->aggregate(array(
    'aggregate' => array(
        'amount' => array('SUM', 'COUNT'),
    ),
    'groupby' => 'customer_id',
));
```

### JOIN Operations

#### `join( $join_name, $args = array() )`

Performs a JOIN query with related collections.

**Parameters:**
- `$join_name` (string|array) - Name(s) of the JOIN(s) to perform
- `$args` (array) - Query arguments

**Returns:** `Query` - A Query object with JOIN applied

**Example:**
```php
// Define JOIN in collection config
'customers' => array(
    'joins' => array(
        'orders' => array(
            'collection' => 'my_store_orders',
            'on' => 'id',
            'foreign_key' => 'customer_id',
            'type' => 'LEFT',
        ),
    ),
)

// Use the JOIN
$query = $collection->join('orders', array(
    'aggregate' => array(
        'orders.total' => array('SUM'),
    ),
    'groupby' => 'id',
));
```

## Usage Examples

### Complete Collection Definition

```php
use Hizzle\Store\Store;

$store = new Store(
    'shop',
    array(
        'products' => array(
            'object'        => 'Product',
            'singular_name' => 'product',
            'capability'    => 'manage_shop',
            'props'         => array(
                'id'     => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Product ID',
                ),
                'name'   => array(
                    'type'        => 'VARCHAR',
                    'length'      => 255,
                    'nullable'    => false,
                    'description' => 'Product name',
                ),
                'price'  => array(
                    'type'        => 'DECIMAL',
                    'length'      => '10,2',
                    'default'     => '0.00',
                    'nullable'    => false,
                    'description' => 'Product price',
                ),
                'stock'  => array(
                    'type'        => 'INT',
                    'default'     => 0,
                    'nullable'    => false,
                    'description' => 'Stock quantity',
                ),
                'status' => array(
                    'type'        => 'VARCHAR',
                    'length'      => 20,
                    'default'     => 'draft',
                    'enum'        => array( 'draft', 'published', 'archived' ),
                    'description' => 'Product status',
                ),
            ),
            'keys'          => array(
                'primary' => array( 'id' ),
                'status'  => array( 'status' ),
            ),
            'labels'        => array(
                'name'          => __( 'Products', 'textdomain' ),
                'singular_name' => __( 'Product', 'textdomain' ),
                'add_new'       => __( 'Add New', 'textdomain' ),
                'add_new_item'  => __( 'Add New Product', 'textdomain' ),
            ),
        ),
    )
);

$products = $store->get_collection('products');
```

### Creating and Managing Records

```php
// Create a product
$product = $products->create(array(
    'name' => 'Widget',
    'price' => 29.99,
    'stock' => 100,
    'status' => 'published',
));

// Get product ID
$product_id = $product->get_id();

// Update the product
$products->update($product_id, array(
    'stock' => 95,
));

// Retrieve the product
$product = $products->get($product_id);
echo $product->get('name'); // Outputs: Widget

// Delete the product
$products->delete($product_id);
```

### Advanced Queries

```php
// Complex query with multiple field filters
$query = $products->query(array(
    'status' => 'published',
    'price_min' => 10,
    'stock' => 0,
    'stock_not' => 0, // Ensure stock is not zero
    'orderby' => array('price' => 'DESC'),
    'per_page' => 20,
    'page' => 1,
));

$results = $query->get_results();
foreach ($results as $product) {
    echo $product->get('name') . ' - $' . $product->get('price') . "\n";
}

// Get total count
$total = $query->get_total();
```

### Additional Collection Methods

#### Get Record by Property

```php
// Get ID by a specific property value
$product_id = $products->get_id_by_prop('sku', 'WIDGET-001');

if ($product_id) {
    $product = $products->get($product_id);
}
```

#### Check if Record Exists

```php
if ($products->exists($product_id)) {
    // Product exists
}
```

#### Bulk Delete Operations

```php
// Delete all records matching criteria
$deleted_count = $products->delete_where(array(
    'status' => 'draft',
    'stock' => 0,
));

echo "Deleted {$deleted_count} products";

// Delete all records (use with extreme caution!)
$products->delete_all();
```

### Working with Record Metadata

```php
// Enable meta table in collection config
'products' => array(
    'use_meta_table' => true,
    // ... other config
)

// Add metadata
$products->add_record_meta($product_id, 'featured', '1');
$products->add_record_meta($product_id, 'gallery', array('image1.jpg', 'image2.jpg'));

// Get metadata
$featured = $products->get_record_meta($product_id, 'featured', true);
$gallery = $products->get_record_meta($product_id, 'gallery', true);

// Update metadata
$products->update_record_meta($product_id, 'featured', '0');

// Delete metadata
$products->delete_record_meta($product_id, 'featured');

// Delete all metadata for a record
$products->delete_all_record_meta($product_id);

// Check if metadata exists
if ($products->record_meta_exists($product_id, 'featured')) {
    // Metadata exists
}

// Delete all metadata by key across all records
$products->delete_all_meta('old_field');
```

## Hooks and Filters

Collections provide numerous WordPress hooks:

- `{namespace}_{collection}_before_create` - Before creating a record
- `{namespace}_{collection}_after_create` - After creating a record
- `{namespace}_{collection}_before_update` - Before updating a record
- `{namespace}_{collection}_after_update` - After updating a record
- `{namespace}_{collection}_before_delete` - Before deleting a record
- `{namespace}_{collection}_after_delete` - After deleting a record

## See Also

- [Store](store.md) - Managing multiple collections
- [Record](record.md) - Working with individual records
- [Query](query.md) - Advanced querying
- [Prop](prop.md) - Property definitions
