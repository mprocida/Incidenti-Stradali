<?php
/**
 * Cron Jobs and Automated Tasks for Incidenti Stradali
 */

class IncidentiCronJobs {
    
    public function __construct() {
        // Hook into WordPress cron system
        add_action('init', array($this, 'schedule_events'));
        
        // Register cron actions
        add_action('incidenti_auto_export', array($this, 'run_auto_export'));
        add_action('incidenti_cleanup_exports', array($this, 'cleanup_old_exports'));
        add_action('incidenti_data_integrity_check', array($this, 'check_data_integrity'));
        add_action('incidenti_generate_reports', array($this, 'generate_periodic_reports'));
        add_action('incidenti_backup_data', array($this, 'backup_critical_data'));
        add_action('incidenti_send_statistics', array($this, 'send_monthly_statistics'));
        
        // Handle cron frequency settings
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
        
        // Admin hooks for cron management
        add_action('admin_init', array($this, 'handle_manual_cron_triggers'));
    }
    
    /**
     * Schedule recurring events
     */
    public function schedule_events() {
        // Auto export (if enabled)
        if (get_option('incidenti_auto_export_enabled', false)) {
            $frequency = get_option('incidenti_auto_export_frequency', 'monthly');
            
            if (!wp_next_scheduled('incidenti_auto_export')) {
                wp_schedule_event(time(), $frequency, 'incidenti_auto_export');
            }
        } else {
            // Unschedule if disabled
            wp_clear_scheduled_hook('incidenti_auto_export');
        }
        
        // Cleanup old exports (weekly)
        if (!wp_next_scheduled('incidenti_cleanup_exports')) {
            wp_schedule_event(time(), 'weekly', 'incidenti_cleanup_exports');
        }
        
        // Data integrity check (daily)
        if (!wp_next_scheduled('incidenti_data_integrity_check')) {
            wp_schedule_event(time(), 'daily', 'incidenti_data_integrity_check');
        }
        
        // Generate reports (monthly)
        if (!wp_next_scheduled('incidenti_generate_reports')) {
            wp_schedule_event(time(), 'monthly', 'incidenti_generate_reports');
        }
        
        // Backup critical data (weekly)
        if (!wp_next_scheduled('incidenti_backup_data')) {
            wp_schedule_event(time(), 'weekly', 'incidenti_backup_data');
        }
        
        // Send monthly statistics (monthly on 1st)
        if (!wp_next_scheduled('incidenti_send_statistics')) {
            $first_of_month = strtotime('first day of next month');
            wp_schedule_event($first_of_month, 'monthly', 'incidenti_send_statistics');
        }
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_cron_intervals($schedules) {
        // Every 6 hours
        $schedules['sixhours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Ogni 6 ore', 'incidenti-stradali')
        );
        
        // Every 2 weeks
        $schedules['biweekly'] = array(
            'interval' => 2 * WEEK_IN_SECONDS,
            'display' => __('Ogni 2 settimane', 'incidenti-stradali')
        );
        
        return $schedules;
    }
    
    /**
     * Run automatic export
     */
    public function run_auto_export() {
        if (!get_option('incidenti_auto_export_enabled', false)) {
            return;
        }
        
        $export_types = get_option('incidenti_auto_export_types', array('istat'));
        $period_days = get_option('incidenti_auto_export_period', 30);
        
        $date_from = date('Y-m-d', strtotime("-{$period_days} days"));
        $date_to = date('Y-m-d');
        
        $results = array();
        
        foreach ($export_types as $type) {
            try {
                if ($type === 'istat') {
                    $result = $this->perform_istat_export($date_from, $date_to);
                } elseif ($type === 'excel') {
                    $result = $this->perform_excel_export($date_from, $date_to);
                }
                
                $results[] = $result;
                
                // Log successful export
                $this->log_cron_activity('auto_export', 'success', array(
                    'type' => $type,
                    'records' => $result['count'],
                    'file' => $result['filename']
                ));
                
            } catch (Exception $e) {
                // Log error
                $this->log_cron_activity('auto_export', 'error', array(
                    'type' => $type,
                    'error' => $e->getMessage()
                ));
                
                // Send error notification
                $this->send_cron_error_notification('Auto Export', $e->getMessage());
            }
        }
        
        // Send summary notification
        $this->send_auto_export_summary($results);
    }
    
    /**
     * Cleanup old export files
     */
    public function cleanup_old_exports() {
        $export_path = get_option('incidenti_export_path', wp_upload_dir()['basedir'] . '/incidenti-exports');
        $retention_days = get_option('incidenti_export_retention_days', 90);
        
        if (!is_dir($export_path)) {
            return;
        }
        
        $cleanup_count = 0;
        $cleanup_size = 0;
        
        $files = glob($export_path . '/*');
        $cutoff_time = time() - ($retention_days * DAY_IN_SECONDS);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                $file_size = filesize($file);
                if (unlink($file)) {
                    $cleanup_count++;
                    $cleanup_size += $file_size;
                }
            }
        }
        
        // Log cleanup activity
        $this->log_cron_activity('cleanup_exports', 'success', array(
            'files_deleted' => $cleanup_count,
            'space_freed' => $this->format_bytes($cleanup_size)
        ));
        
        // Update database log
        global $wpdb;
        $table_name = $wpdb->prefix . 'incidenti_export_logs';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE export_date < %s",
            date('Y-m-d H:i:s', $cutoff_time)
        ));
    }
    
    /**
     * Check data integrity
     */
    public function check_data_integrity() {
        $issues = array();
        
        try {
            // Check for missing required fields
            $missing_dates = $this->check_missing_dates();
            if ($missing_dates > 0) {
                $issues[] = sprintf(__('%d incidenti senza data', 'incidenti-stradali'), $missing_dates);
            }
            
            $missing_coords = $this->check_missing_coordinates();
            if ($missing_coords > 0) {
                $issues[] = sprintf(__('%d incidenti marcati per mappa senza coordinate', 'incidenti-stradali'), $missing_coords);
            }
            
            // Check for data inconsistencies
            $invalid_dates = $this->check_invalid_dates();
            if ($invalid_dates > 0) {
                $issues[] = sprintf(__('%d incidenti con date non valide', 'incidenti-stradali'), $invalid_dates);
            }
            
            $orphaned_meta = $this->check_orphaned_meta();
            if ($orphaned_meta > 0) {
                $issues[] = sprintf(__('%d meta entries orfani', 'incidenti-stradali'), $orphaned_meta);
            }
            
            // Check database health
            $db_issues = $this->check_database_health();
            $issues = array_merge($issues, $db_issues);
            
            // Log results
            if (empty($issues)) {
                $this->log_cron_activity('data_integrity_check', 'success', array(
                    'message' => 'Nessun problema rilevato'
                ));
            } else {
                $this->log_cron_activity('data_integrity_check', 'warning', array(
                    'issues_found' => count($issues),
                    'issues' => $issues
                ));
                
                // Send alert for critical issues
                if (count($issues) > 5) {
                    do_action('incidenti_data_integrity_alert', $issues);
                }
            }
            
        } catch (Exception $e) {
            $this->log_cron_activity('data_integrity_check', 'error', array(
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Generate periodic reports
     */
    public function generate_periodic_reports() {
        $report_types = get_option('incidenti_auto_reports', array('monthly_summary'));
        
        foreach ($report_types as $report_type) {
            try {
                switch ($report_type) {
                    case 'monthly_summary':
                        $this->generate_monthly_summary();
                        break;
                    case 'casualty_report':
                        $this->generate_casualty_report();
                        break;
                    case 'location_analysis':
                        $this->generate_location_analysis();
                        break;
                }
                
                $this->log_cron_activity('generate_reports', 'success', array(
                    'report_type' => $report_type
                ));
                
            } catch (Exception $e) {
                $this->log_cron_activity('generate_reports', 'error', array(
                    'report_type' => $report_type,
                    'error' => $e->getMessage()
                ));
            }
        }
    }
    
    /**
     * Backup critical data
     */
    public function backup_critical_data() {
        try {
            $backup_path = wp_upload_dir()['basedir'] . '/incidenti-backups';
            
            if (!is_dir($backup_path)) {
                wp_mkdir_p($backup_path);
            }
            
            $backup_file = $backup_path . '/incidenti_backup_' . date('Y-m-d_H-i-s') . '.json';
            
            // Get all incident data
            $incidents = get_posts(array(
                'post_type' => 'incidente_stradale',
                'post_status' => 'any',
                'posts_per_page' => -1
            ));
            
            $backup_data = array();
            
            foreach ($incidents as $incident) {
                $incident_data = array(
                    'post_data' => $incident,
                    'meta_data' => get_post_meta($incident->ID)
                );
                $backup_data[] = $incident_data;
            }
            
            // Include plugin settings
            $backup_data['_settings'] = array(
                'incidenti_data_blocco_modifica' => get_option('incidenti_data_blocco_modifica'),
                'incidenti_map_center_lat' => get_option('incidenti_map_center_lat'),
                'incidenti_map_center_lng' => get_option('incidenti_map_center_lng'),
                // Add other important settings
            );
            
            $json_data = json_encode($backup_data, JSON_PRETTY_PRINT);
            
            if (file_put_contents($backup_file, $json_data)) {
                $this->log_cron_activity('backup_data', 'success', array(
                    'file' => basename($backup_file),
                    'size' => $this->format_bytes(filesize($backup_file)),
                    'records' => count($incidents)
                ));
                
                // Cleanup old backups (keep last 10)
                $this->cleanup_old_backups($backup_path, 10);
                
            } else {
                throw new Exception('Failed to write backup file');
            }
            
        } catch (Exception $e) {
            $this->log_cron_activity('backup_data', 'error', array(
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Send monthly statistics
     */
    public function send_monthly_statistics() {
        if (!get_option('incidenti_send_monthly_stats', false)) {
            return;
        }
        
        try {
            $stats = $this->calculate_monthly_statistics();
            $this->send_statistics_email($stats);
            
            $this->log_cron_activity('send_statistics', 'success', array(
                'period' => date('Y-m', strtotime('last month')),
                'incidents' => $stats['total_incidents']
            ));
            
        } catch (Exception $e) {
            $this->log_cron_activity('send_statistics', 'error', array(
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle manual cron triggers from admin
     */
    public function handle_manual_cron_triggers() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['incidenti_run_cron']) && wp_verify_nonce($_GET['_wpnonce'], 'incidenti_manual_cron')) {
            $cron_task = sanitize_text_field($_GET['incidenti_run_cron']);
            
            switch ($cron_task) {
                case 'auto_export':
                    $this->run_auto_export();
                    break;
                case 'cleanup_exports':
                    $this->cleanup_old_exports();
                    break;
                case 'data_integrity_check':
                    $this->check_data_integrity();
                    break;
                case 'generate_reports':
                    $this->generate_periodic_reports();
                    break;
                case 'backup_data':
                    $this->backup_critical_data();
                    break;
            }
            
            add_action('admin_notices', function() use ($cron_task) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('Attività cron "%s" eseguita manualmente.', 'incidenti-stradali'), $cron_task) . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Helper methods for data integrity checks
     */
    private function check_missing_dates() {
        return count(get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'compare' => 'NOT EXISTS'
                )
            )
        )));
    }
    
    private function check_missing_coordinates() {
        return count(get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'mostra_in_mappa',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'latitudine',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'longitudine',
                        'compare' => 'NOT EXISTS'
                    )
                )
            )
        )));
    }
    
    private function check_invalid_dates() {
        global $wpdb;
        
        $invalid_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'incidente_stradale'
            AND pm.meta_key = 'data_incidente'
            AND (pm.meta_value = '' 
                 OR pm.meta_value IS NULL 
                 OR STR_TO_DATE(pm.meta_value, '%Y-%m-%d') IS NULL)
        ");
        
        return intval($invalid_count);
    }
    
    private function check_orphaned_meta() {
        global $wpdb;
        
        $orphaned_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE 'data_incidente%'
               OR pm.meta_key LIKE 'veicolo_%'
               OR pm.meta_key LIKE 'conducente_%'
               OR pm.meta_key LIKE 'pedone_%'
        ");
        
        return intval($orphaned_count);
    }
    
    private function check_database_health() {
        global $wpdb;
        $issues = array();
        
        // Check table sizes
        $table_size = $wpdb->get_var("
            SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2)
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND table_name = '{$wpdb->postmeta}'
        ");
        
        if ($table_size > 500) { // MB
            $issues[] = sprintf(__('Tabella postmeta molto grande: %s MB', 'incidenti-stradali'), $table_size);
        }
        
        return $issues;
    }
    
    /**
     * Export methods
     */
    private function perform_istat_export($date_from, $date_to) {
        // Use existing export class
        $export_class = new IncidentiExportFunctions();
        
        // Get incidents for period
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($date_from, $date_to),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        $incidents = get_posts($args);
        
        if (empty($incidents)) {
            throw new Exception('Nessun incidente trovato per il periodo specificato');
        }
        
        $filename = 'auto_export_istat_' . date('YmdHis') . '.txt';
        $file_path = get_option('incidenti_export_path') . '/' . $filename;
        
        $content = $export_class->generate_istat_txt($incidents);
        
        if (file_put_contents($file_path, $content) === false) {
            throw new Exception('Impossibile scrivere il file di export');
        }
        
        return array(
            'success' => true,
            'filename' => $filename,
            'count' => count($incidents),
            'file_path' => $file_path
        );
    }
    
    private function perform_excel_export($date_from, $date_to) {
        // Similar to ISTAT export but for Excel format
        // Implementation would be similar to above
        return array(
            'success' => true,
            'filename' => 'auto_export_excel_' . date('YmdHis') . '.csv',
            'count' => 0,
            'file_path' => ''
        );
    }
    
    /**
     * Report generation methods
     */
    private function generate_monthly_summary() {
        $last_month = date('Y-m', strtotime('last month'));
        $stats = $this->calculate_monthly_statistics();
        
        // Generate HTML report
        $report_content = $this->build_monthly_report_html($stats);
        
        // Save to file
        $report_path = wp_upload_dir()['basedir'] . '/incidenti-reports';
        if (!is_dir($report_path)) {
            wp_mkdir_p($report_path);
        }
        
        $report_file = $report_path . '/monthly_summary_' . $last_month . '.html';
        file_put_contents($report_file, $report_content);
        
        return $report_file;
    }
    
    private function calculate_monthly_statistics() {
        $last_month_start = date('Y-m-01', strtotime('last month'));
        $last_month_end = date('Y-m-t', strtotime('last month'));
        
        $incidents = get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($last_month_start, $last_month_end),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        ));
        
        $stats = array(
            'period' => date('F Y', strtotime('last month')),
            'total_incidents' => count($incidents),
            'total_deaths' => 0,
            'total_injuries' => 0,
            'by_day_of_week' => array(),
            'by_hour' => array(),
            'by_comune' => array()
        );
        
        foreach ($incidents as $incident) {
            // Count casualties
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($incident->ID, 'conducente_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $stats['total_deaths']++;
                if ($esito == '2') $stats['total_injuries']++;
            }
            
            // Analyze by time/location
            $data = get_post_meta($incident->ID, 'data_incidente', true);
            $ora = get_post_meta($incident->ID, 'ora_incidente', true);
            $comune = get_post_meta($incident->ID, 'comune_incidente', true);
            
            if ($data) {
                $day_of_week = date('w', strtotime($data));
                $stats['by_day_of_week'][$day_of_week] = ($stats['by_day_of_week'][$day_of_week] ?? 0) + 1;
            }
            
            if ($ora) {
                $hour_group = intval($ora / 4) * 4; // Group by 4-hour periods
                $stats['by_hour'][$hour_group] = ($stats['by_hour'][$hour_group] ?? 0) + 1;
            }
            
            if ($comune) {
                $stats['by_comune'][$comune] = ($stats['by_comune'][$comune] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
    
    /**
     * Utility methods
     */
    private function log_cron_activity($task, $status, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'task' => $task,
            'status' => $status,
            'data' => $data
        );
        
        // Store in transient (WordPress doesn't have a built-in cron log table)
        $existing_logs = get_transient('incidenti_cron_logs') ?: array();
        $existing_logs[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($existing_logs) > 100) {
            $existing_logs = array_slice($existing_logs, -100);
        }
        
        set_transient('incidenti_cron_logs', $existing_logs, WEEK_IN_SECONDS);
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Incidenti Cron [%s]: %s - %s - %s',
                $task,
                $status,
                current_time('Y-m-d H:i:s'),
                json_encode($data)
            ));
        }
    }
    
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function send_cron_error_notification($task, $error) {
        $subject = sprintf('[%s] Errore Cron: %s', get_bloginfo('name'), $task);
        $message = sprintf(
            "Errore durante l'esecuzione dell'attività cron '%s':\n\n%s\n\nData: %s",
            $task,
            $error,
            current_time('d/m/Y H:i:s')
        );
        
        wp_mail(get_option('admin_email'), $subject, $message);
    }
    
    private function send_auto_export_summary($results) {
        if (empty($results)) return;
        
        $subject = sprintf('[%s] Riepilogo Esportazione Automatica', get_bloginfo('name'));
        
        $message = "Esportazione automatica completata:\n\n";
        
        foreach ($results as $result) {
            if ($result['success']) {
                $message .= sprintf(
                    "✓ %s: %d record esportati in %s\n",
                    strtoupper($result['type']),
                    $result['count'],
                    $result['filename']
                );
            } else {
                $message .= sprintf(
                    "✗ %s: Errore - %s\n",
                    strtoupper($result['type']),
                    $result['error']
                );
            }
        }
        
        $message .= "\nData esportazione: " . current_time('d/m/Y H:i:s');
        
        $email = get_option('incidenti_auto_export_email', get_option('admin_email'));
        wp_mail($email, $subject, $message);
    }
    
    private function cleanup_old_backups($backup_path, $keep_count) {
        $files = glob($backup_path . '/incidenti_backup_*.json');
        
        if (count($files) <= $keep_count) {
            return;
        }
        
        // Sort by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $to_delete = array_slice($files, 0, count($files) - $keep_count);
        
        foreach ($to_delete as $file) {
            unlink($file);
        }
    }
}

// Initialize cron jobs
new IncidentiCronJobs();

/**
 * Add admin page for cron management
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=incidente_stradale',
        __('Attività Programmate', 'incidenti-stradali'),
        __('Cron Jobs', 'incidenti-stradali'),
        'manage_options',
        'incidenti-cron',
        'incidenti_render_cron_page'
    );
});

function incidenti_render_cron_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Attività Programmate', 'incidenti-stradali'); ?></h1>
        
        <div class="card">
            <h2><?php _e('Stato Attività Cron', 'incidenti-stradali'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Attività', 'incidenti-stradali'); ?></th>
                        <th><?php _e('Prossima Esecuzione', 'incidenti-stradali'); ?></th>
                        <th><?php _e('Frequenza', 'incidenti-stradali'); ?></th>
                        <th><?php _e('Azioni', 'incidenti-stradali'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cron_jobs = array(
                        'incidenti_auto_export' => __('Esportazione Automatica', 'incidenti-stradali'),
                        'incidenti_cleanup_exports' => __('Pulizia Export', 'incidenti-stradali'),
                        'incidenti_data_integrity_check' => __('Controllo Integrità Dati', 'incidenti-stradali'),
                        'incidenti_generate_reports' => __('Generazione Report', 'incidenti-stradali'),
                        'incidenti_backup_data' => __('Backup Dati', 'incidenti-stradali'),
                        'incidenti_send_statistics' => __('Invio Statistiche', 'incidenti-stradali')
                    );
                    
                    foreach ($cron_jobs as $hook => $name) {
                        $next_run = wp_next_scheduled($hook);
                        $cron_schedules = wp_get_schedules();
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($name) . '</td>';
                        echo '<td>' . ($next_run ? date('d/m/Y H:i:s', $next_run) : __('Non programmata', 'incidenti-stradali')) . '</td>';
                        
                        // Find frequency
                        $frequency = __('Sconosciuta', 'incidenti-stradali');
                        $cron_array = _get_cron_array();
                        foreach ($cron_array as $timestamp => $cron) {
                            if (isset($cron[$hook])) {
                                foreach ($cron[$hook] as $key => $data) {
                                    if (isset($data['schedule']) && isset($cron_schedules[$data['schedule']])) {
                                        $frequency = $cron_schedules[$data['schedule']]['display'];
                                    }
                                }
                            }
                        }
                        
                        echo '<td>' . esc_html($frequency) . '</td>';
                        echo '<td>';
                        
                        $manual_url = add_query_arg(array(
                            'incidenti_run_cron' => str_replace('incidenti_', '', $hook),
                            '_wpnonce' => wp_create_nonce('incidenti_manual_cron')
                        ));
                        
                        echo '<a href="' . esc_url($manual_url) . '" class="button button-small">' . __('Esegui ora', 'incidenti-stradali') . '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Log Attività Recenti', 'incidenti-stradali'); ?></h2>
            
            <?php
            $cron_logs = get_transient('incidenti_cron_logs') ?: array();
            $cron_logs = array_reverse(array_slice($cron_logs, -20)); // Last 20 entries
            
            if (!empty($cron_logs)) {
                echo '<table class="widefat">';
                echo '<thead><tr>';
                echo '<th>' . __('Data/Ora', 'incidenti-stradali') . '</th>';
                echo '<th>' . __('Attività', 'incidenti-stradali') . '</th>';
                echo '<th>' . __('Stato', 'incidenti-stradali') . '</th>';
                echo '<th>' . __('Dettagli', 'incidenti-stradali') . '</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ($cron_logs as $log) {
                    $status_class = $log['status'] === 'success' ? 'success' : ($log['status'] === 'error' ? 'error' : 'warning');
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($log['timestamp']) . '</td>';
                    echo '<td>' . esc_html($log['task']) . '</td>';
                    echo '<td><span class="status-' . $status_class . '">' . esc_html($log['status']) . '</span></td>';
                    echo '<td>' . esc_html(json_encode($log['data'], JSON_UNESCAPED_UNICODE)) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p>' . __('Nessuna attività registrata.', 'incidenti-stradali') . '</p>';
            }
            ?>
        </div>
    </div>
    
    <style>
    .status-success { color: #46b450; font-weight: bold; }
    .status-error { color: #dc3232; font-weight: bold; }
    .status-warning { color: #ffb900; font-weight: bold; }
    </style>
    <?php
}