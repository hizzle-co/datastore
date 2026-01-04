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

- `aggregate` (array) - Aggregate functions to perform (supports string, array, or complex configuration)
- `groupby` (string|array) - Fields to group by (supports casting for date fields)
- `having` (array) - HAVING clause for aggregates
- `join` (array) - JOIN operations to perform
- `meta_query` (array) - Query by metadata (uses WP_Meta_Query format)
- `search` (string) - Search term
- `extra_fields` (array) - Additional fields to include in aggregate results without aggregating

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

#### Basic Aggregates

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
$total_value = $result[0]['price_sum'];

// Multiple aggregates
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => array('SUM', 'AVG', 'MIN', 'MAX'),
        'stock' => array('SUM', 'COUNT'),
    ),
));

// Returns:
// array(
//     array(
//         'price_sum' => 5000.00,
//         'price_avg' => 50.00,
//         'price_min' => 10.00,
//         'price_max' => 200.00,
//         'stock_sum' => 1000,
//         'stock_count' => 100,
//     )
// )
```

#### Advanced Aggregate Configurations

You can use array configurations for more control over aggregate functions:

```php
// Custom alias and expression
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => array(
            array(
                'function' => 'SUM',
                'as' => 'total_revenue',
                'expression' => 'price * 1.1', // Add 10% markup
            ),
            array(
                'function' => 'AVG',
                'as' => 'avg_discounted_price',
                'expression' => 'price * discount_field', // Apply 10% discount
            ),
        ),
    ),
));

// Returns:
// array(
//     array(
//         'total_revenue' => 5500.00,
//         'avg_discounted_price' => 45.00,
//     )
// )
```

#### CASE Expressions in Aggregates

CASE expressions allow conditional aggregation:

```php
// Calculate revenue by product status
$result = $products->aggregate(array(
    'aggregate' => array(
        'active_revenue' => array(
            'case' => array(
                'field' => 'status',
                'when' => array(
                    'active' => array(
                        'field' => 'price',
                        'value' => '{field}',
                    ),
                ),
                'else' => 0,
            ),
            'function' => 'SUM',
        ),
        'inactive_count' => array(
            'case' => array(
                'field' => 'status',
                'when' => array(
                    'inactive' => 1,
                ),
                'else' => 0,
            ),
            'function' => 'SUM',
        ),
    ),
));

// More complex CASE with calculations
$result = $products->aggregate(array(
    'aggregate' => array(
        'premium_revenue' => array(
            'case' => array(
                'field' => 'category',
                'when' => array(
                    'premium' => array(
                        'field' => 'price',
                        'value' => '{field} * quantity * 1.2', // 20% premium markup
                    ),
                    'standard' => array(
                        'field' => 'price',
                        'value' => '{field} * quantity',
                    ),
                ),
                'else' => array(
                    'field' => 'price',
                    'value' => '{field} * quantity * 0.8', // 20% discount for other
                ),
            ),
            'function' => 'SUM',
        ),
    ),
));

// CASE with math operations after aggregation
$result = $products->aggregate(array(
    'aggregate' => array(
        'weighted_value' => array(
            'case' => array(
                'field' => 'status',
                'when' => array(
                    'active' => array(
                        'field' => 'price',
                    ),
                ),
                'else' => 0,
            ),
            'function' => 'SUM',
            'math' => '/ 100', // Divide the sum by 100
        ),
    ),
));
```

#### Math Expressions

Math expressions support various operators and SQL functions:

```php
// Basic arithmetic
$result = $products->aggregate(array(
    'aggregate' => array(
        'total_value' => array(
            array(
                'function' => 'SUM',
                'expression' => '{field} * quantity',
                'as' => 'inventory_value',
            ),
        ),
    ),
));

// Complex calculations with SQL functions
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => array(
            array(
                'function' => 'AVG',
                'expression' => 'ROUND({field} * 1.15, 2)', // 15% markup, rounded
                'as' => 'avg_retail_price',
            ),
            array(
                'function' => 'SUM',
                'expression' => 'ABS({field} - cost_price)', // Absolute difference
                'as' => 'total_margin',
            ),
        ),
    ),
));

// Supported SQL functions in expressions:
// ABS, ROUND, CEIL, FLOOR, SQRT, POW
// Supported operators: +, -, *, /
```

### GROUP BY with Aggregates

#### Basic Grouping

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

#### Date Grouping with Casting

Group by date periods with automatic timezone conversion:

```php
// Group by day
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
        'id' => 'COUNT',
    ),
    'groupby' => array(
        'created_at' => 'day',
    ),
));

// Returns:
// array(
//     array('cast_created_at' => '2026-01-01', 'price_sum' => 100, 'id_count' => 5),
//     array('cast_created_at' => '2026-01-02', 'price_sum' => 150, 'id_count' => 7),
// )

// Group by hour
$results = $products->aggregate(array(
    'aggregate' => array(
        'id' => 'COUNT',
    ),
    'groupby' => array(
        'created_at' => 'hour',
    ),
));

// Group by week (normalized to Monday)
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
    'groupby' => array(
        'created_at' => 'week',
    ),
));

// Group by month
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
    'groupby' => array(
        'created_at' => 'month',
    ),
));

// Group by year
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
    'groupby' => array(
        'created_at' => 'year',
    ),
));

// Group by day of week (0=Monday, 6=Sunday)
$results = $products->aggregate(array(
    'aggregate' => array(
        'id' => 'COUNT',
    ),
    'groupby' => array(
        'created_at' => 'day_of_week',
    ),
));

// Supported cast types:
// - 'hour': Groups by hour (e.g., '2026-01-01 14:00:00')
// - 'day': Groups by day (e.g., '2026-01-01')
// - 'week': Groups by week, normalized to Monday (e.g., '2025-12-29')
// - 'month': Groups by month (e.g., '2026-01-01')
// - 'year': Groups by year (e.g., '2026-01-01')
// - 'day_of_week': Groups by weekday number (0-6, where 0=Monday)

// Multiple group by fields
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
    'groupby' => array(
        'created_at' => 'day',
        'category',
    ),
));
```

### Extra Fields in Aggregates

Include additional fields in aggregate queries without aggregating them:

```php
// Include category name without aggregating it
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
        'id' => 'COUNT',
    ),
    'groupby' => 'category',
    'extra_fields' => array('category', 'status'),
));

// Returns:
// array(
//     array(
//         'category' => 'electronics',
//         'status' => 'active',
//         'price_sum' => 5000,
//         'id_count' => 50,
//     ),
//     ...
// )
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

// Complex HAVING conditions
$results = $products->aggregate(array(
    'aggregate' => array(
        'price' => array('SUM', 'AVG'),
        'id' => 'COUNT',
    ),
    'groupby' => 'category',
    'having' => array(
        'relation' => 'AND',
        array('id_count', '>', 10),
        array('price_sum', '>', 1000),
        array('price_avg', '<', 100),
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
            'type' => 'LEFT', // Supported: INNER, LEFT, RIGHT
        ),
    ),
    // ... other config
)

// Use the JOIN in a query
// Note: If any JOIN is LEFT, all JOINs automatically become LEFT
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

// You can also use double underscore (__) instead of dot (.)
$query = $customers->query(array(
    'join' => array('orders'),
    'aggregate' => array(
        'orders__total' => array('SUM', 'COUNT'),
    ),
    'groupby' => 'id',
));
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
$results = $collection->aggregate($args);
// Returns: Array of result rows with aggregate values
// Example without GROUP BY:
// array(
//     array('price_sum' => 1000, 'price_avg' => 50)
// )
//
// Example with GROUP BY:
// array(
//     array('category' => 'electronics', 'price_sum' => 5000, 'id_count' => 50),
//     array('category' => 'clothing', 'price_sum' => 3000, 'id_count' => 75),
// )
```

## Performance Tips

1. **Use specific fields** when you don't need all data:
   ```php
   'fields' => array('id', 'name')
   ```

2. **Use count** instead of fetching all records:
   ```php
   $total = $collection->count($args);
   // Or use count_only in query
   $query = $collection->query(array(
       'count_only' => true,
       'where' => array(...),
   ));
   $total = $query->get_total();
   ```

3. **Disable total count** when you don't need it:
   ```php
   'count_total' => false, // Skips the extra COUNT query
   ```

4. **Add indexes** to frequently queried fields in your collection schema

5. **Limit results** to avoid memory issues with large datasets:
   ```php
   'per_page' => 50,
   'page' => 1,
   ```

6. **Use aggregate queries** instead of fetching all records and calculating in PHP

7. **Be cautious with JOINs** - if any JOIN is LEFT, all JOINs become LEFT automatically

8. **Use CASE expressions** in aggregates instead of multiple queries for conditional calculations

## See Also

- [Collection](collection.md) - Creating queries
- [Record](record.md) - Working with query results
- [JOIN Guide](../JOINS.md) - Detailed JOIN documentation
