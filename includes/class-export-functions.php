<?php
/**
 * Incidenti Export Functions - VERSIONE COMPLETA ISTAT 1939 caratteri
 * 
 * @package IncidentiStradali
 * @version 1.0.0
 * @author Plugin Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include SimpleXLSXGen library
require_once plugin_dir_path(__FILE__) . 'libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

class IncidentiExportFunctions {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_export_menu'), 20);
        add_action('admin_post_export_incidenti_istat', array($this, 'export_istat_txt'));
        add_action('admin_post_export_incidenti_excel', array($this, 'export_excel'));
        add_filter('bulk_actions-edit-incidente_stradale', array($this, 'add_bulk_export_actions'));
        add_filter('handle_bulk_actions-edit-incidente_stradale', array($this, 'handle_bulk_actions'), 10, 3);
    }

    /**
     * Converte safely il risultato di get_post_meta in stringa
     */
    private function safe_meta_string($post_id, $meta_key, $default = '') {
        $value = get_post_meta($post_id, $meta_key, true);
        
        if (is_array($value)) {
            return isset($value[0]) ? (string)$value[0] : $default;
        }
        
        return $value !== '' ? (string)$value : $default;
    }
    
    /**
     * Add export menu to admin
     */
    public function add_export_menu() {
        add_submenu_page(
            'edit.php?post_type=incidente_stradale',
            __('Esporta Dati', 'incidenti-stradali'),
            __('Esporta Dati', 'incidenti-stradali'),
            'export_incidenti',
            'incidenti-export',
            array($this, 'export_page')
        );
    }
    
    /**
     * Add bulk export actions
     */
    public function add_bulk_export_actions($bulk_actions) {
        $bulk_actions['export_istat'] = __('Esporta ISTAT (TXT)', 'incidenti-stradali');
        $bulk_actions['export_excel'] = __('Esporta Excel (CSV)', 'incidenti-stradali');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk export actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'export_istat' || $doaction === 'export_excel') {
            if (!current_user_can('export_incidenti')) {
                wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
            }
            
            $incidenti = get_posts(array(
                'post_type' => 'incidente_stradale',
                'include' => $post_ids,
                'posts_per_page' => -1
            ));
            
            if ($doaction === 'export_istat') {
                $this->download_istat_export($incidenti);
            } else {
                $this->download_excel_export($incidenti);
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Export page
     */
    public function export_page() {
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'incidenti-stradali'));
        }
        
        // Handle export messages
        $message = '';
        if (isset($_GET['export_error'])) {
            switch ($_GET['export_error']) {
                case 'no_data':
                    $message = '<div class="notice notice-warning"><p>' . __('Nessun incidente trovato nel periodo selezionato.', 'incidenti-stradali') . '</p></div>';
                    break;
                case 'export_failed':
                    $message = '<div class="notice notice-error"><p>' . __('Errore durante l\'esportazione.', 'incidenti-stradali') . '</p></div>';
                    break;
            }
        }
        
        if (isset($_GET['export_success'])) {
            $message = '<div class="notice notice-success"><p>' . __('Esportazione completata con successo.', 'incidenti-stradali') . '</p></div>';
        }
        
        ?>
<div class="wrap">
    <h1>
        <?php _e('Esporta Dati Incidenti', 'incidenti-stradali'); ?>
    </h1>
    <?php echo $message; ?>
    <?php if ($this->user_can_access_istat_export()): ?>
    <div class="card">
        <h2>
            <?php _e('Esportazione Formato ISTAT (TXT)', 'incidenti-stradali'); ?>
        </h2>
        <p>
            <?php _e('Esporta i dati nel formato richiesto da ISTAT per la trasmissione ufficiale (1939 caratteri per record).', 'incidenti-stradali'); ?>
        </p>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="export_incidenti_istat">
            <?php wp_nonce_field('export_incidenti_nonce', 'export_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Periodo', 'incidenti-stradali'); ?>
                    </th>
                    <td>
                        <label for="data_inizio">
                            <?php _e('Da:', 'incidenti-stradali'); ?></label>
                        <input type="date" id="data_inizio" name="data_inizio" value="<?php echo date('Y-m-01'); ?>">
                        <label for="data_fine">
                            <?php _e('A:', 'incidenti-stradali'); ?></label>
                        <input type="date" id="data_fine" name="data_fine" value="<?php echo date('Y-m-t'); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Comune', 'incidenti-stradali'); ?>
                    </th>
                    <td>
                        <select name="comune_filtro" class="regular-text">
                            <option value="">
                                <?php _e('Tutti i comuni', 'incidenti-stradali'); ?>
                            </option>
                            <?php 
                                    $comuni = $this->get_comuni_disponibili();
                                    foreach($comuni as $codice => $nome): ?>
                            <option value="<?php echo esc_attr($codice); ?>">
                                <?php echo esc_html($nome); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Filtra per comune specifico', 'incidenti-stradali'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Tipologia Infortunati', 'incidenti-stradali'); ?>
                    </th>
                    <td>
                        <select name="tipologia_infortunati" class="regular-text">
                            <option value="">
                                <?php _e('Tutti i tipi', 'incidenti-stradali'); ?>
                            </option>
                            <option value="con_morti">
                                <?php _e('Incidente con morti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="solo_morti">
                                <?php _e('Incidente con solo morti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="con_feriti">
                                <?php _e('Incidente con feriti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="solo_feriti">
                                <?php _e('Incidente con solo feriti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="morti_e_feriti">
                                <?php _e('Incidente con morti e feriti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="morti_o_feriti">
                                <?php _e('Incidente con morti o feriti', 'incidenti-stradali'); ?>
                            </option>
                            <option value="senza_infortunati">
                                <?php _e('Incidente senza infortunati', 'incidenti-stradali'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Filtra per tipologia di infortunati', 'incidenti-stradali'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Esporta TXT ISTAT (1939 caratteri)', 'incidenti-stradali'), 'primary'); ?>
        </form>
    </div>

    <div class="card">
        <h2>
            <?php _e('Informazioni Tracciato ISTAT', 'incidenti-stradali'); ?>
        </h2>
        <p><strong>
                <?php _e('Lunghezza record:', 'incidenti-stradali'); ?></strong> 1939 caratteri</p>
        <p><strong>
                <?php _e('Encoding:', 'incidenti-stradali'); ?></strong> UTF-8 senza BOM</p>
        <p><strong>
                <?php _e('Terminatore riga:', 'incidenti-stradali'); ?></strong> \\r\\n</p>
        <p><strong>
                <?php _e('Conforme a:', 'incidenti-stradali'); ?></strong> Tracciato record 2 ISTAT 2025</p>
    </div>
    <?php endif; ?>

    <!-- TEMPORANEAMENTE NASCOSTO - Esportazione CSV -->
    <?php
    if ($this->user_can_access_excel_export()):
        if (true): // Cambia a true per riattivare ?>
        <div class="card">
            <h2>
                <?php _e('Esportazione Formato Excel (XLSX)', 'incidenti-stradali'); ?>
            </h2>
            <p>
                <?php _e('Esporta i dati nel formato Excel nativo (.xlsx) per la Polizia Stradale.', 'incidenti-stradali'); ?>
            </p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="export_incidenti_excel">
                <?php wp_nonce_field('export_incidenti_nonce', 'export_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php _e('Periodo', 'incidenti-stradali'); ?>
                        </th>
                        <td>
                            <label for="data_inizio_excel">
                                <?php _e('Da:', 'incidenti-stradali'); ?></label>
                            <input type="date" id="data_inizio_excel" name="data_inizio" value="<?php echo date('Y-m-01'); ?>">
                            <label for="data_fine_excel">
                                <?php _e('A:', 'incidenti-stradali'); ?></label>
                            <input type="date" id="data_fine_excel" name="data_fine" value="<?php echo date('Y-m-t'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Comune', 'incidenti-stradali'); ?>
                        </th>
                        <td>
                            <select name="comune_filtro" class="regular-text">
                                <option value="">
                                    <?php _e('Tutti i comuni', 'incidenti-stradali'); ?>
                                </option>
                                <?php
                                $comuni_xlsx = $this->get_comuni_disponibili();
                                foreach($comuni_xlsx as $codice => $nome): ?>
                                <option value="<?php echo esc_attr($codice); ?>">
                                    <?php echo esc_html($nome); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Esporta File Excel', 'incidenti-stradali'), 'secondary'); ?>
            </form>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <!-- FINE BLOCCO TEMPORANEAMENTE NASCOSTO -->
    
    <?php
    // Controlla se l'utente ha il ruolo di amministratore
    $current_user = wp_get_current_user();
    if (in_array('administrator', $current_user->roles)) : 
    ?>
    <div class="card">
        <h2>
            <?php _e('Log Esportazioni', 'incidenti-stradali'); ?>
        </h2>
        <?php $this->show_export_logs(); ?>
    </div>
    <?php endif; ?>
</div>
<?php
    }
    
    /**
     * Export ISTAT TXT
     */
    public function export_istat_txt() {
        // Verifica permessi
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        // Verifica nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_incidenti_nonce')) {
            wp_die(__('Nonce verification failed.', 'incidenti-stradali'));
        }
        
        // Aumenta il limite di tempo per l'esecuzione
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $data_inizio = sanitize_text_field($_POST['data_inizio']);
        $data_fine = sanitize_text_field($_POST['data_fine']);
        $comune_filtro = sanitize_text_field($_POST['comune_filtro']);
        $tipologia_infortunati = sanitize_text_field($_POST['tipologia_infortunati']);
        
        /* $incidenti = $this->get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro); */
        $incidenti = $this->get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro, $tipologia_infortunati);
        
        if (empty($incidenti)) {
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'page' => 'incidenti-export',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        $this->download_istat_export($incidenti);
    }
    
    /**
     * Export Excel CSV
     */
    public function export_excel() {
        // Verifica permessi
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        // Verifica nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_incidenti_nonce')) {
            wp_die(__('Nonce verification failed.', 'incidenti-stradali'));
        }
        
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $data_inizio = sanitize_text_field($_POST['data_inizio']);
        $data_fine = sanitize_text_field($_POST['data_fine']);
        $comune_filtro = sanitize_text_field($_POST['comune_filtro']);
        
        $incidenti = $this->get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro);
        
        if (empty($incidenti)) {
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'page' => 'incidenti-export',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        $this->download_excel_export($incidenti);
    }
    
    /**
     * Get incidenti for export
     */
    /* private function get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro = '') {
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($data_inizio, $data_fine),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        if (!empty($comune_filtro)) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => $comune_filtro,
                'compare' => '='
            );
        }
        
        return get_posts($args);
    } */

    private function get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro = '', $tipologia_infortunati = '') {
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($data_inizio, $data_fine),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        if (!empty($comune_filtro)) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => $comune_filtro,
                'compare' => '='
            );
        }
        
        $incidenti = get_posts($args);
        
        // Applica filtro tipologia infortunati se specificato
        if (!empty($tipologia_infortunati)) {
            $incidenti = array_filter($incidenti, function($incidente) use ($tipologia_infortunati) {
                return $this->check_tipologia_infortunati($incidente->ID, $tipologia_infortunati);
            });
        }
        
        return $incidenti;
    }

    /**
     * Verifica se un incidente corrisponde alla tipologia infortunati richiesta
     */
    private function check_tipologia_infortunati($post_id, $tipologia) {
        $morti = 0;
        $feriti = 0;
        
        // Conta conducenti morti e feriti
        for ($i = 1; $i <= 3; $i++) {
            $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
            if ($esito === '1') $morti++;
            elseif ($esito === '2') $feriti++;
        }
        
        // Conta passeggeri morti e feriti
        for ($i = 1; $i <= 3; $i++) {
            for ($p = 1; $p <= 4; $p++) {
                $esito = get_post_meta($post_id, 'veicolo_' . $i . '_passeggero_' . $p . '_esito', true);
                if ($esito === '1') $morti++;
                elseif ($esito === '2') $feriti++;
            }
        }
        
        // Conta pedoni morti e feriti
        for ($i = 1; $i <= 5; $i++) {
            $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
            if ($esito === '1') $morti++;
            elseif ($esito === '2') $feriti++;
        }
        
        // Conta altri infortunati
        $altri_morti_m = intval(get_post_meta($post_id, 'altri_morti_maschi', true) ?: 0);
        $altri_morti_f = intval(get_post_meta($post_id, 'altri_morti_femmine', true) ?: 0);
        $altri_feriti_m = intval(get_post_meta($post_id, 'altri_feriti_maschi', true) ?: 0);
        $altri_feriti_f = intval(get_post_meta($post_id, 'altri_feriti_femmine', true) ?: 0);
        
        $morti += $altri_morti_m + $altri_morti_f;
        $feriti += $altri_feriti_m + $altri_feriti_f;
        
        // Applica la logica del filtro
        switch ($tipologia) {
            case 'con_morti':
                return ($morti > 0);
            case 'solo_morti':
                return ($morti > 0 && $feriti == 0);
            case 'con_feriti':
                return ($feriti > 0);
            case 'solo_feriti':
                return ($feriti > 0 && $morti == 0);
            case 'morti_e_feriti':
                return ($morti > 0 && $feriti > 0);
            case 'morti_o_feriti':
                return ($morti > 0 || $feriti > 0);
            case 'senza_infortunati':
                return ($morti == 0 && $feriti == 0);
            default:
                return true;
        }
    }
    
    /**
     * Download ISTAT export
     */
    private function download_istat_export($incidenti) {
        $filename = 'export_incidenti_istat_' . date('YmdHis') . '.txt';
        $output = $this->generate_istat_txt_complete($incidenti);
        
        // Validazione lunghezza record
        //$lines = explode("\r\n", trim($output));
        $lines = explode("\r\n", $output);
        $errori_lunghezza = array();
        
        foreach ($lines as $line_num => $line) {
            if ((mb_strlen($line) > 0) and (mb_strlen($line) !== 1939)) {
                $errori_lunghezza[] = "Record " . ($line_num + 1) . " ha lunghezza " . mb_strlen($line) . " invece di 1939 caratteri";
                error_log("ATTENZIONE ISTAT: Record " . ($line_num + 1) . " ha lunghezza " . mb_strlen($line) . " invece di 1939 caratteri");
            }
        }
        
        // Log dell'esportazione
        $this->log_export('ISTAT_TXT', count($incidenti), $filename, $errori_lunghezza);
        
        // Trigger notification
        do_action('incidenti_after_export', 'ISTAT_TXT', $filename, count($incidenti), get_current_user_id());
        
        // Download del file
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . mb_strlen($output));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Output senza BOM per compatibilità ISTAT
        echo $output;
        exit;
    }
    
    /**
     * Download Excel export
     */
    private function download_excel_export($incidenti) {
        $filename = 'export_incidenti_excel_' . date('YmdHis') . '.xlsx';
        $filepath = wp_upload_dir()['basedir'] . '/incidenti-exports/' . $filename;
        
        // Genera il file Excel
        $this->generate_excel_xlsx($incidenti, $filepath);
        
        // Log dell'esportazione
        $this->log_export('EXCEL_XLSX', count($incidenti), $filename);
        
        // Trigger notification
        do_action('incidenti_after_export', 'EXCEL_XLSX', $filename, count($incidenti), get_current_user_id());
        
        // Download del file
        if (file_exists($filepath)) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            readfile($filepath);
            
            // Elimina il file temporaneo dopo il download
            unlink($filepath);
            exit;
        } else {
            wp_die(__('Errore nella generazione del file Excel', 'incidenti-stradali'));
        }
    }
    
    /**
     * Genera file TXT ISTAT completo (1939 caratteri)
     * Basato su rilevazioni.php esistente e tracciato ISTAT ufficiale
     */

     //  $output .= $esitoTXTstr . "\r\n";

    public function generate_istat_txt_complete($incidenti) {
        // Carica la configurazione ISTAT
        $cfgistat = $this->load_istat_config();
        
        $output = '';
        $index = 0;
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $esitoTXT = array();
            $indTXT = -1; // Inizia da -1 perché incrementiamo prima di usare
            
            // ===== DATI BASE IDENTIFICATIVI (Posizioni 1-26) =====
            // Campo 1-2: Anno (ultime 2 cifre)
            $data_incidente = $this->safe_meta_string($post_id, 'data_incidente');
            $anno = substr($data_incidente, 2, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($anno, 2, '0', STR_PAD_LEFT);
            
            // Campo 3-4: Mese
            $mese = substr($data_incidente, 5, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($mese, 2, '0', STR_PAD_LEFT);
            
            // Campo 5-7: Provincia
            $provincia = $this->safe_meta_string($post_id, 'provincia_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($provincia ?: '000', 3, '0', STR_PAD_LEFT);
            
            // Campo 8-10: Comune
            $comune = $this->safe_meta_string($post_id, 'comune_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($comune ?: '000', 3, '0', STR_PAD_LEFT);
            
            // Campo 11-14: Numero d'ordine
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad(mb_substr($post_id,0,4), 4, '0', STR_PAD_LEFT);
            
            // Campo 15-16: Giorno
            $giorno = substr($data_incidente, 8, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($giorno, 2, '0', STR_PAD_LEFT);
            
            // Campo 17-18: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 2, '  ', STR_PAD_RIGHT);
            
            // Campo 19: Organo di rilevazione
            $organo_rilevazione = $this->safe_meta_string($post_id, 'organo_rilevazione');
            $indTXT++;
            $esitoTXT[$indTXT] = $organo_rilevazione ?: '4'; // Default: Polizia Municipale
            
            // Campo 20-24: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 5, '  ', STR_PAD_RIGHT);
            
            //da controllare
            // Campo 25: Organo coordinatore
            $organo_coordinatore = $this->safe_meta_string($post_id, 'organo_coordinatore');
            $indTXT++;
            $esitoTXT[$indTXT] = $organo_coordinatore ?: '4';
            
            // Campo 26: Localizzazione incidente
            $localizzazione = $this->safe_meta_string($post_id, 'tipo_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = $localizzazione ?: ' ';
            
            // ===== DENOMINAZIONE E CARATTERISTICHE STRADA (Posizioni 27-40) =====
            
            // Campo 27-29: Denominazione strada
            $tipologia_strada = $this->safe_meta_string($post_id, 'numero_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad(substr($tipologia_strada ?: ' ', 0, 3), 3, ' ', STR_PAD_LEFT);
            
            // Campo 30: Illuminazione
            $illuminazione = $this->safe_meta_string($post_id, 'illuminazione');
            $indTXT++;
            $esitoTXT[$indTXT] = $illuminazione ?: ' ';
            
            // Campo 31-32: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 2, '  ', STR_PAD_RIGHT);

              /*Campo 33-34: tronco di strada statale o di autostrada */
            $tronco_strada = $this->safe_meta_string($post_id, 'tronco_strada');
            $indTXT++;
            $esitoTXT[$indTXT] =mb_str_pad($tronco_strada ?: '  ', 2, '0', STR_PAD_LEFT);
            /***oppure modificare in db 01-02-03-04-...
             * 
             *  $esitoTXT[$indTXT] = $tronco_strada ?: '  ';
             */

            // ===== CARATTERISTICHE DEL LUOGO (Posizioni 35-40) =====
            // Campo 35: Tipo di strada
            $tipo_strada = $this->safe_meta_string($post_id, 'geometria_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = $tipo_strada ?: ' ';
            // Campo 36: Pavimentazione
            $pavimentazione = $this->safe_meta_string($post_id, 'pavimentazione_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = $pavimentazione ?: ' ';
            
            // Campo 37: Intersezione
            $intersezione = $this->safe_meta_string($post_id, 'intersezione_tronco');
            $indTXT++;
            //$esitoTXT[$indTXT] = $intersezione ?: ' ';
            $esitoTXT[$indTXT] =mb_str_pad($intersezione ?: '  ', 2, '0', STR_PAD_LEFT);
            
            // Campo 39: Fondo stradale
            $fondo_stradale = $this->safe_meta_string($post_id, 'stato_fondo_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = $fondo_stradale ?: ' ';
            
            // Campo 40: Segnaletica
            $segnaletica = $this->safe_meta_string($post_id, 'segnaletica_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = $segnaletica ?: ' ';
            
            // Campo 41: Condizioni meteorologiche
            $meteo = $this->safe_meta_string($post_id, 'condizioni_meteo');
            $indTXT++;
            $esitoTXT[$indTXT] = $meteo ?: ' ';
            
            // ===== NATURA DELL'INCIDENTE (Posizioni 41-43) ====
            // Campo 42-43: Dettaglio natura
            $dettaglio_natura = $this->safe_meta_string($post_id, 'dettaglio_natura');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($dettaglio_natura ?: '  ', 2, '0', STR_PAD_LEFT);
            
            // ===== VEICOLI COINVOLTI - TIPO (Posizioni 44-49) =====        
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $tipo_veicolo = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_tipo");
                $tipo_veicolo = mb_str_pad($tipo_veicolo ?: '  ', 2, '0', STR_PAD_LEFT);
                $indTXT++;
                $esitoTXT[$indTXT] = $tipo_veicolo;
                
                /*if (trim($esitoTXT[$indTXT]) == '') $esitoTXT[$indTXT] = '   ';
                $esitoTXT[$indTXT] = mb_str_pad($esitoTXT[$indTXT], 2, '  ', STR_PAD_LEFT);*/
            }

            // Campo 50-61: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 4, '  ', STR_PAD_RIGHT);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 4, '  ', STR_PAD_RIGHT);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 4, '  ', STR_PAD_RIGHT);
            
            // ===== VEICOLI - PESO TOTALE (Posizioni 62-73) =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $peso = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_peso_totale");
                // Gestione sicura per valori vuoti o non numerici
                $peso = is_numeric($peso) ? round(floatval($peso)) : 0;
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($peso ?: '    ', 4, ' ', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '0000') $esitoTXT[$indTXT] = '     ';
            }

            // ===== CIRCOSTANZE VEICOLI A e B (Posizioni 74-85) =====         
            // Inconvenienti alla circolazione (74-75)
            $circostanza_veicolo_a = $this->safe_meta_string($post_id, "circostanza_veicolo_a");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($circostanza_veicolo_a ?: '  ', 2, '0', STR_PAD_LEFT);

            // Difetti o avarie (76-77)
            $difetto_veicolo_a = $this->safe_meta_string($post_id, "difetto_veicolo_a");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($difetto_veicolo_a ?: '  ', 2, '0', STR_PAD_LEFT);
                
            // Stato psico-fisico conducente (78-79)
            $stato_psicofisico_a = $this->safe_meta_string($post_id, "stato_psicofisico_a");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($stato_psicofisico_a ?: '  ', 2, '0', STR_PAD_LEFT);

            // Inconvenienti alla circolazione (80-81)
            $circostanza_veicolo_b = $this->safe_meta_string($post_id, "circostanza_veicolo_b");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($circostanza_veicolo_b ?: '  ', 2, '0', STR_PAD_LEFT);

            // Difetti o avarie (82-83)
            $difetto_veicolo_b = $this->safe_meta_string($post_id, "difetto_veicolo_b");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($difetto_veicolo_b ?: '  ', 2, '0', STR_PAD_LEFT);
                
            // Stato psico-fisico conducente (84-85)
            $stato_psicofisico_b = $this->safe_meta_string($post_id, "stato_psicofisico_b");
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($stato_psicofisico_b ?: '  ', 2, '0', STR_PAD_LEFT);            
                
            // ===== TARGHE E DATI VEICOLI (Posizioni 86-139) =====        
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Targa (8 caratteri)
                $targa = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_targa");
                $targa = mb_str_pad($targa ?: '        ', 8, ' ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = $targa;

                // Sigla se estero (3 caratteri)
                $sigla = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_sigla_estero");
                $sigla = mb_str_pad($sigla ?: '   ', 3, ' ', STR_PAD_LEFT);
                $indTXT++;
                $esitoTXT[$indTXT] =  $sigla;
                
                // Anno immatricolazione (2 cifre)
                $anno_imm = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_anno_immatricolazione");
                //$strAppo =  mb_substr($anno_imm, 2, 2);
                $strAppo = mb_str_pad($anno_imm ?: '  ', 2, ' ', STR_PAD_LEFT);
               
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo;

                // Spazi n.5
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 2, '  ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 3, '  ', STR_PAD_RIGHT);
            }
            //7. Conseguenze dell'incidente alle persone 140-244
            // ===== DATI CONDUCENTI E PASSEGGERI =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Età conducente (2 cifre)
                $eta = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_eta");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($eta ?: '  ', 2, ' ', STR_PAD_LEFT);
                
                // Sesso conducente (1 cifra)
                $sesso = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_sesso");
                $strAppo = $sesso;
                $indTXT++;
                $esitoTXT[$indTXT] = $cfgistat['sesso'][$strAppo] ?? ' ';
                
                // Esito conducente (1 cifra)
                $esito = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_esito");
                $indTXT++;
                $esitoTXT[$indTXT] = $esito ?: ' ';

                // Tipo di patente conducente (1 cifra)
                $tipo_patente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_tipo_patente");
                $indTXT++;
                $esitoTXT[$indTXT] = $tipo_patente ?: ' ';

                // Anno patente conducente (2 cifre)
                $anno_patente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_anno_patente");
                
               // $strAppo = substr($anno_patente, 2, 2);
                $strAppo = mb_str_pad($anno_patente ?: '  ', 2, ' ', STR_PAD_LEFT);
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo;                

                /* sul tracciato riga 325 non è specificato il numero di caratteri e se vuoto*/
                // Conducente durante lo svolgimento di attività lavorativa o in itinere
                $tipologia_incidente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_tipologia_incidente");
                $indTXT++;
                $esitoTXT[$indTXT] = $tipologia_incidente ?: ' ';

                // Spazi n.3
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 1, ' ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 1, ' ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 1, ' ', STR_PAD_RIGHT);

                // PASSEGGERI - Gestione dinamica in base alla selezione del sedile
                $numPassAnt = 1; // Previsto da ISTAT (max 1 passeggero anteriore)
                $numPassPost = 3; // Previsto da ISTAT (max 3 passeggeri posteriori)

                // Raccogli tutti i trasportati e ordina per sedile
                $trasportati_anteriori = array();
                $trasportati_posteriori = array();
                $maschi_morti_veicolo = 0;
                $femmine_morte_veicolo = 0;
                $maschi_feriti_veicolo = 0;
                $femmine_ferite_veicolo = 0;
                // Ottieni il numero di trasportati per questo veicolo
                $num_trasportati = get_post_meta($post_id, "veicolo_{$numVeicolo}_numero_trasportati", true) ?: 0;
                // Limita a massimo 4 trasportati
                $num_trasportati = min($num_trasportati, 4);

                // Analizza ogni trasportato e categorizza per sedile
                for ($t = 1; $t <=4; $t++) {
                    $sedile = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_trasportato_{$t}_sedile");
                    $eta = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_trasportato_{$t}_eta");
                    $sesso = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_trasportato_{$t}_sesso");
                    $esito = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_trasportato_{$t}_esito");
                    //if(($esito == '3') || ($esito == '4')){
                    if($esito == '1'){
                       if ($sesso == '3') {
                            $maschi_morti_veicolo++;
                        } elseif ($sesso == '4') {
                            $femmine_morte_veicolo++;
                        }
                    }else {
                        if ($sesso == '3') {
                            $maschi_feriti_veicolo++;
                        } elseif ($sesso == '4') {
                            $femmine_ferite_veicolo++;
                        }
                    }
                        
                    // Crea array con i dati del trasportato
                    $dati_trasportato = array(
                        'eta' => $eta ?: '  ',
                        'sesso' => $sesso ?: ' ',
                        'esito' => $esito ?: ' '
                    );
                    
                    // Categorizza in base al sedile selezionato
                    if ($sedile === 'anteriore' && count($trasportati_anteriori) < $numPassAnt) {
                        $trasportati_anteriori[] = $dati_trasportato;
                    } elseif ($sedile === 'posteriore' && count($trasportati_posteriori) < $numPassPost) {
                        $trasportati_posteriori[] = $dati_trasportato;
                    }
                    // Se il sedile non è specificato o è pieno, viene ignorato per l'esportazione ISTAT
                }

                // Esporta passeggeri anteriori (max 1)
                for ($numPass = 1; $numPass <= $numPassAnt; $numPass++) {
                    if (isset($trasportati_anteriori[$numPass - 1])) {
                        $trasportato = $trasportati_anteriori[$numPass - 1];
                        // Esito
                        $indTXT++;
                        $esitoTXT[$indTXT] = $trasportato['esito'];
                        // Età
                        $indTXT++;
                        $esitoTXT[$indTXT] = mb_str_pad($trasportato['eta'], 2, '0', STR_PAD_LEFT);
                        // Sesso
                        $indTXT++;
                        $esitoTXT[$indTXT] =$trasportato['sesso'] ?? ' ';
                    } else {
                        // Campi vuoti se non c'è passeggero anteriore
                        $indTXT++;
                        $esitoTXT[$indTXT] = ' ';  // Esito non specificato
                        $indTXT++;
                        $esitoTXT[$indTXT] = '  '; // Età non specificata
                        $indTXT++;
                        $esitoTXT[$indTXT] = ' ';  // Sesso non specificato
                    }
                }

                // Esporta passeggeri posteriori (max 3)
                for ($numPass = 1; $numPass <= $numPassPost; $numPass++) {
                    if (isset($trasportati_posteriori[$numPass - 1])) {
                        $trasportato = $trasportati_posteriori[$numPass - 1];

                        // Esito
                        $indTXT++;
                        $esitoTXT[$indTXT] = $trasportato['esito'];
                        // Età
                        $indTXT++;
                        $esitoTXT[$indTXT] = mb_str_pad($trasportato['eta'], 2, '0', STR_PAD_LEFT);                       
                        // Sesso  
                        $indTXT++;
                         $esitoTXT[$indTXT] =$trasportato['sesso'] ?? ' ';
                    } else {
                        // Campi vuoti se non c'è passeggero posteriore
                        $indTXT++;
                        $esitoTXT[$indTXT] = ' ';  // Esito non specificato
                        $indTXT++;
                        $esitoTXT[$indTXT] = '  '; // Età non specificata
                        $indTXT++;
                        $esitoTXT[$indTXT] = ' ';  // Sesso non specificato

                    }
                }
  
                // Altri passeggeri infortunati sul veicolo
                //Numero dei morti di sesso maschile (2 cifre)
                $altri_morti_maschi = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_altri_morti_maschi");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($altri_morti_maschi ?: '  ', 2, ' ', STR_PAD_LEFT);
                //Numero dei morti di sesso femminile (2 cifre)
                $altri_morti_femmine = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_altri_morti_femmine");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($altri_morti_femmine ?: '  ', 2, ' ', STR_PAD_LEFT);
                //Numero dei feriti di sesso maschile (2 cifre)
                $altri_feriti_maschi = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_altri_feriti_maschi");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($altri_feriti_maschi ?: '  ', 2, ' ', STR_PAD_LEFT);                
                //Numero dei feriti di sesso femminile (2 cifre)
                $altri_feriti_femmine = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_altri_feriti_femmine");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($altri_feriti_femmine ?: '  ', 2, ' ', STR_PAD_LEFT);

            }//for num veicoli 140-244

            // Pedoni coinvolti (Posizioni 245-268)
            for ($numPedone = 1; $numPedone <= 4; $numPedone++) {
                //Sesso del pedone morto (1 cifra)
                $sesso_pedone_morto = $this->safe_meta_string($post_id, "pedone_morto_{$numPedone}_sesso");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($sesso_pedone_morto ?: ' ', 1, '0', STR_PAD_LEFT);
                //Età del pedone morto (2 cifre)
                $eta_pedone_morto = $this->safe_meta_string($post_id, "pedone_morto_{$numPedone}_eta");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($eta_pedone_morto ?: '  ', 1, '0', STR_PAD_LEFT);

                //Sesso del pedone ferito (1 cifra)
                $sesso_pedone_ferito = $this->safe_meta_string($post_id, "pedone_ferito_{$numPedone}_sesso");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($sesso_pedone_ferito ?: ' ', 1, '0', STR_PAD_LEFT);
                //Età del pedone ferito (2 cifre)
                $eta_pedone_ferito = $this->safe_meta_string($post_id, "pedone_ferito_{$numPedone}_eta");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($eta_pedone_ferito ?: '  ', 1, '0', STR_PAD_LEFT);
            }

            //Altri veicoli coinvolti altre ai veicoli A, B e C, e persone infortunate 269-278
            // Numero veicoli coinvolti oltre A, B, C 269-270 (2 cifre)
            $altri_veicoli = $this->safe_meta_string($post_id, 'numero_altri_veicoli');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altri_veicoli ?: '  ', 2, '0', STR_PAD_LEFT);
                
            // Numero di morti di sesso maschile su eventuali altri veicoli 271-272 (2 cifre)
            $altri_morti_maschi = $this->safe_meta_string($post_id, 'altri_morti_maschi');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altri_morti_maschi ?: '  ', 2, '0', STR_PAD_LEFT);
            
            // Numero di morti di sesso maschile su eventuali altri veicoli 273-274 (2 cifre)
            $altri_morti_femmine = $this->safe_meta_string($post_id, 'altri_morti_femmine');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altri_morti_femmine ?: '  ', 2, '0', STR_PAD_LEFT);

            // Numero di feriti di sesso maschile su eventuali altri veicoli 275-276 (2 cifre)
            $altri_feriti_maschi = $this->safe_meta_string($post_id, 'altri_feriti_maschi');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altri_feriti_maschi ?: '  ', 2, '0', STR_PAD_LEFT);

            // Numero di feriti di sesso femminile su eventuali altri veicoli 277-278 (2 cifre)
            $altri_feriti_femmine = $this->safe_meta_string($post_id, 'altri_feriti_femmine');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altri_feriti_femmine ?: '  ', 2, '0', STR_PAD_LEFT);

            //Riepilogo infortunati 279-293
            // Totale morti entro le prime 24 ore dall'incidente 279-280 (2 cifre)
            $riepilogo_morti_24h = $this->safe_meta_string($post_id, 'riepilogo_morti_24h');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($riepilogo_morti_24h ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Totale morti dal 2° al 30° giorno dall'incidente 281-282 (2 cifre)
            $riepilogo_morti_2_30gg = $this->safe_meta_string($post_id, 'riepilogo_morti_2_30gg');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($riepilogo_morti_2_30gg ?: '00', 2, '0', STR_PAD_LEFT);

            //Totale feriti 282-284 (2 cifre)
            $riepilogo_feriti = $this->safe_meta_string($post_id, 'riepilogo_feriti');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($riepilogo_feriti ?: '00', 2, '0', STR_PAD_LEFT);

            // Spazi n.9 285-293
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 9, '  ', STR_PAD_RIGHT);

            //Specifiche sulla denominazione della strada 294-450
            // ===== DENOMINAZIONE STRADA COMPLETA 294-350 (57 caratteri) =====
            $strada_completa = $this->safe_meta_string($post_id, 'denominazione_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad(substr($strada_completa ?: '', 0, 57), 57, '  ', STR_PAD_RIGHT);

            // Spazi n.100 351-450
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 100, '  ', STR_PAD_RIGHT);

            // ===== NOMINATIVI MORTI (4 morti massimo - 60 caratteri ciascuno) 451-690 =====
            for ($numMorto = 1; $numMorto <= 4; $numMorto++) {
                // Nome morto (30 caratteri)
                $nome_morto = $this->safe_meta_string($post_id, "morto_{$numMorto}_nome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($nome_morto ?: '', 0, 30), 30, '  ', STR_PAD_RIGHT);
                // Cognome morto (30 caratteri)
                $cognome_morto = $this->safe_meta_string($post_id, "morto_{$numMorto}_cognome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($cognome_morto ?: '', 0, 30), 30, '  ', STR_PAD_RIGHT);
            }

            // ===== NOMINATIVI FERITI (8 feriti massimo - 90 caratteri ciascuno) 691-1410 =====
            for ($numFerito = 1; $numFerito <= 8; $numFerito++) {
                // Nome ferito (30 caratteri)
                $nome_ferito = $this->safe_meta_string($post_id, "ferito_{$numFerito}_nome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($nome_ferito ?: '', 0, 30), 30, '  ', STR_PAD_RIGHT);
                
                // Cognome ferito (30 caratteri)
                $cognome_ferito = $this->safe_meta_string($post_id, "ferito_{$numFerito}_cognome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($cognome_ferito ?: '', 0, 30), 30, '  ', STR_PAD_RIGHT);
                
                // Istituto ricovero (30 caratteri)
                $istituto = $this->safe_meta_string($post_id, "ferito_{$numFerito}_istituto");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($istituto ?: '', 0, 30), 30, '  ', STR_PAD_RIGHT);
            }

            //Spazio riservato ISTAT per elaborazione 1411-1420
            //Spazi n.10
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 10, '  ', STR_PAD_RIGHT);
            
            //Specifiche per la georeferenziazione 1421-1730
            // 1422- 1522 (campi facoltativi)
            // Tipo di coordinata (1 caratteri) 1421
            $tipo_coordinata = $this->safe_meta_string($post_id, 'tipo_coordinata');
            $indTXT++;
            $esitoTXT[$indTXT] = '1';
            //mb_str_pad($tipo_coordinata ?: '', 1, '  ', STR_PAD_RIGHT);
            // Sistema di proiezione (1 caratteri) 1422
            $sistema_di_proiezione = $this->safe_meta_string($post_id, 'sistema_di_proiezione');
            $indTXT++;
            $esitoTXT[$indTXT] = '2';//mb_str_pad($sistema_di_proiezione ?: '', 1, '  ', STR_PAD_RIGHT);
            // Longitudine (10 caratteri) 1423-1472
            $longitudine = $this->safe_meta_string($post_id, 'longitudine');
            $longitudine = str_replace(".", ",",$longitudine);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($longitudine ?: '', 50, '  ', STR_PAD_LEFT);            
            // Latitudine (10 caratteri) 1473-1522
            $latitudine = $this->safe_meta_string($post_id, 'latitudine');
            $latitudine = str_replace(".", ",",$latitudine);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($latitudine ?: '', 50, '  ', STR_PAD_LEFT);
            //Spazio riservato ISTAT per elaborazione 1523-1530
            //Spazi n.8
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 8, '  ', STR_PAD_RIGHT);          

            // Campo 1531-1532: ora
            $ora = $this->safe_meta_string($post_id, 'ora_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($ora ?: '25', 2, '0', STR_PAD_LEFT); // 25 = sconosciuta
            
            // Campo 1533-1534: Minuti  
            $minuti = $this->safe_meta_string($post_id, 'minuti_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($minuti ?: '  ', 2, '0', STR_PAD_LEFT); // 99 = sconosciuti

            //Campo 1535-1564: Codice identificativo Carabinieri
            $codice_carabinieri = $this->safe_meta_string($post_id, 'identificativo_comando');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($codice_carabinieri ?: '', 30, '  ', STR_PAD_RIGHT);

            //Campo 1565-1568: Progressiva chilometrica
            $progressiva_km = $this->safe_meta_string($post_id, 'progressiva_km');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($progressiva_km ?: '', 4, '0', STR_PAD_LEFT);

            //Campo 1569-1571: Ettometrica
            $progressiva_m = $this->safe_meta_string($post_id, 'progressiva_m');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($progressiva_m ?: '', 3, '0', STR_PAD_LEFT);

            // ===== VEICOLI - CILINDRATA (Posizioni 1572-1730) =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $cilindrata = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_cilindrata");
                // Gestione sicura per valori vuoti o non numerici
                $cilindrata = is_numeric($cilindrata) ? round(floatval($cilindrata)) : 0;
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($cilindrata ?: '     ', 5, ' ', STR_PAD_LEFT);
            }
            //Spazio riservato ISTAT per elaborazione 1587-1590
            //Spazi n.4
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 4, '  ', STR_PAD_RIGHT);

            //Campo 1591-1690: Altra strada
            $altra_strada = $this->safe_meta_string($post_id, 'localizzazione_extra_ab');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altra_strada ?: '', 100, '  ', STR_PAD_RIGHT);
            
            //Campo 1691-1730: Località
            $localita_incidente = $this->safe_meta_string($post_id, 'localita_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($localita_incidente ?: '', 40, '  ', STR_PAD_RIGHT);
            
            //1731-1780: Riservato agli Enti in convenzione con Istat
            //Campo 1731-1770: Codice Identificativo Ente  
            $codice__ente = $this->safe_meta_string($post_id, 'codice__ente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($codice__ente ?: '', 40, '  ', STR_PAD_LEFT);
            
            //Spazio riservato ISTAT per elaborazione 1771-1780
            //Spazi n.10
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 10, '  ', STR_PAD_RIGHT);

            // ===== Specifiche per la registrazione delle informazioni sulla Cittadinanza dei conducenti dei veicoli A, B e C  (Posizioni 1781-1882) =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                //Codice cittadinanza del conducente veicolo
                $nazionalita_conducente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_nazionalita");
                if (!empty($nazionalita_conducente)) {            
                    $parti_nazionalita = explode('-', $nazionalita_conducente);
                    // Assegna le parti alla variabili separate
                    $nazionalita_conducente = $parti_nazionalita[0];
                    $nazionalita_altro_conducente = $parti_nazionalita[1];

                    if($nazionalita_conducente==='000') {
                    $tipo_cittadinanza_conducente = '1'; // Default se non specificato
                    } else {
                    $tipo_cittadinanza_conducente = '2';
                    }
                } else {
                    $tipo_cittadinanza_conducente = '';
                    $nazionalita_conducente = '';
                    $nazionalita_altro_conducente = '';
                }
                 //Cittadinanza italiana o straniera del conducente veicolo
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($tipo_cittadinanza_conducente ?: ' ', 1, '  ', STR_PAD_RIGHT);

                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($nazionalita_conducente ?: '', 3, '  ', STR_PAD_RIGHT);

                //Descrizione cittadinanza conducente veicolo
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($nazionalita_altro_conducente ?: '', 30, '  ', STR_PAD_RIGHT);

            }

            // ===== RIMORCHI 2020 (42 caratteri) 1883-1924=====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Tipo rimorchio (4 caratteri)
                $tipo_rimorchio = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_tipo_rimorchio");
                $tipo_rimorchio =  mb_str_pad($tipo_rimorchio ?: '', 4, '  ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = $tipo_rimorchio;
                
                // Targa rimorchio (10 caratteri)
                $targa_rimorchio = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_targa_rimorchio");
                $targa_rimorchio = mb_str_pad($targa_rimorchio ?: '', 10, '  ', STR_PAD_RIGHT);
                $indTXT++;
                $esitoTXT[$indTXT] = $targa_rimorchio;

            }
            
            // ===== CODICE STRADA ACI (15 caratteri) - Posizioni 1925-1939 =====
            $codice_aci = $this->safe_meta_string($post_id, 'codice_strada_aci');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($codice_aci ?: '', 15, '  ', STR_PAD_LEFT);
            
            // ===== VALIDAZIONE E COMPLETAMENTO RECORD =====
             // Conversione finale: sostituisce ~ con spazi e unisce tutti i campi
            // $esitoTXTstr = str_replace('~', ' ', implode('', $esitoTXT));
            $esitoTXTstr = implode('', $esitoTXT);

            // Validazione lunghezze usando l'array di controllo
            $cfgistat_lunghezze = $this->get_istat_field_lengths();
            $numErrori = 0;
            $strErrori = '';

            for ($indChk = 0; $indChk < count($esitoTXT); $indChk++) {
                $lungReale = mb_strlen($esitoTXT[$indChk]);
                $lungAttesa = isset($cfgistat_lunghezze[$indChk]) ? $cfgistat_lunghezze[$indChk] : 0;
                
                if ($lungReale != $lungAttesa && $lungAttesa > 0) {
                    $numErrori++;
                    $strErrori .= "Incidente ID {$post_id} - Campo " . ($indChk + 1) . ": Trovati {$lungReale} caratteri ({$esitoTXT[$indChk]}) invece di {$lungAttesa}\n";
                }
            }       
            if ($numErrori > 0) {
                error_log("Errori validazione ISTAT per incidente {$post_id}:\n" . $strErrori);
            }
            
            // Assicura che il record sia esattamente 1939 caratteri
            $esitoTXTstr = mb_str_pad($esitoTXTstr, 1939, ' ', STR_PAD_RIGHT);
            
            // Aggiungi il record al file finale
            $output .= $esitoTXTstr . "\r\n";
            $index++;
        }
        
        return $output;
    }
    
    /**
     * Genera XLSX per Excel (formato Polizia Stradale)
     */
    public function generate_excel_xlsx($incidenti, $filepath) {
        // Header Excel secondo la tua lista specifica
        $headers = array(
            'IDIncidenti',
            'Data',
            'Ora',
            'IDProv',
            'IDCom',
            'IDTipoIncidente',
            'IDTipoStrada',
            'CentroAbitato',
            'IDPolizia',
            'IDCaratteristiche',
            'CantiereStradale',
            'N_Autovettura',
            'N_Autocarro fino 3,5t',
            'N_Autocarro > 3,5t',
            'N_Autotreno',
            'N_Autoarticolato',
            'N_Autobus',
            'N_Tram',
            'N_Treno',
            'N_Motociclo',
            'N_Ciclomotore',
            'N_Velocipede',
            'N_Bicicletta a pedala assistita',
            'N_Monopattini elettrici',
            'N_Altri dispositivi micromobilita\'',
            'N_AltrIVeicoli',
            'Trasportanti merci pericolose',
            'N_Pedoni',
            'N_Deceduti',
            'N_Feriti',
            'Numero Nome Strada',
            'KM',
            'Metri',
            'Carreggiata',
            'Omissione',
            'Contromano',
            'DettaglioPersone',
            'idPositivita',
            'ArtCds',
            'Coordinata X',
            'Coordinata Y'
        );
        
        // Prepara i dati per Excel

        // Prima riga: Titolo in grassetto con bordi, unita su 41 colonne
        $titolo_text = 'Esportazione Incidenti per Ministero Interni - Periodo: Anno in corso';
        $titolo_styled = '<style font-weight="bold" border="thin" text-align="left">' . $titolo_text . '</style>';

        // Riga vuota
        $empty_row = array('');

        // Terza riga: Headers in grassetto con bordi
        $headers_styled = array();
        foreach ($headers as $header) {
            $headers_styled[] = '<style font-weight="bold" border="thin">' . $header . '</style>';
        }

        $data = array(
            array($titolo_styled),  // Prima riga: titolo (verrà unita dopo)
            $empty_row,              // Seconda riga: vuota
            $headers_styled          // Terza riga: intestazioni
        );

        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            
            $row = array();
            
            // Popola ogni campo (stesso ordine degli header)
            $row[] = $post_id;
            $row[] = $this->safe_meta_string($post_id, 'data_incidente');
            //$row[] = $this->safe_meta_string($post_id, 'ora_incidente');
            $row[] = $this->format_time(
                $this->safe_meta_string($post_id, 'ora_incidente'),
                $this->safe_meta_string($post_id, 'minuti_incidente')
            );
            $row[] = "075";
            //$row[] = $this->get_comune_name($this->safe_meta_string($post_id, 'comune_incidente'));
            $row[] = $this->get_codice_catastale($this->safe_meta_string($post_id, 'comune_incidente'));
            //$row[] = $this->get_natura_incidente_name($this->safe_meta_string($post_id, 'xlsx_tipo_incidente') ?: 0);
            $row[] = $this->get_tipo_incidente_code($this->safe_meta_string($post_id, 'xlsx_tipo_incidente'));
            //$row[] = $this->get_tipo_strada_name($this->safe_meta_string($post_id, 'tipo_strada') ?: 0);
            $row[] = $this->get_tipo_strada_code($this->safe_meta_string($post_id, 'tipo_strada'));
            $row[] = $this->safe_meta_string($post_id, 'xlsx_centro_abitato') ?: 0;
            //$row[] = $this->get_organo_rilevazione_name($this->safe_meta_string($post_id, 'organo_rilevazione'));
            $row[] = "2";
            //$row[] = $this->get_caratteristiche_name($this->safe_meta_string($post_id, 'xlsx_caratteristiche') ?: 0);
            $row[] = str_pad($this->safe_meta_string($post_id, 'xlsx_caratteristiche') ?: 0, 2, '0', STR_PAD_LEFT);
            $cantiere_value = $this->safe_meta_string($post_id, 'xlsx_cantiere_stradale');
            $row[] = ($cantiere_value === 'S' || $cantiere_value === 'N') ? $cantiere_value : 'N';
            
            // Veicoli coinvolti
            /* $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autovettura');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autocarro_35t');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autocarro_oltre_35t');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autotreno');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autoarticolato');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autobus');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_tram');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_treno');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_motociclo');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_ciclomotore');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_velocipede');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_bicicletta_assistita');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_monopattini');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_altri_micromobilita');
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_altri_veicoli'); */
            // Conta automaticamente i veicoli per tipo dai dati dei veicoli A/B/C
            $conteggi_veicoli = $this->conta_veicoli_per_tipo($post_id);
            $row[] = $conteggi_veicoli['autovetture'];
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autocarro_35t') ?: 0; // Questo rimane manuale
            $row[] = $this->safe_meta_string($post_id, 'xlsx_n_autocarro_oltre_35t') ?: 0; // Questo rimane manuale  
            $row[] = $conteggi_veicoli['autotreni'];
            $row[] = $conteggi_veicoli['autoarticolati'];
            $row[] = $conteggi_veicoli['autobus'];
            $row[] = $conteggi_veicoli['tram'];
            $row[] = $conteggi_veicoli['treni'];
            $row[] = $conteggi_veicoli['motocicli'];
            $row[] = $conteggi_veicoli['ciclomotori'];
            $row[] = $conteggi_veicoli['velocipedi'];
            $row[] = $conteggi_veicoli['biciclette_assistite'];
            $row[] = $conteggi_veicoli['monopattini'];
            $row[] = $conteggi_veicoli['altri_micromobilita'];
            $row[] = $conteggi_veicoli['altri_veicoli'];
            $row[] = $this->safe_meta_string($post_id, 'xlsx_trasportanti_merci_pericolose') ?: 0;
            // Conteggi persone
            $val1_pedoni_feriti = (int) $this->safe_meta_string($post_id, 'numero_pedoni_feriti');
            $val2_pedoni_morti = (int) $this->safe_meta_string($post_id, 'numero_pedoni_morti');
            $somma_pedoni = $val1_pedoni_feriti + $val2_pedoni_morti;
            $row[] = $somma_pedoni;
            $val1 = (int) $this->safe_meta_string($post_id, 'riepilogo_morti_2_30gg');
            $val2 = (int) $this->safe_meta_string($post_id, 'riepilogo_morti_24h');
            $somma_morti = $val1 + $val2;
            $row[] = $somma_morti;
            $row[] = $this->safe_meta_string($post_id, 'riepilogo_feriti');
            
            // Informazioni strada
            $row[] = $this->safe_meta_string($post_id, 'denominazione_strada') ?: 0;
            $km_value = $this->safe_meta_string($post_id, 'progressiva_km');
            $row[] = (empty($km_value) || $km_value == '0') ? ' ' : $km_value;
            $metri_value = $this->safe_meta_string($post_id, 'progressiva_m');
            $row[] = (empty($metri_value) || $metri_value == '0') ? ' ' : $metri_value;
            //$row[] = $this->get_geometria_strada_name($this->safe_meta_string($post_id, 'geometria_strada') ?: "Non specificato");
            $row[] = $this->safe_meta_string($post_id, 'geometria_strada') ?: 0;
            
            // Circostanze
            $row[] = $this->safe_meta_string($post_id, 'xlsx_omissione') ?: 0;
            $row[] = $this->safe_meta_string($post_id, 'xlsx_contromano') ?: 0;
            $dettaglio_value = $this->safe_meta_string($post_id, 'xlsx_dettaglio_persone_decedute');
            $row[] = (empty($dettaglio_value) || $dettaglio_value == '0') ? ' ' : $dettaglio_value;
            $positivita_text = $this->safe_meta_string($post_id, 'xlsx_positivita');
            $positivita_map = array(
                'Entrambi' => '4',
                'Droga' => '3',
                'Alcol' => '2',
                'Negativo' => '1'
            );
            $row[] = isset($positivita_map[$positivita_text]) ? $positivita_map[$positivita_text] : '';
            $row[] = $this->safe_meta_string($post_id, 'xlsx_art_cds') ?: 0;
            
            // Coordinate
            $row[] = $this->safe_meta_string($post_id, 'latitudine');
            $row[] = $this->safe_meta_string($post_id, 'longitudine');
            
            // Applica bordi a tutte le celle dati
            $row_styled = array();
            foreach ($row as $cell) {
                $row_styled[] = '<style border="thin">' . $cell . '</style>';
            }
            
            $data[] = $row_styled;
        }
        
        // Crea directory se non esistente
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/incidenti-exports';
        if (!is_dir($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        // Genera il file Excel
        try {
            $xlsx = SimpleXLSXGen::fromArray($data);
            
            // Unisci le celle A1:AO1 (41 colonne) per il titolo
            $xlsx->mergeCells('A1:AO1');
            
            // Imposta larghezza uniforme per tutte le 41 colonne (15 è una larghezza standard)
            $num_cols = 41;
            $col_width = 15; // Larghezza in unità Excel (puoi modificare questo valore)
            
            for ($col = 0; $col < $num_cols; $col++) {
                $col_letter = '';
                if ($col < 26) {
                    $col_letter = chr(65 + $col); // A-Z
                } else {
                    $col_letter = 'A' . chr(65 + ($col - 26)); // AA-AO
                }
                $xlsx->setColWidth($col_letter, $col_width);
            }
            
            $xlsx->saveAs($filepath);
            return true;
        } catch (Exception $e) {
            error_log('Errore generazione Excel: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Converte ora e minuti in formato HH:MM
     */
    private function format_time($ora, $minuti = '') {
        if (empty($ora)) {
            return '';
        }
        
        // Assicura che l'ora sia di 2 cifre
        $ora_formatted = str_pad($ora, 2, '0', STR_PAD_LEFT);
        
        // Se i minuti sono vuoti, usa 00
        if (empty($minuti)) {
            $minuti = '00';
        } else {
            // Assicura che i minuti siano di 2 cifre
            $minuti = str_pad($minuti, 2, '0', STR_PAD_LEFT);
        }
        
        return $ora_formatted . ':' . $minuti;
    }
    
    /**
     * Array delle lunghezze standard per ogni campo del tracciato ISTAT
     * Basato sul file rilevazioni.php esistente
     */
    private function get_istat_field_lengths() {
        return array(
            // Dati base identificativi
            2, // Data dell'incidente:anno
            2, // Data dell'incidente:mese
            3, // Provincia 
            3, // Comune
            4, // Numero d'ordine
            2, // Data dell'incidente: giorno
            2, // Spazi
            1, // Organo di rilevazione
            5, // Spazi
            1, // Organo coordinatore
            1, // Localizzazione dell'incidente
            3, // Denominazione strada
            1, // Illuminazione
            2, // spazi
            2, // Tronco di strada statale o di autostrada
            1, // Tipo di strada
            1, // Pavimentazione
            2, // Intersezione o non intersezione
            1, // Fondo stradale
            1, // Segnaletica
            1, // Condizioni meteorologiche
            2, // Natura dell'incidente
            2, // Tipo di veicolo coinvolto: A
            2, // Tipo di veicolo coinvolto: B
            2, // Tipo di veicolo coinvolto: C
            4, // Spazi
            4, // Spazi
            4, // Spazi
            4, // Peso totale a pieno carico del veicolo A
            4, // Peso totale a pieno carico del veicolo B
            4, // Peso totale a pieno carico del veicolo C
            2, // Circostanza relativa al veicolo A:                           per inconvenienti di circolazione
            2, // Circostanza relativa al veicolo A:                           per difetti o avarie del veicolo
            2, // Circostanza relativa al conducente del veicolo A: per anormale stato psicofisico 
            2, // Circostanza relativa al veicolo B oppure al pedone od all'ostacolo:                                                           per inconvenienti di circolazione
            2, // Circostanza relativa al veicolo B:                             per difetti o avarie del veicolo
            2, // Circostanza relativa al conducente del veicolo B oppure al pedone:                                                 per anormale stato psicofisico
            8, // Identificazione: targa del veicolo A
            3, // Identificazione: sigla del veicolo A
            2, // Anno di immatricolazione del veicolo A
            2, // Spazi
            3, // Spazi
            8, // Identificazione: targa del veicolo B
            3, // Identificazione: sigla del veicolo B
            2, // Anno di immatricolazione del veicolo B
            2, // Spazi
            3, // Spazi
            8, // Identificazione: targa del veicolo C
            3, // Identificazione: sigla del veicolo C
            2, // Anno di immatricolazione del veicolo C
            2, // Spazi
            3, // Spazi
            2, // Età
            1, // Sesso
            1, // Esito
            1, // Tipo di patente
            2, // Anno di primo rilascio della patente
            1, // Conducente durante lo svolgimento di attività lavorativa o in itinere
            1, // Spazi
            1, // Spazi
            1, // Spazi
            1, // Esito del passeggero infortunato sul sedile anteriore 
            2, // Età del passeggero infortunato sul sedile anteriore 
            1, // Sesso del passeggero infortunato sul sedile anteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            2, // Maschi morti
            2, // Femmine morte
            2, // Maschi feriti
            2, // Femmine ferite
            2, // Età
            1, // Sesso
            1, // Esito
            1, // Tipo di patente
            2, // Anno di primo rilascio della patente
            1, // Conducente durante lo svolgimento di attività lavorativa o in itinere
            1, // Spazi
            1, // Spazi
            1, // Spazi
            1, // Esito del passeggero infortunato sul sedile anteriore 
            2, // Età del passeggero infortunato sul sedile anteriore 
            1, // Sesso del passeggero infortunato sul sedile anteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore 
            2, // Maschi morti
            2, // Femmine morte
            2, // Maschi feriti
            2, // Femmine ferite
            2, // Età
            1, // Sesso
            1, // Esito
            1, // Tipo di patente
            2, // Anno di primo rilascio della patente
            1, // Conducente durante lo svolgimento di attività lavorativa o in itinere
            1, // Spazi
            1, // Spazi
            1, // Spazi
            1, // Esito del passeggero infortunato sul sedile anteriore 
            2, // Età del passeggero infortunato sul sedile anteriore 
            1, // Sesso del passeggero infortunato sul sedile anteriore 
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore
            1, // Esito del passeggero infortunato sul sedile posteriore 
            2, // Età del passeggero infortunato sul sedile posteriore 
            1, // Sesso del passeggero infortunato sul sedile posteriore
            2, // Maschi morti
            2, // Femmine morte
            2, // Maschi feriti
            2, // Femmine ferite
            1, // Sesso del 1° pedone morto
            2, // Età del 1° pedone morto
            1, // Sesso del 1° pedone ferito
            2, // Età del 1° pedone ferito
            1, // Sesso del 2° pedone morto
            2, // Età del 2° pedone morto
            1, // Sesso del 2° pedone ferito
            2, // Età del 2° pedone ferito
            1, // Sesso del 3° pedone morto
            2, // Età del 3° pedone morto
            1, // Sesso del 3° pedone ferito
            2, // Età del 3° pedone ferito
            1, // Sesso del 4° pedone morto
            2, // Età del 4° pedone morto
            1, // Sesso del 4° pedone ferito
            2, // Età del 4° pedone ferito
            2, // Numero degli eventuali altri veicoli coinvolti nell'incidente oltre ai primi tre veicoli
            2, // Numero di morti di sesso maschile su eventuali altri veicoli
            2, // Numero di morti di sesso femminile su eventuali altri veicoli
            2, // Numero di feriti di sesso maschile su eventuali altri veicoli
            2, // Numero di feriti di sesso femminile su eventuali altri veicoli
            2, // Totale morti entro le prime 24 ore dall'incidente
            2, // Totale morti dal 2° al 30° giorno dall'incidente
            2, // Totale feriti
            9, // Spazi
            57, // Nome della strada
            100, // Spazi 
            30, // Nome del 1° morto
            30, // Cognome del 1° morto
            30, // Nome del 2° morto
            30, // Cognome del 2° morto
            30, // Nome del 3° morto
            30, // Cognome del 3° morto
            30, // Nome del 4° morto
            30, // Cognome del 4° morto
            30, // Nome del 1° ferito
            30, // Cognome del 1° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 1° ferito
            30, // Nome del 2° ferito
            30, // Cognome del 2° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 2° ferito
            30, // Nome del 3° ferito
            30, // Cognome del 3° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 3° ferito
            30, // Nome del 4° ferito
            30, // Cognome del 4° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 4° ferito
            30, // Nome del 5° ferito
            30, // Cognome del 5° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 5° ferito
            30, // Nome del 6° ferito
            30, // Cognome del 6° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 6° ferito
            30, // Nome del 7° ferito
            30, // Cognome del 7° ferito
            30, // Ospedale dove è stato ricoverato o medicato il 7° ferito
            30, // Nome dell' 8° ferito
            30, // Cognome dell' 8° ferito
            30, // Ospedale dove è stato ricoverato o medicato l' 8° ferito
            10, // Spazio riservato ISTAT per elaborazione 
            1, // Tipo di coordinata
            1, // Sistema di proiezione
            50, // X o Longitudine
            50, // Y o Latitudine
            8, // Spazio riservato ISTAT per elaborazione 
            2, // Ora
            2, // Minuti
            30, // Codice identificativo Carabinieri
            4, // Progressiva chilometrica
            3, // Ettometrica
            5, // Cilindrata del veicolo A
            5, // Cilindrata del veicolo B
            5, // Cilindrata del veicolo C
            4, // Spazio riservato ISTAT per elaborazione 
            100, // Altra strada
            40, // Località
            40, // Codice Identificativo Ente  
            10, // Spazio riservato ISTAT per elaborazione 
            1, // Cittadinanza italiana o straniera del conducente veicolo A
            3, // Codice cittadinanza del conducente veicolo A
            30, // Descrizione cittadinanza conducente veicolo A
            1, // Cittadinanza italiana o straniera del conducente veicolo B
            3, // Codice Cittadinanza del conducente veicolo B
            30, // Descrizione cittadinanza conducente veicolo B
            1, // Cittadinanza italiana o straniera del conducente veicolo C
            3, // Codice Cittadinanza del conducente veicolo C
            30, // Descrizione cittadinanza conducente veicolo C
            4, // Tipo rimorchio A
            10, // Targa rimorchio A
            4, // Tipo rimorchio B
            10, // Targa rimorchio B
            4, // Tipo rimorchio C
            10, // Targa rimorchio C
            15, // Codice strada ACI
        );
    }
    
    /**
     * Carica la configurazione ISTAT
     */
    private function load_istat_config() {
        $config_file = plugin_dir_path(dirname(__FILE__)) . 'data/tracciato-istat.json';
        
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            
            // Se il JSON è valido, restituisci la configurazione
            if ($config !== null) {
                return array_merge($this->get_default_istat_config(), $config);
            }
        }
        
        // Configurazione di fallback se il file non esiste o è corrotto
        return $this->get_default_istat_config();
    }
    
    /**
     * Configurazione ISTAT di default
     */
    private function get_default_istat_config() {
        return array(
            'lunghezza_record' => 1939,
            'versione' => '2025',
            'tipo_veicolo' => array(
                '01' => '01', 'autovettura_privata' => '01',
                '02' => '02', 'autovettura_rimorchio' => '02',
                '03' => '03', 'autovettura_pubblica' => '03',
                '04' => '04', 'autovettura_soccorso' => '04',
                '05' => '05', 'autobus_urbano' => '05',
                '06' => '06', 'autobus_extraurbano' => '06',
                '07' => '07', 'tram' => '07',
                '08' => '08', 'autocarro' => '08',
                '09' => '09', 'autotreno' => '09',
                '10' => '10', 'autoarticolato' => '10',
                '11' => '11', 'veicoli_speciali' => '11',
                '12' => '12', 'trattore_stradale' => '12',
                '13' => '13', 'macchina_agricola' => '13',
                '14' => '14', 'velocipede' => '14',
                '15' => '15', 'ciclomotore' => '15',
                '16' => '16', 'motociclo_solo' => '16',
                '17' => '17', 'motociclo_passeggero' => '17',
                '18' => '18', 'motocarro' => '18',
                '19' => '19', 'veicolo_trazione_animale' => '19',
                '20' => '20', 'veicolo_fuga' => '20',
                '21' => '21', 'quadriciclo' => '21'
            ),
            'sesso' => array(
                'M' => '1', 'maschio' => '1', '1' => '1',
                'F' => '2', 'femmina' => '2', '2' => '2'
            ),
            'esito_persona' => array(
                'incolume' => '1', '1' => '1',
                'ferito' => '2', '2' => '2',
                'morto_24h' => '3', '3' => '3',
                'morto_30gg' => '4', '4' => '4'
            ),
            'casco_conducente' => array(
                'si' => 'C', 'no' => 'D'
            ),
            'cintura_conducente' => array(
                'si' => 'A', 'no' => 'B'
            ),
            'casco_passeggero' => array(
                'si' => 'G', 'no' => 'H'
            ),
            'cintura_passeggero' => array(
                'si' => 'E', 'no' => 'F'
            ),
            'organo_rilevazione' => array(
                '1' => 'Agente di Polizia Stradale',
                '2' => 'Carabiniere',
                '3' => 'Agente di Pubblica Sicurezza',
                '4' => 'Agente di Polizia Municipale o Locale',
                '5' => 'Altri',
                '6' => 'Agente di Polizia Provinciale'
            )
        );
    }
    
    /**
     * Log delle esportazioni
     */
    private function log_export($tipo, $num_record, $filename, $errori = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'tipo_export' => $tipo,
            'num_record' => $num_record,
            'filename' => $filename,
            'errori' => $errori,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        // Salva nel log delle esportazioni
        $logs = get_option('incidenti_export_logs', array());
        $logs[] = $log_entry;
        
        // Mantieni solo gli ultimi 100 log
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('incidenti_export_logs', $logs);
        
        // Log su file se abilitato
        if (defined('INCIDENTI_DEBUG') && INCIDENTI_DEBUG) {
            error_log("Export ISTAT: {$num_record} record salvati in {$filename}");
            if (!empty($errori)) {
                error_log("Errori rilevati: " . implode('; ', $errori));
            }
        }
        
        // Aggiorna statistiche
        $this->update_export_stats($tipo, $num_record);
    }
    
    /**
     * Aggiorna statistiche export
     */
    private function update_export_stats($tipo, $num_record) {
        $stats = get_option('incidenti_export_stats', array(
            'ISTAT_TXT' => array('count' => 0, 'records' => 0, 'last_export' => null),
            'EXCEL_CSV' => array('count' => 0, 'records' => 0, 'last_export' => null)
        ));
        
        $stats[$tipo]['count']++;
        $stats[$tipo]['records'] += $num_record;
        $stats[$tipo]['last_export'] = current_time('mysql');
        
        update_option('incidenti_export_stats', $stats);
    }
    
    /**
     * Mostra log delle esportazioni
     */
    private function show_export_logs() {
        $logs = get_option('incidenti_export_logs', array());
        $logs = array_reverse(array_slice($logs, -10)); // Ultimi 10
        
        if (empty($logs)) {
            echo '<p>' . __('Nessuna esportazione effettuata.', 'incidenti-stradali') . '</p>';
            return;
        }
        
        echo '<div class="export-logs">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Data/Ora', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Utente', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Tipo', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Record', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('File', 'incidenti-stradali') . '</th>';
        //echo '<th>' . __('Errori', 'incidenti-stradali') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($logs as $log) {
            $user = get_user_by('id', $log['user_id']);
            $user_name = $user ? $user->display_name : __('Utente sconosciuto', 'incidenti-stradali');
            
            echo '<tr>';
            echo '<td>' . esc_html(mysql2date('d/m/Y H:i', $log['timestamp'])) . '</td>';
            echo '<td>' . esc_html($user_name) . '</td>';
            echo '<td><span class="export-type ' . strtolower($log['tipo_export']) . '">' . esc_html($log['tipo_export']) . '</span></td>';
            echo '<td><strong>' . esc_html($log['num_record']) . '</strong></td>';
            echo '<td><code>' . esc_html($log['filename']) . '</code></td>';
            /*echo '<td>';
            if (empty($log['errori'])) {
                echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('Nessuno', 'incidenti-stradali');
            } else {
                echo '<span class="dashicons dashicons-warning" style="color: orange;"></span> ' . count($log['errori']) . ' ' . __('errori', 'incidenti-stradali');
            }
            echo '</td>';
            */
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Statistiche generali
        $stats = get_option('incidenti_export_stats', array());
        if (!empty($stats)) {
            echo '<div class="export-stats" style="margin-top: 20px;">';
            echo '<h4>' . __('Statistiche Generali', 'incidenti-stradali') . '</h4>';
            echo '<div class="stats-grid" style="display: flex; gap: 20px;">';
            
            foreach ($stats as $tipo => $stat) {
                echo '<div class="stat-card" style="flex: 1; padding: 15px; background: #f9f9f9; border-radius: 5px;">';
                echo '<h5>' . esc_html($tipo) . '</h5>';
                echo '<p><strong>' . __('Esportazioni:', 'incidenti-stradali') . '</strong> ' . $stat['count'] . '</p>';
                echo '<p><strong>' . __('Record totali:', 'incidenti-stradali') . '</strong> ' . $stat['records'] . '</p>';
                if ($stat['last_export']) {
                    echo '<p><strong>' . __('Ultima esportazione:', 'incidenti-stradali') . '</strong><br/>' . mysql2date('d/m/Y H:i', $stat['last_export']) . '</p>';
                }
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // CSS per migliorare l'aspetto
        echo '<style>
        .export-type.istat_txt { background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .export-type.excel_csv { background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .export-logs code { background: #f1f1f1; padding: 2px 4px; font-size: 11px; }
        .stats-grid .stat-card h5 { margin-top: 0; margin-bottom: 10px; color: #1d2327; }
        .stats-grid .stat-card p { margin: 5px 0; font-size: 13px; }
        </style>';
    }
    
    /**
     * Comuni disponibili (esempio per provincia di Lecce)
     * Personalizza questo metodo con i tuoi comuni
     */
    private function get_comuni_disponibili() {
        // Puoi caricare da database o da un file
        return array(
            '002' => 'Alessano', 
            '003' => 'Alezio',
            '004' => 'Alliste',
            '005' => 'Andrano',
            '006' => 'Aradeo',
            '007' => 'Arnesano',
            '008' => 'Bagnolo Del Salento',
            '009' => 'Botrugno',
            '010' => 'Calimera',
            '011' => 'Campi Salentina',
            '012' => 'Cannole',
            '013' => 'Caprarica di Lecce',
            '014' => 'Carmiano',
            '015' => 'Carpignano Salentino',
            '016' => 'Casarano',
            '017' => 'Castri Di Lecce',
            '018' => 'Castrignano De` Greci',
            '019' => 'Castrignano Del Capo',
            '096' => 'Castro',
            '020' => 'Cavallino',
            '021' => 'Collepasso',
            '022' => 'Copertino',
            '023' => 'Corigliano D`Otranto',
            '024' => 'Corsano',
            '025' => 'Cursi',
            '026' => 'Cutrofiano',
            '027' => 'Diso',
            '028' => 'Gagliano Del Capo',
            '029' => 'Galatina',
            '030' => 'Galatone',
            '031' => 'Gallipoli',
            '032' => 'Giuggianello',
            '033' => 'Giurdignano',
            '034' => 'Guagnano',
            '035' => 'Lecce',
            '036' => 'Lequile',
            '037' => 'Leverano',
            '038' => 'Lizzanello',
            '039' => 'Maglie',
            '040' => 'Martano',
            '041' => 'Martignano',
            '042' => 'Matino',
            '043' => 'Melendugno',
            '044' => 'Melissano',
            '045' => 'Melpignano',
            '046' => 'Miggiano',
            '047' => 'Minervino Di Lecce',
            '048' => 'Monteroni Di Lecce',
            '049' => 'Montesano Salentino',
            '050' => 'Morciano Di Leuca',
            '051' => 'Muro Leccese',
            '052' => 'Nardo`',
            '053' => 'Neviano',
            '054' => 'Nociglia',
            '055' => 'Novoli',
            '056' => 'Ortelle',
            '057' => 'Otranto',
            '058' => 'Palmariggi',
            '059' => 'Parabita',
            '060' => 'Patu`',
            '061' => 'Poggiardo',
            '097' => 'Porto Cesareo',
            '098' => 'Presicce-Acquarica',
            '063' => 'Racale',
            '064' => 'Ruffano',
            '065' => 'Salice Salentino',
            '066' => 'Salve',
            '095' => 'San Cassiano',
            '068' => 'San Cesario Di Lecce',
            '069' => 'San Donato Di Lecce',
            '071' => 'San Pietro In Lama',
            '067' => 'Sanarica',
            '070' => 'Sannicola',
            '072' => 'Santa Cesarea Terme',
            '073' => 'Scorrano',
            '074' => 'Secli`',
            '075' => 'Sogliano Cavour',
            '076' => 'Soleto',
            '077' => 'Specchia',
            '078' => 'Spongano',
            '079' => 'Squinzano',
            '080' => 'Sternatia',
            '081' => 'Supersano',
            '082' => 'Surano',
            '083' => 'Surbo',
            '084' => 'Taurisano',
            '085' => 'Taviano',
            '086' => 'Tiggiano',
            '087' => 'Trepuzzi',
            '088' => 'Tricase',
            '089' => 'Tuglie',
            '090' => 'Ugento',
            '091' => 'Uggiano La Chiesa',
            '092' => 'Veglie',
            '093' => 'Vernole',
            '094' => 'Zollino'
        );
    }
    
    /**
     * Metodi di utilità per la gestione
     */
    
    /**
     * Verifica integrità del sistema di export
     */
    public function verify_export_system() {
        $issues = array();
        
        // Verifica permessi directory
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/incidenti-exports';
        
        if (!is_dir($export_dir)) {
            if (!wp_mkdir_p($export_dir)) {
                $issues[] = __('Impossibile creare la directory di export', 'incidenti-stradali');
            }
        }
        
        if (!is_writable($export_dir)) {
            $issues[] = __('Directory di export non scrivibile', 'incidenti-stradali');
        }
        
        // Verifica configurazione PHP
        if (ini_get('max_execution_time') < 300) {
            $issues[] = __('Tempo di esecuzione PHP potrebbe essere insufficiente per export grandi', 'incidenti-stradali');
        }
        
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->return_bytes($memory_limit);
        if ($memory_bytes < 268435456) { // 256MB
            $issues[] = __('Memoria PHP potrebbe essere insufficiente per export grandi', 'incidenti-stradali');
        }
        
        // Verifica file di configurazione
        $config_file = plugin_dir_path(dirname(__FILE__)) . 'data/tracciato-istat.json';
        if (!file_exists($config_file)) {
            $issues[] = __('File di configurazione ISTAT mancante', 'incidenti-stradali');
        }
        
        return $issues;
    }
    
    /**
     * Converte una stringa memory_limit in bytes
     */
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        switch($last) {
            case 'g':
                $val *= 1024;
            case m:
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
    
    /**
     * Cleanup dei file di export vecchi
     */
    public function cleanup_old_exports($days = 30) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/incidenti-exports';
        
        if (!is_dir($export_dir)) {
            return false;
        }
        
        $files = glob($export_dir . '/*.{txt,csv}', GLOB_BRACE);
        $deleted = 0;
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    /**
 * Carica dati province da JSON
 */
private function get_province_data() {
    static $province_data = null;
    
    if ($province_data === null) {
        $province_file = plugin_dir_path(__FILE__) . '../data/codici-istat-province.json';
        if (file_exists($province_file)) {
            $province_data = json_decode(file_get_contents($province_file), true);
        } else {
            $province_data = array();
        }
    }
    
    return $province_data;
}

    /**
     * Converte codice comune in nome esteso
     */
    private function get_comune_name($codice_comune) {
        if (empty($codice_comune)) {
            return $codice_comune;
        }
        
        $comuni_file = plugin_dir_path(__FILE__) . '../data/codici-istat-comuni.json';
        
        if (!file_exists($comuni_file)) {
            return $codice_comune;
        }
        
        $comuni_data = json_decode(file_get_contents($comuni_file), true);
        
        if (!$comuni_data || !isset($comuni_data['comuni'])) {
            return $codice_comune;
        }
        
        // Cerca il comune nelle varie province
        foreach ($comuni_data['comuni'] as $provincia_codice => $comuni_provincia) {
            if (isset($comuni_provincia[$codice_comune])) {
                return $comuni_provincia[$codice_comune];
            }
        }
        
        return $codice_comune;
    }

    /**
     * Converte codice ISTAT comune in codice catastale
     */
    private function get_codice_catastale($codice_comune) {
        if (empty($codice_comune)) {
            return $codice_comune;
        }
        
        // Mappatura codici ISTAT → Codici Catastali (Provincia Lecce)
        $mapping_catastale = array(
            '001' => 'A042', // ACQUARICA
            '002' => 'A184', // ALESSANO
            '003' => 'A185', // ALEZIO
            '004' => 'A208', // ALLISTE
            '005' => 'A281', // ANDRANO
            '006' => 'A350', // ARADEO
            '007' => 'A425', // ARNESANO
            '008' => 'A572', // BAGNOLO DEL SALENTO
            '009' => 'B086', // BOTRUGNO
            '010' => 'B413', // CALIMERA
            '011' => 'B506', // CAMPI SALENTINA
            '012' => 'B616', // CANNOLE
            '013' => 'B690', // CAPRARICA DI LECCE
            '014' => 'B792', // CARMIANO
            '015' => 'B822', // CARPIGNANO SALENTINO
            '016' => 'B936', // CASARANO
            '017' => 'C334', // CASTRI DI LECCE
            '018' => 'C335', // CASTRIGNANO DE' GRECI
            '019' => 'C336', // CASTRIGNANO DEL CAPO
            '096' => 'M261', // CASTRO
            '020' => 'C377', // CAVALLINO
            '021' => 'C865', // COLLEPASSO
            '022' => 'C978', // COPERTINO
            '023' => 'D006', // CORIGLIANO D'OTRANTO
            '024' => 'D044', // CORSANO
            '025' => 'D223', // CURSI
            '026' => 'D237', // CUTROFIANO
            '027' => 'D305', // DISO
            '028' => 'D851', // GAGLIANO DEL CAPO
            '029' => 'D862', // GALATINA
            '030' => 'D863', // GALATONE
            '031' => 'D883', // GALLIPOLI
            '032' => 'E053', // GIUGGIANELLO
            '033' => 'E061', // GIURDIGNANO
            '034' => 'E227', // GUAGNANO
            '035' => 'E506', // LECCE
            '036' => 'E538', // LEQUILE
            '037' => 'E563', // LEVERANO
            '038' => 'E629', // LIZZANELLO
            '039' => 'E815', // MAGLIE
            '040' => 'E979', // MARTANO
            '041' => 'E984', // MARTIGNANO
            '042' => 'F054', // MATINO
            '043' => 'F101', // MELENDUGNO
            '044' => 'F109', // MELISSANO
            '045' => 'F117', // MELPIGNANO
            '046' => 'F194', // MIGGIANO
            '047' => 'F221', // MINERVINO DI LECCE
            '048' => 'F604', // MONTERONI DI LECCE
            '049' => 'F623', // MONTESANO SALENTINO
            '050' => 'F716', // MORCIANO DI LEUCA
            '051' => 'F816', // MURO LECCESE
            '052' => 'F842', // NARDO'
            '053' => 'F881', // NEVIANO
            '054' => 'F916', // NOCIGLIA
            '055' => 'F970', // NOVOLI
            '056' => 'G136', // ORTELLE
            '057' => 'G188', // OTRANTO
            '058' => 'G285', // PALMARIGGI
            '059' => 'G325', // PARABITA
            '060' => 'G378', // PATU'
            '061' => 'G751', // POGGIARDO
            '062' => 'H047', // PRESICCE
            '097' => 'M263', // PORTO CESAREO
            '098' => 'M428', // PRESICCE-ACQUARICA
            '063' => 'H147', // RACALE
            '064' => 'H632', // RUFFANO
            '065' => 'H708', // SALICE SALENTINO
            '066' => 'H729', // SALVE
            '095' => 'M264', // SAN CASSIANO
            '068' => 'H793', // SAN CESARIO DI LECCE
            '069' => 'H826', // SAN DONATO DI LECCE
            '071' => 'I115', // SAN PIETRO IN LAMA
            '067' => 'H757', // SANARICA
            '070' => 'I059', // SANNICOLA
            '072' => 'I172', // SANTA CESAREA TERME
            '073' => 'I549', // SCORRANO
            '074' => 'I559', // SECLI'
            '075' => 'I780', // SOGLIANO CAVOUR
            '076' => 'I800', // SOLETO
            '077' => 'I887', // SPECCHIA
            '078' => 'I923', // SPONGANO
            '079' => 'I930', // SQUINZANO
            '080' => 'I950', // STERNATIA
            '081' => 'L008', // SUPERSANO
            '082' => 'L010', // SURANO
            '083' => 'L011', // SURBO
            '084' => 'L064', // TAURISANO
            '085' => 'L074', // TAVIANO
            '086' => 'L166', // TIGGIANO
            '087' => 'L383', // TREPUZZI
            '088' => 'L419', // TRICASE
            '089' => 'L462', // TUGLIE
            '090' => 'L484', // UGENTO
            '091' => 'L485', // UGGIANO LA CHIESA
            '092' => 'L711', // VEGLIE
            '093' => 'L776', // VERNOLE
            '094' => 'M187'  // ZOLLINO
        );
        
        return isset($mapping_catastale[$codice_comune]) ? $mapping_catastale[$codice_comune] : $codice_comune;
    }

    /**
     * Converte codice tipo strada in descrizione
     */
    private function get_tipo_strada_name($codice_tipo) {
        $tipi = array(
            '0' => 'Regionale entro l\'abitato',
            '1' => 'Strada urbana',
            '2' => 'Provinciale entro l\'abitato',
            '3' => 'Statale entro l\'abitato',
            '4' => 'Strada comunale extraurbana',
            '5' => 'Strada provinciale fuori dell\'abitato',
            '6' => 'Strada statale fuori dell\'abitato',
            '7' => 'Autostrada',
            '8' => 'Altra strada',
            '9' => 'Strada regionale fuori l\'abitato'
        );
        
        return isset($tipi[$codice_tipo]) ? $tipi[$codice_tipo] : $codice_tipo;
    }

    /**
     * Converte codice organo rilevazione in descrizione
     */
    private function get_organo_rilevazione_name($codice_organo) {
        $organi = array(
            '1' => 'Polizia Stradale',
            '2' => 'Carabinieri',
            '3' => 'Polizia di Stato',
            '4' => 'Polizia Municipale/Locale',
            '5' => 'Altri',
            '6' => 'Polizia Provinciale'
        );
        
        return isset($organi[$codice_organo]) ? $organi[$codice_organo] : $codice_organo;
    }

    /**
     * Converte codice natura incidente in descrizione
     */
    private function get_natura_incidente_name($codice_natura) {
        $nature = array(
            'A' => 'Tra veicoli in marcia',
            'B' => 'Tra veicolo e pedoni',
            'C' => 'Veicolo in marcia che urta veicolo fermo o altro',
            'D' => 'Veicolo in marcia senza urto',
            'E' => 'Altro'
        );
        
        return isset($nature[$codice_natura]) ? $nature[$codice_natura] : $codice_natura;
    }

    /**
     * Converte codice caratteristiche in descrizione
     */
    private function get_caratteristiche_name($codice_caratteristiche) {
        // [Non verificato] - devi verificare se hai un file JSON per questo o usare mappature hardcoded
        $caratteristiche = array(
            '1' => 'Non specificato',
            '2' => 'Incrocio',
            '3' => 'Rotatoria',
            '4' => 'Intersezione segnalata',
            '5' => 'Intersezione con semaforo o vigile',
            '6' => 'Intersezione non segnalata',
            '7' => 'Passaggio a livello',
            '8' => 'Rettilineo',
            '9' => 'Curva',
            '10' => 'Raccordo convesso (dosso)',
            '11' => 'Pendenza pericolosa',
            '12' => 'Galleria illuminata',
            '13' => 'Galleria non illuminata',
            '14' => 'Intersezione con semaf. giallo. lampegg.',
            '15' => 'Passaggio a livello custodito',
            '16' => 'Passaggio a livello non custodito',
            '17' => 'Raccordo concavo (cunetta)',
            '18' => 'Strettoia',
            '19' => 'Pianeggiante',
            '20' => 'Curva a destra',
            '21' => 'Curva a sinistra',
            '22' => 'Salita',
            '23' => 'Discesa',
            '24' => 'Viadotto'
        );
        
        return isset($caratteristiche[$codice_caratteristiche]) ? $caratteristiche[$codice_caratteristiche] : $codice_caratteristiche;
    }

    /**
     * Converte codice carreggiata/geometria in descrizione
     */
    private function get_geometria_strada_name($codice_geometria) {
        $geometrie = array(
            '1' => 'Una carreggiata senso unico',
            '2' => 'Una carreggiata doppio senso',
            '3' => 'Due carreggiate',
            '4' => 'Più di 2 carreggiate'
        );
        
        return isset($geometrie[$codice_geometria]) ? $geometrie[$codice_geometria] : $codice_geometria;
    }

    /**
     * Converte nome tipologia incidente in codice numerico
     */
    private function get_tipo_incidente_code($nome_incidente) {
        $mappatura = array(
            'Altro' => '8',
            'Fuoriuscita/Sbandamento' => '3',
            'Investimento animale' => '6',
            'Investimento pedone' => '7',
            'Scontro frontale' => '1',
            'Scontro laterale' => '2',
            'Tamponamento' => '4',
            'Urto con ostacolo fisso' => '5'
        );
        
        return isset($mappatura[$nome_incidente]) ? $mappatura[$nome_incidente] : '8';
    }

    /**
     * Converte codice/nome tipo strada nel codice corretto
     */
    private function get_tipo_strada_code($valore) {
        // Mappatura nome -> codice
        $mappatura_nomi = array(
            'Altro' => '0',
            'Autostrada' => '1',
            'Provinciale' => '4',
            'Regionale' => '3',
            'Statale' => '2',
            'Strada comunale' => '5'
        );
        
        // Se è un nome, converte in codice
        if (isset($mappatura_nomi[$valore])) {
            return $mappatura_nomi[$valore];
        }
        
        // Mappatura vecchi codici -> nuovi codici
        $mappatura_codici = array(
            '0' => '3', // Regionale entro abitato -> Regionale
            '1' => '5', // Strada urbana -> Strada comunale
            '2' => '4', // Provinciale entro abitato -> Provinciale
            '3' => '2', // Statale entro abitato -> Statale
            '4' => '5', // Strada comunale extraurbana -> Strada comunale
            '5' => '4', // Strada provinciale fuori abitato -> Provinciale
            '6' => '2', // Strada statale fuori abitato -> Statale
            '7' => '1', // Autostrada -> Autostrada
            '8' => '0', // Altra strada -> Altro
            '9' => '3'  // Strada regionale fuori abitato -> Regionale
        );
        
        // Se è un vecchio codice, converte
        if (isset($mappatura_codici[$valore])) {
            return $mappatura_codici[$valore];
        }
        
        // Altrimenti restituisce 0 (Altro)
        return '0';
    }

    /**
     * Conta automaticamente i veicoli per tipo dai dati veicoli A/B/C
     */
    private function conta_veicoli_per_tipo($post_id) {
        $conteggi = array(
            'autovetture' => 0,
            'autotreni' => 0, 
            'autoarticolati' => 0,
            'autobus' => 0,
            'tram' => 0,
            'treni' => 0,
            'motocicli' => 0,
            'ciclomotori' => 0,
            'velocipedi' => 0,
            'biciclette_assistite' => 0,
            'monopattini' => 0,
            'altri_micromobilita' => 0,
            'altri_veicoli' => 0
        );
        
        // Mappa codici tipo veicolo ai contatori
        $mappa_tipi = array(
            '1' => 'autovetture',
            '2' => 'autovetture', 
            '3' => 'autovetture',
            '4' => 'autovetture',
            '5' => 'autovetture',
            '6' => 'autovetture',
            '11' => 'autobus',
            '12' => 'autobus', 
            '13' => 'autotreni',
            '14' => 'autoarticolati',
            '19' => 'tram',
            '20' => 'treni',
            '15' => 'motocicli',
            '16' => 'motocicli',
            '17' => 'ciclomotori',
            '18' => 'ciclomotori',
            '21' => 'velocipedi',
            '22' => 'biciclette_assistite',
            '23' => 'monopattini',
            '24' => 'altri_micromobilita'
            // Altri codici mappare a 'altri_veicoli'
        );
        
        // Controlla veicoli A, B, C
        for ($i = 1; $i <= 3; $i++) {
            $tipo_veicolo = get_post_meta($post_id, "veicolo_{$i}_tipo", true);
            
            if (!empty($tipo_veicolo)) {
                if (isset($mappa_tipi[$tipo_veicolo])) {
                    $conteggi[$mappa_tipi[$tipo_veicolo]]++;
                } else {
                    $conteggi['altri_veicoli']++;
                }
            }
        }
        
        // Aggiungi il valore dal campo "Numero altri veicoli coinvolti" della sezione ISTAT
        $altri_veicoli_istat = (int) get_post_meta($post_id, 'numero_altri_veicoli', true);
        $conteggi['altri_veicoli'] += $altri_veicoli_istat;
        
        return $conteggi;
    }

    /**
     * Verifica se l'utente può accedere all'esportazione ISTAT
     */
    private function user_can_access_istat_export() {
        $current_user = wp_get_current_user();
        
        // Ruoli autorizzati per ISTAT
        $allowed_roles = array('asset', 'supervisor', 'administrator');
        
        foreach ($allowed_roles as $role) {
            if (in_array($role, $current_user->roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verifica se l'utente può accedere all'esportazione Excel
     */
    private function user_can_access_excel_export() {
        $current_user = wp_get_current_user();
        
        // Ruoli autorizzati per Excel (include ruolo Prefettura se esiste)
        $allowed_roles = array('prefettura', 'supervisor', 'administrator', 'operatore_polizia_comunale');
        
        foreach ($allowed_roles as $role) {
            if (in_array($role, $current_user->roles)) {
                return true;
            }
        }
        
        return false;
    }
}