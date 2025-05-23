<?php
/**
 * Dashboard Widget and Additional Hooks for Incidenti Stradali
 */

class IncidentiDashboardWidget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_refresh_incidenti_dashboard', array($this, 'refresh_dashboard_widget'));
        add_action('admin_init', array($this, 'setup_admin_hooks'));
        add_filter('manage_incidente_stradale_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_incidente_stradale_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
        add_filter('manage_edit-incidente_stradale_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_posts', array($this, 'handle_column_sorting'));
    }
    
    public function add_dashboard_widget() {
        if (current_user_can('edit_incidenti') || current_user_can('manage_all_incidenti')) {
            wp_add_dashboard_widget(
                'incidenti_dashboard_widget',
                __('Incidenti Stradali - Panoramica', 'incidenti-stradali'),
                array($this, 'render_dashboard_widget'),
                array($this, 'dashboard_widget_config')
            );
        }
    }
    
    public function render_dashboard_widget() {
        $stats = $this->get_dashboard_stats();
        $recent_incidents = $this->get_recent_incidents();
        
        ?>
        <div class="incidenti-dashboard-widget">
            <div class="widget-content">
                
                <!-- Statistics Cards -->
                <div class="incidenti-dashboard-stats">
                    <div class="incidenti-dashboard-stat">
                        <div class="number"><?php echo $stats['total_month']; ?></div>
                        <div class="label"><?php _e('Questo Mese', 'incidenti-stradali'); ?></div>
                    </div>
                    <div class="incidenti-dashboard-stat">
                        <div class="number"><?php echo $stats['morti_month']; ?></div>
                        <div class="label"><?php _e('Morti', 'incidenti-stradali'); ?></div>
                    </div>
                    <div class="incidenti-dashboard-stat">
                        <div class="number"><?php echo $stats['feriti_month']; ?></div>
                        <div class="label"><?php _e('Feriti', 'incidenti-stradali'); ?></div>
                    </div>
                    <div class="incidenti-dashboard-stat">
                        <div class="number"><?php echo $stats['total_year']; ?></div>
                        <div class="label"><?php _e('Quest\'Anno', 'incidenti-stradali'); ?></div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="incidenti-dashboard-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=incidente_stradale'); ?>" class="button button-primary">
                        <?php _e('Nuovo Incidente', 'incidenti-stradali'); ?>
                    </a>
                    <?php if (current_user_can('export_incidenti')): ?>
                    <a href="<?php echo admin_url('edit.php?post_type=incidente_stradale&page=incidenti-export'); ?>" class="button">
                        <?php _e('Esporta Dati', 'incidenti-stradali'); ?>
                    </a>
                    <?php endif; ?>
                    <button type="button" class="button incidenti-dashboard-refresh" data-nonce="<?php echo wp_create_nonce('refresh_dashboard'); ?>">
                        <?php _e('Aggiorna', 'incidenti-stradali'); ?>
                    </button>
                </div>
                
                <!-- Recent Incidents -->
                <?php if (!empty($recent_incidents)): ?>
                <div class="incidenti-dashboard-recent">
                    <h4><?php _e('Incidenti Recenti', 'incidenti-stradali'); ?></h4>
                    <ul>
                        <?php foreach ($recent_incidents as $incident): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($incident->ID); ?>">
                                    <?php echo esc_html($incident->post_title); ?>
                                </a>
                                <span class="incident-date">
                                    <?php echo mysql2date('d/m/Y', $incident->post_date); ?>
                                </span>
                                <?php
                                $morti = $this->count_casualties($incident->ID, 'morti');
                                $feriti = $this->count_casualties($incident->ID, 'feriti');
                                if ($morti > 0): ?>
                                    <span class="casualty-badge morti"><?php echo $morti; ?> morti</span>
                                <?php endif;
                                if ($feriti > 0): ?>
                                    <span class="casualty-badge feriti"><?php echo $feriti; ?> feriti</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Alerts -->
                <?php $this->render_dashboard_alerts(); ?>
                
            </div>
        </div>
        
        <style>
        .incidenti-dashboard-widget .incidenti-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .incidenti-dashboard-stat {
            text-align: center;
            background: #f8f9fa;
            padding: 12px 8px;
            border-radius: 4px;
            border-left: 3px solid #0073aa;
        }
        
        .incidenti-dashboard-stat .number {
            font-size: 1.4em;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        
        .incidenti-dashboard-stat .label {
            font-size: 0.75em;
            color: #666;
            margin-top: 2px;
        }
        
        .incidenti-dashboard-actions {
            margin: 15px 0;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .incidenti-dashboard-recent ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .incidenti-dashboard-recent li {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .incidenti-dashboard-recent li:last-child {
            border-bottom: none;
        }
        
        .incident-date {
            color: #666;
            font-size: 11px;
        }
        
        .casualty-badge {
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 2px;
            color: white;
            margin-left: 4px;
        }
        
        .casualty-badge.morti {
            background: #d63384;
        }
        
        .casualty-badge.feriti {
            background: #fd7e14;
        }
        
        .incidenti-dashboard-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 8px 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        @media (max-width: 782px) {
            .incidenti-dashboard-stats {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            .incidenti-dashboard-actions {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
    
    public function dashboard_widget_config() {
        if (wp_verify_nonce($_POST['dashboard_widget_nonce'], 'edit-dashboard-widget_incidenti_dashboard_widget')) {
            $widget_options = get_option('incidenti_dashboard_widget_options', array());
            
            if (isset($_POST['show_recent_count'])) {
                $widget_options['show_recent_count'] = intval($_POST['show_recent_count']);
            }
            
            update_option('incidenti_dashboard_widget_options', $widget_options);
        }
        
        $options = get_option('incidenti_dashboard_widget_options', array(
            'show_recent_count' => 5
        ));
        
        ?>
        <p>
            <label for="show_recent_count"><?php _e('Numero incidenti recenti da mostrare:', 'incidenti-stradali'); ?></label>
            <input type="number" id="show_recent_count" name="show_recent_count" value="<?php echo $options['show_recent_count']; ?>" min="1" max="20" class="small-text">
        </p>
        <?php
    }
    
    public function refresh_dashboard_widget() {
        check_ajax_referer('refresh_dashboard', 'nonce');
        
        if (!current_user_can('edit_incidenti')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        ob_start();
        $this->render_dashboard_widget();
        $content = ob_get_clean();
        
        wp_send_json_success($content);
    }
    
    private function get_dashboard_stats() {
        $current_user_id = get_current_user_id();
        $user_comune = get_user_meta($current_user_id, 'comune_assegnato', true);
        
        // Base query args
        $base_args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        // Add comune filter for non-admin users
        if (!current_user_can('manage_all_incidenti') && $user_comune) {
            $base_args['meta_query'] = array(
                array(
                    'key' => 'comune_incidente',
                    'value' => $user_comune,
                    'compare' => '='
                )
            );
        }
        
        // This month
        $month_args = $base_args;
        $month_args['meta_query'][] = array(
            'key' => 'data_incidente',
            'value' => date('Y-m-01'),
            'compare' => '>=',
            'type' => 'DATE'
        );
        
        $month_incidents = get_posts($month_args);
        
        // This year
        $year_args = $base_args;
        $year_args['meta_query'][] = array(
            'key' => 'data_incidente',
            'value' => date('Y-01-01'),
            'compare' => '>=',
            'type' => 'DATE'
        );
        
        $year_incidents = get_posts($year_args);
        
        // Count casualties for this month
        $morti_month = 0;
        $feriti_month = 0;
        
        foreach ($month_incidents as $incident_id) {
            $morti_month += $this->count_casualties($incident_id, 'morti');
            $feriti_month += $this->count_casualties($incident_id, 'feriti');
        }
        
        return array(
            'total_month' => count($month_incidents),
            'total_year' => count($year_incidents),
            'morti_month' => $morti_month,
            'feriti_month' => $feriti_month
        );
    }
    
    private function get_recent_incidents() {
        $current_user_id = get_current_user_id();
        $user_comune = get_user_meta($current_user_id, 'comune_assegnato', true);
        
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add comune filter for non-admin users
        if (!current_user_can('manage_all_incidenti') && $user_comune) {
            $args['meta_query'] = array(
                array(
                    'key' => 'comune_incidente',
                    'value' => $user_comune,
                    'compare' => '='
                )
            );
        }
        
        return get_posts($args);
    }
    
    private function count_casualties($post_id, $type) {
        $count = 0;
        
        // Count drivers
        for ($i = 1; $i <= 3; $i++) {
            $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
            if ($type === 'morti' && ($esito == '3' || $esito == '4')) {
                $count++;
            } elseif ($type === 'feriti' && $esito == '2') {
                $count++;
            }
        }
        
        // Count pedestrians
        $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
        for ($i = 1; $i <= $num_pedoni; $i++) {
            $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
            if ($type === 'morti' && ($esito == '3' || $esito == '4')) {
                $count++;
            } elseif ($type === 'feriti' && $esito == '2') {
                $count++;
            }
        }
        
        return $count;
    }
    
    private function render_dashboard_alerts() {
        $alerts = array();
        
        // Check for data integrity issues
        $posts_without_date = get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        if (!empty($posts_without_date)) {
            $alerts[] = __('Alcuni incidenti non hanno la data impostata.', 'incidenti-stradali');
        }
        
        // Check export path writability
        $export_path = get_option('incidenti_export_path', wp_upload_dir()['basedir'] . '/incidenti-exports');
        if (!is_writable(dirname($export_path))) {
            $alerts[] = __('La cartella di esportazione non è scrivibile.', 'incidenti-stradali');
        }
        
        // Check for pending drafts
        $drafts = wp_count_posts('incidente_stradale');
        if ($drafts->draft > 0) {
            $alerts[] = sprintf(__('Ci sono %d incidenti in bozza.', 'incidenti-stradali'), $drafts->draft);
        }
        
        foreach ($alerts as $alert) {
            echo '<div class="incidenti-dashboard-alert">' . esc_html($alert) . '</div>';
        }
    }
    
    public function setup_admin_hooks() {
        // Add admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Add admin styles
        add_action('admin_head', array($this, 'admin_head_styles'));
    }
    
    public function display_admin_notices() {
        global $pagenow, $post_type;
        
        if ($post_type !== 'incidente_stradale') {
            return;
        }
        
        // Welcome notice for new installations
        if (get_option('incidenti_show_welcome_notice', true)) {
            ?>
            <div class="notice notice-info is-dismissible incidenti-welcome-notice">
                <h3><?php _e('Benvenuto nel Plugin Incidenti Stradali!', 'incidenti-stradali'); ?></h3>
                <p><?php _e('Inizia configurando le impostazioni base e assegnando i ruoli utente.', 'incidenti-stradali'); ?></p>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=incidente_stradale&page=incidenti-settings'); ?>" class="button button-primary">
                        <?php _e('Configura Ora', 'incidenti-stradali'); ?>
                    </a>
                    <button type="button" class="button dismiss-welcome-notice">
                        <?php _e('Nascondi Avviso', 'incidenti-stradali'); ?>
                    </button>
                </p>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('.dismiss-welcome-notice').on('click', function() {
                    $('.incidenti-welcome-notice').fadeOut();
                    $.post(ajaxurl, {
                        action: 'dismiss_incidenti_welcome_notice',
                        nonce: '<?php echo wp_create_nonce('dismiss_welcome'); ?>'
                    });
                });
            });
            </script>
            <?php
        }
        
        // Data validation notices
        if ($pagenow === 'edit.php') {
            $this->check_and_display_data_issues();
        }
    }
    
    public function admin_head_styles() {
        global $post_type;
        
        if ($post_type === 'incidente_stradale') {
            ?>
            <style>
            .incidenti-welcome-notice h3 {
                margin-top: 0;
            }
            
            .incidenti-admin-highlight {
                background: #fff2cc;
                border-left: 4px solid #ffb900;
                padding: 12px;
                margin: 15px 0;
            }
            
            .incidenti-quick-stats {
                display: flex;
                gap: 15px;
                margin: 15px 0;
            }
            
            .incidenti-quick-stat {
                text-align: center;
                background: #f1f1f1;
                padding: 10px;
                border-radius: 4px;
                min-width: 80px;
            }
            
            .incidenti-quick-stat .number {
                font-size: 1.5em;
                font-weight: bold;
                color: #0073aa;
            }
            
            .incidenti-quick-stat .label {
                font-size: 0.8em;
                color: #666;
            }
            </style>
            <?php
        }
    }
    
    private function check_and_display_data_issues() {
        // Check for common data issues
        $issues = array();
        
        // Incidents without coordinates but marked for map
        $no_coords = get_posts(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => 1,
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
        ));
        
        if (!empty($no_coords)) {
            $issues[] = __('Alcuni incidenti sono marcati per la mappa ma non hanno coordinate.', 'incidenti-stradali');
        }
        
        if (!empty($issues)) {
            ?>
            <div class="notice notice-warning">
                <h4><?php _e('Problemi Rilevati nei Dati', 'incidenti-stradali'); ?></h4>
                <ul>
                    <?php foreach ($issues as $issue): ?>
                        <li><?php echo esc_html($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=incidente_stradale&page=incidenti-settings'); ?>" class="button">
                        <?php _e('Vai alle Impostazioni', 'incidenti-stradali'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    // Custom columns for post list
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['incidente_data'] = __('Data', 'incidenti-stradali');
                $new_columns['incidente_comune'] = __('Comune', 'incidenti-stradali');
                $new_columns['incidente_gravita'] = __('Gravità', 'incidenti-stradali');
            }
        }
        
        return $new_columns;
    }
    
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'incidente_data':
                $data = get_post_meta($post_id, 'data_incidente', true);
                if ($data) {
                    echo date('d/m/Y', strtotime($data));
                    $ora = get_post_meta($post_id, 'ora_incidente', true);
                    if ($ora) {
                        echo '<br><small>' . $ora . ':00</small>';
                    }
                }
                break;
                
            case 'incidente_comune':
                $comune = get_post_meta($post_id, 'comune_incidente', true);
                echo esc_html($comune);
                break;
                
            case 'incidente_gravita':
                $morti = $this->count_casualties($post_id, 'morti');
                $feriti = $this->count_casualties($post_id, 'feriti');
                
                if ($morti > 0) {
                    echo '<span class="incidente-gravita-badge morti">' . $morti . ' morti</span>';
                }
                if ($feriti > 0) {
                    echo '<span class="incidente-gravita-badge feriti">' . $feriti . ' feriti</span>';
                }
                if ($morti == 0 && $feriti == 0) {
                    echo '<span class="incidente-gravita-badge danni">Solo danni</span>';
                }
                break;
        }
    }
    
    public function make_columns_sortable($columns) {
        $columns['incidente_data'] = 'incidente_data';
        $columns['incidente_comune'] = 'incidente_comune';
        return $columns;
    }
    
    public function handle_column_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'incidente_data') {
            $query->set('meta_key', 'data_incidente');
            $query->set('orderby', 'meta_value');
            $query->set('meta_type', 'DATE');
        } elseif ($orderby === 'incidente_comune') {
            $query->set('meta_key', 'comune_incidente');
            $query->set('orderby', 'meta_value');
        }
    }
}

// AJAX handler for dismissing welcome notice
add_action('wp_ajax_dismiss_incidenti_welcome_notice', function() {
    check_ajax_referer('dismiss_welcome', 'nonce');
    update_option('incidenti_show_welcome_notice', false);
    wp_die();
});

// Initialize dashboard widget
new IncidentiDashboardWidget();