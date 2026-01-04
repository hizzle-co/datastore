# Datastore Documentation

Welcome to the Hizzle Datastore documentation. This library provides a standardized way of creating data stores for your PHP/WordPress projects.

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
Store::init('my_store', array(
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
                'nullable' => false,
            ),
            'email' => array(
                'type' => 'varchar',
                'length' => 255,
                'nullable' => false,
            ),
        ),
        'keys' => array(
            'primary' => 'id',
        ),
    ),
));
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
