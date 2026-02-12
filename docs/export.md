# Export Feature

The datastore library includes a built-in export feature that allows you to export records as CSV files via a REST API endpoint.

## How It Works

1. The user makes a POST request to the export endpoint with optional filters
2. A background task is scheduled to generate the CSV file
3. The CSV is generated using **batch processing** to handle large datasets efficiently
4. Records are processed in batches of 1,000 (configurable) to avoid memory issues
5. The CSV file is saved in a protected uploads folder with a unique name
6. A secure download token is generated and stored temporarily
7. An email is sent to the user with a secure download link
8. The file and token are automatically deleted after 24 hours

## REST API Endpoints

### Export Items

**Endpoint:** `POST /{namespace}/v1/{collection}/export`

**Parameters:**
- All the same parameters as `get_items` endpoint (filters, sorting, etc.)
- `__fields` (optional): Comma-separated list of fields to export

**Example Request:**

```bash
curl -X POST \
  'https://example.com/wp-json/my_store/v1/payments/export' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{
    "status": "completed",
    "date_created_after": "2024-01-01",
    "__fields": "id,customer_id,amount,status,date_created"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Export task has been scheduled. You will receive an email with the download link shortly.",
  "export_id": 1704614400
}
```

### Download Export

**Endpoint:** `GET /{namespace}/v1/{collection}/export/download/{token}`

**Authentication:** User must be logged in as the user who requested the export

**Example Request:**

```bash
curl -X GET \
  'https://example.com/wp-json/my_store/v1/payments/export/download/abc123def456...' \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

The download endpoint will stream the CSV file directly to the user.

## Email Notification

The user will receive an email with the following information:

**Subject:** Your Export is Ready

**Message:**
```
Your export has been generated successfully. You can download it from the link below:

https://example.com/wp-json/my_store/v1/payments/export/download/abc123def456...

Please note that this file will be automatically deleted in 24 hours.

Thank you!
```

## Export File Location

Export files are stored in:
```
wp-content/uploads/hizzle-exports/
```

The directory is protected with an `.htaccess` file that prevents direct access to files. Files can only be downloaded via the secure download endpoint with a valid token.

## Automatic Cleanup

Export files and download tokens are automatically deleted 24 hours after creation using WordPress cron.

## Filtering Exported Fields

You can control which fields are exported using the `__fields` parameter:

```bash
curl -X POST \
  'https://example.com/wp-json/my_store/v1/payments/export' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{"__fields": "id,customer_id,amount,status"}'
```

If `__fields` is not provided, all non-hidden and non-dynamic fields will be exported.

## Security

- Users must have permission to read items from the collection to initiate an export
- Download tokens are unique, unpredictable, and time-limited (24 hours)
- Only the user who requested the export can download it (verified by user ID)
- Export files are stored in a directory protected by `.htaccess` to prevent direct access
- Files are automatically deleted after 24 hours
- Email sending failures are logged for debugging
- Token-based authentication prevents unauthorized access to export files

## Error Handling

If the export fails, the user will receive an error email:

**Subject:** Export Failed

**Message:**
```
Unfortunately, your export failed with the following error:

[Error message here]

Please try again or contact support if the problem persists.
```

Email sending failures are logged to the error log for debugging.

## Background Processing

The export process runs in the background using WordPress cron:

1. `hizzle_store_process_export` - Processes the export task (runs 10 seconds after request)
2. `hizzle_store_cleanup_export` - Cleans up old export files (runs 24 hours after creation)

These hooks are automatically registered when the REST_Controller is instantiated.

## Data Type Handling

The CSV export handles various data types:

- **Dates**: Converted to 'Y-m-d H:i:s' format
- **Arrays**: Converted to comma-separated strings
- **Booleans**: Converted to 0/1
- **Null values**: Converted to empty strings
- **Objects**: Left as-is (may require custom handling)

## Constants

- `REST_Controller::EXPORT_TASK_DELAY` - Delay in seconds before processing export (default: 10)
- `REST_Controller::EXPORT_BATCH_SIZE` - Number of records to process per batch (default: 1000)

## Performance & Scalability

The export feature is designed to handle large datasets efficiently:

- **Batch Processing**: Records are processed in batches of 1,000 (configurable) to avoid loading millions of records into memory at once
- **Memory Management**: Each batch is freed from memory after processing
- **No Timeout Issues**: Background processing via WordPress cron prevents HTTP timeouts
- **Suitable for Large Datasets**: Can handle exports of 1,000,000+ records without crashing

### Customizing Batch Size

To customize the batch size, you can filter the constant:

```php
// Increase batch size for better performance on servers with more memory
add_filter( 'hizzle_store_export_batch_size', function() {
    return 5000;
});
```

Note: Larger batch sizes improve performance but require more memory. The default of 1,000 is a good balance for most servers.

