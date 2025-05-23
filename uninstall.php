<?php
/**
 * Uninstall script for Incidenti Stradali Plugin
 * This file is called when the plugin is deleted from WordPress admin
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
class IncidentiUninstaller {
    
    public static function uninstall() {
        // Check if user has permission to delete plugins
        if (!current_user_can('delete_plugins')) {
            return;
        }
        
        // Ask user what to do with data
        $keep_data = get_option('incidenti_keep_data_on_uninstall', false);
        
        if (!$keep_data) {
            self::delete_posts();
            self::delete_post_meta();
            self::delete_options();
            self::delete_user_meta();
            self::delete_database_tables();
            self::delete_uploaded_files();
            self::remove_capabilities();
            self::cleanup_cron_jobs();
        }
        
        // Always remove transients and cache
        self::delete_transients();
        self::flush_rewrite_rules();
    }
    
    /**
     * Delete all incidente_stradale posts
     */
    private static function delete_posts() {
        global $wpdb;
        
        // Get all incidente_stradale posts
        $posts = get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        // Delete each post and its meta
        foreach ($posts as $post_id) {
            wp_delete_post($post_id, true); // Force delete
        }
        
        // Clean up any remaining orphaned posts
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->posts} WHERE post_type = %s",
            'incidente_stradale'
        ));
        
        // Log deletion
        error_log(sprintf('Incidenti Plugin: Deleted %d posts', count($posts)));
    }
    
    /**
     * Delete all post meta related to incidenti
     */
    private static function delete_post_meta() {
        global $wpdb;
        
        $meta_keys = array(
            'data_incidente', 'ora_incidente', 'minuti_incidente', 
            'provincia_incidente', 'comune_incidente', 'organo_rilevazione',
            'organo_coordinatore', 'nell_abitato', 'tipo_strada', 
            'denominazione_strada', 'numero_strada', 'progressiva_km',
            'progressiva_m', 'geometria_strada', 'pavimentazione_strada',
            'intersezione_tronco', 'stato_fondo_strada', 'segnaletica_strada',
            'condizioni_meteo', 'natura_incidente', 'dettaglio_natura',
            'numero_veicoli_coinvolti', 'numero_pedoni_coinvolti',
            'latitudine', 'longitudine', 'tipo_coordinata', 'mostra_in_mappa'
        );
        
        // Add vehicle meta keys
        for ($i = 1; $i <= 3; $i++) {
            $meta_keys = array_merge($meta_keys, array(
                "veicolo_{$i}_tipo", "veicolo_{$i}_targa", "veicolo_{$i}_anno_immatricolazione",
                "veicolo_{$i}_cilindrata", "veicolo_{$i}_peso_totale"
            ));
        }
        
        // Add driver meta keys
        for ($i = 1; $i <= 3; $i++) {
            $meta_keys = array_merge($meta_keys, array(
                "conducente_{$i}_eta", "conducente_{$i}_sesso", "conducente_{$i}_esito",
                "conducente_{$i}_tipo_patente", "conducente_{$i}_anno_patente"
            ));
        }
        
        // Add pedestrian meta keys
        for ($i = 1; $i <= 4; $i++) {
            $meta_keys = array_merge($meta_keys, array(
                "pedone_{$i}_eta", "pedone_{$i}_sesso", "pedone_{$i}_esito"
            ));
        }
        
        // Delete meta entries
        foreach ($meta_keys as $meta_key) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                $meta_key
            ));
        }
        
        error_log('Incidenti Plugin: Deleted post meta data');
    }
    
    /**
     * Delete plugin options
     */
    private static function delete_options() {
        $options = array(
            'incidenti_data_blocco_modifica',
            'incidenti_map_center_lat',
            'incidenti_map_center_lng',
            'incidenti_export_path',
            'incidenti_auto_export_enabled',
            'incidenti_auto_export_frequency',
            'incidenti_auto_export_email',
            'incidenti_restrict_by_ip',
            'incidenti_allowed_ips',
            'incidenti_notify_new_incident',
            'incidenti_notification_emails',
            'incidenti_map_provider',
            'incidenti_map_api_key',
            'incidenti_map_cluster_enabled',
            'incidenti_map_cluster_radius',
            'incidenti_dashboard_widget_options',
            'incidenti_show_welcome_notice',
            'incidenti_keep_data_on_uninstall',
            'incidenti_plugin_version',
            'incidenti_db_version'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        error_log('Incidenti Plugin: Deleted plugin options');
    }
    
    /**
     * Delete user meta related to incidenti
     */
    private static function delete_user_meta() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'comune_assegnato'"
        );
        
        error_log('Incidenti Plugin: Deleted user meta data');
    }
    
    /**
     * Delete custom database tables
     */
    private static function delete_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'incidenti_export_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        error_log('Incidenti Plugin: Deleted custom database tables');
    }
    
    /**
     * Delete uploaded files
     */
    private static function delete_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $incidenti_dir = $upload_dir['basedir'] . '/incidenti-exports';
        
        if (is_dir($incidenti_dir)) {
            self::delete_directory($incidenti_dir);
        }
        
        error_log('Incidenti Plugin: Deleted uploaded files');
    }
    
    /**
     * Remove custom capabilities from roles
     */
    private static function remove_capabilities() {
        $capabilities = array(
            'manage_all_incidenti',
            'edit_incidenti',
            'read_incidenti',
            'delete_incidenti',
            'publish_incidenti',
            'edit_others_incidenti',
            'read_private_incidenti',
            'delete_others_incidenti',
            'export_incidenti',
            'edit_incidente',
            'read_incidente',
            'delete_incidente',
            'edit_private_incidenti',
            'edit_published_incidenti'
        );
        
        // Remove from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
        
        // Remove custom role
        remove_role('operatore_polizia_comunale');
        
        error_log('Incidenti Plugin: Removed custom roles and capabilities');
    }
    
    /**
     * Clean up cron jobs
     */
    private static function cleanup_cron_jobs() {
        wp_clear_scheduled_hook('incidenti_auto_export');
        wp_clear_scheduled_hook('incidenti_cleanup_exports');
        wp_clear_scheduled_hook('incidenti_data_integrity_check');
        
        error_log('Incidenti Plugin: Cleaned up cron jobs');
    }
    
    /**
     * Delete transients
     */
    private static function delete_transients() {
        global $wpdb;
        
        // Delete specific transients
        $transients = array(
            'incidenti_dashboard_stats',
            'incidenti_map_data',
            'incidenti_export_progress',
            'incidenti_validation_errors'
        );
        
        foreach ($transients as $transient) {
            delete_transient($transient);
        }
        
        // Delete any remaining incidenti-related transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_incidenti_%' 
             OR option_name LIKE '_transient_timeout_incidenti_%'"
        );
        
        error_log('Incidenti Plugin: Deleted transients');
    }
    
    /**
     * Flush rewrite rules
     */
    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
        error_log('Incidenti Plugin: Flushed rewrite rules');
    }
    
    /**
     * Recursively delete directory
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Create uninstall survey (optional)
     */
    private static function create_uninstall_survey() {
        $admin_email = get_option('admin_email');
        $site_url = get_site_url();
        
        $survey_data = array(
            'site_url' => $site_url,
            'admin_email' => $admin_email,
            'uninstall_date' => current_time('mysql'),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => get_option('incidenti_plugin_version', '1.0.0')
        );
        
        // Optional: Send anonymous usage data
        if (get_option('incidenti_allow_usage_tracking', false)) {
            wp_remote_post('https://stats.plugin-incidenti.it/uninstall', array(
                'body' => $survey_data,
                'timeout' => 5
            ));
        }
    }
}

// Run uninstall
IncidentiUninstaller::uninstall();

// Final cleanup
wp_cache_flush();

// Log completion
error_log('Incidenti Stradali Plugin: Uninstall completed successfully');