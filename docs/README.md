# Datastore Documentation

Welcome to the Hizzle Datastore documentation. This library provides a standardized way of creating data stores for your PHP/WordPress projects.

## Installation

Install via Composer:

```bash
composer require hizzle/store
```

## Core Components

The Datastore library consists of the following main components:

### Primary Classes

- **[Store](store.md)** - Main store management class that handles multiple collections
- **[Collection](collection.md)** - Manages CRUD operations on a single collection of data
- **[Record](record.md)** - Handles CRUD operations on individual records/objects
- **[Query](query.md)** - Powerful query builder with filtering, sorting, and pagination

### Supporting Classes

- **[Prop](prop.md)** - Manages individual property definitions and metadata
- **[REST_Controller](rest-controller.md)** - Automatic REST API endpoints for collections
- **[List_Table](list-table.md)** - WordPress admin list table integration
- **[Webhooks](webhooks.md)** - Event-driven webhook system for data changes

### Utility Classes

- **[Date_Time](date-time.md)** - Enhanced DateTime with WordPress timezone support
- **[Store_Exception](store-exception.md)** - Exception handling for store operations

## Quick Start

### Using Main Class (Recommended)

The `Main` class provides a simplified, WordPress-friendly API:

```php
use Hizzle\Store\Main;

// Initialize store with collections
Main::instance('my_store')->init_store(
    array(
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
            ),
            'labels'        => array(
                'name'          => __( 'Customers', 'textdomain' ),
                'singular_name' => __( 'Customer', 'textdomain' ),
            ),
        ),
    )
);

// Work with records
$customer = Main::instance('my_store')->get('customers',123);
$customers = Main::instance('my_store')->query('customers', array('status' => 'active'));
```

### Using Store Class Directly

```php
use Hizzle\Store\Store;

// Initialize a store
$store = new Store(
    'my_store',
    array(
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
            ),
            'labels'        => array(
                'name'          => __( 'Customers', 'textdomain' ),
                'singular_name' => __( 'Customer', 'textdomain' ),
            ),
        ),
    )
);
```

## Working with Data

### Basic CRUD Operations

#### Creating Records

```php

// Init a new record.
$customer = Store::instance('my_store')->get(
    'customers',
    array(
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    )
);

// Set any other properties.
$customer->set( 'country', 'US' );

// Save the record to the database.
$customer->save();

// Get the customer ID
$customer_id = $customer->get_id();
```

#### Reading Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Get a single record by ID
$customer = $db->get('customers', $customer_id);

if ($customer && !is_wp_error($customer)) {
    echo $customer->get('name');
    echo $customer->get('email');
}

// Get ID by a specific property
$customer_id = $db->get_id_by_prop('email', 'john@example.com', 'customers');

```

#### Updating Records

```php
// Using Main class with record object
$customer = Main::instance('my_store')->get('customers',$customer_id);

if ($customer && !is_wp_error($customer)) {
    $customer->set('name', 'Jane Doe');
    $customer->save();
}

// Or update via collection
$collection = Store::instance('my_store')->get('customers');
$collection->update($customer_id, array(
    'name' => 'Jane Doe',
));
```

#### Deleting Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Delete records matching criteria
$db->delete_where(
    array('status' => 'inactive'),
    'customers'
);

// Delete all records (use with caution!)
$db->delete_all('customers');

// Or via record object
$customer = $db->get('customers', $customer_id);
if ($customer && !is_wp_error($customer)) {
    $customer->delete();
}
```

### Querying Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Basic query - returns results by default
$customers = $db->query('customers', array(
    'status' => 'active',
    'per_page' => 10,
    'page' => 1,
));

// Count records
$active_count = $db->query('customers', array(
    'status' => 'active',
), 'count');

// Get Query object
$query = $db->query('customers', array(
    'status' => 'active',
), 'query');

$customers = $query->get_results();
$total = $query->get_total();

// Using Collection
$collection = Store::instance('my_store')->get('customers');
$query = $collection->query(array(
    'status' => 'active',
    'per_page' => 10,
    'page' => 1,
));

$customers = $query->get_results();
$total = $query->get_total();

// Query with filters
$customers = $db->query('customers', array(
    'status' => array('active', 'pending'),
    'created_at_after' => '2026-01-01',
    'orderby' => 'created_at',
    'order' => 'DESC',
));
```

### Working with Metadata

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Add metadata
$db->add_record_meta($customer_id, 'vip', '1', false, 'customers');

// Get metadata
$is_vip = $db->get_record_meta($customer_id, 'vip', true, 'customers');

// Update metadata
$db->update_record_meta($customer_id, 'vip', '0', '', 'customers');

// Delete metadata
$db->delete_record_meta($customer_id, 'vip', '', 'customers');

// Check if metadata exists
if ($db->record_meta_exists($customer_id, 'vip', 'customers')) {
    // Metadata exists
}

// Using Collection
$collection = Store::instance('my_store')->get('customers');
$collection->add_record_meta($customer_id, 'vip', '1');
$is_vip = $collection->get_record_meta($customer_id, 'vip', true);
$collection->update_record_meta($customer_id, 'vip', '0');
$collection->delete_record_meta($customer_id, 'vip');
```

### Error Handling

```php
use Hizzle\Store\Main;

// Main class automatically returns WP_Error on failure
$db = Main::instance('my_store');
$customer = $db->get('customers', $customer_id);

if (is_wp_error($customer)) {
    error_log($customer->get_error_message());
} else {
    // Work with customer
    echo $customer->get('name');
}

// When using Store/Collection directly
try {
    $customer = $collection->get($customer_id);
    // Work with customer
    
} catch (\Hizzle\Store\Store_Exception $e) {
    // Handle exception
    error_log($e->getMessage());
    
    // Convert to WP_Error if needed
    $error = new WP_Error(
        $e->getErrorCode(),
        $e->getMessage(),
        $e->getErrorData()
    );
}
```

## Features

- **CRUD Operations**: Create, read, update, and delete records
- **Query Builder**: Powerful query builder with filtering, sorting, and pagination
- **Aggregate Functions**: Support for SUM, AVG, COUNT, MIN, MAX with grouping
- **JOIN Queries**: Relate collections together for complex data analysis
- **REST API**: Automatic REST API endpoints for all collections
- **Meta Fields**: Support for custom meta fields with multiple values
- **Custom Post Types**: Integrate with WordPress custom post types
- **Webhooks**: Event-driven architecture for data changes

## Additional Resources

- [Main Repository README](../README.md)

## Requirements

- PHP >= 5.3.0
- WordPress >= 4.7.0
