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
// Get the collection
$collection = $store->get('customers');

// Create a new customer
$customer = $collection->create(array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
));

// Get the customer ID
$customer_id = $customer->get_id();
```

#### Reading Records

```php
// Get a single record by ID
$customer = $collection->get($customer_id);

if ($customer) {
    echo $customer->get('name');
    echo $customer->get('email');
}

// Get ID by a specific property
$customer_id = $collection->get_id_by_prop('email', 'john@example.com');

// Check if record exists
if ($collection->exists($customer_id)) {
    // Customer exists
}
```

#### Updating Records

```php
// Update via collection
$collection->update($customer_id, array(
    'name' => 'Jane Doe',
));

// Or update via record object
$customer = $collection->get($customer_id);
$customer->set('name', 'Jane Doe');
$customer->save();
```

#### Deleting Records

```php
// Delete a single record
$collection->delete($customer_id);

// Delete records matching criteria
$collection->delete_where(array(
    'status' => 'inactive',
));

// Delete all records (use with caution!)
$collection->delete_all();
```

### Querying Records

```php
// Basic query
$query = $collection->query(array(
    'status' => 'active',
    'per_page' => 10,
    'page' => 1,
));

$customers = $query->get_results();
$total = $query->get_total();

// Count records
$active_count = $collection->count(array(
    'status' => 'active',
));

// Query with filters
$customers = $collection->query(array(
    'status' => array('active', 'pending'),
    'created_at_after' => '2026-01-01',
    'orderby' => 'created_at',
    'order' => 'DESC',
))->get_results();
```

### Working with Metadata

```php
// Add metadata
$collection->add_record_meta($customer_id, 'vip', '1');

// Get metadata
$is_vip = $collection->get_record_meta($customer_id, 'vip', true);

// Update metadata
$collection->update_record_meta($customer_id, 'vip', '0');

// Delete metadata
$collection->delete_record_meta($customer_id, 'vip');
```

### Error Handling

```php
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
