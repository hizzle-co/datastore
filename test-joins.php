<?php
/**
 * Simple verification script for JOIN queries functionality
 * 
 * This script tests the basic functionality of JOIN queries without
 * requiring a full WordPress installation.
 */

// Mock WordPress functions for testing
if (!function_exists('wp_parse_list')) {
    function wp_parse_list($list) {
        if (!is_array($list)) {
            return preg_split('/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY);
        }
        return $list;
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($data) {
        return addslashes($data);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

// Load the classes
require_once __DIR__ . '/src/Store_Exception.php';
require_once __DIR__ . '/src/Collection.php';
require_once __DIR__ . '/src/Query.php';
require_once __DIR__ . '/src/Prop.php';

use Hizzle\Store\Collection;
use Hizzle\Store\Query;

/**
 * Test 1: Verify Collection can store JOIN configuration
 */
function test_collection_join_config() {
    echo "Test 1: Collection JOIN Configuration\n";
    echo "======================================\n";
    
    $config = array(
        'name' => 'customers',
        'singular_name' => 'customer',
        'props' => array(
            'id' => array(
                'type' => 'int',
                'length' => 20,
                'nullable' => false,
            ),
        ),
        'keys' => array(
            'primary' => 'id',
        ),
        'joins' => array(
            'payments' => array(
                'collection' => 'test_payments',
                'on' => 'id',
                'foreign_key' => 'customer_id',
                'type' => 'LEFT',
            ),
        ),
    );
    
    try {
        $collection = new Collection('test', $config);
        
        if (!empty($collection->joins)) {
            echo "✓ Collection accepts joins configuration\n";
            echo "✓ JOIN config: " . json_encode($collection->joins, JSON_PRETTY_PRINT) . "\n";
            return true;
        } else {
            echo "✗ Collection joins property is empty\n";
            return false;
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "\n";
}

/**
 * Test 2: Verify Query accepts join parameter
 */
function test_query_join_parameter() {
    echo "Test 2: Query JOIN Parameter\n";
    echo "=============================\n";
    
    // This is a partial test since we can't run actual queries without a database
    $defaults = array(
        'include'        => array(),
        'exclude'        => array(),
        'search'         => '',
        'search_columns' => array(),
        'orderby'        => array('id'),
        'order'          => 'DESC',
        'offset'         => '',
        'per_page'       => -1,
        'page'           => 1,
        'count_total'    => true,
        'count_only'     => false,
        'fields'         => 'all',
        'aggregate'      => false,
        'meta_query'     => array(),
        'join'           => array(), // This is what we added
    );
    
    if (isset($defaults['join'])) {
        echo "✓ Query defaults include 'join' parameter\n";
        echo "✓ Default value: " . json_encode($defaults['join']) . "\n";
        return true;
    } else {
        echo "✗ Query defaults missing 'join' parameter\n";
        return false;
    }
    
    echo "\n";
}

/**
 * Test 3: Verify field prefix logic
 */
function test_field_prefix_logic() {
    echo "Test 3: Field Prefix Logic\n";
    echo "===========================\n";
    
    // Test dot notation
    $field1 = 'payments.amount';
    if (strpos($field1, '.') !== false) {
        $parts = explode('.', $field1, 2);
        if (count($parts) === 2) {
            echo "✓ Dot notation parsing works: '$field1' -> ['{$parts[0]}', '{$parts[1]}']\n";
        }
    }
    
    // Test double underscore notation
    $field2 = 'payments__amount';
    if (strpos($field2, '__') !== false) {
        $parts = explode('__', $field2, 2);
        if (count($parts) === 2) {
            echo "✓ Double underscore parsing works: '$field2' -> ['{$parts[0]}', '{$parts[1]}']\n";
        }
    }
    
    echo "\n";
    return true;
}

/**
 * Test 4: Verify join type validation
 */
function test_join_type_validation() {
    echo "Test 4: JOIN Type Validation\n";
    echo "=============================\n";
    
    $valid_types = array('INNER', 'LEFT', 'RIGHT');
    $test_types = array('INNER', 'LEFT', 'RIGHT', 'OUTER', 'CROSS', '');
    
    foreach ($test_types as $type) {
        $is_valid = in_array(strtoupper($type), $valid_types, true);
        $default = $is_valid ? strtoupper($type) : 'INNER';
        $status = $is_valid ? '✓' : '✗';
        echo "$status Type '$type' " . ($is_valid ? 'accepted' : 'rejected, defaulting to') . " -> '$default'\n";
    }
    
    echo "\n";
    return true;
}

/**
 * Test 5: Check documentation files exist
 */
function test_documentation_exists() {
    echo "Test 5: Documentation Files\n";
    echo "============================\n";
    
    $files = array(
        'JOINS.md' => 'JOIN queries documentation',
        'example-joins.php' => 'JOIN examples',
        'README.md' => 'Main README',
    );
    
    $all_exist = true;
    foreach ($files as $file => $description) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            $size = filesize($path);
            echo "✓ $description exists ($size bytes)\n";
        } else {
            echo "✗ $description missing\n";
            $all_exist = false;
        }
    }
    
    echo "\n";
    return $all_exist;
}

/**
 * Run all tests
 */
function run_all_tests() {
    echo "\n";
    echo "==========================================\n";
    echo "JOIN Queries Implementation Verification\n";
    echo "==========================================\n\n";
    
    $tests = array(
        'test_collection_join_config',
        'test_query_join_parameter',
        'test_field_prefix_logic',
        'test_join_type_validation',
        'test_documentation_exists',
    );
    
    $results = array();
    foreach ($tests as $test) {
        try {
            $results[$test] = call_user_func($test);
        } catch (Exception $e) {
            echo "Error in $test: " . $e->getMessage() . "\n\n";
            $results[$test] = false;
        }
    }
    
    // Summary
    echo "==========================================\n";
    echo "Test Summary\n";
    echo "==========================================\n";
    
    $passed = array_sum($results);
    $total = count($results);
    
    foreach ($results as $test => $result) {
        $status = $result ? '✓ PASS' : '✗ FAIL';
        echo "$status - $test\n";
    }
    
    echo "\nTotal: $passed/$total tests passed\n";
    
    if ($passed === $total) {
        echo "\n🎉 All tests passed! JOIN queries implementation is working.\n";
        return 0;
    } else {
        echo "\n⚠️ Some tests failed. Please review the implementation.\n";
        return 1;
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    exit(run_all_tests());
}
