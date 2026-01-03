# List_Table

The `List_Table` class integrates with WordPress admin to display collection data in a standard WordPress list table format with sorting, filtering, and bulk actions.

## Class Overview

**Namespace:** `Hizzle\Store`  
**File:** `src/List_Table.php`  
**Extends:** `WP_List_Table`

## Description

Provides a WordPress admin interface for viewing and managing collection records. Automatically handles pagination, sorting, searching, and bulk actions using the familiar WordPress list table UI.

## Usage Examples

### Basic List Table

```php
use Hizzle\Store\List_Table;
use Hizzle\Store\Store;

// In your admin page callback
function display_customers_page() {
    $collection = Store::instance('shop')->get_collection('customers');
    
    $list_table = new List_Table(array(
        'collection' => $collection,
        'screen' => 'customers',
    ));
    
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1>Customers</h1>
        <form method="get">
            <input type="hidden" name="page" value="customers" />
            <?php $list_table->display(); ?>
        </form>
    </div>
    <?php
}
```

### Customizing Columns

```php
// Filter columns
add_filter('shop_customers_list_table_columns', function($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status',
        'created_at' => 'Registered',
    );
});

// Custom column output
add_action('shop_customers_list_table_column_status', function($customer) {
    $status = $customer->get('status');
    $class = $status === 'active' ? 'active' : 'inactive';
    echo "<span class='status-{$class}'>" . ucfirst($status) . "</span>";
});
```

### Adding Bulk Actions

```php
// Define bulk actions
add_filter('shop_customers_list_table_bulk_actions', function($actions) {
    return array(
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'delete' => 'Delete',
    );
});

// Handle bulk actions
add_action('shop_customers_list_table_bulk_action_activate', function($ids, $collection) {
    foreach ($ids as $id) {
        $customer = $collection->get($id);
        if ($customer) {
            $customer->set('status', 'active');
            $customer->save();
        }
    }
}, 10, 2);
```

### Adding Row Actions

```php
// Add row actions (Edit, Delete, etc.)
add_filter('shop_customers_list_table_row_actions', function($actions, $customer) {
    $actions['edit'] = sprintf(
        '<a href="%s">Edit</a>',
        admin_url('admin.php?page=edit-customer&id=' . $customer->get_id())
    );
    
    $actions['delete'] = sprintf(
        '<a href="%s" class="delete">Delete</a>',
        wp_nonce_url(
            admin_url('admin.php?page=customers&action=delete&id=' . $customer->get_id()),
            'delete-customer-' . $customer->get_id()
        )
    );
    
    return $actions;
}, 10, 2);
```

### Custom Sortable Columns

```php
// Make columns sortable
add_filter('shop_customers_list_table_sortable_columns', function($columns) {
    return array(
        'name' => 'name',
        'email' => 'email',
        'created_at' => 'created_at',
    );
});
```

### Adding Filters

```php
// Add filter dropdowns above the table
add_action('shop_customers_list_table_filters', function($collection) {
    // Status filter
    $statuses = array('active', 'inactive', 'pending');
    $current_status = isset($_GET['status']) ? $_GET['status'] : '';
    ?>
    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $status) : ?>
            <option value="<?php echo esc_attr($status); ?>" <?php selected($current_status, $status); ?>>
                <?php echo esc_html(ucfirst($status)); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
});

// Apply filters to query
add_filter('shop_customers_list_table_query_args', function($args) {
    if (!empty($_GET['status'])) {
        $args['where'][] = array('status', '=', sanitize_text_field($_GET['status']));
    }
    return $args;
});
```

## Complete Admin Page Example

```php
// Register admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Customers',
        'Customers',
        'manage_shop',
        'shop-customers',
        'shop_customers_page',
        'dashicons-groups'
    );
});

// Display customers page
function shop_customers_page() {
    $collection = Store::instance('shop')->get_collection('customers');
    
    // Handle bulk actions
    $list_table = new List_Table(array(
        'collection' => $collection,
        'screen' => 'shop-customers',
    ));
    
    $list_table->prepare_items();
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Customers</h1>
        <a href="<?php echo admin_url('admin.php?page=add-customer'); ?>" class="page-title-action">
            Add New
        </a>
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted') : ?>
            <div class="notice notice-success is-dismissible">
                <p>Customer deleted successfully.</p>
            </div>
        <?php endif; ?>
        
        <form method="get">
            <input type="hidden" name="page" value="shop-customers" />
            <?php
            $list_table->search_box('Search Customers', 'customer');
            $list_table->display();
            ?>
        </form>
    </div>
    <?php
}
```

## Advanced Customization

### Custom Column Content

```php
// Override specific column output
add_action('shop_customers_list_table_column_email', function($customer) {
    $email = $customer->get('email');
    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
});

add_action('shop_customers_list_table_column_created_at', function($customer) {
    $date = $customer->get('created_at');
    echo human_time_diff(strtotime($date), current_time('timestamp')) . ' ago';
});
```

### Adding Views (Filter Links)

```php
// Add status filter links above the table
add_filter('shop_customers_list_table_views', function($views, $collection) {
    $total = $collection->count();
    $active = $collection->count(array('where' => array(array('status', '=', 'active'))));
    $inactive = $collection->count(array('where' => array(array('status', '=', 'inactive'))));
    
    $current = isset($_GET['view']) ? $_GET['view'] : 'all';
    
    $views = array(
        'all' => sprintf(
            '<a href="%s" class="%s">All <span class="count">(%d)</span></a>',
            admin_url('admin.php?page=shop-customers'),
            $current === 'all' ? 'current' : '',
            $total
        ),
        'active' => sprintf(
            '<a href="%s" class="%s">Active <span class="count">(%d)</span></a>',
            admin_url('admin.php?page=shop-customers&view=active'),
            $current === 'active' ? 'current' : '',
            $active
        ),
        'inactive' => sprintf(
            '<a href="%s" class="%s">Inactive <span class="count">(%d)</span></a>',
            admin_url('admin.php?page=shop-customers&view=inactive'),
            $current === 'inactive' ? 'current' : '',
            $inactive
        ),
    );
    
    return $views;
}, 10, 2);

// Apply view filter
add_filter('shop_customers_list_table_query_args', function($args) {
    if (isset($_GET['view'])) {
        switch ($_GET['view']) {
            case 'active':
                $args['where'][] = array('status', '=', 'active');
                break;
            case 'inactive':
                $args['where'][] = array('status', '=', 'inactive');
                break;
        }
    }
    return $args;
});
```

### Inline Editing

```php
// Make a column inline-editable
add_filter('shop_customers_list_table_column_status', function($customer) {
    $status = $customer->get('status');
    ?>
    <select class="inline-edit-status" data-id="<?php echo $customer->get_id(); ?>">
        <option value="active" <?php selected($status, 'active'); ?>>Active</option>
        <option value="inactive" <?php selected($status, 'inactive'); ?>>Inactive</option>
    </select>
    <?php
});

// Handle AJAX update
add_action('wp_ajax_update_customer_status', function() {
    check_ajax_referer('update-status');
    
    $id = intval($_POST['id']);
    $status = sanitize_text_field($_POST['status']);
    
    $customer = Store::instance('shop')
        ->get_collection('customers')
        ->get($id);
    
    if ($customer) {
        $customer->set('status', $status);
        $customer->save();
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});
```

## Configuration Options

```php
$list_table = new List_Table(array(
    'collection' => $collection,     // Required: The collection to display
    'screen' => 'customers',         // Required: Screen ID
    'per_page' => 25,               // Items per page (default: 25)
    'orderby' => 'created_at',      // Default sort field
    'order' => 'DESC',              // Default sort order
));
```

## Available Hooks

### Filters

- `{namespace}_{collection}_list_table_columns` - Customize columns
- `{namespace}_{collection}_list_table_sortable_columns` - Define sortable columns
- `{namespace}_{collection}_list_table_bulk_actions` - Add bulk actions
- `{namespace}_{collection}_list_table_row_actions` - Add row actions
- `{namespace}_{collection}_list_table_query_args` - Modify query
- `{namespace}_{collection}_list_table_views` - Add filter views

### Actions

- `{namespace}_{collection}_list_table_column_{column_name}` - Render column
- `{namespace}_{collection}_list_table_filters` - Add filter UI
- `{namespace}_{collection}_list_table_bulk_action_{action}` - Handle bulk action

## Styling

The list table inherits WordPress core styling. You can add custom CSS:

```css
/* Custom status badges */
.status-active {
    color: #46b450;
    font-weight: 600;
}

.status-inactive {
    color: #dc3232;
}

/* Custom column widths */
.wp-list-table .column-email {
    width: 25%;
}

.wp-list-table .column-status {
    width: 10%;
}
```

## Best Practices

1. **Keep it simple** - Don't show too many columns
2. **Make important columns sortable** - Helps users find data
3. **Add search** - Always include search functionality
4. **Use bulk actions** - For operations on multiple items
5. **Show meaningful data** - Display the most important information first
6. **Add filters** - Help users narrow down results
7. **Pagination** - Don't load thousands of rows at once

## See Also

- [Collection](collection.md) - Working with collections
- [Query](query.md) - Querying data
- [REST_Controller](rest-controller.md) - REST API alternative
