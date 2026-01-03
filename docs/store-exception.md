# Store_Exception

The `Store_Exception` class provides structured exception handling for store operations with HTTP status codes and additional error data.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Store_Exception.php`  
**Extends:** `Exception`

## Description

A specialized exception class for handling errors in datastore operations. It extends PHP's built-in Exception class to include machine-readable error codes, HTTP status codes, and additional error data for better error handling and API responses.

## Key Properties

- `$error_code` (string) - Sanitized, machine-readable error code
- `$error_data` (array) - Additional error data and context
- `$message` (string) - Human-readable error message (inherited from Exception)
- `$code` (int) - HTTP status code (inherited from Exception)

## Constructor

```php
new Store_Exception( $code, $message, $http_status_code = 400, $data = array() )
```

**Parameters:**
- `$code` (string) - Machine-readable error code (e.g., 'subscriber_not_found')
- `$message` (string) - User-friendly translated error message
- `$http_status_code` (int) - HTTP status code (default: 400)
- `$data` (array) - Additional error data (optional)

## Main Methods

### `getErrorCode()`

Returns the machine-readable error code.

```php
try {
    // Some operation
} catch (Store_Exception $e) {
    echo $e->getErrorCode(); // e.g., 'record_not_found'
}
```

### `getErrorData()`

Returns all error data.

```php
try {
    // Some operation
} catch (Store_Exception $e) {
    $data = $e->getErrorData();
    // Returns: array('status' => 404, 'field' => 'email', ...)
}
```

### `getErrorDataValue( $key, $default = null )`

Gets a specific error data value.

```php
try {
    // Some operation
} catch (Store_Exception $e) {
    $field = $e->getErrorDataValue('field', 'unknown');
    echo "Error in field: " . $field;
}
```

### `setErrorDataValue( $key, $value )`

Sets an error data value.

```php
$exception = new Store_Exception('validation_failed', 'Validation failed', 400);
$exception->setErrorDataValue('field', 'email');
$exception->setErrorDataValue('validation_rule', 'email_format');
```

## Usage Examples

### Basic Exception

```php
use Hizzle\Store\Store_Exception;

throw new Store_Exception(
    'record_not_found',
    'The requested record was not found',
    404
);
```

### Exception with Additional Data

```php
throw new Store_Exception(
    'validation_failed',
    'Email address is invalid',
    400,
    array(
        'field' => 'email',
        'value' => 'invalid-email',
        'rule' => 'email_format',
    )
);
```

### Catching Exceptions

```php
use Hizzle\Store\Store_Exception;

try {
    $customer = $collection->get(999);
    if (!$customer) {
        throw new Store_Exception(
            'customer_not_found',
            'Customer not found',
            404
        );
    }
} catch (Store_Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "Code: " . $e->getErrorCode();
    echo "HTTP Status: " . $e->getCode();
}
```

### Validation Errors

```php
function validate_email($email) {
    if (!is_email($email)) {
        throw new Store_Exception(
            'invalid_email',
            'Please provide a valid email address',
            400,
            array(
                'field' => 'email',
                'value' => $email,
            )
        );
    }
}

try {
    validate_email('not-an-email');
} catch (Store_Exception $e) {
    $field = $e->getErrorDataValue('field');
    echo "Validation error in field: {$field}";
    echo $e->getMessage();
}
```

### REST API Error Handling

```php
function handle_api_request() {
    try {
        $customer = $collection->create(array(
            'email' => $_POST['email'],
        ));
        
        return new WP_REST_Response($customer->get_data(), 201);
        
    } catch (Store_Exception $e) {
        return new WP_Error(
            $e->getErrorCode(),
            $e->getMessage(),
            $e->getErrorData()
        );
    }
}
```

### Permission Errors

```php
if (!current_user_can('manage_customers')) {
    throw new Store_Exception(
        'insufficient_permissions',
        'You do not have permission to perform this action',
        403,
        array(
            'required_capability' => 'manage_customers',
        )
    );
}
```

### Database Errors

```php
try {
    $collection->create_table();
} catch (Store_Exception $e) {
    if ($e->getErrorCode() === 'table_creation_failed') {
        error_log('Failed to create table: ' . $e->getMessage());
        // Handle gracefully
    }
}
```

## Common Error Codes

### Not Found (404)

```php
throw new Store_Exception('record_not_found', 'Record not found', 404);
throw new Store_Exception('collection_not_found', 'Collection not found', 404);
```

### Bad Request (400)

```php
throw new Store_Exception('invalid_input', 'Invalid input provided', 400);
throw new Store_Exception('validation_failed', 'Validation failed', 400);
throw new Store_Exception('missing_required_field', 'Required field is missing', 400);
```

### Forbidden (403)

```php
throw new Store_Exception('insufficient_permissions', 'Insufficient permissions', 403);
throw new Store_Exception('access_denied', 'Access denied', 403);
```

### Conflict (409)

```php
throw new Store_Exception('duplicate_entry', 'Record already exists', 409);
throw new Store_Exception('email_already_exists', 'Email already registered', 409);
```

### Internal Server Error (500)

```php
throw new Store_Exception('database_error', 'Database error occurred', 500);
throw new Store_Exception('internal_error', 'An internal error occurred', 500);
```

## Advanced Examples

### Nested Try-Catch

```php
try {
    // Outer operation
    try {
        // Inner validation
        if (empty($email)) {
            throw new Store_Exception('missing_email', 'Email is required', 400);
        }
    } catch (Store_Exception $e) {
        // Re-throw with additional context
        $e->setErrorDataValue('context', 'customer_creation');
        throw $e;
    }
} catch (Store_Exception $e) {
    echo "Context: " . $e->getErrorDataValue('context');
    echo "Error: " . $e->getMessage();
}
```

### Custom Validation with Multiple Errors

```php
function validate_customer_data($data) {
    $errors = array();
    
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!is_email($data['email'])) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (!empty($errors)) {
        throw new Store_Exception(
            'validation_failed',
            'Validation failed for one or more fields',
            400,
            array('fields' => $errors)
        );
    }
}

try {
    validate_customer_data($_POST);
} catch (Store_Exception $e) {
    $fields = $e->getErrorDataValue('fields', array());
    foreach ($fields as $field => $error) {
        echo "{$field}: {$error}<br>";
    }
}
```

### Logging Errors

```php
function log_store_exception(Store_Exception $e) {
    error_log(sprintf(
        '[Store Error] Code: %s, Message: %s, HTTP: %d, Data: %s',
        $e->getErrorCode(),
        $e->getMessage(),
        $e->getCode(),
        json_encode($e->getErrorData())
    ));
}

try {
    // Some operation
} catch (Store_Exception $e) {
    log_store_exception($e);
    throw $e; // Re-throw if needed
}
```

### Converting to WP_Error

```php
function store_exception_to_wp_error(Store_Exception $e) {
    return new WP_Error(
        $e->getErrorCode(),
        $e->getMessage(),
        $e->getErrorData()
    );
}

try {
    $customer = $collection->create($data);
} catch (Store_Exception $e) {
    return store_exception_to_wp_error($e);
}
```

### AJAX Error Responses

```php
add_action('wp_ajax_create_customer', function() {
    try {
        $customer = Store::instance('shop')
            ->get_collection('customers')
            ->create($_POST);
        
        wp_send_json_success($customer->get_data());
        
    } catch (Store_Exception $e) {
        wp_send_json_error(array(
            'code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'data' => $e->getErrorData(),
        ), $e->getCode());
    }
});
```

## HTTP Status Codes Reference

| Code | Meaning | When to Use |
|------|---------|-------------|
| 400 | Bad Request | Invalid input, validation errors |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Record or resource not found |
| 409 | Conflict | Duplicate entry, constraint violation |
| 422 | Unprocessable Entity | Validation failed (semantic errors) |
| 500 | Internal Server Error | Unexpected server errors |

## Best Practices

1. **Use descriptive error codes** - Make them machine-readable and consistent
2. **Provide helpful messages** - User-friendly, actionable error messages
3. **Include context in error data** - Add fields, values, rules that failed
4. **Use appropriate HTTP codes** - Match the error type to HTTP status
5. **Log exceptions** - Keep track of errors for debugging
6. **Don't expose sensitive data** - Be careful what you include in errors
7. **Translate messages** - Use WordPress translation functions for messages
8. **Document error codes** - Maintain a list of possible error codes

## Translation Support

```php
throw new Store_Exception(
    'record_not_found',
    __('The requested record was not found', 'your-textdomain'),
    404
);

throw new Store_Exception(
    'validation_failed',
    sprintf(
        __('The %s field is required', 'your-textdomain'),
        $field_name
    ),
    400
);
```

## See Also

- [Collection](collection.md) - Collection-level error handling
- [Record](record.md) - Record validation and errors
- [REST_Controller](rest-controller.md) - REST API error responses
