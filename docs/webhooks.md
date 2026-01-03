# Webhooks

The `Webhooks` class provides an event-driven webhook system that triggers HTTP callbacks when data changes occur in collections.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/Webhooks.php`

## Description

Automatically sends HTTP POST requests to configured webhook URLs when records are created, updated, or deleted. This enables real-time integrations with external systems and services.

## Webhook Events

The following events are automatically triggered for each collection:

- **created** - Fired when a new record is created
- **updated** - Fired when an existing record is updated
- **deleted** - Fired when a record is deleted
- **status_changed** - Fired when a status/enum field changes

## Usage Examples

### Enabling Webhooks

```php
use Hizzle\Store\Store;
use Hizzle\Store\Webhooks;

// Initialize your store
$store = Store::init('shop', array(
    'customers' => array(
        'name' => 'customers',
        'object' => '\Hizzle\Store\Record', // Must have a CRUD class
        'props' => array(
            'id' => array('type' => 'int'),
            'name' => array('type' => 'varchar', 'length' => 255),
            'email' => array('type' => 'varchar', 'length' => 255),
            'status' => array('type' => 'varchar', 'enum' => array('active', 'inactive')),
        ),
    ),
));

// Enable webhooks
new Webhooks($store);
```

### Registering Webhook Endpoints

```php
// Register a webhook for customer creation
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://example.com/webhooks/customer-created',
        'secret' => 'your-secret-key',
    );
    
    return $endpoints;
});

// Register multiple webhooks
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_updated',
        'url' => 'https://example.com/webhooks/customer-updated',
        'secret' => 'your-secret-key',
    );
    
    $endpoints[] = array(
        'event' => 'customer_deleted',
        'url' => 'https://example.com/webhooks/customer-deleted',
        'secret' => 'your-secret-key',
    );
    
    return $endpoints;
});
```

### Webhook Payload

When an event occurs, a POST request is sent with the following payload:

```json
{
    "event": "customer_created",
    "data": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "status": "active",
        "created_at": "2024-01-15 10:30:00"
    },
    "timestamp": 1705318200,
    "site_url": "https://yoursite.com"
}
```

### Webhook Signature

Each webhook includes a signature for verification:

**Headers:**
```
X-Webhook-Signature: sha256=abc123...
Content-Type: application/json
User-Agent: Hizzle-Webhook/1.0
```

### Verifying Webhook Signatures

On your webhook endpoint:

```php
// Verify webhook signature
function verify_webhook_signature($payload, $signature, $secret) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

// In your webhook handler
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'your-secret-key';

if (!verify_webhook_signature($payload, $signature, $secret)) {
    http_response_code(401);
    die('Invalid signature');
}

$data = json_decode($payload, true);

// Process the webhook
switch ($data['event']) {
    case 'customer_created':
        // Handle new customer
        break;
    case 'customer_updated':
        // Handle customer update
        break;
    case 'customer_deleted':
        // Handle customer deletion
        break;
}
```

## Advanced Examples

### Filtering Webhook Data

```php
// Customize data sent in webhooks
add_filter('shop_customer_created_webhook_data', function($data, $customer) {
    // Add computed fields
    $data['full_name'] = $customer->get('first_name') . ' ' . $customer->get('last_name');
    
    // Remove sensitive data
    unset($data['internal_notes']);
    
    // Add metadata
    $data['custom_field'] = $customer->get_meta('custom_field');
    
    return $data;
}, 10, 2);
```

### Conditional Webhooks

```php
// Only send webhook for specific conditions
add_filter('shop_should_send_customer_created_webhook', function($should_send, $customer) {
    // Only send for active customers
    return $customer->get('status') === 'active';
}, 10, 2);
```

### Status Change Webhooks

```php
// Webhook fired when status changes
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_status_changed',
        'url' => 'https://example.com/webhooks/status-changed',
        'secret' => 'your-secret-key',
    );
    
    return $endpoints;
});

// Status change payload includes old and new values
// {
//     "event": "customer_status_changed",
//     "field": "status",
//     "old_value": "pending",
//     "new_value": "active",
//     "data": { ... customer data ... }
// }
```

### Retry Logic

Webhooks are sent asynchronously and will retry on failure:

```php
// Customize retry behavior
add_filter('shop_webhook_retry_count', function($retries) {
    return 5; // Retry up to 5 times (default: 3)
});

add_filter('shop_webhook_retry_delay', function($delay) {
    return 300; // Wait 5 minutes between retries (default: 60 seconds)
});
```

### Multiple Endpoints per Event

```php
// Send same event to multiple URLs
add_filter('shop_webhook_endpoints', function($endpoints) {
    // Send to Slack
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
    );
    
    // Send to CRM
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://crm.example.com/api/webhooks',
        'secret' => 'crm-secret',
    );
    
    // Send to analytics
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://analytics.example.com/track',
        'secret' => 'analytics-secret',
    );
    
    return $endpoints;
});
```

## Integration Examples

### Slack Integration

```php
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK',
    );
    
    return $endpoints;
});

// Format message for Slack
add_filter('shop_customer_created_webhook_data', function($data, $customer) {
    return array(
        'text' => "New customer registered: {$customer->get('name')}",
        'attachments' => array(
            array(
                'fields' => array(
                    array('title' => 'Email', 'value' => $customer->get('email')),
                    array('title' => 'Status', 'value' => $customer->get('status')),
                ),
            ),
        ),
    );
}, 10, 2);
```

### Zapier Integration

```php
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://hooks.zapier.com/hooks/catch/YOUR/ZAPIER/WEBHOOK',
    );
    
    return $endpoints;
});
```

### Email Notifications

```php
// Send email when customer is created
add_action('shop_customer_created', function($customer) {
    wp_mail(
        'admin@example.com',
        'New Customer Registration',
        "A new customer has registered:\n\n" .
        "Name: {$customer->get('name')}\n" .
        "Email: {$customer->get('email')}"
    );
});
```

## Testing Webhooks

### Using RequestBin

```php
// Test webhooks with RequestBin
add_filter('shop_webhook_endpoints', function($endpoints) {
    $endpoints[] = array(
        'event' => 'customer_created',
        'url' => 'https://requestbin.com/YOUR_BIN_ID',
    );
    
    return $endpoints;
});
```

### Local Testing with ngrok

```bash
# Start ngrok
ngrok http 80

# Use the ngrok URL in your webhook
# https://abc123.ngrok.io/your-webhook-endpoint
```

## Debugging Webhooks

```php
// Log webhook attempts
add_action('shop_webhook_sent', function($event, $url, $response) {
    error_log("Webhook sent: {$event} to {$url}");
    error_log("Response: " . print_r($response, true));
}, 10, 3);

// Log webhook failures
add_action('shop_webhook_failed', function($event, $url, $error) {
    error_log("Webhook failed: {$event} to {$url}");
    error_log("Error: " . $error->get_error_message());
}, 10, 3);
```

## Security Best Practices

1. **Always use HTTPS** - Never send webhooks to HTTP URLs
2. **Verify signatures** - Always verify webhook signatures on receiving end
3. **Use strong secrets** - Generate cryptographically secure secrets
4. **Validate payloads** - Validate incoming webhook data structure
5. **Rate limit** - Implement rate limiting on webhook endpoints
6. **Log failures** - Monitor and log webhook failures
7. **Timeout protection** - Set reasonable timeouts for webhook requests

## Available Hooks

### Actions

- `{namespace}_webhook_sent` - After webhook is sent successfully
- `{namespace}_webhook_failed` - When webhook fails
- `{namespace}_{collection}_created` - When record is created
- `{namespace}_{collection}_updated` - When record is updated
- `{namespace}_{collection}_deleted` - When record is deleted
- `{namespace}_{collection}_{field}_changed` - When specific field changes

### Filters

- `{namespace}_webhook_endpoints` - Register webhook endpoints
- `{namespace}_webhook_retry_count` - Set retry count
- `{namespace}_webhook_retry_delay` - Set retry delay
- `{namespace}_{collection}_{event}_webhook_data` - Customize webhook payload
- `{namespace}_should_send_{collection}_{event}_webhook` - Conditionally send webhooks

## See Also

- [Record](record.md) - Record lifecycle events
- [Collection](collection.md) - Collection events
- [REST_Controller](rest-controller.md) - REST API integration
