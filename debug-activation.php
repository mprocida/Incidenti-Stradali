<?php
/**
 * Debug file for Incidenti Stradali Plugin activation issues
 * Place this file in the plugin root and access it via browser to check for issues
 * URL: yoursite.com/wp-content/plugins/incidenti-stradali/debug-activation.php
 */

// Basic WordPress environment check
if (!defined('ABSPATH')) {
    // Manually load WordPress if not already loaded
    $wp_config_path = __DIR__ . '/../../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
    } else {
        die('WordPress not found. Run this from the plugin directory.');
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Incidenti Stradali Plugin - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; background: #fee; padding: 10px; border: 1px solid red; }
        .success { color: green; background: #efe; padding: 10px; border: 1px solid green; }
        .warning { color: orange; background: #ffe; padding: 10px; border: 1px solid orange; }
        .info { color: blue; background: #eef; padding: 10px; border: 1px solid blue; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Debug Incidenti Stradali Plugin</h1>
    
    <?php
    echo '<h2>1. Environment Check</h2>';
    
    // PHP Version
    echo '<div class="' . (version_compare(PHP_VERSION, '7.4', '>=') ? 'success' : 'error') . '">';
    echo '<strong>PHP Version:</strong> ' . PHP_VERSION . ' (Required: 7.4+)';
    echo '</div>';
    
    // WordPress Version
    if (function_exists('get_bloginfo')) {
        $wp_version = get_bloginfo('version');
        echo '<div class="' . (version_compare($wp_version, '5.0', '>=') ? 'success' : 'error') . '">';
        echo '<strong>WordPress Version:</strong> ' . $wp_version . ' (Required: 5.0+)';
        echo '</div>';
    } else {
        echo '<div class="error"><strong>WordPress:</strong> Not properly loaded</div>';
    }
    
    // Memory Limit
    $memory_limit = ini_get('memory_limit');
    echo '<div class="info"><strong>Memory Limit:</strong> ' . $memory_limit . '</div>';
    
    // Plugin Directory
    $plugin_dir = __DIR__;
    echo '<div class="info"><strong>Plugin Directory:</strong> ' . $plugin_dir . '</div>';
    
    echo '<h2>2. File Structure Check</h2>';
    
    $required_files = array(
        'incidenti-stradali.php' => 'Main plugin file',
        'includes/class-custom-post-type.php' => 'Custom Post Type class',
        'includes/class-meta-boxes.php' => 'Meta Boxes class',
        'includes/class-user-roles.php' => 'User Roles class',
        'includes/class-export-functions.php' => 'Export Functions class',
        'includes/class-validation.php' => 'Validation class',
        'includes/class-shortcodes.php' => 'Shortcodes class',
        'includes/class-admin-settings.php' => 'Admin Settings class'
    );
    
    foreach ($required_files as $file => $description) {
        $file_path = $plugin_dir . '/' . $file;
        $exists = file_exists($file_path);
        $class = $exists ? 'success' : 'error';
        
        echo '<div class="' . $class . '">';
        echo '<strong>' . $file . ':</strong> ' . ($exists ? 'Found' : 'Missing') . ' - ' . $description;
        if ($exists) {
            echo ' (' . number_format(filesize($file_path)) . ' bytes)';
        }
        echo '</div>';
    }
    
    echo '<h2>3. Class Loading Test</h2>';
    
    // Test loading main plugin file
    try {
        if (file_exists($plugin_dir . '/incidenti-stradali.php')) {
            ob_start();
            include_once $plugin_dir . '/incidenti-stradali.php';
            $output = ob_get_clean();
            
            echo '<div class="success"><strong>Main file loaded successfully</strong></div>';
            
            if (!empty($output)) {
                echo '<div class="warning"><strong>Output during load:</strong><pre>' . htmlspecialchars($output) . '</pre></div>';
            }
        } else {
            echo '<div class="error"><strong>Main plugin file not found!</strong></div>';
        }
    } catch (Exception $e) {
        echo '<div class="error"><strong>Error loading main file:</strong> ' . $e->getMessage() . '</div>';
    }
    
    // Test individual class files
    $class_files = array(
        'includes/class-custom-post-type.php' => 'IncidentiCustomPostType',
        'includes/class-user-roles.php' => 'IncidentiUserRoles',
        'includes/class-export-functions.php' => 'Incidenti_Export_Functions',
        'includes/class-admin-settings.php' => 'IncidentiAdminSettings'
    );
    
    foreach ($class_files as $file => $expected_class) {
        $file_path = $plugin_dir . '/' . $file;
        
        if (file_exists($file_path)) {
            try {
                ob_start();
                include_once $file_path;
                $output = ob_get_clean();
                
                if (class_exists($expected_class)) {
                    echo '<div class="success"><strong>' . $file . ':</strong> Class ' . $expected_class . ' loaded successfully</div>';
                } else {
                    echo '<div class="error"><strong>' . $file . ':</strong> Class ' . $expected_class . ' not found after include</div>';
                }
                
                if (!empty($output)) {
                    echo '<div class="warning"><strong>Output from ' . $file . ':</strong><pre>' . htmlspecialchars($output) . '</pre></div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error"><strong>' . $file . ':</strong> Error - ' . $e->getMessage() . '</div>';
            }
        }
    }
    
    echo '<h2>4. WordPress Functions Check</h2>';
    
    $wp_functions = array(
        'add_action', 'add_filter', 'register_post_type', 'add_meta_box', 
        'wp_enqueue_script', 'wp_enqueue_style', 'get_option', 'update_option'
    );
    
    foreach ($wp_functions as $func) {
        $exists = function_exists($func);
        $class = $exists ? 'success' : 'error';
        echo '<div class="' . $class . '"><strong>' . $func . '():</strong> ' . ($exists ? 'Available' : 'Missing') . '</div>';
    }
    
    echo '<h2>5. Database Check</h2>';
    
    if (function_exists('is_wp_error') && isset($GLOBALS['wpdb'])) {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            echo '<div class="success"><strong>Database Connection:</strong> OK</div>';
            
            // Check if posts table exists
            $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'");
            if ($table_check) {
                echo '<div class="success"><strong>Posts Table:</strong> Found</div>';
            } else {
                echo '<div class="error"><strong>Posts Table:</strong> Missing</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error"><strong>Database Error:</strong> ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="error"><strong>Database:</strong> WordPress database not available</div>';
    }
    
    echo '<h2>6. Plugin Activation Simulation</h2>';
    
    try {
        // Try to simulate what happens during plugin activation
        if (class_exists('IncidentiStradaliPlugin')) {
            echo '<div class="success"><strong>IncidentiStradaliPlugin class:</strong> Found</div>';
            
            // Try to instantiate (but don't actually activate)
            $reflection = new ReflectionClass('IncidentiStradaliPlugin');
            echo '<div class="success"><strong>Class instantiation:</strong> Ready</div>';
            
            // Check methods
            $methods = $reflection->getMethods();
            echo '<div class="info"><strong>Available methods:</strong> ' . implode(', ', array_map(function($m) { return $m->name; }, $methods)) . '</div>';
            
        } else {
            echo '<div class="error"><strong>IncidentiStradaliPlugin class:</strong> Not found</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error"><strong>Activation simulation failed:</strong> ' . $e->getMessage() . '</div>';
    }
    
    echo '<h2>7. Error Log Check</h2>';
    
    // Check for recent PHP errors
    $error_log_locations = array(
        ini_get('error_log'),
        ABSPATH . 'wp-content/debug.log',
        ABSPATH . 'error_log',
        '/var/log/php_errors.log'
    );
    
    foreach ($error_log_locations as $log_file) {
        if ($log_file && file_exists($log_file) && is_readable($log_file)) {
            $recent_errors = shell_exec("tail -20 " . escapeshellarg($log_file) . " 2>/dev/null | grep -i incidenti");
            if ($recent_errors) {
                echo '<div class="warning"><strong>Recent errors in ' . $log_file . ':</strong><pre>' . htmlspecialchars($recent_errors) . '</pre></div>';
            } else {
                echo '<div class="info"><strong>Error log ' . $log_file . ':</strong> No recent Incidenti-related errors</div>';
            }
            break; // Only check the first available log
        }
    }
    
    echo '<h2>8. Recommended Actions</h2>';
    
    echo '<div class="info">';
    echo '<p><strong>If you see errors above, try these steps:</strong></p>';
    echo '<ol>';
    echo '<li>Check that all files are uploaded correctly</li>';
    echo '<li>Verify file permissions (644 for files, 755 for directories)</li>';
    echo '<li>Enable WordPress debug mode in wp-config.php</li>';
    echo '<li>Check the exact error message in the error logs</li>';
    echo '<li>Try activating the plugin from command line: <code>wp plugin activate incidenti-stradali</code></li>';
    echo '</ol>';
    echo '</div>';
    
    echo '<h2>9. WordPress Debug Mode</h2>';
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="success"><strong>WP_DEBUG:</strong> Enabled</div>';
    } else {
        echo '<div class="warning"><strong>WP_DEBUG:</strong> Disabled - Enable it in wp-config.php for better error reporting</div>';
        echo '<div class="info">Add these lines to wp-config.php:<pre>define(\'WP_DEBUG\', true);\ndefine(\'WP_DEBUG_LOG\', true);\ndefine(\'WP_DEBUG_DISPLAY\', false);</pre></div>';
    }
    ?>
    
    <h2>10. Quick Fix</h2>
    <div class="info">
        <p><strong>If the plugin still won't activate, try this minimal version of the main file:</strong></p>
        <p>Replace the content of <code>incidenti-stradali.php</code> with this minimal version to test:</p>
        <textarea style="width:100%; height:200px;">&lt;?php
/*
Plugin Name: Incidenti Stradali ISTAT (Debug)
Description: Minimal version for debugging
Version: 1.0.0-debug
*/

if (!defined('ABSPATH')) exit;

// Only register the post type for now
add_action('init', function() {
    register_post_type('incidente_stradale', array(
        'labels' => array(
            'name' => 'Incidenti Stradali',
            'singular_name' => 'Incidente Stradale'
        ),
        'public' => true,
        'menu_icon' => 'dashicons-warning'
    ));
});

// Simple admin notice
add_action('admin_notices', function() {
    echo '&lt;div class="notice notice-success">&lt;p>Plugin Incidenti Stradali caricato correttamente (versione debug)&lt;/p>&lt;/div>';
});
</textarea>
    </div>

</body>
</html>