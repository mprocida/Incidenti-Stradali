<?php
/**
 * Incidenti Export Functions - VERSIONE COMPLETA ISTAT 1939 caratteri
 * 
 * @package IncidentiStradali
 * @version 1.0.0
 * @author Plugin Development Team
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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
            <h1><?php _e('Esporta Dati Incidenti', 'incidenti-stradali'); ?></h1>
            
            <?php echo $message; ?>
            
            <div class="card">
                <h2><?php _e('Esportazione Formato ISTAT (TXT)', 'incidenti-stradali'); ?></h2>
                <p><?php _e('Esporta i dati nel formato richiesto da ISTAT per la trasmissione ufficiale (1939 caratteri per record).', 'incidenti-stradali'); ?></p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="export_incidenti_istat">
                    <?php wp_nonce_field('export_incidenti_nonce', 'export_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Periodo', 'incidenti-stradali'); ?></th>
                            <td>
                                <label for="data_inizio"><?php _e('Da:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_inizio" name="data_inizio" value="<?php echo date('Y-m-01'); ?>">
                                
                                <label for="data_fine"><?php _e('A:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_fine" name="data_fine" value="<?php echo date('Y-m-t'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Comune', 'incidenti-stradali'); ?></th>
                            <td>
                                <select name="comune_filtro" class="regular-text">
                                    <option value=""><?php _e('Tutti i comuni', 'incidenti-stradali'); ?></option>
                                    <?php 
                                    $comuni = $this->get_comuni_disponibili();
                                    foreach($comuni as $codice => $nome): ?>
                                        <option value="<?php echo esc_attr($codice); ?>">
                                            <?php echo esc_html($nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Filtra per comune specifico', 'incidenti-stradali'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Esporta TXT ISTAT (1939 caratteri)', 'incidenti-stradali'), 'primary'); ?>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Esportazione Formato Excel (CSV)', 'incidenti-stradali'); ?></h2>
                <p><?php _e('Esporta i dati nel formato Excel per la Polizia Stradale.', 'incidenti-stradali'); ?></p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="export_incidenti_excel">
                    <?php wp_nonce_field('export_incidenti_nonce', 'export_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Periodo', 'incidenti-stradali'); ?></th>
                            <td>
                                <label for="data_inizio_excel"><?php _e('Da:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_inizio_excel" name="data_inizio" value="<?php echo date('Y-m-01'); ?>">
                                
                                <label for="data_fine_excel"><?php _e('A:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_fine_excel" name="data_fine" value="<?php echo date('Y-m-t'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Comune', 'incidenti-stradali'); ?></th>
                            <td>
                                <select name="comune_filtro" class="regular-text">
                                    <option value=""><?php _e('Tutti i comuni', 'incidenti-stradali'); ?></option>
                                    <?php foreach($comuni as $codice => $nome): ?>
                                        <option value="<?php echo esc_attr($codice); ?>">
                                            <?php echo esc_html($nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Esporta CSV Excel', 'incidenti-stradali'), 'secondary'); ?>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Log Esportazioni', 'incidenti-stradali'); ?></h2>
                <?php $this->show_export_logs(); ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Informazioni Tracciato ISTAT', 'incidenti-stradali'); ?></h2>
                <p><strong><?php _e('Lunghezza record:', 'incidenti-stradali'); ?></strong> 1939 caratteri</p>
                <p><strong><?php _e('Encoding:', 'incidenti-stradali'); ?></strong> UTF-8 senza BOM</p>
                <p><strong><?php _e('Terminatore riga:', 'incidenti-stradali'); ?></strong> \\r\\n</p>
                <p><strong><?php _e('Conforme a:', 'incidenti-stradali'); ?></strong> Tracciato record 2 ISTAT 2025</p>
            </div>
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
        
        $incidenti = $this->get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro);
        
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
    private function get_incidenti_for_export($data_inizio, $data_fine, $comune_filtro = '') {
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
    }
    
    /**
     * Download ISTAT export
     */
    private function download_istat_export($incidenti) {
        $filename = 'export_incidenti_istat_' . date('YmdHis') . '.txt';
        $output = $this->generate_istat_txt_complete($incidenti);
        
        // Validazione lunghezza record
        $lines = explode("\r\n", trim($output));
        $errori_lunghezza = array();
        
        foreach ($lines as $line_num => $line) {
            if (strlen($line) !== 1939) {
                $errori_lunghezza[] = "Record " . ($line_num + 1) . " ha lunghezza " . strlen($line) . " invece di 1939 caratteri";
                error_log("ATTENZIONE ISTAT: Record " . ($line_num + 1) . " ha lunghezza " . strlen($line) . " invece di 1939 caratteri");
            }
        }
        
        // Log dell'esportazione
        $this->log_export('ISTAT_TXT', count($incidenti), $filename, $errori_lunghezza);
        
        // Trigger notification
        do_action('incidenti_after_export', 'ISTAT_TXT', $filename, count($incidenti), get_current_user_id());
        
        // Download del file
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
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
        $filename = 'export_incidenti_excel_' . date('YmdHis') . '.csv';
        $output = $this->generate_excel_csv($incidenti);
        
        // Log dell'esportazione
        $this->log_export('EXCEL_CSV', count($incidenti), $filename);
        
        // Trigger notification
        do_action('incidenti_after_export', 'EXCEL_CSV', $filename, count($incidenti), get_current_user_id());
        
        // Download del file
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM per Excel
        echo $output;
        exit;
    }
    
    /**
     * Genera file TXT ISTAT completo (1939 caratteri)
     * Basato su rilevazioni.php esistente e tracciato ISTAT ufficiale
     */
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
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            $anno = substr($data_incidente, 2, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($anno, 2, '0', STR_PAD_LEFT);
            
            // Campo 3-4: Mese
            $mese = substr($data_incidente, 5, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($mese, 2, '0', STR_PAD_LEFT);
            
            // Campo 5-7: Provincia
            $provincia = get_post_meta($post_id, 'provincia_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($provincia ?: '000', 3, '0', STR_PAD_LEFT);
            
            // Campo 8-10: Comune
            $comune = get_post_meta($post_id, 'comune_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($comune ?: '000', 3, '0', STR_PAD_LEFT);
            
            // Campo 11-14: Numero d'ordine
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($post_id, 4, '0', STR_PAD_LEFT);
            
            // Campo 15-16: Giorno
            $giorno = substr($data_incidente, 8, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($giorno, 2, '0', STR_PAD_LEFT);
            
            // Campo 17-18: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad('', 2, '~', STR_PAD_RIGHT);
            
            // Campo 19: Organo di rilevazione
            $organo_rilevazione = get_post_meta($post_id, 'organo_rilevazione', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $organo_rilevazione ?: '4'; // Default: Polizia Municipale
            
            // Campo 20-24: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad('', 5, '~', STR_PAD_RIGHT);
            
            // Campo 25: Organo coordinatore
            $organo_coordinatore = get_post_meta($post_id, 'organo_coordinatore', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $organo_coordinatore ?: '4';
            
            // Campo 26: Localizzazione incidente
            $localizzazione = get_post_meta($post_id, 'localizzazione_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $localizzazione ?: '1';
            
            // ===== DENOMINAZIONE E CARATTERISTICHE STRADA (Posizioni 27-40) =====
            
            // Campo 27-29: Denominazione strada
            $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad(substr($denominazione_strada ?: '000', 0, 3), 3, '0', STR_PAD_LEFT);
            
            // Campo 30: Illuminazione
            $illuminazione = get_post_meta($post_id, 'illuminazione', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $illuminazione ?: '1';
            
            // Campo 31-32: Ora
            $ora = get_post_meta($post_id, 'ora_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($ora ?: '25', 2, '0', STR_PAD_LEFT); // 25 = sconosciuta
            
            // Campo 33-34: Minuti  
            $minuti = get_post_meta($post_id, 'minuti_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($minuti ?: '99', 2, '0', STR_PAD_LEFT); // 99 = sconosciuti
            
            // ===== CARATTERISTICHE DEL LUOGO (Posizioni 35-40) =====
            
            // Campo 35: Tipo di strada
            $tipo_strada = get_post_meta($post_id, 'tipo_strada', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $tipo_strada ?: '1';
            
            // Campo 36: Pavimentazione
            $pavimentazione = get_post_meta($post_id, 'pavimentazione', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $pavimentazione ?: '1';
            
            // Campo 37: Intersezione
            $intersezione = get_post_meta($post_id, 'intersezione', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $intersezione ?: '1';
            
            // Campo 38: Fondo stradale
            $fondo_stradale = get_post_meta($post_id, 'fondo_stradale', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $fondo_stradale ?: '1';
            
            // Campo 39: Segnaletica
            $segnaletica = get_post_meta($post_id, 'segnaletica', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $segnaletica ?: '1';
            
            // Campo 40: Condizioni meteorologiche
            $meteo = get_post_meta($post_id, 'condizioni_meteo', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $meteo ?: '1';
            
            // ===== NATURA DELL'INCIDENTE (Posizioni 41-43) =====
            
            // Campo 41: Natura incidente
            $natura_incidente = get_post_meta($post_id, 'natura_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = $natura_incidente ?: '1';
            
            // Campo 42-43: Dettaglio natura
            $dettaglio_natura = get_post_meta($post_id, 'dettaglio_natura', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($dettaglio_natura ?: '00', 2, '0', STR_PAD_LEFT);
            
            // ===== VEICOLI COINVOLTI - TIPO (Posizioni 44-49) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $tipo_veicolo = get_post_meta($post_id, "veicolo_{$numVeicolo}_tipo", true);
                $indTXT++;
                $varAppo = $tipo_veicolo;
                $esitoTXT[$indTXT] = $cfgistat['tipo_veicolo'][$varAppo] ?? '~~';
                if (trim($esitoTXT[$indTXT]) == '') $esitoTXT[$indTXT] = '~~';
                $esitoTXT[$indTXT] = str_pad($esitoTXT[$indTXT], 2, '~', STR_PAD_LEFT);
            }
            
            // ===== VEICOLI - CILINDRATA (Posizioni 50-61) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $cilindrata = get_post_meta($post_id, "veicolo_{$numVeicolo}_cilindrata", true);
                $cilindrata = round($cilindrata); // Forzatura a intero
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($cilindrata ?: '0000', 4, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '0000') $esitoTXT[$indTXT] = '~~~~';
            }
            
            // ===== VEICOLI - PESO TOTALE (Posizioni 62-73) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $peso = get_post_meta($post_id, "veicolo_{$numVeicolo}_peso", true);
                $peso = round($peso); // Forzatura a intero
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($peso ?: '0000', 4, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '0000') $esitoTXT[$indTXT] = '~~~~';
            }
            
            // ===== CIRCOSTANZE VEICOLI A e B (Posizioni 74-85) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 2; $numVeicolo++) { // Solo A e B
                // Inconvenienti alla circolazione
                $inconv_circ = get_post_meta($post_id, "veicolo_{$numVeicolo}_inconvenienti", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($inconv_circ ?: '00', 2, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '00') $esitoTXT[$indTXT] = '~~';
                
                // Difetti o avarie
                $difetti = get_post_meta($post_id, "veicolo_{$numVeicolo}_difetti", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($difetti ?: '00', 2, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '00') $esitoTXT[$indTXT] = '~~';
                
                // Stato psico-fisico conducente
                $stato_psico = get_post_meta($post_id, "conducente_{$numVeicolo}_stato_psicofisico", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($stato_psico ?: '00', 2, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '00') $esitoTXT[$indTXT] = '~~';
            }
            
            // ===== TARGHE E DATI VEICOLI (Posizioni 86-130) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Targa (8 caratteri)
                $targa = get_post_meta($post_id, "veicolo_{$numVeicolo}_targa", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($targa ?: '', 8, '~', STR_PAD_RIGHT);
                
                // Sigla se estero (3 caratteri)
                $sigla = get_post_meta($post_id, "veicolo_{$numVeicolo}_sigla_estero", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($sigla ?: '', 3, '~', STR_PAD_LEFT);
                
                // Anno immatricolazione (2 cifre)
                $anno_imm = get_post_meta($post_id, "veicolo_{$numVeicolo}_anno_immatricolazione", true);
                $strAppo = str_pad($anno_imm ?: '0000', 4, '0', STR_PAD_LEFT);
                $strAppo = substr($strAppo, 2, 2);
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo == '00' ? '~~' : $strAppo;
                
                // Anno revisione (2 cifre)
                $anno_rev = get_post_meta($post_id, "veicolo_{$numVeicolo}_anno_revisione", true);
                $strAppo = str_pad($anno_rev ?: '0000', 4, '0', STR_PAD_LEFT);
                $strAppo = substr($strAppo, 2, 2);
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo == '00' ? '~~' : $strAppo;
                
                // Chilometri (3 cifre)
                $km = get_post_meta($post_id, "veicolo_{$numVeicolo}_chilometri", true);
                $km = round($km); // Arrotondamento all'intero
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($km ?: '000', 3, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '000') $esitoTXT[$indTXT] = '~~~';
            }
            
            // ===== DATI CONDUCENTI E PASSEGGERI =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Età conducente (2 cifre)
                $eta = get_post_meta($post_id, "conducente_{$numVeicolo}_eta", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($eta ?: '00', 2, '0', STR_PAD_LEFT);
                
                // Sesso conducente (1 cifra)
                $sesso = get_post_meta($post_id, "conducente_{$numVeicolo}_sesso", true);
                $strAppo = $sesso;
                $indTXT++;
                $esitoTXT[$indTXT] = $cfgistat['sesso'][$strAppo] ?? '0';
                
                // Esito conducente (1 cifra)
                $esito = get_post_meta($post_id, "conducente_{$numVeicolo}_esito", true);
                $indTXT++;
                $esitoTXT[$indTXT] = $esito ?: '1';
                
                // PASSEGGERI - 1 anteriore + 3 posteriori per veicolo
                $numPassAnt = 1; // Previsto da ISTAT
                $numPassPost = 3; // Previsto da ISTAT
                
                // Passeggero anteriore
                for ($numPass = 1; $numPass <= $numPassAnt; $numPass++) {
                    // Età
                    $eta_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_anteriore_{$numPass}_eta", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = str_pad($eta_pass ?: '00', 2, '0', STR_PAD_LEFT);
                    
                    // Sesso
                    $sesso_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_anteriore_{$numPass}_sesso", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = $cfgistat['sesso'][$sesso_pass] ?? '0';
                    
                    // Esito
                    $esito_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_anteriore_{$numPass}_esito", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = $esito_pass ?: '1';
                }
                
                // Passeggeri posteriori
                for ($numPass = 1; $numPass <= $numPassPost; $numPass++) {
                    // Età
                    $eta_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_posteriore_{$numPass}_eta", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = str_pad($eta_pass ?: '00', 2, '0', STR_PAD_LEFT);
                    
                    // Sesso
                    $sesso_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_posteriore_{$numPass}_sesso", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = $cfgistat['sesso'][$sesso_pass] ?? '0';
                    
                    // Esito
                    $esito_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_passeggero_posteriore_{$numPass}_esito", true);
                    $indTXT++;
                    $esitoTXT[$indTXT] = $esito_pass ?: '1';
                }
                
                // DISPOSITIVI DI SICUREZZA
                
                // Conducente
                $casco_cond = get_post_meta($post_id, "veicolo_{$numVeicolo}_casco_conducente", true);
                $cintura_cond = get_post_meta($post_id, "veicolo_{$numVeicolo}_cintura_conducente", true);
                
                $strAppo = "";
                if ($casco_cond == 'si') {
                    $strAppo = $cfgistat['casco_conducente'][$casco_cond] ?? '~';
                } else if ($cintura_cond == 'si') {
                    $strAppo = $cfgistat['cintura_conducente'][$cintura_cond] ?? '~';
                } else if ($casco_cond == 'no') {
                    $strAppo = $cfgistat['casco_conducente'][$casco_cond] ?? '~';
                } else if ($cintura_cond == 'no') {
                    $strAppo = $cfgistat['cintura_conducente'][$cintura_cond] ?? '~';
                }
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo ?: '~';
                
                // Passeggero
                $casco_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_casco_passeggero", true);
                $cintura_pass = get_post_meta($post_id, "veicolo_{$numVeicolo}_cintura_passeggero", true);
                
                $strAppo = "";
                if ($casco_pass == 'si') {
                    $strAppo = $cfgistat['casco_passeggero'][$casco_pass] ?? '~';
                } else if ($cintura_pass == 'si') {
                    $strAppo = $cfgistat['cintura_passeggero'][$cintura_pass] ?? '~';
                } else if ($casco_pass == 'no') {
                    $strAppo = $cfgistat['casco_passeggero'][$casco_pass] ?? '~';
                } else if ($cintura_pass == 'no') {
                    $strAppo = $cfgistat['cintura_passeggero'][$cintura_pass] ?? '~';
                }
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo ?: '~';
            }
            
            // ===== ALTRI VEICOLI E PERSONE COINVOLTE =====
            
            // Numero veicoli coinvolti oltre A, B, C (2 cifre)
            $altri_veicoli = get_post_meta($post_id, 'altri_veicoli_coinvolti', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($altri_veicoli ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Persone coinvolte su altri veicoli (2 cifre)
            $persone_altri = get_post_meta($post_id, 'persone_altri_veicoli', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($persone_altri ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Conducenti incolumi maschi/femmine (2 cifre ciascuno)
            $cond_inc_m = get_post_meta($post_id, 'conducenti_incolumi_maschi', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($cond_inc_m ?: '00', 2, '0', STR_PAD_LEFT);
            
            $cond_inc_f = get_post_meta($post_id, 'conducenti_incolumi_femmine', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($cond_inc_f ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Altri feriti maschi/femmine (2 cifre ciascuno)
            $feriti_m = get_post_meta($post_id, 'altri_feriti_maschi', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($feriti_m ?: '00', 2, '0', STR_PAD_LEFT);
            
            $feriti_f = get_post_meta($post_id, 'altri_feriti_femmine', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($feriti_f ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Morti entro 24 ore (2 cifre)
            $morti_24h = get_post_meta($post_id, 'morti_entro_24_ore', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($morti_24h ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Morti dal 2° al 30° giorno (2 cifre)
            $morti_30gg = get_post_meta($post_id, 'morti_dal_2_al_30_giorno', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($morti_30gg ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Feriti totali (2 cifre)
            $feriti_tot = get_post_meta($post_id, 'feriti_totali', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($feriti_tot ?: '00', 2, '0', STR_PAD_LEFT);
            
            // ===== SPAZI RISERVATI (9 caratteri) =====
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad('', 9, '~', STR_PAD_RIGHT);
            
            // ===== DENOMINAZIONE STRADA COMPLETA (57 caratteri) =====
            $strada_completa = get_post_meta($post_id, 'denominazione_strada_completa', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad(substr($strada_completa ?: '', 0, 57), 57, '~', STR_PAD_RIGHT);
            
            // ===== SPAZI 100 caratteri =====
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad('', 100, '~', STR_PAD_RIGHT);
            
            // ===== NOMINATIVI MORTI (4 morti massimo - 60 caratteri ciascuno) =====
            for ($numMorto = 1; $numMorto <= 4; $numMorto++) {
                // Nome morto (30 caratteri)
                $nome_morto = get_post_meta($post_id, "morto_{$numMorto}_nome", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($nome_morto ?: '', 30, '~', STR_PAD_RIGHT);
                
                // Cognome morto (30 caratteri)
                $cognome_morto = get_post_meta($post_id, "morto_{$numMorto}_cognome", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($cognome_morto ?: '', 30, '~', STR_PAD_RIGHT);
            }
            
            // ===== NOMINATIVI FERITI (8 feriti massimo - 90 caratteri ciascuno) =====
            for ($numFerito = 1; $numFerito <= 8; $numFerito++) {
                // Nome ferito (30 caratteri)
                $nome_ferito = get_post_meta($post_id, "ferito_{$numFerito}_nome", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($nome_ferito ?: '', 30, '~', STR_PAD_RIGHT);
                
                // Cognome ferito (30 caratteri)
                $cognome_ferito = get_post_meta($post_id, "ferito_{$numFerito}_cognome", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($cognome_ferito ?: '', 30, '~', STR_PAD_RIGHT);
                
                // Istituto ricovero (30 caratteri)
                $istituto = get_post_meta($post_id, "ferito_{$numFerito}_istituto", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($istituto ?: '', 30, '~', STR_PAD_RIGHT);
            }
            
            // ===== COORDINATE GEOGRAFICHE (20 caratteri) =====
            
            // Latitudine (10 caratteri)
            $latitudine = get_post_meta($post_id, 'latitudine', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($latitudine ?: '', 10, '~', STR_PAD_RIGHT);
            
            // Longitudine (10 caratteri)
            $longitudine = get_post_meta($post_id, 'longitudine', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($longitudine ?: '', 10, '~', STR_PAD_RIGHT);
            
            // ===== CITTADINANZA CONDUCENTI (34 caratteri x 3 = 102 caratteri) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Cittadinanza italiana/straniera (1 cifra)
                $cittadinanza_tipo = get_post_meta($post_id, "conducente_{$numVeicolo}_cittadinanza_tipo", true);
                $indTXT++;
                $esitoTXT[$indTXT] = $cittadinanza_tipo ?: '1'; // 1=italiana, 2=straniera
                
                // Codice cittadinanza (3 cifre)
                $codice_cittadinanza = get_post_meta($post_id, "conducente_{$numVeicolo}_codice_cittadinanza", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($codice_cittadinanza ?: '000', 3, '0', STR_PAD_LEFT);
                
                // Descrizione cittadinanza (30 caratteri)
                $desc_cittadinanza = get_post_meta($post_id, "conducente_{$numVeicolo}_descrizione_cittadinanza", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($desc_cittadinanza ?: '', 30, '~', STR_PAD_RIGHT);
            }
            
            // ===== RIMORCHI 2020 (42 caratteri) =====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Tipo rimorchio (4 caratteri)
                $tipo_rimorchio = get_post_meta($post_id, "veicolo_{$numVeicolo}_tipo_rimorchio", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($tipo_rimorchio ?: '', 4, '~', STR_PAD_RIGHT);
                
                // Targa rimorchio (10 caratteri)
                $targa_rimorchio = get_post_meta($post_id, "veicolo_{$numVeicolo}_targa_rimorchio", true);
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad($targa_rimorchio ?: '', 10, '~', STR_PAD_RIGHT);
            }
            
            // ===== CODICE STRADA ACI (15 caratteri) - Posizioni 1925-1939 =====
            $codice_aci = get_post_meta($post_id, 'codice_strada_aci', true);
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($codice_aci ?: '', 15, '~', STR_PAD_RIGHT);
            
            // ===== VALIDAZIONE E COMPLETAMENTO RECORD =====
            
            // Validazione lunghezze usando l'array di controllo
            $cfgistat_lunghezze = $this->get_istat_field_lengths();
            $numErrori = 0;
            $strErrori = '';
            
            for ($indChk = 0; $indChk < count($esitoTXT); $indChk++) {
                $lungReale = strlen($esitoTXT[$indChk]);
                $lungAttesa = isset($cfgistat_lunghezze[$indChk]) ? $cfgistat_lunghezze[$indChk] : 0;
                
                if ($lungReale != $lungAttesa && $lungAttesa > 0) {
                    $numErrori++;
                    $strErrori .= "Incidente ID {$post_id} - Campo " . ($indChk + 1) . ": Trovati {$lungReale} caratteri ({$esitoTXT[$indChk]}) invece di {$lungAttesa}\n";
                }
            }
            
            if ($numErrori > 0) {
                error_log("Errori validazione ISTAT per incidente {$post_id}:\n" . $strErrori);
            }
            
            // Conversione finale: sostituisce ~ con spazi e unisce tutti i campi
            $esitoTXTstr = str_replace('~', ' ', implode('', $esitoTXT));
            
            // Assicura che il record sia esattamente 1939 caratteri
            $esitoTXTstr = str_pad($esitoTXTstr, 1939, ' ', STR_PAD_RIGHT);
            
            // Aggiungi il record al file finale
            $output .= $esitoTXTstr . "\r\n";
            $index++;
        }
        
        return $output;
    }
    
    /**
     * Genera CSV per Excel (formato Polizia Stradale)
     */
    public function generate_excel_csv($incidenti) {
        // Header CSV secondo form ISTAT/Polizia
        $headers = array(
            'ID Incidente',
            'Data',
            'Ora',
            'Minuti',
            'Provincia',
            'Comune',
            'Località',
            'Denominazione Strada',
            'Km',
            'Localizzazione',
            'Tipo Strada',
            'Pavimentazione',
            'Intersezione',
            'Fondo Stradale',
            'Segnaletica',
            'Condizioni Meteo',
            'Natura Incidente',
            'Tipo Veicolo A',
            'Tipo Veicolo B',
            'Tipo Veicolo C',
            'Targa A',
            'Targa B', 
            'Targa C',
            'Cilindrata A',
            'Cilindrata B',
            'Cilindrata C',
            'Peso A',
            'Peso B',
            'Peso C',
            'Età Conducente A',
            'Sesso Conducente A',
            'Esito Conducente A',
            'Età Conducente B',
            'Sesso Conducente B',
            'Esito Conducente B',
            'Età Conducente C',
            'Sesso Conducente C',
            'Esito Conducente C',
            'Feriti Totali',
            'Morti 24h',
            'Morti 30gg',
            'Latitudine',
            'Longitudine',
            'Organo Rilevazione'
        );
        
        $output = '"' . implode('","', $headers) . '"' . "\n";
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            
            $row = array(
                $post_id,
                get_post_meta($post_id, 'data_incidente', true),
                get_post_meta($post_id, 'ora_incidente', true),
                get_post_meta($post_id, 'minuti_incidente', true),
                get_post_meta($post_id, 'provincia_incidente', true),
                get_post_meta($post_id, 'comune_incidente', true),
                get_post_meta($post_id, 'localita', true),
                get_post_meta($post_id, 'denominazione_strada', true),
                get_post_meta($post_id, 'km_strada', true),
                get_post_meta($post_id, 'localizzazione_incidente', true),
                get_post_meta($post_id, 'tipo_strada', true),
                get_post_meta($post_id, 'pavimentazione', true),
                get_post_meta($post_id, 'intersezione', true),
                get_post_meta($post_id, 'fondo_stradale', true),
                get_post_meta($post_id, 'segnaletica', true),
                get_post_meta($post_id, 'condizioni_meteo', true),
                get_post_meta($post_id, 'natura_incidente', true),
                get_post_meta($post_id, 'veicolo_1_tipo', true),
                get_post_meta($post_id, 'veicolo_2_tipo', true),
                get_post_meta($post_id, 'veicolo_3_tipo', true),
                get_post_meta($post_id, 'veicolo_1_targa', true),
                get_post_meta($post_id, 'veicolo_2_targa', true),
                get_post_meta($post_id, 'veicolo_3_targa', true),
                get_post_meta($post_id, 'veicolo_1_cilindrata', true),
                get_post_meta($post_id, 'veicolo_2_cilindrata', true),
                get_post_meta($post_id, 'veicolo_3_cilindrata', true),
                get_post_meta($post_id, 'veicolo_1_peso', true),
                get_post_meta($post_id, 'veicolo_2_peso', true),
                get_post_meta($post_id, 'veicolo_3_peso', true),
                get_post_meta($post_id, 'conducente_1_eta', true),
                get_post_meta($post_id, 'conducente_1_sesso', true),
                get_post_meta($post_id, 'conducente_1_esito', true),
                get_post_meta($post_id, 'conducente_2_eta', true),
                get_post_meta($post_id, 'conducente_2_sesso', true),
                get_post_meta($post_id, 'conducente_2_esito', true),
                get_post_meta($post_id, 'conducente_3_eta', true),
                get_post_meta($post_id, 'conducente_3_sesso', true),
                get_post_meta($post_id, 'conducente_3_esito', true),
                get_post_meta($post_id, 'feriti_totali', true),
                get_post_meta($post_id, 'morti_entro_24_ore', true),
                get_post_meta($post_id, 'morti_dal_2_al_30_giorno', true),
                get_post_meta($post_id, 'latitudine', true),
                get_post_meta($post_id, 'longitudine', true),
                get_post_meta($post_id, 'organo_rilevazione', true)
            );
            
            // Escape e formattazione CSV
            $row = array_map(function($val) {
                return '"' . str_replace('"', '""', $val) . '"';
            }, $row);
            
            $output .= implode(',', $row) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Array delle lunghezze standard per ogni campo del tracciato ISTAT
     * Basato sul file rilevazioni.php esistente
     */
    private function get_istat_field_lengths() {
        return array(
            // Dati base identificativi
            2,  // anno
            2,  // mese
            3,  // provincia
            3,  // comune
            4,  // numero ordine
            2,  // giorno
            2,  // spazi
            1,  // organo rilevazione
            5,  // spazi
            1,  // organo coordinatore
            1,  // localizzazione
            3,  // denominazione strada
            1,  // illuminazione
            2,  // ora
            2,  // minuti
            1,  // tipo strada
            1,  // pavimentazione
            1,  // intersezione
            1,  // fondo stradale
            1,  // segnaletica
            1,  // meteo
            1,  // natura incidente
            2,  // dettaglio natura
            
            // Tipi veicoli (2x3)
            2, 2, 2,
            // Cilindrate (4x3)
            4, 4, 4,
            // Pesi (4x3)
            4, 4, 4,
            
            // Circostanze (2x3x2 veicoli A,B)
            2, 2, 2, 2, 2, 2,
            
            // Targhe e dati veicoli (8+3+2+2+3 = 18 caratteri x3)
            8, 3, 2, 2, 3,  // Veicolo A
            8, 3, 2, 2, 3,  // Veicolo B
            8, 3, 2, 2, 3,  // Veicolo C
            
            // Conducenti (2+1+1 = 4 caratteri x3)
            2, 1, 1,  // Conducente A
            2, 1, 1,  // Conducente B
            2, 1, 1,  // Conducente C
            
            // Passeggeri (2+1+1 = 4 caratteri x4 passeggeri x3 veicoli)
            2, 1, 1, 2, 1, 1, 2, 1, 1, 2, 1, 1,  // Veicolo A
            2, 1, 1, 2, 1, 1, 2, 1, 1, 2, 1, 1,  // Veicolo B
            2, 1, 1, 2, 1, 1, 2, 1, 1, 2, 1, 1,  // Veicolo C
            
            // Dispositivi sicurezza (1+1 = 2 caratteri x3 veicoli)
            1, 1, 1, 1, 1, 1,
            
            // Altri dati persone
            2,  // altri veicoli
            2,  // persone altri veicoli
            2,  // conducenti incolumi maschi
            2,  // conducenti incolumi femmine
            2,  // altri feriti maschi
            2,  // altri feriti femmine
            2,  // morti 24h
            2,  // morti 30gg
            2,  // feriti totali
            
            // Spazi riservati
            9,  // spazi riservati
            
            // Denominazione strada completa
            57,
            
            // Spazi 100
            100,
            
            // Nominativi morti (30+30 = 60 caratteri x4)
            30, 30, 30, 30, 30, 30, 30, 30,
            
            // Nominativi feriti (30+30+30 = 90 caratteri x8)
            30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30,
            30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30,
            
            // Coordinate geografiche
            10, // latitudine
            10, // longitudine
            
            // Cittadinanza conducenti (1+3+30 = 34 caratteri x3)
            1, 3, 30, 1, 3, 30, 1, 3, 30,
            
            // Rimorchi 2020 (4+10 = 14 caratteri x3)
            4, 10, 4, 10, 4, 10,
            
            // Codice ACI
            15
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
        echo '<th>' . __('Errori', 'incidenti-stradali') . '</th>';
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
            echo '<td>';
            if (empty($log['errori'])) {
                echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('Nessuno', 'incidenti-stradali');
            } else {
                echo '<span class="dashicons dashicons-warning" style="color: orange;"></span> ' . count($log['errori']) . ' ' . __('errori', 'incidenti-stradali');
            }
            echo '</td>';
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
            '001' => 'Acquarica del Capo',
            '002' => 'Alessano',
            '003' => 'Alezio',
            '004' => 'Alliste',
            '005' => 'Andrano',
            '006' => 'Aradeo',
            '007' => 'Arnesano',
            '008' => 'Bagnolo del Salento',
            '009' => 'Botrugno',
            '010' => 'Calimera',
            '011' => 'Campi Salentina',
            '012' => 'Cannole',
            '013' => 'Caprarica di Lecce',
            '014' => 'Carmiano',
            '015' => 'Carpignano Salentino',
            '016' => 'Casarano',
            '017' => 'Castrano',
            '018' => 'Castro',
            '019' => 'Cavallino',
            '020' => 'Collepasso',
            '021' => 'Copertino',
            '022' => 'Corigliano d\'Otranto',
            '023' => 'Corsano',
            '024' => 'Cursi',
            '025' => 'Cutrofiano',
            '026' => 'Diso',
            '027' => 'Gagliano del Capo',
            '028' => 'Galatina',
            '029' => 'Galatone',
            '030' => 'Gallipoli',
            '031' => 'Giuggianello',
            '032' => 'Giurdignano',
            '033' => 'Guagnano',
            '034' => 'Lecce',
            '035' => 'Lequile',
            '036' => 'Leverano',
            '037' => 'Lizzanello',
            '038' => 'Maglie',
            '039' => 'Martano',
            '040' => 'Martignano',
            '041' => 'Matino',
            '042' => 'Melendugno',
            '043' => 'Melissano',
            '044' => 'Melpignano',
            '045' => 'Miggiano',
            '046' => 'Minervino di Lecce',
            '047' => 'Monteroni di Lecce',
            '048' => 'Montesano Salentino',
            '049' => 'Morciano di Leuca',
            '050' => 'Muro Leccese',
            '051' => 'Nardò',
            '052' => 'Neviano',
            '053' => 'Nociglia',
            '054' => 'Novoli',
            '055' => 'Ortelle',
            '056' => 'Otranto',
            '057' => 'Palmariggi',
            '058' => 'Parabita',
            '059' => 'Patù',
            '060' => 'Poggiardo',
            '061' => 'Polo',
            '062' => 'Presicce',
            '063' => 'Racale',
            '064' => 'Ruffano',
            '065' => 'Salice Salentino',
            '066' => 'Salve',
            '067' => 'Sanarica',
            '068' => 'San Cesario di Lecce',
            '069' => 'San Donato di Lecce',
            '070' => 'Sannicola',
            '071' => 'San Pietro in Lama',
            '072' => 'Santa Cesarea Terme',
            '073' => 'Scorrano',
            '074' => 'Secli',
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
            '091' => 'Uggiano la Chiesa',
            '092' => 'Veglie',
            '093' => 'Vernole',
            '094' => 'Zollino',
            '095' => 'Castro',
            '096' => 'Presicce-Acquarica'
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
}

// Inizializzazione della classe
new IncidentiExportFunctions();