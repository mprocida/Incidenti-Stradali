<?php
/**
 * User Roles and Capabilities for Incidenti Stradali
 */

class IncidentiUserRoles {
    
    public function __construct() {
        add_action('init', array($this, 'init_roles'), 5);
        add_action('admin_init', array($this, 'ensure_capabilities'));
        add_filter('user_has_cap', array($this, 'map_meta_cap'), 10, 3);
    }
    
    /**
     * Initialize roles and capabilities
     */
    public function init_roles() {
        // Only run this once
        if (get_option('incidenti_roles_initialized')) {
            return;
        }
        
        $this->add_roles_and_capabilities();
        update_option('incidenti_roles_initialized', true);
    }
    
    /**
     * Add custom roles and capabilities
     */
    public function add_roles_and_capabilities() {
        // Define capabilities for incidenti management
        $incidenti_caps = array(
            'edit_incidente' => true,
            'read_incidente' => true,
            'delete_incidente' => true,
            'edit_incidenti' => true,
            'edit_others_incidenti' => true,
            'publish_incidenti' => true,
            'read_private_incidenti' => true,
            'delete_incidenti' => true,
            'delete_private_incidenti' => true,
            'delete_published_incidenti' => true,
            'delete_others_incidenti' => true,
            'edit_private_incidenti' => true,
            'edit_published_incidenti' => true,
            'export_incidenti' => true
        );
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($incidenti_caps as $cap => $grant) {
                $admin_role->add_cap($cap, $grant);
            }
            // Add special admin capability
            $admin_role->add_cap('manage_all_incidenti', true);
        }
        
        // Create custom role for police operators
        $police_caps = array_merge(
            array(
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'edit_published_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'delete_published_posts' => true
            ),
            $incidenti_caps
        );
        
        // Remove some capabilities for police operators
        unset($police_caps['edit_others_incidenti']);
        unset($police_caps['delete_others_incidenti']);
        unset($police_caps['manage_all_incidenti']);
        
        add_role(
            'operatore_polizia_comunale',
            __('Operatore Polizia Comunale', 'incidenti-stradali'),
            $police_caps
        );
        
        // Add capabilities to editor role (limited)
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('read_incidente', true);
            $editor_role->add_cap('edit_incidenti', true);
            $editor_role->add_cap('export_incidenti', true);
        }
    }
    
    /**
     * Ensure capabilities are properly set
     */
    public function ensure_capabilities() {
        // Check if capabilities need to be refreshed
        if (get_transient('incidenti_capabilities_checked')) {
            return;
        }
        
        $admin_role = get_role('administrator');
        
        if ($admin_role && !$admin_role->has_cap('manage_all_incidenti')) {
            $this->add_roles_and_capabilities();
        }
        
        // Set transient to avoid checking too frequently
        set_transient('incidenti_capabilities_checked', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Map meta capabilities for incidenti
     */
    public function map_meta_cap($allcaps, $caps, $args) {
        // Only apply to incidente_stradale related capabilities
        if (isset($args[0]) && (
            strpos($args[0], 'incidente') !== false || 
            (isset($args[2]) && get_post_type($args[2]) === 'incidente_stradale')
        )) {
            $current_user = wp_get_current_user();
            
            // Grant access to administrators
            if (in_array('administrator', $current_user->roles)) {
                foreach ($caps as $cap) {
                    $allcaps[$cap] = true;
                }
            }
            
            // Grant access to police operators with restrictions
            if (in_array('operatore_polizia_comunale', $current_user->roles)) {
                foreach ($caps as $cap) {
                    // Allow most capabilities
                    if (in_array($cap, array('edit_incidente', 'read_incidente', 'delete_incidente', 'edit_incidenti', 'publish_incidenti'))) {
                        $allcaps[$cap] = true;
                    }
                    
                    // Check if user can edit others' posts based on comune assignment
                    if (in_array($cap, array('edit_others_incidenti', 'delete_others_incidenti')) && isset($args[2])) {
                        $post_id = $args[2];
                        $user_comune = get_user_meta($current_user->ID, 'comune_assegnato', true);
                        $post_comune = get_post_meta($post_id, 'comune_incidente', true);
                        
                        if ($user_comune && $post_comune && $user_comune === $post_comune) {
                            $allcaps[$cap] = true;
                        }
                    }
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Get available user roles for incidenti
     */
    public static function get_incidenti_roles() {
        return array(
            'administrator' => __('Amministratore', 'incidenti-stradali'),
            'operatore_polizia_comunale' => __('Operatore Polizia Comunale', 'incidenti-stradali'),
            'editor' => __('Editor (solo lettura)', 'incidenti-stradali')
        );
    }
    
    /**
     * Check if user can manage incidenti
     */
    public static function user_can_manage_incidenti($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return user_can($user, 'edit_incidenti') || user_can($user, 'manage_all_incidenti');
    }
    
    /**
     * Check if user can export incidenti
     */
    public static function user_can_export_incidenti($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return user_can($user, 'export_incidenti') || user_can($user, 'manage_all_incidenti');
    }
    
    /**
     * Get user's assigned comune
     */
    public static function get_user_comune($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return get_user_meta($user_id, 'comune_assegnato', true);
    }
    
    /**
     * Set user's assigned comune
     */
    public static function set_user_comune($user_id, $comune_code) {
        return update_user_meta($user_id, 'comune_assegnato', sanitize_text_field($comune_code));
    }
    
    /**
     * Remove roles and capabilities (for uninstall)
     */
    public static function remove_roles() {
        // Remove custom role
        remove_role('operatore_polizia_comunale');
        
        // Remove capabilities from existing roles
        $capabilities = array(
            'edit_incidente',
            'read_incidente',
            'delete_incidente',
            'edit_incidenti',
            'edit_others_incidenti',
            'publish_incidenti',
            'read_private_incidenti',
            'delete_incidenti',
            'delete_private_incidenti',
            'delete_published_incidenti',
            'delete_others_incidenti',
            'edit_private_incidenti',
            'edit_published_incidenti',
            'manage_all_incidenti',
            'export_incidenti'
        );
        
        $roles = array('administrator', 'editor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        
        // Remove initialization flag
        delete_option('incidenti_roles_initialized');
        delete_transient('incidenti_capabilities_checked');
    }
    
    /**
     * Add user profile fields for comune assignment
     */
    public function add_user_profile_fields($user) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $comune_assegnato = get_user_meta($user->ID, 'comune_assegnato', true);
        ?>
        <h3><?php _e('Impostazioni Incidenti Stradali', 'incidenti-stradali'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="comune_assegnato"><?php _e('Comune Assegnato', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" name="comune_assegnato" id="comune_assegnato" value="<?php echo esc_attr($comune_assegnato); ?>" class="regular-text" />
                    <p class="description"><?php _e('Codice ISTAT del comune assegnato all\'operatore (3 cifre)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['comune_assegnato'])) {
            update_user_meta($user_id, 'comune_assegnato', sanitize_text_field($_POST['comune_assegnato']));
        }
    }
}
