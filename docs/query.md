# Query

The `Query` class provides a powerful and flexible query builder for retrieving and analyzing data from collections. It supports filtering, sorting, pagination, aggregation, and JOIN operations.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Query.php`

## Description

Provides a fluent interface for building and executing database queries against collections. The Query class handles complex operations including filtering by field values, ORDER BY, LIMIT/OFFSET, aggregate functions (SUM, AVG, COUNT, MIN, MAX), and JOIN queries.

## Key Properties

- `$collection_name` (string) - The collection being queried
- `$query_vars` (array) - Parsed query variables
- `$aggregate` (int|float|array) - Results of aggregate queries
- `$results` (array) - IDs or objects of found records
- `$total_results` (int) - Total count of matching records

## Query Arguments

### Basic Query Args

- **Field Filtering** - Pass field names directly as query arguments (e.g., `status => 'active'`)
- `orderby` (array|string) - Sort field(s)
- `order` (string) - Sort direction (ASC/DESC, default: DESC)
- `per_page` (int) - Number of records to return per page (default: -1 for all, also accepts `number`)
- `page` (int) - Page number for pagination (default: 1, also accepts `paged`)
- `offset` (int) - Number of records to skip
- `fields` (string|array) - Fields to retrieve ('all' or array of field names, default: 'all')
- `count_total` (bool) - Whether to count total matching records (default: true)
- `count_only` (bool) - Return only count instead of records (default: false)

### Advanced Query Args

- `aggregate` (array) - Aggregate functions to perform (supports string, array, or complex configuration)
- `groupby` (string|array) - Fields to group by (supports casting for date fields)
- `join` (array) - JOIN operations to perform
- `meta_query` (array) - Query by metadata (uses WP_Meta_Query format)
- `search` (string) - Search term to search across fields
- `search_columns` (array) - Specific fields to search in
- `include` (array) - Array of IDs to include
- `exclude` (array) - Array of IDs to exclude
- `extra_fields` (array) - Additional fields to include in aggregate results without aggregating

## Main Methods

### Executing Queries

#### `get_results()`

Executes the query and returns matching records.

**Returns:** `Record[]` - Array of Record objects (when fields='all'), or array of values

**Example:**
```php
$query = $collection->query(array(
    'status' => 'active',
    'per_page' => 10,
));

$results = $query->get_results();
foreach ($results as $record) {
    echo $record->get('name');
}
```

#### `get_total()`

Returns the total count of matching records (without pagination limit).

**Returns:** `int` - Total number of records

**Example:**
```php
$query = $collection->query(array('per_page' => 10));
$results = $query->get_results();
$total = $query->get_total(); // Total count ignoring per_page
```

#### `get_ids()`

Returns only the IDs of matching records.

**Returns:** `int[]` - Array of record IDs

**Example:**
```php
$query = $collection->query(array(
    'status' => 'active',
    'fields' => 'id',
));
$ids = $query->get_results();
// Returns: array(1, 5, 12, 23, ...)
```

## Usage Examples

### Basic Queries

```php
$products = Store::instance('shop')->get_collection('products');

// Simple field filter - equals
$query = $products->query(array(
    'price' => 50,
));

// Field filter - array values (IN query)
$query = $products->query(array(
    'status' => array('published', 'featured'),
));

// Multiple field conditions (all are AND)
$query = $products->query(array(
    'status' => 'published',
    'stock' => 10,
));

// Negation with _not suffix
$query = $products->query(array(
    'status_not' => 'draft',
));

// Negation with array (NOT IN)
$query = $products->query(array(
    'status_not' => array('draft', 'pending'),
));
```

### Field Filtering

The Query class automatically filters records based on field names passed directly as query arguments. There is no `where` argument - you pass the field name directly.

```php
// Simple equality
$query = $products->query(array(
    'status' => 'published',
));
// Generates: WHERE status = 'published'

// Array values (IN operator)
$query = $products->query(array(
    'category' => array('electronics', 'gadgets'),
));
// Generates: WHERE category IN ('electronics', 'gadgets')

// Negation (NOT EQUAL)
$query = $products->query(array(
    'status_not' => 'draft',
));
// Generates: WHERE status <> 'draft'

// Negation with array (NOT IN)
$query = $products->query(array(
    'id_not' => array(1, 2, 3),
));
// Generates: WHERE id NOT IN (1, 2, 3)

// Special value 'any' - skips filtering for that field
$query = $products->query(array(
    'status' => 'any', // No filter applied for status
    'category' => 'electronics',
));

// Combining multiple conditions (all are AND)
$query = $products->query(array(
    'status' => 'published',
    'featured' => 1,
    'category_not' => 'discontinued',
));
```

### Date Field Queries

For date fields, you can use special suffixes:

```php
// Date range with _before and _after
$query = $products->query(array(
    'created_at_after' => '2026-01-01',
    'created_at_before' => '2026-12-31',
));

// Complex date query with _query suffix
$query = $products->query(array(
    'created_at_query' => array(
        array(
            'after' => '2026-01-01',
            'before' => '2026-12-31',
            'inclusive' => true,
        ),
    ),
));

// Date queries support WP_Date_Query format
$query = $products->query(array(
    'created_at_query' => array(
        array(
            'year' => 2026,
            'month' => 1,
        ),
    ),
));
```

### Numeric Field Queries

For numeric and float fields, use `_min` and `_max` suffixes:

```php
// Minimum value
$query = $products->query(array(
    'price_min' => 50,
));
// Generates: WHERE price >= 50

// Maximum value
$query = $products->query(array(
    'price_max' => 100,
));
// Generates: WHERE price <= 100

// Range query
$query = $products->query(array(
    'price_min' => 50,
    'price_max' => 100,
));
// Generates: WHERE price >= 50 AND price <= 100

// Combine with other filters
$query = $products->query(array(
    'status' => 'published',
    'price_min' => 50,
    'stock' => 0,
    'stock_not' => 0, // stock is not zero
));
```

### Sorting and Pagination

```php
// Order by single field (default order is DESC)
$query = $products->query(array(
    'orderby' => 'price',
    'order' => 'ASC',
));

// Order by multiple fields
$query = $products->query(array(
    'orderby' => array(
        'status' => 'ASC',
        'price' => 'DESC',
    ),
));

// Pagination with per_page and page
$page = 2;
$per_page = 20;
$query = $products->query(array(
    'per_page' => $per_page,
    'page' => $page,
));

$results = $query->get_results();
$total = $query->get_total();
$pages = ceil($total / $per_page);

// Alternative: use offset
$query = $products->query(array(
    'per_page' => 20,
    'offset' => 40, // Skip first 40 records
));

// Backward compatibility: number and paged
$query = $products->query(array(
    'number' => 20, // Alias for per_page
    'paged' => 2,   // Alias for page
));
```

### Selecting Specific Fields

```php
// Get only specific fields
$query = $products->query(array(
    'fields' => array('id', 'name', 'price'),
));

// Get only IDs (returns array of integers)
$query = $products->query(array(
    'fields' => 'id',
));
$ids = $query->get_results();
// Returns: array(1, 5, 12, 23, ...)

// Get all fields (default)
$query = $products->query(array(
    'fields' => 'all',
));
```

### Aggregate Functions

#### Basic Aggregates

```php
// COUNT - using the count() method
$count = $products->count(array(
    'status' => 'published',
));

// SUM
$result = $products->aggregate(array(
    'aggregate' => array(
        'price' => 'SUM',
    ),
));
$total_value = $result[0]['price_sum'];

// Multiple aggregates on one field
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

### Filtering Aggregates

You can filter aggregate results by applying regular field filters to your aggregate query:

```php
// Find total sales by category for published products only
$results = $products->aggregate(array(
    'status' => 'published', // Filter condition
    'aggregate' => array(
        'price' => 'SUM',
        'id' => 'COUNT',
    ),
    'groupby' => 'category',
));

// Combine with date filters
$results = $products->aggregate(array(
    'created_at_after' => '2026-01-01',
    'status' => 'published',
    'aggregate' => array(
        'price' => array('SUM', 'AVG'),
    ),
    'groupby' => 'category',
));

// Note: There is no HAVING clause. All filtering happens via field filters.
// To filter on aggregate results, you need to post-process the results in PHP.
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
// Query by single metadata field
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

// Shorthand for meta fields (if defined in collection schema)
// If your collection has meta fields defined, you can filter them directly
$query = $products->query(array(
    'color' => 'red', // Assumes 'color' is a meta field
));

// Negation for meta fields
$query = $products->query(array(
    'color_not' => 'red',
));
```

### Search

```php
// Search across searchable fields (defined in collection schema)
$query = $products->query(array(
    'search' => 'widget',
));

// Search in specific columns
$query = $products->query(array(
    'search' => 'phone',
    'search_columns' => array('name', 'description'),
));

// Combine search with other filters
$query = $products->query(array(
    'search' => 'phone',
    'price_max' => 500,
    'orderby' => 'price',
    'order' => 'ASC',
));
```

### Include/Exclude IDs

```php
// Include specific IDs
$query = $products->query(array(
    'include' => array(1, 5, 12, 23),
));

// Exclude specific IDs
$query = $products->query(array(
    'exclude' => array(1, 2, 3),
    'status' => 'published',
));
```

### Complex Query Example

```php
// Advanced query combining multiple features
$query = $products->query(array(
    // Field filters
    'status' => 'published',
    'featured' => 1,
    'category' => array('electronics', 'gadgets'),
    'price_min' => 10,
    'price_max' => 500,
    
    // Meta query for complex metadata conditions
    'meta_query' => array(
        array(
            'key' => 'brand',
            'value' => 'premium',
        ),
    ),
    
    // Sorting
    'orderby' => array(
        'featured' => 'DESC',
        'price' => 'ASC',
    ),
    
    // Pagination
    'per_page' => 20,
    'page' => 1,
));

$results = $query->get_results();
$total = $query->get_total();

// Or using shorthand if 'brand' is a defined meta field in schema
$query = $products->query(array(
    'status' => 'published',
    'featured' => 1,
    'category' => array('electronics', 'gadgets'),
    'price_min' => 10,
    'price_max' => 500,
    'brand' => 'premium', // Direct meta field filter
    'orderby' => array(
        'featured' => 'DESC',
        'price' => 'ASC',
    ),
    'per_page' => 20,
    'page' => 1,
));
```

## Return Values

### Standard Query

```php
$results = $query->get_results();
// Returns: Array of Record objects (when fields='all')
// Or: Array of field values (when fields is specific field)
// Or: Array of arrays (when fields is array of multiple fields)
```

### Count Query

```php
$count = $collection->count($args);
// Returns: Integer

// Or using count_only
$query = $collection->query(array(
    'count_only' => true,
    'status' => 'published',
));
$count = $query->get_total();
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

2. **Use count_only** instead of fetching all records:
   ```php
   $query = $collection->query(array(
       'count_only' => true,
       'status' => 'published',
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

9. **Filter early** - apply field filters in the query rather than filtering results in PHP

## See Also

- [Collection](collection.md) - Creating queries
- [Record](record.md) - Working with query results
- [JOIN Guide](joins.md) - Detailed JOIN documentation
