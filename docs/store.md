# Store

The `Store` class is the main entry point for managing multiple collections of data. A store contains an array of [collections](collection.md), which in turn contain [records](record.md) with [properties](prop.md).

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Store.php`

## Description

Handles CRUD operations on an array of collections. The Store class acts as a registry and manager for multiple data collections, providing a centralized way to initialize and access different collections within your application.

## Key Properties

- `$namespace` (string) - Namespace of this store's instance
- `$collections` (Collection[]) - A list of collections managed by this store

## Main Methods

### `__construct( $namespace, $collections )`

Creates a new store instance.

**Parameters:**
- `$namespace` (string) - Namespace of this store's instance
- `$collections` (array) - A list of collections to initialize

**Example:**
```php
$store = new Store(
    'my_store',
    array(
        'payments' => array(
            // This object must extend Hizzle\Store\Record
            'object'        => 'Payment',
            'singular_name' => 'payment',
            'props'         => array(
                'id'                  => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Payment ID',
                ),
                'customer_id'       => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'description' => 'Customer ID',
                ),
                /* ... */
            ),
            'joins'         => array(
                'customers' => array(
                    'collection' => 'my_store_customers',
                    'on'         => 'customer_id',
                    'type'       => 'LEFT',
                ),
                'plans'     => array(
                    'collection' => 'my_store_plans',
                    'on'         => 'plan_id',
                    'type'       => 'LEFT',
                ),
                'products'  => array(
                    'collection' => 'my_store_products',
                    // We are assuming that the above payments schema has
                    // No 'plan_id' property, so we join via plans table
                    // Which is already joined above
                    'on'         => 'plans.product_id',
                    'type'       => 'LEFT',
                ),
            ),
            'keys'          => array(
                'primary'             => array( 'id' ),
                'customer_id'         => array( 'customer_id' ),
                'subscription_id'     => array( 'subscription_id' ),
                'status'              => array( 'status' ),
                'date_created_status' => array( 'date_created', 'status' ),
                'unique'              => array( 'uuid', 'transaction_id' ),
            ),
            'labels'        => array(
                'name'          => __( 'Payments', 'textdomain' ),
                'singular_name' => __( 'Payment', 'textdomain' ),
                'add_new'       => __( 'Add New', 'textdomain' ),
                'add_new_item'  => __( 'Add New Payment', 'textdomain' ),
                'edit_item'     => __( 'Overview', 'textdomain' ),
                'new_item'      => __( 'Add Payment', 'textdomain' ),
                'view_item'     => __( 'View Payment', 'textdomain' ),
                'view_items'    => __( 'View Payments', 'textdomain' ),
                'search_items'  => __( 'Search payments', 'textdomain' ),
                'not_found'     => __( 'No payments found.', 'textdomain' ),
                'import'        => __( 'Import Payments', 'textdomain' ),
            ),
        ),
        'customers' => array( /* ... */ ),
        /** Other collections **/
    )
);

```

### `init( $namespace, $collections = array() )` (static)

Initializes a new store or loads an existing store. This is the recommended way to create or retrieve a store.

**Parameters:**
- `$namespace` (string) - Namespace of the store
- `$collections` (array) - A list of collections (optional if store already exists)

**Returns:** `Store` - The store instance

**Example:**
```php
use Hizzle\Store\Store;

$store = Store::init('my_store', array(
    'customers' => array(
        'object'        => 'Customer',
        'singular_name' => 'customer',
        'props'         => array(
            'id'    => array(
                'type'        => 'BIGINT',
                'length'      => 20,
                'nullable'    => false,
                'extra'       => 'AUTO_INCREMENT',
                'description' => 'Customer ID',
            ),
            'name'  => array(
                'type'        => 'VARCHAR',
                'length'      => 255,
                'nullable'    => false,
                'description' => 'Customer name',
            ),
            'email' => array(
                'type'        => 'VARCHAR',
                'length'      => 255,
                'nullable'    => false,
                'description' => 'Customer email',
            ),
        ),
        'keys'          => array(
            'primary' => array( 'id' ),
            'email'   => array( 'email' ),
        ),
        'labels'        => array(
            'name'          => __( 'Customers', 'textdomain' ),
            'singular_name' => __( 'Customer', 'textdomain' ),
        ),
    ),
));
```

### `instance( $namespace )` (static)

Retrieves a store by its namespace.

**Parameters:**
- `$namespace` (string) - Namespace of the store

**Returns:** `Store|null` - The store instance or null if not found

**Example:**
```php
$store = Store::instance('my_store');
```

### `get_collection( $name )`

Retrieves a specific collection from the store.

**Parameters:**
- `$name` (string) - Name of the collection

**Returns:** `Collection|null` - The collection or null if not found

**Example:**
```php
$collection = $store->get_collection('customers');
```

### `get_collections()`

Retrieves all collections in the store.

**Returns:** `Collection[]` - Array of all collections

**Example:**
```php
$collections = $store->get_collections();
foreach ($collections as $collection) {
    // Work with each collection
}
```

### `hook_prefix( $suffix = '' )`

Generates a hook prefix for WordPress actions and filters.

**Parameters:**
- `$suffix` (string) - Optional suffix to append

**Returns:** `string` - The hook name

**Example:**
```php
$hook = $store->hook_prefix('before_save'); // Returns: my_store_before_save
```

## Usage Examples

### Basic Store Initialization

```php
use Hizzle\Store\Store;

// Initialize a simple store
$store = new Store(
    'products_store',
    array(
        'products' => array(
            'object'        => 'Product',
            'singular_name' => 'product',
            'props'         => array(
                'id'          => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Product ID',
                ),
                'name'        => array(
                    'type'        => 'VARCHAR',
                    'length'      => 255,
                    'nullable'    => false,
                    'description' => 'Product name',
                ),
                'price'       => array(
                    'type'        => 'DECIMAL',
                    'length'      => '10,2',
                    'nullable'    => false,
                    'description' => 'Product price',
                ),
                'description' => array(
                    'type'        => 'TEXT',
                    'description' => 'Product description',
                ),
            ),
            'keys'          => array(
                'primary' => array( 'id' ),
            ),
            'labels'        => array(
                'name'          => __( 'Products', 'textdomain' ),
                'singular_name' => __( 'Product', 'textdomain' ),
            ),
        ),
    )
);
```

### Multiple Collections

```php
$store = new Store(
    'ecommerce',
    array(
        'products'  => array(
            'object'        => 'Product',
            'singular_name' => 'product',
            'props'         => array( /* ... */ ),
            'keys'          => array( 'primary' => array( 'id' ) ),
        ),
        'customers' => array(
            'object'        => 'Customer',
            'singular_name' => 'customer',
            'props'         => array( /* ... */ ),
            'keys'          => array( 'primary' => array( 'id' ) ),
        ),
        'orders'    => array(
            'object'        => 'Order',
            'singular_name' => 'order',
            'props'         => array( /* ... */ ),
            'keys'          => array( 'primary' => array( 'id' ) ),
        ),
    )
);

// Access individual collections
$products = $store->get_collection('products');
$customers = $store->get_collection('customers');
```

### Retrieving Existing Store

```php
// Later in your code, retrieve the store
$store = Store::instance('ecommerce');
if ($store) {
    $products = $store->get_collection('products');
}
```

## Hooks and Filters

The Store class provides WordPress hooks for extensibility:

- `{namespace}_collections` - Filter the collections array before initialization

## See Also

- [Collection](collection.md) - Individual collection management
- [Record](record.md) - Working with individual records
- [Query](query.md) - Querying collections
