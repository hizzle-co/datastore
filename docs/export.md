# Export Feature

The datastore library includes a built-in export feature that allows you to export records as CSV files via a REST API endpoint.

## How It Works

1. The user makes a POST request to the export endpoint with optional filters
2. A background task is scheduled to generate the CSV file
3. The CSV file is saved in the uploads folder with a unique name
4. An email is sent to the user with a download link
5. The file is automatically deleted after 24 hours

## REST API Endpoint

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
    "date_created_after": "2026-01-01",
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

## Email Notification

The user will receive an email with the following information:

**Subject:** Your Export is Ready

**Message:**
```
Your export has been generated successfully. You can download it from the link below:

https://example.com/wp-content/uploads/hizzle-exports/payments-export-1704614400-abc123def456.csv

Please note that this file will be automatically deleted in 24 hours.

Thank you!
```

## Export File Location

Export files are stored in:
```
wp-content/uploads/hizzle-exports/
```

The directory is protected with an `.htaccess` file to prevent direct browsing, but the files are still accessible via direct URL.

## Automatic Cleanup

Export files are automatically deleted 24 hours after creation using WordPress cron.

## Filtering Exported Fields

You can control which fields are exported using the `__fields` parameter:

```bash
curl -X POST \
  'https://example.com/wp-json/my_store/v1/payments/export' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{"__fields": "id,customer_id,amount,status"}'
```

If `__fields` is not provided, all non-hidden fields will be exported.

## Security

- Users must have permission to read items from the collection
- Export files have unique, unpredictable names
- Files are automatically deleted after 24 hours
- The exports directory is protected with `.htaccess`

## Error Handling

If the export fails, the user will receive an error email:

**Subject:** Export Failed

**Message:**
```
Unfortunately, your export failed with the following error:

[Error message here]

Please try again or contact support if the problem persists.
```

## Background Processing

The export process runs in the background using WordPress cron:

1. `hizzle_store_process_export` - Processes the export task
2. `hizzle_store_cleanup_export` - Cleans up old export files

These hooks are automatically registered when the REST_Controller is instantiated.
