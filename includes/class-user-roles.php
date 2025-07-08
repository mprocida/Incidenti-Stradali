<?php
/**
 * User Roles and Capabilities for Incidenti Stradali
 */

class IncidentiUserRoles {
    
    public function __construct() {
        add_action('init', array($this, 'init_roles'), 5);
        add_action('admin_init', array($this, 'ensure_capabilities'));
        add_filter('user_has_cap', array($this, 'map_meta_cap'), 10, 3);
        
        // Hook per i campi del profilo utente
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
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
        $ente_gestione = get_user_meta($user->ID, 'ente_gestione', true);
        $enti_disponibili = $this->get_enti_gestione();
        ?>
        <h3><?php _e('Impostazioni Incidenti Stradali', 'incidenti-stradali'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="ente_gestione"><?php _e('Ente di Gestione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select name="ente_gestione" id="ente_gestione" class="regular-text">
                        <option value=""><?php _e('Seleziona Ente', 'incidenti-stradali'); ?></option>
                        <?php foreach ($enti_disponibili as $codice => $nome): ?>
                            <option value="<?php echo esc_attr($codice); ?>" <?php selected($ente_gestione, $codice); ?>>
                                <?php echo esc_html($nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Seleziona l\'ente di appartenenza per questo utente.', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="comune_assegnato"><?php _e('Comune Assegnato', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" name="comune_assegnato" id="comune_assegnato" value="<?php echo esc_attr($comune_assegnato); ?>" class="regular-text" />
                    <p class="description"><?php _e('Codice ISTAT del comune assegnato all\'operatore (3 cifre) - Si autocompila in base all\'ente selezionato', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ente_gestione').change(function() {
                var mappingEntiComuni = <?php echo json_encode($this->get_mapping_enti_comuni()); ?>;
                var enteSelezionato = $(this).val();
                var comuneAssociato = mappingEntiComuni[enteSelezionato] || '';
                $('#comune_assegnato').val(comuneAssociato);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['ente_gestione'])) {
            update_user_meta($user_id, 'ente_gestione', sanitize_text_field($_POST['ente_gestione']));
        }
        
        if (isset($_POST['comune_assegnato'])) {
            update_user_meta($user_id, 'comune_assegnato', sanitize_text_field($_POST['comune_assegnato']));
        }
    }

    /**
     * Restituisce l'elenco degli enti di gestione
     */
    private function get_enti_gestione() {
        return array(
            'agente_polizia_stradale' => 'Agente di Polizia Stradale',
            'carabiniere' => 'Carabiniere',
            'pm_acquarica_capo' => 'POLIZIA MUNICIPALE DI ACQUARICA DEL CAPO',
            'pm_alessano' => 'POLIZIA MUNICIPALE DI ALESSANO',
            'pm_alezio' => 'POLIZIA MUNICIPALE DI ALEZIO',
            'pm_alliste' => 'POLIZIA MUNICIPALE DI ALLISTE',
            'pm_andrano' => 'POLIZIA MUNICIPALE DI ANDRANO',
            'pm_aradeo' => 'POLIZIA MUNICIPALE DI ARADEO',
            'pm_arnesano' => 'POLIZIA MUNICIPALE DI ARNESANO',
            'pm_bagnolo_salento' => 'POLIZIA MUNICIPALE DI BAGNOLO DEL SALENTO',
            'pm_botrugno' => 'POLIZIA MUNICIPALE DI BOTRUGNO',
            'pm_calimera' => 'POLIZIA MUNICIPALE DI CALIMERA',
            'pm_campi_salentina' => 'POLIZIA MUNICIPALE DI CAMPI SALENTINA',
            'pm_cannole' => 'POLIZIA MUNICIPALE DI CANNOLE',
            'pm_caprarica_lecce' => 'POLIZIA MUNICIPALE DI CAPRARICA DI LECCE',
            'pm_carmiano' => 'POLIZIA MUNICIPALE DI CARMIANO',
            'pm_carpignano_salentino' => 'POLIZIA MUNICIPALE DI CARPIGNANO SALENTINO',
            'pm_casarano' => 'POLIZIA MUNICIPALE DI CASARANO',
            'pm_castrignano_greci' => 'POLIZIA MUNICIPALE DI CASTRIGNANO DEI GRECI',
            'pm_castrignano_capo' => 'POLIZIA MUNICIPALE DI CASTRIGNANO DEL CAPO',
            'pm_castri' => 'POLIZIA MUNICIPALE DI CASTRI',
            'pm_castro' => 'POLIZIA MUNICIPALE DI CASTRO',
            'pm_cavallino' => 'POLIZIA MUNICIPALE DI CAVALLINO',
            'pm_collepasso' => 'POLIZIA MUNICIPALE DI COLLEPASSO',
            'pm_copertino' => 'POLIZIA MUNICIPALE DI COPERTINO',
            'pm_corigliano_otranto' => 'POLIZIA MUNICIPALE DI CORIGLIANO D\'OTRANTO',
            'pm_corsano' => 'POLIZIA MUNICIPALE DI CORSANO',
            'pm_cursi' => 'POLIZIA MUNICIPALE DI CURSI',
            'pm_cutrofiano' => 'POLIZIA MUNICIPALE DI CUTROFIANO',
            'pm_diso' => 'POLIZIA MUNICIPALE DI DISO',
            'pm_gagliano_capo' => 'POLIZIA MUNICIPALE DI GAGLIANO DEL CAPO',
            'pm_galatina' => 'POLIZIA MUNICIPALE DI GALATINA',
            'pm_galatone' => 'POLIZIA MUNICIPALE DI GALATONE',
            'pm_gallipoli' => 'POLIZIA MUNICIPALE DI GALLIPOLI',
            'pm_giuggianello' => 'POLIZIA MUNICIPALE DI GIUGGIANELLO',
            'pm_giurdignano' => 'POLIZIA MUNICIPALE DI GIURDIGNANO',
            'pm_guagnano' => 'POLIZIA MUNICIPALE DI GUAGNANO',
            'pm_lecce' => 'POLIZIA MUNICIPALE DI LECCE',
            'pm_lequile' => 'POLIZIA MUNICIPALE DI LEQUILE',
            'pm_leverano' => 'POLIZIA MUNICIPALE DI LEVERANO',
            'pm_lizzanello' => 'POLIZIA MUNICIPALE DI LIZZANELLO',
            'pm_maglie' => 'POLIZIA MUNICIPALE DI MAGLIE',
            'pm_martano' => 'POLIZIA MUNICIPALE DI MARTANO',
            'pm_martignano' => 'POLIZIA MUNICIPALE DI MARTIGNANO',
            'pm_matino' => 'POLIZIA MUNICIPALE DI MATINO',
            'pm_melendugno' => 'POLIZIA MUNICIPALE DI MELENDUGNO',
            'pm_melissano' => 'POLIZIA MUNICIPALE DI MELISSANO',
            'pm_melpignano' => 'POLIZIA MUNICIPALE DI MELPIGNANO',
            'pm_miggiano' => 'POLIZIA MUNICIPALE DI MIGGIANO',
            'pm_minervino_lecce' => 'POLIZIA MUNICIPALE DI MINERVINO DI LECCE',
            'pm_monteroni_lecce' => 'POLIZIA MUNICIPALE DI MONTERONI DI LECCE',
            'pm_montesano_salentino' => 'POLIZIA MUNICIPALE DI MONTESANO SALENTINO',
            'pm_morciano_leuca' => 'POLIZIA MUNICIPALE DI MORCIANO DI LEUCA',
            'pm_muro' => 'POLIZIA MUNICIPALE DI MURO',
            'pm_nardo' => 'POLIZIA MUNICIPALE DI NARDO',
            'pm_neviano' => 'POLIZIA MUNICIPALE DI NEVIANO',
            'pm_nociglia' => 'POLIZIA MUNICIPALE DI NOCIGLIA',
            'pm_novoli' => 'POLIZIA MUNICIPALE DI NOVOLI',
            'pm_ortelle' => 'POLIZIA MUNICIPALE DI ORTELLE',
            'pm_otranto' => 'POLIZIA MUNICIPALE DI OTRANTO',
            'pm_palmariggi' => 'POLIZIA MUNICIPALE DI PALMARIGGI',
            'pm_parabita' => 'POLIZIA MUNICIPALE DI PARABITA',
            'pm_patu' => 'POLIZIA MUNICIPALE DI PATU',
            'pm_poggiardo' => 'POLIZIA MUNICIPALE DI POGGIARDO',
            'pm_porto_cesareo' => 'POLIZIA MUNICIPALE DI PORTO CESAREO',
            'pm_presicce' => 'POLIZIA MUNICIPALE DI PRESICCE',
            'pm_presicce_acquarica' => 'POLIZIA MUNICIPALE DI PRESICCE-ACQUARICA',
            'pm_racale' => 'POLIZIA MUNICIPALE DI RACALE',
            'pm_ruffano' => 'POLIZIA MUNICIPALE DI RUFFANO',
            'pm_salice_salentino' => 'POLIZIA MUNICIPALE DI SALICE SALENTINO',
            'pm_salve' => 'POLIZIA MUNICIPALE DI SALVE',
            'pm_san_cassiano' => 'POLIZIA MUNICIPALE DI SAN CASSIANO',
            'pm_san_cesario_lecce' => 'POLIZIA MUNICIPALE DI SAN CESARIO DI LECCE',
            'pm_san_donato_lecce' => 'POLIZIA MUNICIPALE DI SAN DONATO DI LECCE',
            'pm_san_pietro_lama' => 'POLIZIA MUNICIPALE DI SAN PIETRO IN LAMA',
            'pm_sanarica' => 'POLIZIA MUNICIPALE DI SANARICA',
            'pm_sannicola' => 'POLIZIA MUNICIPALE DI SANNICOLA',
            'pm_santa_cesarea_terme' => 'POLIZIA MUNICIPALE DI SANTA CESAREA TERME',
            'pm_scorrano' => 'POLIZIA MUNICIPALE DI SCORRANO',
            'pm_secli' => 'POLIZIA MUNICIPALE DI SECLI',
            'pm_sogliano_cavour' => 'POLIZIA MUNICIPALE DI SOGLIANO CAVOUR',
            'pm_soleto' => 'POLIZIA MUNICIPALE DI SOLETO',
            'pm_specchia' => 'POLIZIA MUNICIPALE DI SPECCHIA',
            'pm_spongano' => 'POLIZIA MUNICIPALE DI SPONGANO',
            'pm_squinzano' => 'POLIZIA MUNICIPALE DI SQUINZANO',
            'pm_sternatia' => 'POLIZIA MUNICIPALE DI STERNATIA',
            'pm_supersano' => 'POLIZIA MUNICIPALE DI SUPERSANO',
            'pm_surano' => 'POLIZIA MUNICIPALE DI SURANO',
            'pm_surbo' => 'POLIZIA MUNICIPALE DI SURBO',
            'pm_taurisano' => 'POLIZIA MUNICIPALE DI TAURISANO',
            'pm_taviano' => 'POLIZIA MUNICIPALE DI TAVIANO',
            'pm_tiggiano' => 'POLIZIA MUNICIPALE DI TIGGIANO',
            'pm_trepuzzi' => 'POLIZIA MUNICIPALE DI TREPUZZI',
            'pm_tricase' => 'POLIZIA MUNICIPALE DI TRICASE',
            'pm_tuglie' => 'POLIZIA MUNICIPALE DI TUGLIE',
            'pm_ugento' => 'POLIZIA MUNICIPALE DI UGENTO',
            'pm_uggiano_chiesa' => 'POLIZIA MUNICIPALE DI UGGIANO LA CHIESA',
            'pm_veglie' => 'POLIZIA MUNICIPALE DI VEGLIE',
            'pm_vernole' => 'POLIZIA MUNICIPALE DI VERNOLE',
            'pm_zollino' => 'POLIZIA MUNICIPALE DI ZOLLINO',
            'polizia_provinciale' => 'Polizia Provinciale'
        );
    }

    /**
     * Mapping tra enti e codici ISTAT dei comuni
     */
    private function get_mapping_enti_comuni() {
        return array(
            'pm_acquarica_capo' => '001',
            'pm_alessano' => '002',
            'pm_alezio' => '003',
            'pm_alliste' => '004',
            'pm_andrano' => '005',
            'pm_aradeo' => '006',
            'pm_arnesano' => '007',
            'pm_bagnolo_salento' => '008',
            'pm_botrugno' => '009',
            'pm_calimera' => '010',
            'pm_campi_salentina' => '011',
            'pm_cannole' => '012',
            'pm_caprarica_lecce' => '013',
            'pm_carmiano' => '014',
            'pm_carpignano_salentino' => '015',
            'pm_casarano' => '016',
            'pm_castrignano_greci' => '017',
            'pm_castrignano_capo' => '018',
            'pm_castri' => '019',
            'pm_castro' => '020',
            'pm_cavallino' => '021',
            'pm_collepasso' => '022',
            'pm_copertino' => '023',
            'pm_corigliano_otranto' => '024',
            'pm_corsano' => '025',
            'pm_cursi' => '026',
            'pm_cutrofiano' => '027',
            'pm_diso' => '028',
            'pm_gagliano_capo' => '029',
            'pm_galatina' => '030',
            'pm_galatone' => '031',
            'pm_gallipoli' => '032',
            'pm_giuggianello' => '033',
            'pm_giurdignano' => '034',
            'pm_guagnano' => '035',
            'pm_lecce' => '036',
            'pm_lequile' => '037',
            'pm_leverano' => '038',
            'pm_lizzanello' => '039',
            'pm_maglie' => '040',
            'pm_martano' => '041',
            'pm_martignano' => '042',
            'pm_matino' => '043',
            'pm_melendugno' => '044',
            'pm_melissano' => '045',
            'pm_melpignano' => '046',
            'pm_miggiano' => '047',
            'pm_minervino_lecce' => '048',
            'pm_monteroni_lecce' => '049',
            'pm_montesano_salentino' => '050',
            'pm_morciano_leuca' => '051',
            'pm_muro' => '052',
            'pm_nardo' => '053',
            'pm_neviano' => '054',
            'pm_nociglia' => '055',
            'pm_novoli' => '056',
            'pm_ortelle' => '057',
            'pm_otranto' => '058',
            'pm_palmariggi' => '059',
            'pm_parabita' => '060',
            'pm_patu' => '061',
            'pm_poggiardo' => '062',
            'pm_porto_cesareo' => '063',
            'pm_presicce' => '064',
            'pm_presicce_acquarica' => '065',
            'pm_racale' => '066',
            'pm_ruffano' => '067',
            'pm_salice_salentino' => '068',
            'pm_salve' => '069',
            'pm_san_cassiano' => '070',
            'pm_san_cesario_lecce' => '071',
            'pm_san_donato_lecce' => '072',
            'pm_san_pietro_lama' => '073',
            'pm_sanarica' => '074',
            'pm_sannicola' => '075',
            'pm_santa_cesarea_terme' => '076',
            'pm_scorrano' => '077',
            'pm_secli' => '078',
            'pm_sogliano_cavour' => '079',
            'pm_soleto' => '080',
            'pm_specchia' => '081',
            'pm_spongano' => '082',
            'pm_squinzano' => '083',
            'pm_sternatia' => '084',
            'pm_supersano' => '085',
            'pm_surano' => '086',
            'pm_surbo' => '087',
            'pm_taurisano' => '088',
            'pm_taviano' => '089',
            'pm_tiggiano' => '090',
            'pm_trepuzzi' => '091',
            'pm_tricase' => '092',
            'pm_tuglie' => '093',
            'pm_ugento' => '094',
            'pm_uggiano_chiesa' => '095',
            'pm_veglie' => '096',
            'pm_vernole' => '097',
            'pm_zollino' => '098'
            // Gli enti sovracomunali non hanno mapping automatico
        );
    }
}
