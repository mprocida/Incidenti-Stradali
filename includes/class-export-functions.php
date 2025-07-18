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
            </table>
            <?php submit_button(__('Esporta TXT ISTAT (1939 caratteri)', 'incidenti-stradali'), 'primary'); ?>
        </form>
    </div>
    <div class="card">
        <h2>
            <?php _e('Esportazione Formato Excel (CSV)', 'incidenti-stradali'); ?>
        </h2>
        <p>
            <?php _e('Esporta i dati nel formato Excel per la Polizia Stradale.', 'incidenti-stradali'); ?>
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
        <h2>
            <?php _e('Log Esportazioni', 'incidenti-stradali'); ?>
        </h2>
        <?php $this->show_export_logs(); ?>
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
            $esitoTXT[$indTXT] = mb_str_pad($post_id, 4, '0', STR_PAD_LEFT);
            
            // Campo 15-16: Giorno
            $giorno = substr($data_incidente, 8, 2);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($giorno, 2, '0', STR_PAD_LEFT);
            
            // Campo 17-18: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 2, '~', STR_PAD_RIGHT);
            
            // Campo 19: Organo di rilevazione
            $organo_rilevazione = $this->safe_meta_string($post_id, 'organo_rilevazione');
            $indTXT++;
            $esitoTXT[$indTXT] = $organo_rilevazione ?: '4'; // Default: Polizia Municipale
            
            // Campo 20-24: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 5, '~', STR_PAD_RIGHT);
            
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
            $esitoTXT[$indTXT] = mb_str_pad('', 2, '~', STR_PAD_RIGHT);

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
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($tipo_veicolo ?: '  ', 2, '0', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '') $esitoTXT[$indTXT] = '~~';
                $esitoTXT[$indTXT] = mb_str_pad($esitoTXT[$indTXT], 2, '~', STR_PAD_LEFT);
            }

            // Campo 50-61: Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 12, '~', STR_PAD_RIGHT);
            
            // ===== VEICOLI - PESO TOTALE (Posizioni 62-73) =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $peso = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_peso_totale");
                // Gestione sicura per valori vuoti o non numerici
                $peso = is_numeric($peso) ? round(floatval($peso)) : 0;
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($peso ?: '    ', 4, ' ', STR_PAD_LEFT);
                if (trim($esitoTXT[$indTXT]) == '0000') $esitoTXT[$indTXT] = '~~~~';
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
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($targa ?: '        ', 8, ' ', STR_PAD_RIGHT);
                
                // Sigla se estero (3 caratteri)
                $sigla = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_sigla_estero");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($sigla ?: '   ', 3, ' ', STR_PAD_LEFT);
                
                // Anno immatricolazione (2 cifre)
                $anno_imm = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_anno_immatricolazione");
                $strAppo = mb_str_pad($anno_imm ?: '  ', 2, ' ', STR_PAD_LEFT);
                $strAppo = substr($strAppo, 2, 2);
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo;

                // Spazi n.5
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 5, '~', STR_PAD_RIGHT);

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
                
                $strAppo = mb_str_pad($anno_patente ?: '  ', 2, ' ', STR_PAD_LEFT);
                $strAppo = substr($strAppo, 2, 2);
                $indTXT++;
                $esitoTXT[$indTXT] = $strAppo;                

                /* sul tracciato riga 325 non è specificato il numero di caratteri e se vuoto*/
                // Conducente durante lo svolgimento di attività lavorativa o in itinere
                $tipologia_incidente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_tipologia_incidente");
                $indTXT++;
                $esitoTXT[$indTXT] = $tipologia_incidente ?: ' ';

                // Spazi n.3
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad('', 3, '~', STR_PAD_RIGHT);

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
                        $esitoTXT[$indTXT] = '~';  // Esito non specificato
                        $indTXT++;
                        $esitoTXT[$indTXT] = '~~'; // Età non specificata
                        $indTXT++;
                        $esitoTXT[$indTXT] = '~';  // Sesso non specificato
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
                        $esitoTXT[$indTXT] = '~';  // Esito non specificato
                        $indTXT++;
                        $esitoTXT[$indTXT] = '~~'; // Età non specificata
                        $indTXT++;
                        $esitoTXT[$indTXT] = '~';  // Sesso non specificato

                    }
                }

                // Log per debug (rimuovi in produzione)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Veicolo {$numVeicolo}: Anteriori=" . count($trasportati_anteriori) . ", Posteriori=" . count($trasportati_posteriori));
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
            $esitoTXT[$indTXT] = mb_str_pad('', 9, '~', STR_PAD_RIGHT);

            //Specifiche sulla denominazione della strada 294-450
            // ===== DENOMINAZIONE STRADA COMPLETA 294-350 (57 caratteri) =====
            $strada_completa = $this->safe_meta_string($post_id, 'denominazione_strada');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad(substr($strada_completa ?: '', 0, 57), 57, '~', STR_PAD_RIGHT);

            // Spazi n.100 351-450
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 100, '~', STR_PAD_RIGHT);

            // ===== NOMINATIVI MORTI (4 morti massimo - 60 caratteri ciascuno) 451-690 =====
            for ($numMorto = 1; $numMorto <= 4; $numMorto++) {
                // Nome morto (30 caratteri)
                $nome_morto = $this->safe_meta_string($post_id, "morto_{$numMorto}_nome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($nome_morto ?: '', 0, 30), 30, '~', STR_PAD_RIGHT);
                // Cognome morto (30 caratteri)
                $cognome_morto = $this->safe_meta_string($post_id, "morto_{$numMorto}_cognome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($cognome_morto ?: '', 0, 30), 30, '~', STR_PAD_RIGHT);
            }

            // ===== NOMINATIVI FERITI (8 feriti massimo - 90 caratteri ciascuno) 691-1410 =====
            for ($numFerito = 1; $numFerito <= 8; $numFerito++) {
                // Nome ferito (30 caratteri)
                $nome_ferito = $this->safe_meta_string($post_id, "ferito_{$numFerito}_nome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($nome_ferito ?: '', 0, 30), 30, '~', STR_PAD_RIGHT);
                
                // Cognome ferito (30 caratteri)
                $cognome_ferito = $this->safe_meta_string($post_id, "ferito_{$numFerito}_cognome");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($cognome_ferito ?: '', 0, 30), 30, '~', STR_PAD_RIGHT);
                
                // Istituto ricovero (30 caratteri)
                $istituto = $this->safe_meta_string($post_id, "ferito_{$numFerito}_istituto");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad(substr($istituto ?: '', 0, 30), 30, '~', STR_PAD_RIGHT);
            }

            //Spazio riservato ISTAT per elaborazione 1411-1420
            //Spazi n.10
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 10, '~', STR_PAD_RIGHT);
            
            //Specifiche per la georeferenziazione 1421-1730
            // 1422- 1522 (campi facoltativi)
            // Tipo di coordinata (1 caratteri) 1421
            $tipo_coordinata = $this->safe_meta_string($post_id, 'tipo_coordinata');
            $indTXT++;
            $esitoTXT[$indTXT] = '1';
            //mb_str_pad($tipo_coordinata ?: '', 1, '~', STR_PAD_RIGHT);
            // Sistema di proiezione (1 caratteri) 1422
            $sistema_di_proiezione = $this->safe_meta_string($post_id, 'sistema_di_proiezione');
            $indTXT++;
            $esitoTXT[$indTXT] = '2';//mb_str_pad($sistema_di_proiezione ?: '', 1, '~', STR_PAD_RIGHT);
            // Longitudine (10 caratteri) 1423-1472
            $longitudine = $this->safe_meta_string($post_id, 'longitudine');
            $longitudine = str_replace(".", ",",$longitudine);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($longitudine ?: '', 50, '~', STR_PAD_LEFT);            
            // Latitudine (10 caratteri) 1473-1522
            $latitudine = $this->safe_meta_string($post_id, 'latitudine');
            $latitudine = str_replace(".", ",",$latitudine);
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($latitudine ?: '', 50, '~', STR_PAD_LEFT);
            //Spazio riservato ISTAT per elaborazione 1523-1530
            //Spazi n.8
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 8, '~', STR_PAD_RIGHT);          

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
            $esitoTXT[$indTXT] = mb_str_pad($codice_carabinieri ?: '', 30, '~', STR_PAD_RIGHT);

            //Campo 1565-1568: Progressiva chilometrica
            $progressiva_km = $this->safe_meta_string($post_id, 'progressiva_km');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($progressiva_km ?: '', 4, '0', STR_PAD_RIGHT);

            //Campo 1569-1571: Ettometrica
            $progressiva_m = $this->safe_meta_string($post_id, 'progressiva_m');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($progressiva_m ?: '', 3, '0', STR_PAD_RIGHT);

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
            $esitoTXT[$indTXT] = mb_str_pad('', 4, '~', STR_PAD_RIGHT);

            //Campo 1591-1690: Altra strada
            $altra_strada = $this->safe_meta_string($post_id, 'localizzazione_extra_ab');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($altra_strada ?: '', 100, '~', STR_PAD_RIGHT);
            
            //Campo 1691-1730: Località
            $localita_incidente = $this->safe_meta_string($post_id, 'localita_incidente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($localita_incidente ?: '', 40, '~', STR_PAD_RIGHT);
            
            //1731-1780: Riservato agli Enti in convenzione con Istat
            //Campo 1731-1770: Codice Identificativo Ente  
            $codice__ente = $this->safe_meta_string($post_id, 'codice__ente');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($codice__ente ?: '', 40, '~', STR_PAD_LEFT);
            
            //Spazio riservato ISTAT per elaborazione 1771-1780
            //Spazi n.10
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad('', 10, '~', STR_PAD_RIGHT);

            // ===== Specifiche per la registrazione delle informazioni sulla Cittadinanza dei conducenti dei veicoli A, B e C  (Posizioni 1781-1882) =====
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                //Codice cittadinanza del conducente veicolo
                $nazionalita_conducente = $this->safe_meta_string($post_id, "conducente_{$numVeicolo}_nazionalita");

                $parti_nazionalita = explode('-', $nazionalita_conducente);
                // Assegna le parti alla variabili separate
                $nazionalita_conducente = $parti_nazionalita[0];
                $nazionalita_altro_conducente = $parti_nazionalita[1];

                if($nazionalita_conducente==='000') {
                   $tipo_cittadinanza_conducente = '1'; // Default se non specificato
                } else {
                   $tipo_cittadinanza_conducente = '2';
                }
                 //Cittadinanza italiana o straniera del conducente veicolo
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($tipo_cittadinanza_conducente ?: ' ', 1, '~', STR_PAD_RIGHT);

                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($nazionalita_conducente ?: '', 3, '~', STR_PAD_RIGHT);

                //Descrizione cittadinanza conducente veicolo
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($nazionalita_altro_conducente ?: '', 30, '~', STR_PAD_RIGHT);

            }

            // ===== RIMORCHI 2020 (42 caratteri) 1883-1924=====
            
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Tipo rimorchio (4 caratteri)
                $tipo_rimorchio = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_tipo_rimorchio");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($tipo_rimorchio ?: '', 4, '~', STR_PAD_RIGHT);
                
                // Targa rimorchio (10 caratteri)
                $targa_rimorchio = $this->safe_meta_string($post_id, "veicolo_{$numVeicolo}_targa_rimorchio");
                $indTXT++;
                $esitoTXT[$indTXT] = mb_str_pad($targa_rimorchio ?: '', 10, '~', STR_PAD_RIGHT);
            }

            // ===== CODICE STRADA ACI (15 caratteri) - Posizioni 1925-1939 =====
            $codice_aci = $this->safe_meta_string($post_id, 'codice_strada_aci');
            $indTXT++;
            $esitoTXT[$indTXT] = mb_str_pad($codice_aci ?: '', 15, '~', STR_PAD_RIGHT);
            
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
            $esitoTXTstr = mb_str_pad($esitoTXTstr, 1939, ' ', STR_PAD_RIGHT);
            
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
        // Header CSV secondo la tua lista specifica
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
        
        // Inizia il CSV con gli header
        $output = '"' . implode('","', $headers) . '"' . "\n";
        
        // Processa ogni incidente
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $row = array();
            
            // IDIncidenti
            $row[] = $post_id;
            
            // Data
            $data_incidente = $this->safe_meta_string($post_id, 'data_incidente');
            $row[] = $data_incidente;
            
            // Ora
            $ora_incidente = $this->safe_meta_string($post_id, 'ora_incidente');
            $row[] = $ora_incidente;
            
            // IDProv
            $row[] = $this->safe_meta_string($post_id, 'provincia_incidente');
            
            // IDCom
            $row[] = $this->safe_meta_string($post_id, 'comune_incidente');
            
            // IDTipoIncidente
            $row[] = $this->safe_meta_string($post_id, 'natura_incidente');
            
            // IDTipoStrada
            $row[] = $this->safe_meta_string($post_id, 'csv_tipo_strada');
            
            // CentroAbitato
            $row[] = $this->safe_meta_string($post_id, 'csv_centro_abitato');
            
            // IDPolizia
            $row[] = $this->safe_meta_string($post_id, 'organo_rilevazione');
            
            // IDCaratteristiche
            $row[] = $this->safe_meta_string($post_id, 'csv_caratteristiche');
            
            // CantiereStradale
            $row[] = $this->safe_meta_string($post_id, 'csv_cantiere_stradale');
            
            // Conteggi veicoli - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autovettura');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autocarro_35t');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autocarro_oltre_35t');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autotreno');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autoarticolato');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_autobus');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_tram');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_treno');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_motociclo');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_ciclomotore');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_velocipede');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_bicicletta_assistita');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_monopattini');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_altri_micromobilita');
            $row[] = $this->safe_meta_string($post_id, 'csv_n_altri_veicoli');

            // Trasportanti merci pericolose - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_trasportanti_merci_pericolose');
            
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
            $row[] = $this->safe_meta_string($post_id, 'denominazione_strada');
            $row[] = $this->safe_meta_string($post_id, 'progressiva_km');
            $row[] = $this->safe_meta_string($post_id, 'progressiva_m');
            $row[] = $this->safe_meta_string($post_id, 'geometria_strada');
            
            // Circostanze - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_omissione');

            // Contromano - NUOVO: usa i campi CSV (più semplice)
            $row[] = $this->safe_meta_string($post_id, 'csv_contromano');

            // Dettaglio persone - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_dettaglio_persone_decedute');

            // idPositivita - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_positivita');

            // ArtCds - NUOVO: usa i campi CSV
            $row[] = $this->safe_meta_string($post_id, 'csv_art_cds');
            
            // Coordinate
            $row[] = $this->safe_meta_string($post_id, 'latitudine');
            $row[] = $this->safe_meta_string($post_id, 'longitudine');
            
            // Aggiungi la riga al CSV
            $output .= '"' . implode('","', array_map('str_replace', array_fill(0, count($row), '"'), array_fill(0, count($row), '""'), $row)) . '"' . "\n";
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
}