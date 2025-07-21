<?php
/**
 * Custom Post Type for Incidenti Stradali
 */

class IncidentiCustomPostType {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('admin_init', array($this, 'check_menu_visibility'), 20);
        add_action('pre_get_posts', array($this, 'filter_posts_by_user_role'));
        add_filter('wp_insert_post_data', array($this, 'validate_insert_post'), 10, 2);
        add_action('admin_menu', array($this, 'fix_menu_position'), 999);
        add_action('admin_notices', array($this, 'debug_menu_registration'));
        /* add_action('admin_notices', array($this, 'show_debug_info')); */

        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_incidente_request'));
        
        // Force registration immediately if we're in admin
        if (is_admin()) {
            $this->register_post_type();
        }
    }

    /**
     * Aggiungi query vars personalizzate
     */
    public function add_query_vars($vars) {
        $vars[] = 'incidente_stradale';
        return $vars;
    }

    /**
     * Gestisce il parsing delle richieste per incidenti
     */
    public function parse_incidente_request($wp) {
        // Se la richiesta è per un incidente
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'incidente_stradale') {
            // Assicurati che la query sia corretta
            if (isset($wp->query_vars['name']) && !isset($wp->query_vars['pagename'])) {
                $wp->query_vars['incidente_stradale'] = $wp->query_vars['name'];
            }
        }
    }
    
    /**
     * Debug menu registration
     */
    public function debug_menu_registration() {
        if (!current_user_can('manage_options') || !isset($_GET['debug_incidenti'])) {
            return;
        }
        
        global $menu, $submenu;
        
        echo '<div class="notice notice-info"><pre>';
        echo "Current user capabilities:\n";
        $current_user = wp_get_current_user();
        foreach ($current_user->allcaps as $cap => $has_cap) {
            if (strpos($cap, 'incidenti') !== false || strpos($cap, 'incidente') !== false) {
                echo "- $cap: " . ($has_cap ? 'YES' : 'NO') . "\n";
            }
        }
        
        echo "\nMenu items containing 'incidenti':\n";
        foreach ($menu as $menu_item) {
            if (isset($menu_item[0]) && stripos($menu_item[0], 'incidenti') !== false) {
                echo "- " . $menu_item[0] . " (file: " . $menu_item[2] . ")\n";
            }
        }
        
        echo "\nPost type object:\n";
        $post_type_object = get_post_type_object('incidente_stradale');
        if ($post_type_object) {
            echo "- show_in_menu: " . var_export($post_type_object->show_in_menu, true) . "\n";
            echo "- capability_type: " . $post_type_object->capability_type . "\n";
            echo "- map_meta_cap: " . var_export($post_type_object->map_meta_cap, true) . "\n";
        } else {
            echo "- Post type not registered!\n";
        }
        echo '</pre></div>';
    }
    
    /**
     * Check if menu is visible and fix if needed
     */
    public function check_menu_visibility() {
        $post_type_object = get_post_type_object('incidente_stradale');
        
        if (!$post_type_object || !current_user_can('edit_posts')) {
            return;
        }
        
        // Check if user can access the menu
        if (!current_user_can('edit_incidenti') && !current_user_can('edit_posts')) {
            // Add basic post capabilities if custom capabilities aren't working
            $current_user = wp_get_current_user();
            if (in_array('administrator', $current_user->roles) || in_array('operatore_polizia_comunale', $current_user->roles)) {
                add_filter('user_has_cap', array($this, 'grant_post_type_access'), 10, 3);
            }
        }
    }
    
    /**
     * Grant access to post type for specific users
     */
    public function grant_post_type_access($allcaps, $caps, $args) {
        // Only apply to incidente_stradale related capabilities
        if (isset($args[0]) && (
            strpos($args[0], 'incidente') !== false || 
            (isset($args[2]) && get_post_type($args[2]) === 'incidente_stradale')
        )) {
            $current_user = wp_get_current_user();
            
            // Grant access to administrators
            if (in_array('administrator', $current_user->roles)) {
                $allcaps['edit_incidenti'] = true;
                $allcaps['edit_incidente'] = true;
                $allcaps['read_incidente'] = true;
                $allcaps['delete_incidente'] = true;
                $allcaps['publish_incidenti'] = true;
            }
            
            // Grant access to police operators
            if (in_array('operatore_polizia_comunale', $current_user->roles)) {
                $allcaps['edit_incidenti'] = true;
                $allcaps['edit_incidente'] = true;
                $allcaps['read_incidente'] = true;
                $allcaps['delete_incidente'] = true;
                $allcaps['publish_incidenti'] = true;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Fix menu position if needed
     */
    public function fix_menu_position() {
        global $menu;
        
        // Check if our menu item exists
        $menu_exists = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'edit.php?post_type=incidente_stradale') {
                $menu_exists = true;
                break;
            }
        }
        
        // Always add menu manually if it doesn't exist
        if (!$menu_exists && current_user_can('edit_posts')) {
            add_menu_page(
                'Incidenti Stradali',
                'Incidenti Stradali',
                'edit_posts',
                'edit.php?post_type=incidente_stradale',
                '',
                'dashicons-warning',
                25
            );
        }
    }
    
    public function register_post_type() {
        error_log('Incidenti Plugin: register_post_type() called');
        
        $labels = array(
            'name'                  => _x('Incidenti Stradali', 'Post type general name', 'incidenti-stradali'),
            'singular_name'         => _x('Incidente Stradale', 'Post type singular name', 'incidenti-stradali'),
            'menu_name'             => _x('Incidenti Stradali', 'Admin Menu text', 'incidenti-stradali'),
            'name_admin_bar'        => _x('Incidente Stradale', 'Add New on Toolbar', 'incidenti-stradali'),
            'add_new'               => __('Aggiungi Nuovo', 'incidenti-stradali'),
            'add_new_item'          => __('Aggiungi Nuovo Incidente', 'incidenti-stradali'),
            'new_item'              => __('Nuovo Incidente', 'incidenti-stradali'),
            'edit_item'             => __('Modifica Incidente', 'incidenti-stradali'),
            'view_item'             => __('Visualizza Incidente', 'incidenti-stradali'),
            'all_items'             => __('Tutti gli Incidenti', 'incidenti-stradali'),
            'search_items'          => __('Cerca Incidenti', 'incidenti-stradali'),
            'parent_item_colon'     => __('Incidenti Parent:', 'incidenti-stradali'),
            'not_found'             => __('Nessun incidente trovato.', 'incidenti-stradali'),
            'not_found_in_trash'    => __('Nessun incidente nel cestino.', 'incidenti-stradali'),
            'featured_image'        => _x('Immagine Incidente', 'Overrides the "Featured Image" phrase', 'incidenti-stradali'),
            'set_featured_image'    => _x('Imposta immagine incidente', 'Overrides the "Set featured image" phrase', 'incidenti-stradali'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-warning',
            'menu_position'      => 25,
            'query_var'          => true,
            'rewrite'            => array(
                'slug' => 'incidente-stradale',
                'with_front' => false,
                'feeds' => false,
                'pages' => true
            ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => array('title', 'author', 'custom-fields'),
            'show_in_rest'       => false,
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => true,
            'exclude_from_search' => false,
            'can_export'         => true,
            'delete_with_user'   => false
        );
        
        // Register the post type
        register_post_type('incidente_stradale', $args);
        
        // Force flush rewrite rules on first registration
        $rewrite_version = get_option('incidenti_rewrite_version', '0');
        if ($rewrite_version !== INCIDENTI_VERSION) {
            flush_rewrite_rules(false);
            update_option('incidenti_rewrite_version', INCIDENTI_VERSION);
            error_log('Incidenti Plugin: Rewrite rules flushed for version ' . INCIDENTI_VERSION);
        }

        // Assicurati che le regole siano sempre aggiornate durante lo sviluppo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_option('incidenti_flush_rewrite_rules', true);
        }
    }
    
    /**
     * Ensure post type capabilities are properly set
     */
    public function ensure_post_type_capabilities() {
        // Only run this once per admin session
        if (get_transient('incidenti_capabilities_checked')) {
            return;
        }
        
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Ensure administrators have all capabilities for incidenti
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
            
            foreach ($capabilities as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }
        
        // Set transient to avoid running this check repeatedly
        set_transient('incidenti_capabilities_checked', true, HOUR_IN_SECONDS);
    }
    
    public function filter_posts_by_user_role($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ('incidente_stradale' !== $query->get('post_type')) {
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Se è amministratore può vedere tutto
        if (current_user_can('manage_all_incidenti')) {
            return;
        }
        
        // Se è operatore comunale può vedere solo i suoi incidenti
        if (current_user_can('edit_incidenti')) {
            $user_comune = get_user_meta($current_user->ID, 'comune_assegnato', true);
            if ($user_comune) {
                $query->set('meta_query', array(
                    array(
                        'key'     => 'comune_incidente',
                        'value'   => $user_comune,
                        'compare' => '='
                    )
                ));
            }
        }
    }
    
    public function validate_insert_post($data, $postarr) {
        if ('incidente_stradale' !== $data['post_type']) {
            return $data;
        }
        
        // Verifica data di blocco modifica
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        if ($data_blocco) {
            $data_incidente = isset($_POST['data_incidente']) ? $_POST['data_incidente'] : '';
            if ($data_incidente && strtotime($data_incidente) < strtotime($data_blocco)) {
                wp_die(__('Non è possibile inserire incidenti avvenuti prima della data di blocco impostata.', 'incidenti-stradali'));
            }
        }
        
        return $data;
    }
    
}
