# Query

The `Query` class provides a powerful and flexible query builder for retrieving and analyzing data from collections. It supports filtering, sorting, pagination, aggregation, and JOIN operations.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Query.php`

## Description

Provides a fluent interface for building and executing database queries against collections. The Query class handles complex operations including WHERE clauses, ORDER BY, LIMIT/OFFSET, aggregate functions (SUM, AVG, COUNT, MIN, MAX), and JOIN queries.

## Key Properties

- `$collection_name` (string) - The collection being queried
- `$query_vars` (array) - Parsed query variables
- `$aggregate` (int|float|array) - Results of aggregate queries
- `$found_rows` (array) - IDs of found objects
- `$found_items` (array) - Found record objects
- `$total` (int) - Total count of matching records

## Query Arguments

### Basic Query Args

- `where` (array) - Array of WHERE conditions
- `orderby` (array|string) - Sort field(s)
- `order` (string) - Sort direction (ASC/DESC)
- `limit` (int) - Number of records to return
- `offset` (int) - Number of records to skip
- `fields` (string|array) - Fields to retrieve (default: all)
- `count` (bool) - Return count instead of records

### Advanced Query Args

- `aggregate` (array) - Aggregate functions to perform
- `groupby` (string|array) - Fields to group by
- `having` (array) - HAVING clause for aggregates
- `join` (array) - JOIN operations to perform
- `meta_query` (array) - Query by metadata
- `search` (string) - Search term

## Main Methods

### Executing Queries

#### `get_results()`

Executes the query and returns matching records.

**Returns:** `Record[]` - Array of Record objects

**Example:**
```php
$query = $collection->query(array(
    'where' => array(
        array('status', '=', 'active'),
    ),
    'limit' => 10,
));

$results = $query->get_results();
foreach ($results as $record) {
    echo $record->get('name');
}
```

#### `get_total()`

Returns the total count of matching records (without limit).

**Returns:** `int` - Total number of records

**Example:**
```php
$query = $collection->query(array('limit' => 10));
$results = $query->get_results();
$total = $query->get_total(); // Total count ignoring limit
```

#### `get_ids()`

Returns only the IDs of matching records.

**Returns:** `int[]` - Array of record IDs

**Example:**
```php
$query = $collection->query(array('where' => array(
    array('status', '=', 'active'),
)));
$ids = $query->get_ids();
// Returns: array(1, 5, 12, 23, ...)
```

## Usage Examples

### Basic Queries

```php
$products = Store::instance('shop')->get_collection('products');

// Simple WHERE clause
$query = $products->query(array(
    'where' => array(
        array('price', '>', 50),
    ),
));

// Multiple conditions (AND)
$query = $products->query(array(
    'where' => array(
        array('status', '=', 'published'),
        array('stock', '>', 0),
        array('price', '<', 100),
    ),
));

// OR conditions
$query = $products->query(array(
    'where' => array(
        'relation' => 'OR',
        array('category', '=', 'electronics'),
        array('category', '=', 'gadgets'),
    ),
));
```

### Comparison Operators

```php
// Supported operators: =, !=, >, <, >=, <=, LIKE, NOT LIKE, IN, NOT IN

// LIKE operator
$query = $products->query(array(
    'where' => array(
        array('name', 'LIKE', '%widget%'),
    ),
));

// IN operator
$query = $products->query(array(
    'where' => array(
        array('status', 'IN', array('published', 'featured')),
    ),
));

// NOT IN operator
$query = $products->query(array(
    'where' => array(
        array('id', 'NOT IN', array(1, 2, 3)),
    ),
));
```

### Sorting and Pagination

```php
// Order by single field
$query = $products->query(array(
    'orderby' => 'price',
    'order' => 'DESC',
));

// Order by multiple fields
$query = $products->query(array(
    'orderby' => array(
        'status' => 'ASC',
        'price' => 'DESC',
    ),
));

// Pagination
$page = 2;
$per_page = 20;
$query = $products->query(array(
    'limit' => $per_page,
    'offset' => ($page - 1) * $per_page,
));

$results = $query->get_results();
$total = $query->get_total();
$pages = ceil($total / $per_page);
```

### Selecting Specific Fields

```php
// Get only specific fields
$query = $products->query(array(
    'fields' => array('id', 'name', 'price'),
));

// Get only IDs
$query = $products->query(array(
    'fields' => 'id',
));
$ids = $query->get_ids();
```

### Aggregate Functions

```php
// COUNT
$count = $products->count(array(
    'where' => array(
        array('status', '=', 'published'),
    ),
));

// SUM
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
));
$total_value = $result['price_sum'];

// Multiple aggregates
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => array('SUM', 'AVG', 'MIN', 'MAX'),
        'stock' => array('SUM', 'COUNT'),
    ),
));

// Returns:
// array(
//     'price_sum' => 5000.00,
//     'price_avg' => 50.00,
//     'price_min' => 10.00,
//     'price_max' => 200.00,
//     'stock_sum' => 1000,
//     'stock_count' => 100,
// )
```

### GROUP BY with Aggregates

```php
// Group by category and sum prices
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
        'id' => 'COUNT',
    ),
    'groupby' => 'category',
));

// Returns results grouped by category
foreach ($results as $row) {
    echo "Category: {$row['category']}\n";
    echo "Total Price: {$row['price_sum']}\n";
    echo "Product Count: {$row['id_count']}\n";
}
```

### HAVING Clause

```php
// Find categories with more than 10 products
$results = $products->aggregate(array(
    'aggregate' => array(
        'id' => 'COUNT',
    ),
    'groupby' => 'category',
    'having' => array(
        array('id_count', '>', 10),
    ),
));
```

### JOIN Queries

```php
// First, define the JOIN in collection configuration
'customers' => array(
    'name' => 'customers',
    'joins' => array(
        'orders' => array(
            'collection' => 'shop_orders',
            'on' => 'id',
            'foreign_key' => 'customer_id',
            'type' => 'LEFT',
        ),
    ),
    // ... other config
)

// Use the JOIN in a query
$query = $customers->query(array(
    'join' => array('orders'),
    'aggregate' => array(
        'orders.total' => array('SUM', 'COUNT'),
    ),
    'groupby' => 'id',
));

$results = $query->get_results();
foreach ($results as $customer) {
    echo "{$customer->get('name')}: ";
    echo "{$customer->orders_total_sum} from {$customer->orders_total_count} orders\n";
}
```

### Multiple JOINs

```php
// Define multiple JOINs
'customers' => array(
    'joins' => array(
        'orders' => array(
            'collection' => 'shop_orders',
            'on' => 'id',
            'foreign_key' => 'customer_id',
        ),
        'reviews' => array(
            'collection' => 'shop_reviews',
            'on' => 'id',
            'foreign_key' => 'customer_id',
        ),
    ),
)

// Use multiple JOINs
$query = $customers->query(array(
    'join' => array('orders', 'reviews'),
    'aggregate' => array(
        'orders.total' => 'SUM',
        'reviews.rating' => 'AVG',
    ),
    'groupby' => 'id',
));
```

### Meta Queries

```php
// Query by metadata
$query = $products->query(array(
    'meta_query' => array(
        array(
            'key' => 'featured',
            'value' => 'yes',
            'compare' => '=',
        ),
    ),
));

// Multiple meta conditions
$query = $products->query(array(
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'color',
            'value' => 'red',
        ),
        array(
            'key' => 'size',
            'value' => array('S', 'M', 'L'),
            'compare' => 'IN',
        ),
    ),
));
```

### Search

```php
// Search across searchable fields
$query = $products->query(array(
    'search' => 'widget',
));

// Combine search with other filters
$query = $products->query(array(
    'search' => 'phone',
    'where' => array(
        array('price', '<', 500),
    ),
    'orderby' => 'price',
    'order' => 'ASC',
));
```

### Complex Query Example

```php
// Advanced query combining multiple features
$query = $products->query(array(
    'where' => array(
        'relation' => 'AND',
        array('status', '=', 'published'),
        array(
            'relation' => 'OR',
            array('category', '=', 'electronics'),
            array('featured', '=', 1),
        ),
    ),
    'meta_query' => array(
        array(
            'key' => 'brand',
            'value' => 'premium',
        ),
    ),
    'orderby' => array(
        'featured' => 'DESC',
        'price' => 'ASC',
    ),
    'limit' => 20,
    'offset' => 0,
));

$results = $query->get_results();
$total = $query->get_total();
```

## Return Values

### Standard Query

```php
$results = $query->get_results();
// Returns: Array of Record objects
```

### Count Query

```php
$count = $collection->count($args);
// Returns: Integer
```

### Aggregate Query

```php
$result = $collection->aggregate($args);
// Returns: Array with aggregate results
// Example: array('price_sum' => 1000, 'price_avg' => 50)
```

## Performance Tips

1. **Use specific fields** when you don't need all data:
   ```php
   'fields' => array('id', 'name')
   ```

2. **Use count** instead of fetching all records:
   ```php
   $total = $collection->count($args);
   ```

3. **Add indexes** to frequently queried fields in your collection schema

4. **Limit results** to avoid memory issues with large datasets

5. **Use aggregate queries** instead of fetching all records and calculating in PHP

## See Also

- [Collection](collection.md) - Creating queries
- [Record](record.md) - Working with query results
- [JOIN Guide](../JOINS.md) - Detailed JOIN documentation
