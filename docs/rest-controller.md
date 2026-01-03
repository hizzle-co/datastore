# REST_Controller

The `REST_Controller` class automatically creates REST API endpoints for collections, providing a complete CRUD API with minimal configuration.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/REST_Controller.php`  
**Extends:** `WP_REST_Controller`

## Description

Automatically generates WordPress REST API endpoints for a collection, enabling create, read, update, and delete operations via HTTP. The controller handles authentication, validation, and response formatting.

## Automatic Endpoints

For a collection named `customers` in a store with namespace `shop`, the following endpoints are automatically created:

### List/Create Endpoint

- **GET** `/wp-json/shop/v1/customers` - List all customers
- **POST** `/wp-json/shop/v1/customers` - Create a new customer

### Single Item Endpoints

- **GET** `/wp-json/shop/v1/customers/{id}` - Get a single customer
- **PUT** `/wp-json/shop/v1/customers/{id}` - Update a customer
- **PATCH** `/wp-json/shop/v1/customers/{id}` - Partially update a customer
- **DELETE** `/wp-json/shop/v1/customers/{id}` - Delete a customer

## Usage Examples

### Enable REST API for Collection

The REST API is automatically enabled when you create a collection:

```php
use Hizzle\Store\Store;

Store::init('shop', array(
    'customers' => array(
        'name' => 'customers',
        'singular_name' => 'customer',
        'capabillity' => 'manage_shop', // Required permission
        'props' => array(
            'id' => array('type' => 'int', 'nullable' => false),
            'name' => array('type' => 'varchar', 'length' => 255),
            'email' => array('type' => 'varchar', 'length' => 255),
        ),
    ),
));

// REST API is now available at /wp-json/shop/v1/customers
```

### Making API Requests

#### List Customers

```bash
curl http://example.com/wp-json/shop/v1/customers
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 10, max: 100)
- `orderby` - Field to sort by
- `order` - Sort direction (asc/desc)
- `search` - Search term
- Any property name for filtering

**Response:**
```json
[
    {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com"
    }
]
```

**Headers:**
```
X-WP-Total: 50
X-WP-TotalPages: 5
```

#### Create Customer

```bash
curl -X POST http://example.com/wp-json/shop/v1/customers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Customer",
    "email": "new@example.com"
  }'
```

**Response:**
```json
{
    "id": 3,
    "name": "New Customer",
    "email": "new@example.com"
}
```

#### Get Single Customer

```bash
curl http://example.com/wp-json/shop/v1/customers/1
```

**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
}
```

#### Update Customer

```bash
curl -X PUT http://example.com/wp-json/shop/v1/customers/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "email": "john.updated@example.com"
  }'
```

#### Partial Update

```bash
curl -X PATCH http://example.com/wp-json/shop/v1/customers/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Modified"
  }'
```

#### Delete Customer

```bash
curl -X DELETE http://example.com/wp-json/shop/v1/customers/1
```

**Response:**
```json
{
    "deleted": true,
    "previous": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Advanced Filtering

#### Filter by Property

```bash
curl "http://example.com/wp-json/shop/v1/customers?status=active"
```

#### Multiple Filters

```bash
curl "http://example.com/wp-json/shop/v1/customers?status=active&country=US"
```

#### Search

```bash
curl "http://example.com/wp-json/shop/v1/customers?search=john"
```

#### Sorting

```bash
curl "http://example.com/wp-json/shop/v1/customers?orderby=created_at&order=desc"
```

#### Pagination

```bash
curl "http://example.com/wp-json/shop/v1/customers?page=2&per_page=20"
```

## Authentication

The REST API respects WordPress authentication and the capability set in the collection configuration:

```php
'capabillity' => 'manage_shop', // Users need this capability
```

### Authentication Methods

1. **Cookie Authentication** - For logged-in users making requests from the same site
2. **Application Passwords** - WordPress application passwords
3. **OAuth** - Via plugins like WP OAuth Server
4. **JWT** - Via plugins like JWT Authentication

### Example with Application Password

```bash
curl -u "username:application_password" \
  http://example.com/wp-json/shop/v1/customers
```

## Response Formats

### Success Response

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
}
```

### Error Response

```json
{
    "code": "rest_invalid_param",
    "message": "Invalid parameter(s): email",
    "data": {
        "status": 400,
        "params": {
            "email": "Email is required"
        }
    }
}
```

### Collection Response

```json
[
    {"id": 1, "name": "Item 1"},
    {"id": 2, "name": "Item 2"}
]
```

**Headers include:**
- `X-WP-Total` - Total number of items
- `X-WP-TotalPages` - Total number of pages

## Customizing the REST API

### Custom Endpoints

```php
add_action('rest_api_init', function() {
    register_rest_route('shop/v1', '/customers/(?P<id>\d+)/activate', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $customer = Store::instance('shop')
                ->get_collection('customers')
                ->get($request['id']);
            
            if (!$customer) {
                return new WP_Error('not_found', 'Customer not found', array('status' => 404));
            }
            
            $customer->set('status', 'active');
            $customer->save();
            
            return rest_ensure_response($customer->get_data());
        },
        'permission_callback' => function() {
            return current_user_can('manage_shop');
        },
    ));
});
```

### Filtering Response Data

```php
add_filter('shop_customers_rest_response', function($data, $record) {
    // Add computed fields
    $data['full_name'] = $record->get('first_name') . ' ' . $record->get('last_name');
    
    // Remove sensitive data
    unset($data['internal_notes']);
    
    return $data;
}, 10, 2);
```

### Validating Input

```php
add_filter('shop_customers_rest_validate', function($valid, $data, $request) {
    if (isset($data['email']) && !is_email($data['email'])) {
        return new WP_Error(
            'invalid_email',
            'Please provide a valid email address',
            array('status' => 400)
        );
    }
    
    return $valid;
}, 10, 3);
```

## JavaScript Example

Using the REST API from JavaScript:

```javascript
// Using WordPress REST API JavaScript client
wp.apiFetch({
    path: '/shop/v1/customers',
    method: 'GET',
    data: {
        page: 1,
        per_page: 10,
        status: 'active'
    }
}).then(customers => {
    console.log(customers);
});

// Create customer
wp.apiFetch({
    path: '/shop/v1/customers',
    method: 'POST',
    data: {
        name: 'New Customer',
        email: 'new@example.com'
    }
}).then(customer => {
    console.log('Created:', customer);
});

// Update customer
wp.apiFetch({
    path: '/shop/v1/customers/123',
    method: 'PUT',
    data: {
        name: 'Updated Name'
    }
}).then(customer => {
    console.log('Updated:', customer);
});

// Delete customer
wp.apiFetch({
    path: '/shop/v1/customers/123',
    method: 'DELETE'
}).then(response => {
    console.log('Deleted:', response.deleted);
});
```

## CORS Support

For cross-origin requests, you may need to enable CORS:

```php
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        return $value;
    });
});
```

## Best Practices

1. **Set appropriate capabilities** - Don't use 'read' for write operations
2. **Validate input** - Always validate data before saving
3. **Sanitize output** - Remove sensitive data from responses
4. **Use pagination** - Don't return thousands of items at once
5. **Rate limit** - Consider rate limiting for public APIs
6. **Version your API** - The `/v1/` in the URL supports versioning
7. **Document your endpoints** - Provide clear API documentation for consumers

## Hooks and Filters

- `{namespace}_{collection}_rest_response` - Filter response data
- `{namespace}_{collection}_rest_validate` - Validate request data
- `{namespace}_{collection}_rest_query_args` - Filter query arguments
- `rest_api_init` - Register custom endpoints

## See Also

- [Collection](collection.md) - Collection configuration
- [Record](record.md) - Working with records
- [Query](query.md) - Querying data
