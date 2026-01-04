# datastore

This is currently in Beta so expect the API to change alot.

## Installation

Install via Composer:

```bash
composer require hizzle/store
```

## Features

- **CRUD Operations**: Create, read, update, and delete records
- **Query Builder**: Powerful query builder with filtering, sorting, and pagination
- **Aggregate Functions**: Support for SUM, AVG, COUNT, MIN, MAX with grouping
- **JOIN Queries**: Relate collections together for complex data analysis
- **REST API**: Automatic REST API endpoints for all collections
- **Meta Fields**: Support for custom meta fields with multiple values
- **Custom Post Types**: Integrate with WordPress custom post types

## Quick Start

### Basic Usage

```php
use Hizzle\Store\Store;

// Initialize a store
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

### JOIN Queries (New!)

Define relationships between collections:

```php
'customers' => array(
    'status' => 'complete',
    // ... other config
    'joins' => array(
        'payments' => array(
            'collection' => 'my_store_payments',
            'on' => 'id',
            'foreign_key' => 'customer_id',
            'type' => 'LEFT',
        ),
    ),
)
```

Use JOINs in aggregate queries:

```php
$query = $collection->query(array(
    'join' => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM', 'COUNT'),
    ),
    'groupby' => 'id',
));
```

## Documentation

### API Reference

Complete documentation for all components is available in the [docs](docs/) folder:

- **Core Classes**
  - [Store](docs/store.md) - Main store management
  - [Collection](docs/collection.md) - Collection CRUD operations
  - [Record](docs/record.md) - Individual record operations
  - [Query](docs/query.md) - Query builder and filtering

- **Supporting Classes**
  - [Prop](docs/prop.md) - Property definitions
  - [REST_Controller](docs/rest-controller.md) - REST API endpoints
  - [List_Table](docs/list-table.md) - WordPress admin tables
  - [Webhooks](docs/webhooks.md) - Event-driven webhooks

- **Utilities**
  - [Date_Time](docs/date-time.md) - Date/time handling
  - [Store_Exception](docs/store-exception.md) - Exception handling

### Guides

- [JOIN Queries Guide](docs/joins.md) - Comprehensive guide to using JOINs
- [Example Code](example-joins.php) - Working examples with JOINs

## Requirements

- PHP >= 5.3.0
- WordPress >= 4.7.0
