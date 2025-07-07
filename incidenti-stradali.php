<?php
/**
 * Plugin Name: Incidenti Stradali ISTAT
 * Plugin URI: https://example.com
 * Description: Plugin per la gestione degli incidenti stradali secondo il formato ISTAT
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check minimum WordPress version
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('Il plugin Incidenti Stradali richiede WordPress 5.0 o superiore.', 'incidenti-stradali');
        echo '</p></div>';
    });
    return;
}

// Check minimum PHP version  
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('Il plugin Incidenti Stradali richiede PHP 7.4 o superiore.', 'incidenti-stradali');
        echo '</p></div>';
    });
    return;
}

// Define plugin constants
define('INCIDENTI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INCIDENTI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('INCIDENTI_VERSION', '1.0.0');

class IncidentiStradaliPlugin {
    
    public function __construct() {
        // Include required files first
        $this->include_files();
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        add_filter('single_template', array($this, 'load_single_template'));

        // AGGIUNGI QUESTA RIGA TEMPORANEAMENTE:
        add_action('init', function() { flush_rewrite_rules(); }, 999);

        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function include_files() {
        // Check if files exist before including them
        $files_to_include = array(
            'includes/class-custom-post-type.php',
            'includes/class-meta-boxes.php', 
            'includes/class-user-roles.php',
            'includes/class-export-functions.php',
            'includes/class-import-functions.php',
            'includes/class-validation.php',
            'includes/class-shortcodes.php',
            'includes/class-admin-settings.php',
            'includes/class-email-notifications.php',
            'includes/class-delete-handler.php'
        );
        
        foreach ($files_to_include as $file) {
            $file_path = INCIDENTI_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('Incidenti Plugin: File not found - ' . $file_path);
            }
        }
    }
    
    public function init() {
        // Load text domain FIRST
        load_plugin_textdomain('incidenti-stradali', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize all classes
        if (class_exists('IncidentiCustomPostType')) {
            new IncidentiCustomPostType();
        }
        
        if (class_exists('IncidentiMetaBoxes')) {
            new IncidentiMetaBoxes();
        }
        
        if (class_exists('IncidentiUserRoles')) {
            new IncidentiUserRoles();
        }
        
        if (class_exists('IncidentiExportFunctions')) {
            new IncidentiExportFunctions();
        }

        if (class_exists('IncidentiImportFunctions')) {
            new IncidentiImportFunctions();
        }
        
        if (class_exists('IncidentiValidation')) {
            new IncidentiValidation();
        }
        
        if (class_exists('IncidentiShortcodes')) {
            new IncidentiShortcodes();
        }
        
        if (class_exists('IncidentiAdminSettings')) {
            new IncidentiAdminSettings();
        }
        
        // NUOVO: Inizializza gestore eliminazioni
        if (class_exists('IncidentiDeleteHandler')) {
            new IncidentiDeleteHandler();
        }
        
        // Flush rewrite rules if needed
        if (get_option('incidenti_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('incidenti_flush_rewrite_rules');
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('incidenti-frontend', INCIDENTI_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), INCIDENTI_VERSION, true);
        wp_enqueue_style('incidenti-frontend', INCIDENTI_PLUGIN_URL . 'assets/css/frontend.css', array(), INCIDENTI_VERSION);
        
        // Leaflet for maps
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', array(), '1.7.1');
        
        // Localize script for AJAX
        wp_localize_script('incidenti-frontend', 'incidenti_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('incidenti_nonce')
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        // Carica script solo nelle pagine del plugin
        if ($post_type === 'incidente_stradale' || 
            strpos($hook, 'incidenti') !== false || 
            $hook === 'post.php' || 
            $hook === 'post-new.php') {

            // AGGIUNGI LEAFLET PER L'ADMIN
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', array(), '1.7.1');
        
            
            wp_enqueue_script('incidenti-admin', INCIDENTI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), INCIDENTI_VERSION, true);
            wp_enqueue_style('incidenti-admin', INCIDENTI_PLUGIN_URL . 'assets/css/admin.css', array(), INCIDENTI_VERSION);
            
            // Localizza script per AJAX
            wp_localize_script('incidenti-admin', 'incidenti_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('incidenti_ajax_nonce')
            ));
            
            // Date picker
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-datepicker', '//code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        }
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Add user roles and capabilities (only if class exists)
        if (class_exists('IncidentiUserRoles')) {
            $user_roles = new IncidentiUserRoles();
            if (method_exists($user_roles, 'add_roles_and_capabilities')) {
                $user_roles->add_roles_and_capabilities();
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for export logs
        $table_name = $wpdb->prefix . 'incidenti_export_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            export_type varchar(20) NOT NULL,
            export_date datetime DEFAULT CURRENT_TIMESTAMP,
            file_path varchar(255) NOT NULL,
            records_count int(11) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Carica template personalizzato per incidenti
     */
    public function load_single_template($template) {
        global $post;
        
        if ($post && $post->post_type == 'incidente_stradale') {
            $plugin_template = INCIDENTI_PLUGIN_PATH . 'templates/single-incidente_stradale.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
}

// Initialize the plugin
new IncidentiStradaliPlugin();
