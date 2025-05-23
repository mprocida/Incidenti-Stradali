<?php
/**
 * Admin Settings for Incidenti Stradali
 */

class IncidentiAdminSettings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function add_settings_menu() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_submenu_page(
            'edit.php?post_type=incidente_stradale',
            __('Impostazioni Incidenti', 'incidenti-stradali'),
            __('Impostazioni', 'incidenti-stradali'),
            'manage_options',
            'incidenti-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        // General settings
        register_setting('incidenti_settings', 'incidenti_data_blocco_modifica');
        register_setting('incidenti_settings', 'incidenti_map_center_lat');
        register_setting('incidenti_settings', 'incidenti_map_center_lng');
        register_setting('incidenti_settings', 'incidenti_export_path');
        
        // Auto export settings
        register_setting('incidenti_settings', 'incidenti_auto_export_enabled');
        register_setting('incidenti_settings', 'incidenti_auto_export_frequency');
        register_setting('incidenti_settings', 'incidenti_auto_export_email');
        
        // Security settings
        register_setting('incidenti_settings', 'incidenti_restrict_by_ip');
        register_setting('incidenti_settings', 'incidenti_allowed_ips');
        
        // Notification settings
        register_setting('incidenti_settings', 'incidenti_notify_new_incident');
        register_setting('incidenti_settings', 'incidenti_notification_emails');
        
        // Map settings
        register_setting('incidenti_settings', 'incidenti_map_provider');
        register_setting('incidenti_settings', 'incidenti_map_api_key');
        register_setting('incidenti_settings', 'incidenti_map_cluster_enabled');
        register_setting('incidenti_settings', 'incidenti_map_cluster_radius');
        
        // Dashboard settings
        register_setting('incidenti_settings', 'incidenti_dashboard_widget_options');
        register_setting('incidenti_settings', 'incidenti_show_welcome_notice');
        
        // Data retention settings
        register_setting('incidenti_settings', 'incidenti_keep_data_on_uninstall');
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'incidenti-stradali'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'incidenti_settings-options')) {
            $this->save_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Impostazioni Incidenti Stradali', 'incidenti-stradali'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('incidenti_settings-options'); ?>
                
                <div class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('Generale', 'incidenti-stradali'); ?></a>
                    <a href="#export" class="nav-tab"><?php _e('Esportazione', 'incidenti-stradali'); ?></a>
                    <a href="#map" class="nav-tab"><?php _e('Mappa', 'incidenti-stradali'); ?></a>
                    <a href="#notifications" class="nav-tab"><?php _e('Notifiche', 'incidenti-stradali'); ?></a>
                    <a href="#security" class="nav-tab"><?php _e('Sicurezza', 'incidenti-stradali'); ?></a>
                    <a href="#advanced" class="nav-tab"><?php _e('Avanzate', 'incidenti-stradali'); ?></a>
                </div>
                
                <div class="tab-content">
                    <!-- General Settings -->
                    <div id="general" class="tab-pane active">
                        <h2><?php _e('Impostazioni Generali', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_data_blocco_modifica"><?php _e('Data Blocco Modifica', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="date" 
                                           id="incidenti_data_blocco_modifica" 
                                           name="incidenti_data_blocco_modifica" 
                                           value="<?php echo esc_attr(get_option('incidenti_data_blocco_modifica')); ?>">
                                    <p class="description"><?php _e('Gli incidenti avvenuti prima di questa data non potranno essere modificati dagli operatori.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_show_welcome_notice"><?php _e('Mostra Avviso di Benvenuto', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_show_welcome_notice" 
                                           name="incidenti_show_welcome_notice" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_show_welcome_notice', true)); ?>>
                                    <label for="incidenti_show_welcome_notice"><?php _e('Mostra l\'avviso di benvenuto ai nuovi utenti', 'incidenti-stradali'); ?></label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Export Settings -->
                    <div id="export" class="tab-pane">
                        <h2><?php _e('Impostazioni Esportazione', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_export_path"><?php _e('Percorso Esportazione', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="incidenti_export_path" 
                                           name="incidenti_export_path" 
                                           value="<?php echo esc_attr(get_option('incidenti_export_path', wp_upload_dir()['basedir'] . '/incidenti-exports')); ?>" 
                                           class="regular-text">
                                    <p class="description"><?php _e('Directory dove salvare i file esportati.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_auto_export_enabled"><?php _e('Esportazione Automatica', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_auto_export_enabled" 
                                           name="incidenti_auto_export_enabled" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_auto_export_enabled')); ?>>
                                    <label for="incidenti_auto_export_enabled"><?php _e('Abilita esportazione automatica periodica', 'incidenti-stradali'); ?></label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_auto_export_frequency"><?php _e('Frequenza Esportazione', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <select id="incidenti_auto_export_frequency" name="incidenti_auto_export_frequency">
                                        <option value="daily" <?php selected(get_option('incidenti_auto_export_frequency'), 'daily'); ?>><?php _e('Giornaliera', 'incidenti-stradali'); ?></option>
                                        <option value="weekly" <?php selected(get_option('incidenti_auto_export_frequency'), 'weekly'); ?>><?php _e('Settimanale', 'incidenti-stradali'); ?></option>
                                        <option value="monthly" <?php selected(get_option('incidenti_auto_export_frequency'), 'monthly'); ?>><?php _e('Mensile', 'incidenti-stradali'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_auto_export_email"><?php _e('Email per Esportazione', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="email" 
                                           id="incidenti_auto_export_email" 
                                           name="incidenti_auto_export_email" 
                                           value="<?php echo esc_attr(get_option('incidenti_auto_export_email')); ?>" 
                                           class="regular-text">
                                    <p class="description"><?php _e('Email a cui inviare i file esportati automaticamente.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Map Settings -->
                    <div id="map" class="tab-pane">
                        <h2><?php _e('Impostazioni Mappa', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_map_center_lat"><?php _e('Centro Mappa - Latitudine', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="incidenti_map_center_lat" 
                                           name="incidenti_map_center_lat" 
                                           value="<?php echo esc_attr(get_option('incidenti_map_center_lat', '41.9028')); ?>" 
                                           class="small-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_map_center_lng"><?php _e('Centro Mappa - Longitudine', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="incidenti_map_center_lng" 
                                           name="incidenti_map_center_lng" 
                                           value="<?php echo esc_attr(get_option('incidenti_map_center_lng', '12.4964')); ?>" 
                                           class="small-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_map_provider"><?php _e('Provider Mappa', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <select id="incidenti_map_provider" name="incidenti_map_provider">
                                        <option value="openstreetmap" <?php selected(get_option('incidenti_map_provider', 'openstreetmap'), 'openstreetmap'); ?>>OpenStreetMap</option>
                                        <option value="google" <?php selected(get_option('incidenti_map_provider'), 'google'); ?>>Google Maps</option>
                                        <option value="mapbox" <?php selected(get_option('incidenti_map_provider'), 'mapbox'); ?>>Mapbox</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_map_api_key"><?php _e('API Key Mappa', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="incidenti_map_api_key" 
                                           name="incidenti_map_api_key" 
                                           value="<?php echo esc_attr(get_option('incidenti_map_api_key')); ?>" 
                                           class="regular-text">
                                    <p class="description"><?php _e('Necessaria per Google Maps e Mapbox.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_map_cluster_enabled"><?php _e('Raggruppa Marker', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_map_cluster_enabled" 
                                           name="incidenti_map_cluster_enabled" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_map_cluster_enabled', true)); ?>>
                                    <label for="incidenti_map_cluster_enabled"><?php _e('Raggruppa i marker vicini sulla mappa', 'incidenti-stradali'); ?></label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Notifications Settings -->
                    <div id="notifications" class="tab-pane">
                        <h2><?php _e('Impostazioni Notifiche', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_notify_new_incident"><?php _e('Notifica Nuovo Incidente', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_notify_new_incident" 
                                           name="incidenti_notify_new_incident" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_notify_new_incident')); ?>>
                                    <label for="incidenti_notify_new_incident"><?php _e('Invia email quando viene inserito un nuovo incidente', 'incidenti-stradali'); ?></label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_notification_emails"><?php _e('Email per Notifiche', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <textarea id="incidenti_notification_emails" 
                                              name="incidenti_notification_emails" 
                                              rows="4" 
                                              class="large-text"><?php echo esc_textarea(get_option('incidenti_notification_emails')); ?></textarea>
                                    <p class="description"><?php _e('Una email per riga. Queste email riceveranno le notifiche.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Security Settings -->
                    <div id="security" class="tab-pane">
                        <h2><?php _e('Impostazioni Sicurezza', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_restrict_by_ip"><?php _e('Restrizione per IP', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_restrict_by_ip" 
                                           name="incidenti_restrict_by_ip" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_restrict_by_ip')); ?>>
                                    <label for="incidenti_restrict_by_ip"><?php _e('Limita l\'accesso solo agli IP autorizzati', 'incidenti-stradali'); ?></label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_allowed_ips"><?php _e('IP Autorizzati', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <textarea id="incidenti_allowed_ips" 
                                              name="incidenti_allowed_ips" 
                                              rows="4" 
                                              class="large-text"><?php echo esc_textarea(get_option('incidenti_allowed_ips')); ?></textarea>
                                    <p class="description"><?php _e('Un indirizzo IP per riga. Solo questi IP potranno accedere al plugin.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Advanced Settings -->
                    <div id="advanced" class="tab-pane">
                        <h2><?php _e('Impostazioni Avanzate', 'incidenti-stradali'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="incidenti_keep_data_on_uninstall"><?php _e('Mantieni Dati alla Disinstallazione', 'incidenti-stradali'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="incidenti_keep_data_on_uninstall" 
                                           name="incidenti_keep_data_on_uninstall" 
                                           value="1" 
                                           <?php checked(get_option('incidenti_keep_data_on_uninstall')); ?>>
                                    <label for="incidenti_keep_data_on_uninstall"><?php _e('Non eliminare i dati quando il plugin viene disinstallato', 'incidenti-stradali'); ?></label>
                                    <p class="description"><?php _e('ATTENZIONE: Se disabilitato, tutti i dati verranno eliminati definitivamente alla disinstallazione.', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('Informazioni Sistema', 'incidenti-stradali'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Versione Plugin', 'incidenti-stradali'); ?></th>
                                <td><?php echo INCIDENTI_VERSION; ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Versione WordPress', 'incidenti-stradali'); ?></th>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Versione PHP', 'incidenti-stradali'); ?></th>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Directory Upload', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $upload_dir = wp_upload_dir();
                                    echo $upload_dir['basedir'];
                                    echo is_writable($upload_dir['basedir']) ? ' ✓' : ' ✗';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and panes
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-pane').removeClass('active');
                
                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');
                
                // Show corresponding pane
                var target = $(this).attr('href');
                $(target).addClass('active');
            });
            
            // Show only active tab content
            $('.tab-pane').hide();
            $('.tab-pane.active').show();
            
            $('.nav-tab').on('click', function() {
                $('.tab-pane').hide();
                var target = $(this).attr('href');
                $(target).show();
            });
        });
        </script>
        
        <style>
        .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        
        .tab-pane {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: none;
            padding: 20px;
        }
        
        .tab-pane h2 {
            margin-top: 0;
        }
        
        .tab-pane h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        </style>
        <?php
    }
    
    private function save_settings() {
        // Validate and save all settings
        $settings = array(
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
            'incidenti_show_welcome_notice',
            'incidenti_keep_data_on_uninstall'
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = $_POST[$setting];
                
                // Sanitize based on setting type
                switch ($setting) {
                    case 'incidenti_data_blocco_modifica':
                        $value = sanitize_text_field($value);
                        break;
                    case 'incidenti_map_center_lat':
                    case 'incidenti_map_center_lng':
                        $value = floatval($value);
                        break;
                    case 'incidenti_export_path':
                        $value = sanitize_text_field($value);
                        break;
                    case 'incidenti_auto_export_email':
                        $value = sanitize_email($value);
                        break;
                    case 'incidenti_allowed_ips':
                    case 'incidenti_notification_emails':
                        $value = sanitize_textarea_field($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }
                
                update_option($setting, $value);
            } else {
                // Handle checkboxes that might not be set
                if (strpos($setting, '_enabled') !== false || 
                    strpos($setting, '_notify') !== false || 
                    strpos($setting, '_restrict') !== false ||
                    strpos($setting, '_cluster') !== false ||
                    strpos($setting, '_show') !== false ||
                    strpos($setting, '_keep') !== false) {
                    update_option($setting, false);
                }
            }
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Impostazioni salvate con successo.', 'incidenti-stradali') . '</p>';
            echo '</div>';
        });
    }
    
    public function admin_notices() {
        // Check for configuration issues
        $notices = array();
        
        // Check export directory
        $export_path = get_option('incidenti_export_path', wp_upload_dir()['basedir'] . '/incidenti-exports');
        if (!is_writable(dirname($export_path))) {
            $notices[] = array(
                'type' => 'warning',
                'message' => sprintf(__('La directory di esportazione %s non è scrivibile.', 'incidenti-stradali'), $export_path)
            );
        }
        
        // Check auto export settings
        if (get_option('incidenti_auto_export_enabled') && !get_option('incidenti_auto_export_email')) {
            $notices[] = array(
                'type' => 'warning',
                'message' => __('L\'esportazione automatica è abilitata ma non è stata configurata nessuna email.', 'incidenti-stradali')
            );
        }
        
        foreach ($notices as $notice) {
            echo '<div class="notice notice-' . $notice['type'] . '">';
            echo '<p>' . $notice['message'] . '</p>';
            echo '</div>';
        }
    }
}
