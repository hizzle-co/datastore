# Store

The `Store` class is the main entry point for managing multiple collections of data. A store contains an array of collections, which in turn contain records with properties.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Store.php`

## Description

Handles CRUD operations on an array of collections. The Store class acts as a registry and manager for multiple data collections, providing a centralized way to initialize and access different collections within your application.

## Key Properties

- `$namespace` (string) - Namespace of this store's instance
- `$collections` (Collection[]) - A list of collections managed by this store
- `$instances` (Store[]) - Static registry of all store instances

## Main Methods

### `__construct( $namespace, $collections )`

Creates a new store instance.

**Parameters:**
- `$namespace` (string) - Namespace of this store's instance
- `$collections` (array) - A list of collections to initialize

**Example:**
```php
$store = new Store('my_store', array(
    'customers' => array(
        'name' => 'customers',
        'props' => array(/* ... */),
    ),
));
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
        'name' => 'customers',
        'singular_name' => 'customer',
        'props' => array(
            'id' => array(
                'type' => 'int',
                'length' => 20,
                'nullable' => false,
            ),
            'name' => array(
                'type' => 'varchar',
                'length' => 255,
            ),
        ),
        'keys' => array(
            'primary' => 'id',
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
$store = Store::init('products_store', array(
    'products' => array(
        'name' => 'products',
        'singular_name' => 'product',
        'props' => array(
            'id' => array('type' => 'int', 'nullable' => false),
            'name' => array('type' => 'varchar', 'length' => 255),
            'price' => array('type' => 'decimal', 'length' => '10,2'),
            'description' => array('type' => 'text'),
        ),
        'keys' => array('primary' => 'id'),
    ),
));
```

### Multiple Collections

```php
$store = Store::init('ecommerce', array(
    'products' => array(/* ... */),
    'customers' => array(/* ... */),
    'orders' => array(/* ... */),
));

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
