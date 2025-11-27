<?php
/**
 * Incidenti Import ISTAT Functions - VERSIONE ULTRA-OTTIMIZZATA
 * 
 * @package IncidentiStradali
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class IncidentiIstatImportFunctions {
    
    /**
     * Cache per controllo duplicati ottimizzato
     */
    private $duplicate_cache = array();
    private $duplicate_cache_loaded = false;
    
    private function get_comune_name($codice) {
        $comuni = array(
            '002' => 'Alessano', '003' => 'Alezio', '004' => 'Alliste', '005' => 'Andrano',
            '006' => 'Aradeo', '007' => 'Arnesano', '008' => 'Bagnolo Del Salento', '009' => 'Botrugno',
            '010' => 'Calimera', '011' => 'Campi Salentina', '012' => 'Cannole', '013' => 'Caprarica di Lecce',
            '014' => 'Carmiano', '015' => 'Carpignano Salentino', '016' => 'Casarano', '017' => 'Castri Di Lecce',
            '018' => 'Castrignano De` Greci', '019' => 'Castrignano Del Capo', '096' => 'Castro',
            '020' => 'Cavallino', '021' => 'Collepasso', '022' => 'Copertino', '023' => 'Corigliano D`Otranto',
            '024' => 'Corsano', '025' => 'Cursi', '026' => 'Cutrofiano', '027' => 'Diso',
            '028' => 'Gagliano Del Capo', '029' => 'Galatina', '030' => 'Galatone', '031' => 'Gallipoli',
            '032' => 'Giuggianello', '033' => 'Giurdignano', '034' => 'Guagnano', '035' => 'Lecce',
            '036' => 'Lequile', '037' => 'Leverano', '038' => 'Lizzanello', '039' => 'Maglie',
            '040' => 'Martano', '041' => 'Martignano', '042' => 'Matino', '043' => 'Melendugno',
            '044' => 'Melissano', '045' => 'Melpignano', '046' => 'Miggiano', '047' => 'Minervino Di Lecce',
            '048' => 'Monteroni Di Lecce', '049' => 'Montesano Salentino', '050' => 'Morciano Di Leuca',
            '051' => 'Muro Leccese', '052' => 'Nardo`', '053' => 'Neviano', '054' => 'Nociglia',
            '055' => 'Novoli', '056' => 'Ortelle', '057' => 'Otranto', '058' => 'Palmariggi',
            '059' => 'Parabita', '060' => 'Patu`', '061' => 'Poggiardo', '097' => 'Porto Cesareo',
            '098' => 'Presicce-Acquarica', '063' => 'Racale', '064' => 'Ruffano', '065' => 'Salice Salentino',
            '066' => 'Salve', '095' => 'San Cassiano', '068' => 'San Cesario Di Lecce',
            '069' => 'San Donato Di Lecce', '071' => 'San Pietro In Lama', '067' => 'Sanarica',
            '070' => 'Sannicola', '072' => 'Santa Cesarea Terme', '073' => 'Scorrano', '074' => 'Secli`',
            '075' => 'Sogliano Cavour', '076' => 'Soleto', '077' => 'Specchia', '078' => 'Spongano',
            '079' => 'Squinzano', '080' => 'Sternatia', '081' => 'Supersano', '082' => 'Surano',
            '083' => 'Surbo', '084' => 'Taurisano', '085' => 'Taviano', '086' => 'Tiggiano',
            '087' => 'Trepuzzi', '088' => 'Tricase', '089' => 'Tuglie', '090' => 'Ugento',
            '091' => 'Uggiano La Chiesa', '092' => 'Veglie', '093' => 'Vernole', '094' => 'Zollino'
        );
        return isset($comuni[$codice]) ? $comuni[$codice] : $codice;
    }

    public function __construct() {
        // Aumenta limiti PHP per import pesanti
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '600');
        
        add_action('admin_menu', array($this, 'add_import_istat_menu'), 22);
        add_action('admin_post_import_incidenti_istat_txt', array($this, 'handle_txt_import'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts')); 
    }
    
    /**
     * Aggiungi menu import ISTAT
     */
    public function add_import_istat_menu() {
        add_submenu_page(
            'edit.php?post_type=incidente_stradale',
            __('Importa ISTAT', 'incidenti-stradali'),
            __('Importa ISTAT', 'incidenti-stradali'),
            'edit_posts',
            'incidenti-import-istat',
            array($this, 'import_page')
        );
    }
    
    /**
     * Pagina import
     */
    public function import_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'incidenti-stradali'));
        }
        
        $message = '';
        $message_type = '';

        if (isset($_GET['imported'])) {
            $imported = intval($_GET['imported']);
            $errors = intval($_GET['errors']);
            $duplicates_param = isset($_GET['duplicates']) ? sanitize_text_field($_GET['duplicates']) : '';
            
            $message = sprintf(__('Importazione completata: %d incidenti importati, %d errori.', 'incidenti-stradali'), $imported, $errors);
            
            if (!empty($duplicates_param)) {
                $duplicate_codes = explode(',', $duplicates_param);
                $duplicate_count = count($duplicate_codes);
                $message .= ' ' . sprintf(__('%d incidenti non importati perché duplicati (codici: %s).', 'incidenti-stradali'), 
                    $duplicate_count, 
                    implode(', ', array_slice($duplicate_codes, 0, 10))
                );
            }
            
            $message_type = $errors > 0 ? 'warning' : 'success';
        }
        
        if (isset($_GET['error'])) {
            $message = sanitize_text_field($_GET['error']);
            $message_type = 'error';
        }
        
        include INCIDENTI_PLUGIN_PATH . 'templates/admin-import-istat-page.php';
    }
    
    /**
     * Gestisce l'importazione TXT
     */
    public function handle_txt_import() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        if (!wp_verify_nonce($_POST['import_nonce'], 'import_incidenti_nonce')) {
            wp_die(__('Errore di sicurezza.', 'incidenti-stradali'));
        }
        
        $uploaded_file = $this->handle_file_upload();
        if (is_wp_error($uploaded_file)) {
            $this->redirect_with_error($uploaded_file->get_error_message());
            return;
        }
        
        $result = $this->process_txt_file($uploaded_file);
        
        // Cleanup uploaded file
        wp_delete_file($uploaded_file);
        
        $redirect_url = admin_url('edit.php?post_type=incidente_stradale&page=incidenti-import-istat');

        if ($result['success']) {
            $redirect_url .= '&imported=' . $result['imported'] . '&errors=' . $result['errors'];
            if (!empty($result['duplicates'])) {
                $redirect_url .= '&duplicates=' . implode(',', $result['duplicates']);
            }
        } else {
            $redirect_url .= '&error=' . urlencode($result['message']);
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload() {
        if (!isset($_FILES['txt_file']) || $_FILES['txt_file']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Errore durante l\'upload del file.', 'incidenti-stradali'));
        }
        
        $file = $_FILES['txt_file'];
        
        // Validazione tipo file
        $allowed_types = array('text/plain');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) && $file_type['ext'] !== 'txt') {
            return new WP_Error('invalid_file', __('Il file deve essere in formato TXT.', 'incidenti-stradali'));
        }
        
        // Validazione dimensione (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Il file è troppo grande. Dimensione massima: 10MB.', 'incidenti-stradali'));
        }
        
        // Sposta file nella directory temporanea
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/temp_import_' . uniqid() . '.txt';
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            return new WP_Error('move_error', __('Impossibile salvare il file temporaneo.', 'incidenti-stradali'));
        }
        
        return $temp_file;
    }
    
    /**
     * Process full TXT file - VERSIONE ULTRA-OTTIMIZZATA
     */
    private function process_txt_file($file_path) {      
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => __('Impossibile leggere il file.', 'incidenti-stradali'));
        }

        // ===== OTTIMIZZAZIONI PERFORMANCE =====
        // Disabilita revisioni durante import
        add_filter('wp_revisions_to_keep', '__return_false');

        // Disabilita altri hook che rallentano
        remove_action('post_updated', 'wp_save_post_revision');
        remove_action('save_post', 'wp_save_post_revision');

        // Aumenta buffer lettura file
        stream_set_chunk_size($handle, 8192);

        // Disabilita term counting per velocizzare
        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        // Carica cache duplicati UNA VOLTA (se necessario)
        // $this->load_duplicate_cache();
        // ===== FINE OTTIMIZZAZIONI =====

        $imported = 0;
        $errors = 0;
        $duplicate_codes = array();
        $line_number = 1;
        
        // Batch processing per ridurre pressione memoria
        $batch_size = 100;
        $batch = array();
        
        while (($row = fgets($handle)) !== false) {
            $row = trim($row);
            if (empty($row)) {
                continue;
            }
            
            $batch[] = $row;
            
            // Processa batch quando raggiunge la dimensione
            if (count($batch) >= $batch_size) {
                foreach ($batch as $line) {
                    $mapped_data = $this->map_row_data($line);
                    
                    // Controllo anti-duplicazione (commentato per ora)
                    /* $duplicate_check = $this->check_for_duplicates($mapped_data);
                    if ($duplicate_check['is_duplicate']) {
                        $errors++;
                        $duplicate_codes[] = $duplicate_check['existing_post_id'];
                        error_log("Incidente riga $line_number non importato - duplicato di post ID: " . $duplicate_check['existing_post_id']);
                        $line_number++;
                        continue;
                    } */
                    
                    $post_id = $this->create_incidente_from_data($mapped_data);
                    if ($post_id) {
                        $imported++;
                    } else {
                        $errors++;
                        error_log("Errore creazione incidente riga $line_number");
                    }
                    $line_number++;
                }
                
                $batch = array(); // Reset batch
                
                // Libera memoria ogni batch
                gc_collect_cycles();
            }
        }
        
        // Processa righe rimaste nel batch
        foreach ($batch as $line) {
            $mapped_data = $this->map_row_data($line);
            
            /* $duplicate_check = $this->check_for_duplicates($mapped_data);
            if ($duplicate_check['is_duplicate']) {
                $errors++;
                $duplicate_codes[] = $duplicate_check['existing_post_id'];
                error_log("Incidente riga $line_number non importato - duplicato di post ID: " . $duplicate_check['existing_post_id']);
                $line_number++;
                continue;
            } */
            
            $post_id = $this->create_incidente_from_data($mapped_data);
            if ($post_id) {
                $imported++;
            } else {
                $errors++;
                error_log("Errore creazione incidente riga $line_number");
            }
            $line_number++;
        }

        // ===== RIABILITA TUTTO =====
        remove_filter('wp_revisions_to_keep', '__return_false');
        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);
        // ===== FINE RIABILITAZIONE =====

        fclose($handle);
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'duplicates' => $duplicate_codes
        );
    }

    /**
     * Carica cache duplicati UNA VOLTA
     */
    private function load_duplicate_cache() {
        global $wpdb;
        
        error_log("Caricamento cache duplicati in memoria...");
        
        $results = $wpdb->get_results("
            SELECT 
                p.ID,
                MAX(CASE WHEN pm.meta_key = 'data_incidente' THEN pm.meta_value END) as data_inc,
                MAX(CASE WHEN pm.meta_key = 'ora_incidente' THEN pm.meta_value END) as ora_inc,
                MAX(CASE WHEN pm.meta_key = 'comune_incidente' THEN pm.meta_value END) as comune_inc,
                MAX(CASE WHEN pm.meta_key = 'latitudine' THEN pm.meta_value END) as lat,
                MAX(CASE WHEN pm.meta_key = 'longitudine' THEN pm.meta_value END) as lng
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'incidente_stradale'
            AND p.post_status = 'publish'
            AND pm.meta_key IN ('data_incidente', 'ora_incidente', 'comune_incidente', 'latitudine', 'longitudine')
            GROUP BY p.ID
        ", ARRAY_A);
        
        foreach ($results as $row) {
            $cache_key = sprintf(
                '%s_%s_%s_%s_%s',
                $row['data_inc'],
                trim($row['ora_inc']),
                $row['comune_inc'],
                $row['lat'],
                $row['lng']
            );
            $this->duplicate_cache[$cache_key] = $row['ID'];
        }
        
        $this->duplicate_cache_loaded = true;
        error_log("Cache duplicati caricata: " . count($this->duplicate_cache) . " incidenti");
    }

    /**
     * Check for duplicate incidents - VERSIONE OTTIMIZZATA
     */
    private function check_for_duplicates($data) {
        if (!$this->duplicate_cache_loaded) {
            $this->load_duplicate_cache();
        }
        
        $cache_key = sprintf(
            '%s_%s_%s_%s_%s',
            $data['data_incidente'],
            trim($data['ora_incidente']),
            $data['comune_incidente'],
            $data['latitudine'],
            $data['longitudine']
        );
        
        if (isset($this->duplicate_cache[$cache_key])) {
            return array(
                'is_duplicate' => true,
                'existing_post_id' => $this->duplicate_cache[$cache_key]
            );
        }
        
        return array(
            'is_duplicate' => false,
            'existing_post_id' => null
        );
    }
     
    /**
     * Map row data using field mapping
     */
    private function map_row_data($row) {
         $mapped_data =  array(
            "data_incidente" => "0000-00-00",
            "provincia_incidente" => "000",
            "comune_incidente" => "000",
            "organo_rilevazione" => " ",
            "organo_coordinatore" => " ",
            "tipo_strada" => " ",
            "numero_strada" => "   ",
            "illuminazione" => " ",
            "tronco_strada" => "  ",
            "geometria_strada" => " ",
            "pavimentazione_strada" => " ",
            "intersezione_tronco" => "  ",
            "stato_fondo_strada" => " ",
            "segnaletica_strada" => " ",
            "condizioni_meteo" => " ",
            "dettaglio_natura" => "  ",
            "veicolo_1_tipo" => "  ",
            "veicolo_2_tipo" => "  ",
            "veicolo_3_tipo" => "  ",
            "veicolo_1_peso_totale" => "    ",
            "veicolo_2_peso_totale" => "    ",
            "veicolo_3_peso_totale" => "    ",
            "circostanza_veicolo_a" => "  ",
            "difetto_veicolo_a" => "  ",
            "stato_psicofisico_a" => "  ",
            "circostanza_veicolo_b" => "  ",
            "difetto_veicolo_b" => "  ",
            "stato_psicofisico_b" => "  ",
            "veicolo_1_targa" => "        ",
            "veicolo_1_sigla_estero" => "   ",
            "veicolo_1_anno_immatricolazione" => "  ",
            "veicolo_2_targa" => "        ",
            "veicolo_2_sigla_estero" => "   ",
            "veicolo_2_anno_immatricolazione" => "  ",
            "veicolo_3_targa" => "        ",
            "veicolo_3_sigla_estero" => "   ",
            "veicolo_3_anno_immatricolazione" => "  ",
            "conducente_1_eta" => "  ",
            "conducente_1_sesso" => " ",
            "conducente_1_esito" => " ",
            "conducente_1_tipo_patente" => " ",
            "conducente_1_anno_patente" => "  ",
            "conducente_1_tipologia_incidente" => " ",
            "veicolo_1_trasportato_1_esito" => " ",
            "veicolo_1_trasportato_1_eta" => "  ",
            "veicolo_1_trasportato_1_sesso" => " ",
            "veicolo_1_trasportato_2_esito" => " ",
            "veicolo_1_trasportato_2_eta" => "  ",
            "veicolo_1_trasportato_2_sesso" => " ",
            "veicolo_1_trasportato_3_esito" => " ",
            "veicolo_1_trasportato_3_eta" => "  ",
            "veicolo_1_trasportato_3_sesso" => " ",
            "veicolo_1_trasportato_4_esito" => " ",
            "veicolo_1_trasportato_4_eta" => "  ",
            "veicolo_1_trasportato_4_sesso" => " ",
            "veicolo_1_altri_morti_maschi" => "  ",
            "veicolo_1_altri_morti_femmine" => "  ",
            "veicolo_1_altri_feriti_maschi" => "  ",
            "veicolo_1_altri_feriti_femmine" => "  ",
            "conducente_2_eta" => "  ",
            "conducente_2_sesso" => " ",
            "conducente_2_esito" => " ",
            "conducente_2_tipo_patente" => " ",
            "conducente_2_anno_patente" => "  ",
            "conducente_2_tipologia_incidente" => " ",
            "veicolo_2_trasportato_1_esito" => " ",
            "veicolo_2_trasportato_1_eta" => "  ",
            "veicolo_2_trasportato_1_sesso" => " ",
            "veicolo_2_trasportato_2_esito" => " ",
            "veicolo_2_trasportato_2_eta" => "  ",
            "veicolo_2_trasportato_2_sesso" => " ",
            "veicolo_2_trasportato_3_esito" => " ",
            "veicolo_2_trasportato_3_eta" => "  ",
            "veicolo_2_trasportato_3_sesso" => " ",
            "veicolo_2_trasportato_4_esito" => " ",
            "veicolo_2_trasportato_4_eta" => "  ",
            "veicolo_2_trasportato_4_sesso" => " ",
            "veicolo_2_altri_morti_maschi" => "  ",
            "veicolo_2_altri_morti_femmine" => "  ",
            "veicolo_2_altri_feriti_maschi" => "  ",
            "veicolo_2_altri_feriti_femmine" => "  ",
            "conducente_3_eta" => "  ",
            "conducente_3_sesso" => " ",
            "conducente_3_esito" => " ",
            "conducente_3_tipo_patente" => " ",
            "conducente_3_anno_patente" => "  ",
            "conducente_3_tipologia_incidente" => " ",
            "veicolo_3_trasportato_1_esito" => " ",
            "veicolo_3_trasportato_1_eta" => "  ",
            "veicolo_3_trasportato_1_sesso" => " ",
            "veicolo_3_trasportato_2_esito" => " ",
            "veicolo_3_trasportato_2_eta" => "  ",
            "veicolo_3_trasportato_2_sesso" => " ",
            "veicolo_3_trasportato_3_esito" => " ",
            "veicolo_3_trasportato_3_eta" => "  ",
            "veicolo_3_trasportato_3_sesso" => " ",
            "veicolo_3_trasportato_4_esito" => " ",
            "veicolo_3_trasportato_4_eta" => "  ",
            "veicolo_3_trasportato_4_sesso" => " ",
            "veicolo_3_altri_morti_maschi" => "  ",
            "veicolo_3_altri_morti_femmine" => "  ",
            "veicolo_3_altri_feriti_maschi" => "  ",
            "veicolo_3_altri_feriti_femmine" => "  ",
            "pedone_morto_1_sesso" => "  ",
            "pedone_morto_1_eta" => "  ",
            "pedone_morto_2_sesso" => "  ",
            "pedone_morto_2_eta" => "  ",
            "pedone_morto_3_sesso" => "  ",
            "pedone_morto_3_eta" => "  ",
            "pedone_morto_4_sesso" => "  ",
            "pedone_morto_4_eta" => "  ",
            "pedone_ferito_1_sesso" => "  ",
            "pedone_ferito_1_eta" => "  ",
            "pedone_ferito_2_sesso" => "  ",
            "pedone_ferito_2_eta" => "  ",
            "pedone_ferito_3_sesso" => "  ",
            "pedone_ferito_3_eta" => "  ",
            "pedone_ferito_4_sesso" => "  ",
            "pedone_ferito_4_eta" => "  ",
            "numero_altri_veicoli" => "  ",
            "altri_morti_maschi" => "  ",
            "altri_morti_femmine" => "  ",
            "altri_feriti_maschi" => "  ",
            "altri_feriti_femmine" => "  ",
            "riepilogo_morti_24h" => "  ",
            "riepilogo_morti_2_30gg" => "  ",
            "riepilogo_feriti" => "  ",
            "denominazione_strada" => "",
            "morto_1_nome" => "",
            "morto_1_cognome" => "",
            "morto_2_nome" => "",   
            "morto_2_cognome" => "",
            "morto_3_nome" => "",
            "morto_3_cognome" => "",
            "morto_4_nome" => "",
            "morto_4_cognome" => "",
            "ferito_1_nome" => "",
            "ferito_1_cognome" => "",
            "ferito_1_istituto" => "",
            "ferito_2_nome" => "",
            "ferito_2_cognome" => "",
            "ferito_2_istituto" => "",
            "ferito_3_nome" => "",
            "ferito_3_cognome" => "",
            "ferito_3_istituto" => "",
            "ferito_4_nome" => "",
            "ferito_4_cognome" => "",
            "ferito_4_istituto" => "",
            "ferito_5_nome" => "",
            "ferito_5_cognome" => "",
            "ferito_5_istituto" => "",
            "ferito_6_nome" => "",
            "ferito_6_cognome" => "",
            "ferito_6_istituto" => "",
            "ferito_7_nome" => "",
            "ferito_7_cognome" => "",
            "ferito_7_istituto" => "",
            "ferito_8_nome" => "",
            "ferito_8_cognome" => "",
            "ferito_8_istituto" => "",
            "tipo_coordinata" => "1",
            "sistema_di_proiezione" => "2",
            "longitudine" => "",
            "latitudine" => "",
            "ora_incidente" => "",
            "minuti_incidente" => "",
            "codice_carabinieri" => "",
            "progressiva_km" => "",
            "progressiva_m" => "",
            "veicolo_1_cilindrata" => "",
            "veicolo_2_cilindrata" => "",
            "veicolo_3_cilindrata" => "",
            "localizzazione_extra_ab" => "",
            "localita_incidente" => "",
            "codice__ente" => "",
            "conducente_1_nazionalita" => "",
            "conducente_1_nazionalita_altro" => "",
            "conducente_2_nazionalita" => "",
            "conducente_2_nazionalita_altro" => "",
            "conducente_3_nazionalita" => "",
            "conducente_3_nazionalita_altro" => "",
            "veicolo_1_tipo_rimorchio" => "",
            "veicolo_1_targa_rimorchio" => "",
            "veicolo_2_tipo_rimorchio" => "",
            "veicolo_2_targa_rimorchio" => "",
            "veicolo_3_tipo_rimorchio" => "",
            "veicolo_3_targa_rimorchio" => "",
            "codice_strada_aci" => "",
            "ente_rilevatore" => "",
            "natura_incidente" => "A",
            "numero_veicoli_coinvolti" => "1",
            "numero_pedoni_feriti" => "0",
            "numero_pedoni_morti" => "0",
            "veicolo_1_numero_trasportati" => "0",
            "veicolo_2_numero_trasportati" => "0",
            "veicolo_3_numero_trasportati" => "0",
            "veicolo_1_trasportato_1_sedile" => "",
            "veicolo_1_trasportato_2_sedile" => "",
            "veicolo_1_trasportato_3_sedile" => "",
            "veicolo_1_trasportato_4_sedile" => "",
            "veicolo_2_trasportato_1_sedile" => "",
            "veicolo_2_trasportato_2_sedile" => "",
            "veicolo_2_trasportato_3_sedile" => "",
            "veicolo_2_trasportato_4_sedile" => "",
            "veicolo_3_trasportato_1_sedile" => "",
            "veicolo_3_trasportato_2_sedile" => "",
            "veicolo_3_trasportato_3_sedile" => "",
            "veicolo_3_trasportato_4_sedile" => "",
        );
        
        $numero_veicoli_coinvolti = 0;
        $numero_pedoni_feriti = 0;
        $numero_pedoni_morti = 0;

        mb_internal_encoding("UTF-8");

        // Estrai i campi dal tracciato
        $data_incidente_anno = trim(mb_substr($row, 0, 2));
        $data_incidente_mese = trim(mb_substr($row, 2, 2));
        $mapped_data["provincia_incidente"] = trim(mb_substr($row, 4, 3));
        $mapped_data["comune_incidente"] = trim(mb_substr($row, 7, 3));
        $data_incidente_giorno = trim(mb_substr($row, 14, 2));
        $mapped_data["organo_rilevazione"] = trim(mb_substr($row, 18, 1));
        
        if($mapped_data["organo_rilevazione"] == "1") {
            $mapped_data["ente_rilevatore"] = "Agente di Polizia stradale";
        } elseif($mapped_data["organo_rilevazione"] == "2") {
            $mapped_data["ente_rilevatore"] = "Carabiniere";
        } elseif($mapped_data["organo_rilevazione"] == "3") {
            $mapped_data["ente_rilevatore"] = "Agente di Pubblica sicurezza";
        } elseif($mapped_data["organo_rilevazione"] == "4") {
             $mapped_data["ente_rilevatore"] = "POLIZIA MUNICIPALE DI " . strtoupper($this->get_comune_name($mapped_data["comune_incidente"]));
        } elseif($mapped_data["organo_rilevazione"] == "5") {
            $mapped_data["ente_rilevatore"] = "Altri";
        } elseif($mapped_data["organo_rilevazione"] == "6") {
            $mapped_data["ente_rilevatore"] = "Agente di Polizia provinciale";
        }
        
        $mapped_data["organo_coordinatore"] = trim(mb_substr($row, 24, 1));
        $mapped_data["tipo_strada"] = trim(mb_substr($row, 25, 1));
        $mapped_data["numero_strada"] = trim(mb_substr($row, 26, 3));
        $mapped_data["illuminazione"] = trim(mb_substr($row, 29, 1));
        $mapped_data["tronco_strada"] = str_replace("0","",trim(mb_substr($row, 32, 2)));    
        $mapped_data["geometria_strada"] = trim(mb_substr($row, 34, 1));
        $mapped_data["pavimentazione_strada"] = trim(mb_substr($row, 35, 1));
        $mapped_data["intersezione_tronco"] = str_replace("0","",trim(mb_substr($row, 36, 2)));
        $mapped_data["stato_fondo_strada"] = trim(mb_substr($row, 38, 1));
        $mapped_data["segnaletica_strada"] = trim(mb_substr($row, 39, 1));
        $mapped_data["condizioni_meteo"] = trim(mb_substr($row, 40, 1));
        $mapped_data["dettaglio_natura"] = trim(mb_substr($row, 41, 2));
        
        if (preg_match('/^(01|02|03|04)$/', $mapped_data["dettaglio_natura"])) {
            $mapped_data["natura_incidente"] = 'A';
            $mapped_data["dettaglio_natura"] = str_replace("0","",$mapped_data["dettaglio_natura"]);
        } elseif (preg_match('/^(05)$/',  $mapped_data["dettaglio_natura"])) {
            $mapped_data["natura_incidente"] = 'B';
             $mapped_data["dettaglio_natura"] = str_replace("0","",$mapped_data["dettaglio_natura"]);
        } elseif (preg_match('/^(06|07|08|09)$/',  $mapped_data["dettaglio_natura"])) {
            $mapped_data["natura_incidente"] = 'C';
             $mapped_data["dettaglio_natura"] = str_replace("0","",$mapped_data["dettaglio_natura"]);
        } elseif (preg_match('/^(10|11|12)$/', $mapped_data["dettaglio_natura"])) {
            $mapped_data["natura_incidente"] = 'D';
        }
        
        // Tipo di veicoli coinvolti
        $mapped_data["veicolo_1_tipo"] = ltrim(trim(mb_substr($row, 43, 2)), '0');
        if ($mapped_data["veicolo_1_tipo"] !== '  ') {
            $numero_veicoli_coinvolti++;
        }
        $mapped_data["veicolo_2_tipo"] = ltrim(trim(mb_substr($row, 45, 2)), '0');
        if ($mapped_data["veicolo_2_tipo"] !== '  ') {
            $numero_veicoli_coinvolti++;
        }
        $mapped_data["veicolo_3_tipo"] = ltrim(trim(mb_substr($row, 47, 2)), '0');
        if ($mapped_data["veicolo_3_tipo"] !== '  ') {
            $numero_veicoli_coinvolti++;
        }
        
        $mapped_data["veicolo_1_peso_totale"] = trim(mb_substr($row, 61, 4));
        $mapped_data["veicolo_2_peso_totale"] = trim(mb_substr($row, 65, 4));
        $mapped_data["veicolo_3_peso_totale"] = trim(mb_substr($row, 69, 4));
        $mapped_data["numero_veicoli_coinvolti"] = $numero_veicoli_coinvolti;

        // Circostanze accertate o presunte dell'incidente
        $mapped_data["circostanza_veicolo_a"] = trim(mb_substr($row, 73, 2));
        $mapped_data["difetto_veicolo_a"] = trim(mb_substr($row, 75, 2));
        $mapped_data["stato_psicofisico_a"] = trim(mb_substr($row, 77, 2));
        $mapped_data["circostanza_veicolo_b"] = trim(mb_substr($row, 79, 2));
        $mapped_data["difetto_veicolo_b"] = trim(mb_substr($row, 81, 2));
        $mapped_data["stato_psicofisico_b"] = trim(mb_substr($row, 83, 2));

        // Veicoli coinvolti
        $mapped_data["veicolo_1_targa"] = trim(mb_substr($row, 85, 8));
        $mapped_data["veicolo_1_sigla_estero"] = trim(mb_substr($row, 93, 3));
        $mapped_data["veicolo_1_anno_immatricolazione"] = trim(mb_substr($row, 96, 2));
        $mapped_data["veicolo_2_targa"] = trim(mb_substr($row, 103, 8));
        $mapped_data["veicolo_2_sigla_estero"] = trim(mb_substr($row, 111, 3));
        $mapped_data["veicolo_2_anno_immatricolazione"] = trim(mb_substr($row, 114, 2));
        $mapped_data["veicolo_3_targa"] = trim(mb_substr($row, 121, 8));
        $mapped_data["veicolo_3_sigla_estero"] = trim(mb_substr($row, 129, 3));
        $mapped_data["veicolo_3_anno_immatricolazione"] = trim(mb_substr($row, 132, 2));

        // Conseguenze dell'incidente alle persone
        // Veicolo A: conducente
        $mapped_data["conducente_1_eta"] = trim(mb_substr($row, 139, 2));
        $mapped_data["conducente_1_sesso"] = trim(mb_substr($row, 141, 1));
        $mapped_data["conducente_1_esito"] = trim(mb_substr($row, 142, 1));
        $mapped_data["conducente_1_tipo_patente"] = trim(mb_substr($row, 143, 1));
        $mapped_data["conducente_1_anno_patente"] = trim(mb_substr($row, 144, 2));
        $mapped_data["conducente_1_tipologia_incidente"] = trim(mb_substr($row, 146, 1));
        
        // Passeggeri veicolo A
        $mapped_data["veicolo_1_trasportato_1_esito"] = trim(mb_substr($row, 150, 1));
        $mapped_data["veicolo_1_trasportato_1_eta"] = trim(mb_substr($row, 151, 2));
        $mapped_data["veicolo_1_trasportato_1_sesso"] = trim(mb_substr($row, 153, 1));
        $mapped_data["veicolo_1_trasportato_2_esito"] = trim(mb_substr($row, 154, 1));
        $mapped_data["veicolo_1_trasportato_2_eta"] = trim(mb_substr($row, 155, 2));
        $mapped_data["veicolo_1_trasportato_2_sesso"] = trim(mb_substr($row, 157, 1));
        $mapped_data["veicolo_1_trasportato_3_esito"] = trim(mb_substr($row, 158, 1));
        $mapped_data["veicolo_1_trasportato_3_eta"] = trim(mb_substr($row, 159, 2));
        $mapped_data["veicolo_1_trasportato_3_sesso"] = trim(mb_substr($row, 161, 1));
        $mapped_data["veicolo_1_trasportato_4_esito"] = trim(mb_substr($row, 162, 1));
        $mapped_data["veicolo_1_trasportato_4_eta"] = trim(mb_substr($row, 163, 2));
        $mapped_data["veicolo_1_trasportato_4_sesso"] = trim(mb_substr($row, 165, 1));

        // Altri passeggeri infortunati sul veicolo A
        $mapped_data["veicolo_1_altri_morti_maschi"] = trim(mb_substr($row, 166, 2));
        $mapped_data["veicolo_1_altri_morti_femmine"] = trim(mb_substr($row, 168, 2));
        $mapped_data["veicolo_1_altri_feriti_maschi"] = trim(mb_substr($row, 170, 2));
        $mapped_data["veicolo_1_altri_feriti_femmine"] = trim(mb_substr($row, 172, 2));

        // Veicolo B: conducente
        $mapped_data["conducente_2_eta"] = trim(mb_substr($row, 174, 2));
        $mapped_data["conducente_2_sesso"] = trim(mb_substr($row, 176, 1));
        $mapped_data["conducente_2_esito"] = trim(mb_substr($row, 177, 1));
        $mapped_data["conducente_2_tipo_patente"] = trim(mb_substr($row, 178, 1));
        $mapped_data["conducente_2_anno_patente"] = trim(mb_substr($row, 179, 2));
        $mapped_data["conducente_2_tipologia_incidente"] = trim(mb_substr($row, 181, 1));
        
        // Passeggeri veicolo B
        $mapped_data["veicolo_2_trasportato_1_esito"] = trim(mb_substr($row, 185, 1));
        $mapped_data["veicolo_2_trasportato_1_eta"] = trim(mb_substr($row, 186, 2));
        $mapped_data["veicolo_2_trasportato_1_sesso"] = trim(mb_substr($row, 188, 1));
        $mapped_data["veicolo_2_trasportato_2_esito"] = trim(mb_substr($row, 189, 1));
        $mapped_data["veicolo_2_trasportato_2_eta"] = trim(mb_substr($row, 190, 2));
        $mapped_data["veicolo_2_trasportato_2_sesso"] = trim(mb_substr($row, 192, 1));
        $mapped_data["veicolo_2_trasportato_3_esito"] = trim(mb_substr($row, 193, 1));
        $mapped_data["veicolo_2_trasportato_3_eta"] = trim(mb_substr($row, 194, 2));
        $mapped_data["veicolo_2_trasportato_3_sesso"] = trim(mb_substr($row, 196, 1));
        $mapped_data["veicolo_2_trasportato_4_esito"] = trim(mb_substr($row, 197, 1));
        $mapped_data["veicolo_2_trasportato_4_eta"] = trim(mb_substr($row, 198, 2));
        $mapped_data["veicolo_2_trasportato_4_sesso"] = trim(mb_substr($row, 200, 1));

        // Altri passeggeri infortunati sul veicolo B
        $mapped_data["veicolo_2_altri_morti_maschi"] = trim(mb_substr($row, 201, 2));
        $mapped_data["veicolo_2_altri_morti_femmine"] = trim(mb_substr($row, 203, 2));
        $mapped_data["veicolo_2_altri_feriti_maschi"] = trim(mb_substr($row, 205, 2));
        $mapped_data["veicolo_2_altri_feriti_femmine"] = trim(mb_substr($row, 207, 2));

        // Veicolo C: conducente
        $mapped_data["conducente_3_eta"] = trim(mb_substr($row, 209, 2));
        $mapped_data["conducente_3_sesso"] = trim(mb_substr($row, 211, 1));
        $mapped_data["conducente_3_esito"] = trim(mb_substr($row, 212, 1));
        $mapped_data["conducente_3_tipo_patente"] = trim(mb_substr($row, 213, 1));
        $mapped_data["conducente_3_anno_patente"] = trim(mb_substr($row, 214, 2));
        $mapped_data["conducente_3_tipologia_incidente"] = trim(mb_substr($row, 216, 1));

        // Controlla tipologia incidente conducenti
        for ($i = 1; $i <= $numero_veicoli_coinvolti; $i++) {
            if (empty($mapped_data["conducente_{$i}_tipologia_incidente"])) {
                $mapped_data["conducente_{$i}_tipologia_incidente"] = "0";
            }
        }

        // Passeggeri veicolo C
        $mapped_data["veicolo_3_trasportato_1_esito"] = trim(mb_substr($row, 220, 1));
        $mapped_data["veicolo_3_trasportato_1_eta"] = trim(mb_substr($row, 221, 2));
        $mapped_data["veicolo_3_trasportato_1_sesso"] = trim(mb_substr($row, 223, 1));
        $mapped_data["veicolo_3_trasportato_2_esito"] = trim(mb_substr($row, 224, 1));
        $mapped_data["veicolo_3_trasportato_2_eta"] = trim(mb_substr($row, 225, 2));
        $mapped_data["veicolo_3_trasportato_2_sesso"] = trim(mb_substr($row, 227, 1));
        $mapped_data["veicolo_3_trasportato_3_esito"] = trim(mb_substr($row, 228, 1));
        $mapped_data["veicolo_3_trasportato_3_eta"] = trim(mb_substr($row, 229, 2));
        $mapped_data["veicolo_3_trasportato_3_sesso"] = trim(mb_substr($row, 231, 1));
        $mapped_data["veicolo_3_trasportato_4_esito"] = trim(mb_substr($row, 232, 1));
        $mapped_data["veicolo_3_trasportato_4_eta"] = trim(mb_substr($row, 233, 2));
        $mapped_data["veicolo_3_trasportato_4_sesso"] = trim(mb_substr($row, 235, 1));
           
        // Altri passeggeri infortunati sul veicolo C
        $mapped_data["veicolo_3_altri_morti_maschi"] = trim(mb_substr($row, 236, 2));
        $mapped_data["veicolo_3_altri_morti_femmine"] = trim(mb_substr($row, 238, 2));
        $mapped_data["veicolo_3_altri_feriti_maschi"] = trim(mb_substr($row, 240, 2));
        $mapped_data["veicolo_3_altri_feriti_femmine"] = trim(mb_substr($row, 242, 2));

        // Pedoni coinvolti
        $mapped_data["pedone_morto_1_sesso"] = trim(mb_substr($row, 244, 1));
        $mapped_data["pedone_morto_1_eta"] = trim(mb_substr($row, 245, 2));
        $mapped_data["pedone_ferito_1_sesso"] = trim(mb_substr($row, 247, 1));
        $mapped_data["pedone_ferito_1_eta"] = trim(mb_substr($row, 248, 2));
        $mapped_data["pedone_morto_2_sesso"] = trim(mb_substr($row, 250, 1));
        $mapped_data["pedone_morto_2_eta"] = trim(mb_substr($row, 251, 2));
        $mapped_data["pedone_ferito_2_sesso"] = trim(mb_substr($row, 253, 1));
        $mapped_data["pedone_ferito_2_eta"] = trim(mb_substr($row, 254, 2));
        $mapped_data["pedone_morto_3_sesso"] = trim(mb_substr($row, 256, 1));
        $mapped_data["pedone_morto_3_eta"] = trim(mb_substr($row, 257, 2));
        $mapped_data["pedone_ferito_3_sesso"] = trim(mb_substr($row, 259, 1));
        $mapped_data["pedone_ferito_3_eta"] = trim(mb_substr($row, 260, 2));
        $mapped_data["pedone_morto_4_sesso"] = trim(mb_substr($row, 262, 1));
        $mapped_data["pedone_morto_4_eta"] = trim(mb_substr($row, 263, 2));
        $mapped_data["pedone_ferito_4_sesso"] = trim(mb_substr($row, 265, 1));
        $mapped_data["pedone_ferito_4_eta"] = trim(mb_substr($row, 266, 2));
        
        // Conta pedoni morti e feriti
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($mapped_data["pedone_morto_{$i}_sesso"]) or !empty($mapped_data["pedone_morto_{$i}_eta"])) {
                $numero_pedoni_morti++;
            }
        }

        for ($i = 1; $i <= 4; $i++) {
            if (!empty($mapped_data["pedone_ferito_{$i}_sesso"]) or !empty($mapped_data["pedone_ferito_{$i}_eta"])) {
                $numero_pedoni_feriti++;
            }
        }
        
        $mapped_data["numero_pedoni_feriti"] = $numero_pedoni_feriti;
        $mapped_data["numero_pedoni_morti"] = $numero_pedoni_morti;
        
        // Controllo passeggeri dei veicoli A, B e C
        for ($numveicolo = 1; $numveicolo <= $numero_veicoli_coinvolti; $numveicolo++) {
            $veicolo_numero_trasportati = 0;
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_esito"]) or 
                    !empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sesso"]) or 
                    !empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_eta"])) {
                    $veicolo_numero_trasportati++;
                    if($i == 1){
                        $mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sedile"] = "anteriore";
                    } else {
                        $mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sedile"] = "posteriore";
                    }
                }
            }
            $mapped_data["veicolo_{$numveicolo}_numero_trasportati"] = $veicolo_numero_trasportati;
        }
        
        // Altri veicoli coinvolti
        $mapped_data["numero_altri_veicoli"] = trim(mb_substr($row, 268, 2));
        $mapped_data["altri_morti_maschi"] = trim(mb_substr($row, 270, 2));
        $mapped_data["altri_morti_femmine"] = trim(mb_substr($row, 272, 2));
        $mapped_data["altri_feriti_maschi"] = trim(mb_substr($row, 274, 2));
        $mapped_data["altri_feriti_femmine"] = trim(mb_substr($row, 276, 2));

        // Riepilogo infortunati
        $mapped_data["riepilogo_morti_24h"] = trim(mb_substr($row, 278, 2));
        $mapped_data["riepilogo_morti_2_30gg"] = trim(mb_substr($row, 280, 2));
        $mapped_data["riepilogo_feriti"] = trim(mb_substr($row, 282, 2));

        // Specifiche sulla denominazione della strada
        $mapped_data["denominazione_strada"] = trim(mb_substr($row, 293, 57));

        // Specifiche per l'inserimento del nome e cognome dei morti
        $mapped_data["morto_1_nome"] = trim(mb_substr($row, 450, 30));
        $mapped_data["morto_1_cognome"] = trim(mb_substr($row, 480, 30));
        $mapped_data["morto_2_nome"] = trim(mb_substr($row, 510, 30));
        $mapped_data["morto_2_cognome"] = trim(mb_substr($row, 540, 30));
        $mapped_data["morto_3_nome"] = trim(mb_substr($row, 570, 30));
        $mapped_data["morto_3_cognome"] = trim(mb_substr($row, 600, 30));
        $mapped_data["morto_4_nome"] = trim(mb_substr($row, 630, 30));
        $mapped_data["morto_4_cognome"] = trim(mb_substr($row, 660, 30));

        // Specifiche per l'inserimento del nome, cognome e luogo di ricovero dei feriti
        $mapped_data["ferito_1_nome"] = trim(mb_substr($row, 690, 30));
        $mapped_data["ferito_1_cognome"] = trim(mb_substr($row, 720, 30));
        $mapped_data["ferito_1_istituto"] = trim(mb_substr($row, 750, 30));
        $mapped_data["ferito_2_nome"] = trim(mb_substr($row, 780, 30));
        $mapped_data["ferito_2_cognome"] = trim(mb_substr($row, 810, 30));
        $mapped_data["ferito_2_istituto"] = trim(mb_substr($row, 840, 30));
        $mapped_data["ferito_3_nome"] = trim(mb_substr($row, 870, 30));
        $mapped_data["ferito_3_cognome"] = trim(mb_substr($row, 900, 30));
        $mapped_data["ferito_3_istituto"] = trim(mb_substr($row, 930, 30));
        $mapped_data["ferito_4_nome"] = trim(mb_substr($row, 960, 30));
        $mapped_data["ferito_4_cognome"] = trim(mb_substr($row, 990, 30));
        $mapped_data["ferito_4_istituto"] = trim(mb_substr($row, 1020, 30));
        $mapped_data["ferito_5_nome"] = trim(mb_substr($row, 1050, 30));
        $mapped_data["ferito_5_cognome"] = trim(mb_substr($row, 1080, 30));
        $mapped_data["ferito_5_istituto"] = trim(mb_substr($row, 1110, 30));
        $mapped_data["ferito_6_nome"] = trim(mb_substr($row, 1140, 30));
        $mapped_data["ferito_6_cognome"] = trim(mb_substr($row, 1170, 30));
        $mapped_data["ferito_6_istituto"] = trim(mb_substr($row, 1200, 30));
        $mapped_data["ferito_7_nome"] = trim(mb_substr($row, 1230, 30));
        $mapped_data["ferito_7_cognome"] = trim(mb_substr($row, 1260, 30));
        $mapped_data["ferito_7_istituto"] = trim(mb_substr($row, 1290, 30));
        $mapped_data["ferito_8_nome"] = trim(mb_substr($row, 1320, 30));
        $mapped_data["ferito_8_cognome"] = trim(mb_substr($row, 1350, 30));
        $mapped_data["ferito_8_istituto"] = trim(mb_substr($row, 1380, 30));

        // Specifiche per la georeferenziazione 
        $mapped_data["tipo_coordinata"] = trim(mb_substr($row, 1420, 1));
        $mapped_data["sistema_di_proiezione"] = trim(mb_substr($row, 1421, 1));
        $mapped_data["longitudine"] = str_replace(",", ".", trim(mb_substr($row, 1422, 50)));
        $mapped_data["latitudine"] = str_replace(",", ".", trim(mb_substr($row, 1472, 50)));
        $mapped_data["ora_incidente"] = trim(mb_substr($row, 1530, 2));
        $mapped_data["minuti_incidente"] = trim(mb_substr($row, 1532, 2));
        $mapped_data["codice_carabinieri"] = trim(mb_substr($row, 1534, 30));
        $mapped_data["progressiva_km"] = trim(mb_substr($row, 1564, 4));
        $mapped_data["progressiva_m"] = trim(mb_substr($row, 1568, 3));
        $mapped_data["veicolo_1_cilindrata"] = trim(mb_substr($row, 1571, 5));
        $mapped_data["veicolo_2_cilindrata"] = trim(mb_substr($row, 1576, 5));
        $mapped_data["veicolo_3_cilindrata"] = trim(mb_substr($row, 1581, 5));
        $mapped_data["localizzazione_extra_ab"] = trim(mb_substr($row, 1590, 100));
        $mapped_data["localita_incidente"] = trim(mb_substr($row, 1690, 40));

        // Riservato agli Enti in convenzione con Istat
        $mapped_data["codice__ente"] = trim(mb_substr($row, 1730, 40));

        // Specifiche per la registrazione delle informazioni sulla Cittadinanza dei conducenti
        $mapped_data["conducente_1_nazionalita"] = trim(mb_substr($row, 1781, 3));
        $mapped_data["conducente_1_nazionalita_altro"] = trim(mb_substr($row, 1784, 30));
        $mapped_data["conducente_1_nazionalita"] = $mapped_data["conducente_1_nazionalita"] ."-" . $mapped_data["conducente_1_nazionalita_altro"];
        
        $mapped_data["conducente_2_nazionalita"] = trim(mb_substr($row, 1815, 3));
        $mapped_data["conducente_2_nazionalita_altro"] = trim(mb_substr($row, 1818, 30));
        $mapped_data["conducente_2_nazionalita"] = $mapped_data["conducente_2_nazionalita"] ."-" . $mapped_data["conducente_2_nazionalita_altro"];
        
        $mapped_data["conducente_3_nazionalita"] = trim(mb_substr($row, 1849, 3));
        $mapped_data["conducente_3_nazionalita_altro"] = trim(mb_substr($row, 1852, 30));
        $mapped_data["conducente_3_nazionalita"] = $mapped_data["conducente_3_nazionalita"] ."-" . $mapped_data["conducente_3_nazionalita_altro"];

        // Nuove variabili 2020
        $mapped_data["veicolo_1_tipo_rimorchio"] = trim(mb_substr($row, 1882, 4));
        $mapped_data["veicolo_1_targa_rimorchio"] = trim(mb_substr($row, 1886, 10));
        $mapped_data["veicolo_2_tipo_rimorchio"] = trim(mb_substr($row, 1896, 4));
        $mapped_data["veicolo_2_targa_rimorchio"] = trim(mb_substr($row, 1900, 10));
        $mapped_data["veicolo_3_tipo_rimorchio"] = trim(mb_substr($row, 1910, 4));
        $mapped_data["veicolo_3_targa_rimorchio"] = trim(mb_substr($row, 1914, 10));
        $mapped_data["codice_strada_aci"] = trim(mb_substr($row, 1924, 15));

        $mapped_data["data_incidente"] = "20" . $data_incidente_anno . "-" . $data_incidente_mese . "-" . $data_incidente_giorno;                

        return $mapped_data;
    }
    
    /**
     * Create incidente from TXT data - VERSIONE ULTRA-OTTIMIZZATA
     */
    private function create_incidente_from_data($data) {
        global $wpdb;
        
        // ===== STEP 1: GENERA CODICE ENTE PRIMA DI CREARE IL POST =====
        $progressivo_temp = '99999';
        $anno = substr($data['data_incidente'], 2, 2);
        
        $id_ente = '01';
        if (!empty($data['organo_rilevazione'])) {
            $ente_map = array('1' => '01', '2' => '02', '4' => '04', '6' => '06');
            $id_ente = isset($ente_map[$data['organo_rilevazione']]) ? $ente_map[$data['organo_rilevazione']] : '99';
        }
        
        $id_comune = str_pad($data['comune_incidente'], 3, '0', STR_PAD_LEFT);
        $codice_ente_temp = $progressivo_temp . $anno . $id_ente . $id_comune;
        
        // ===== STEP 2: CREA POST CON TITOLO TEMPORANEO =====
        $post_id = wp_insert_post(array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'post_title' => $codice_ente_temp,
            'post_author' => $this->get_or_create_import_user()
        ), true);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // ===== STEP 3: AGGIORNA CODICE ENTE CON POST_ID REALE =====
        $progressivo = str_pad($post_id, 5, '0', STR_PAD_LEFT);
        $codice_ente_finale = $progressivo . $anno . $id_ente . $id_comune;
        
        // Aggiorna titolo con codice finale
        $wpdb->update(
            $wpdb->posts,
            array('post_title' => $codice_ente_finale),
            array('ID' => $post_id),
            array('%s'),
            array('%d')
        );
        
        // ===== STEP 4: PREPARA TUTTI I META (INCLUSO CODICE_ENTE) =====
        // Aggiungi valori di default
        if (empty($data['provincia_incidente'])) {
            $data['provincia_incidente'] = '000';
        }
        if (empty($data['numero_veicoli_coinvolti'])) {
            $data['numero_veicoli_coinvolti'] = '1';
        }
        
        // Aggiungi codice_ente ai dati
        $data['codice__ente'] = $codice_ente_finale;
        
        // Lista completa dei campi meta (ordine alfabetico per manutenibilità)
        $meta_fields = array(
            'altri_feriti_femmine', 'altri_feriti_maschi', 'altri_morti_femmine', 'altri_morti_maschi',
            'circostanza_veicolo_a', 'circostanza_veicolo_b', 'codice__ente', 'codice_carabinieri', 'codice_strada_aci',
            'comune_incidente', 'condizioni_meteo', 'conducente_1_anno_patente', 'conducente_1_esito', 'conducente_1_eta',
            'conducente_1_nazionalita', 'conducente_1_sesso', 'conducente_1_tipo_patente', 'conducente_1_tipologia_incidente',
            'conducente_2_anno_patente', 'conducente_2_esito', 'conducente_2_eta', 'conducente_2_nazionalita',
            'conducente_2_sesso', 'conducente_2_tipo_patente', 'conducente_2_tipologia_incidente',
            'conducente_3_anno_patente', 'conducente_3_esito', 'conducente_3_eta', 'conducente_3_nazionalita',
            'conducente_3_sesso', 'conducente_3_tipo_patente', 'conducente_3_tipologia_incidente',
            'data_incidente', 'denominazione_strada', 'dettaglio_natura', 'difetto_veicolo_a', 'difetto_veicolo_b',
            'ente_rilevatore', 'ferito_1_cognome', 'ferito_1_istituto', 'ferito_1_nome',
            'ferito_2_cognome', 'ferito_2_istituto', 'ferito_2_nome',
            'ferito_3_cognome', 'ferito_3_istituto', 'ferito_3_nome',
            'ferito_4_cognome', 'ferito_4_istituto', 'ferito_4_nome',
            'ferito_5_cognome', 'ferito_5_istituto', 'ferito_5_nome',
            'ferito_6_cognome', 'ferito_6_istituto', 'ferito_6_nome',
            'ferito_7_cognome', 'ferito_7_istituto', 'ferito_7_nome',
            'ferito_8_cognome', 'ferito_8_istituto', 'ferito_8_nome',
            'geometria_strada', 'illuminazione', 'intersezione_tronco', 'latitudine', 'localita_incidente',
            'localizzazione_extra_ab', 'longitudine', 'minuti_incidente', 'morto_1_cognome', 'morto_1_nome',
            'morto_2_cognome', 'morto_2_nome', 'morto_3_cognome', 'morto_3_nome', 'morto_4_cognome', 'morto_4_nome',
            'natura_incidente', 'numero_altri_veicoli', 'numero_pedoni_feriti', 'numero_pedoni_morti',
            'numero_strada', 'numero_veicoli_coinvolti', 'ora_incidente', 'organo_coordinatore', 'organo_rilevazione',
            'pavimentazione_strada', 'pedone_ferito_1_eta', 'pedone_ferito_1_sesso', 'pedone_ferito_2_eta',
            'pedone_ferito_2_sesso', 'pedone_ferito_3_eta', 'pedone_ferito_3_sesso', 'pedone_ferito_4_eta',
            'pedone_ferito_4_sesso', 'pedone_morto_1_eta', 'pedone_morto_1_sesso', 'pedone_morto_2_eta',
            'pedone_morto_2_sesso', 'pedone_morto_3_eta', 'pedone_morto_3_sesso', 'pedone_morto_4_eta',
            'pedone_morto_4_sesso', 'progressiva_km', 'progressiva_m', 'provincia_incidente',
            'riepilogo_feriti', 'riepilogo_morti_24h', 'riepilogo_morti_2_30gg', 'segnaletica_strada',
            'sistema_di_proiezione', 'stato_fondo_strada', 'stato_psicofisico_a', 'stato_psicofisico_b',
            'tipo_coordinata', 'tipo_strada', 'tronco_strada',
            'veicolo_1_altri_feriti_femmine', 'veicolo_1_altri_feriti_maschi', 'veicolo_1_altri_morti_femmine',
            'veicolo_1_altri_morti_maschi', 'veicolo_1_anno_immatricolazione', 'veicolo_1_cilindrata',
            'veicolo_1_numero_trasportati', 'veicolo_1_peso_totale', 'veicolo_1_sigla_estero', 'veicolo_1_targa',
            'veicolo_1_targa_rimorchio', 'veicolo_1_tipo', 'veicolo_1_tipo_rimorchio',
            'veicolo_1_trasportato_1_esito', 'veicolo_1_trasportato_1_eta', 'veicolo_1_trasportato_1_sedile',
            'veicolo_1_trasportato_1_sesso', 'veicolo_1_trasportato_2_esito', 'veicolo_1_trasportato_2_eta',
            'veicolo_1_trasportato_2_sedile', 'veicolo_1_trasportato_2_sesso', 'veicolo_1_trasportato_3_esito',
            'veicolo_1_trasportato_3_eta', 'veicolo_1_trasportato_3_sedile', 'veicolo_1_trasportato_3_sesso',
            'veicolo_1_trasportato_4_esito', 'veicolo_1_trasportato_4_eta', 'veicolo_1_trasportato_4_sedile',
            'veicolo_1_trasportato_4_sesso',
            'veicolo_2_altri_feriti_femmine', 'veicolo_2_altri_feriti_maschi', 'veicolo_2_altri_morti_femmine',
            'veicolo_2_altri_morti_maschi', 'veicolo_2_anno_immatricolazione', 'veicolo_2_cilindrata',
            'veicolo_2_numero_trasportati', 'veicolo_2_peso_totale', 'veicolo_2_sigla_estero', 'veicolo_2_targa',
            'veicolo_2_targa_rimorchio', 'veicolo_2_tipo', 'veicolo_2_tipo_rimorchio',
            'veicolo_2_trasportato_1_esito', 'veicolo_2_trasportato_1_eta', 'veicolo_2_trasportato_1_sedile',
            'veicolo_2_trasportato_1_sesso', 'veicolo_2_trasportato_2_esito', 'veicolo_2_trasportato_2_eta',
            'veicolo_2_trasportato_2_sedile', 'veicolo_2_trasportato_2_sesso', 'veicolo_2_trasportato_3_esito',
            'veicolo_2_trasportato_3_eta', 'veicolo_2_trasportato_3_sedile', 'veicolo_2_trasportato_3_sesso',
            'veicolo_2_trasportato_4_esito', 'veicolo_2_trasportato_4_eta', 'veicolo_2_trasportato_4_sedile',
            'veicolo_2_trasportato_4_sesso',
            'veicolo_3_altri_feriti_femmine', 'veicolo_3_altri_feriti_maschi', 'veicolo_3_altri_morti_femmine',
            'veicolo_3_altri_morti_maschi', 'veicolo_3_anno_immatricolazione', 'veicolo_3_cilindrata',
            'veicolo_3_numero_trasportati', 'veicolo_3_peso_totale', 'veicolo_3_sigla_estero', 'veicolo_3_targa',
            'veicolo_3_targa_rimorchio', 'veicolo_3_tipo', 'veicolo_3_tipo_rimorchio',
            'veicolo_3_trasportato_1_esito', 'veicolo_3_trasportato_1_eta', 'veicolo_3_trasportato_1_sedile',
            'veicolo_3_trasportato_1_sesso', 'veicolo_3_trasportato_2_esito', 'veicolo_3_trasportato_2_eta',
            'veicolo_3_trasportato_2_sedile', 'veicolo_3_trasportato_2_sesso', 'veicolo_3_trasportato_3_esito',
            'veicolo_3_trasportato_3_eta', 'veicolo_3_trasportato_3_sedile', 'veicolo_3_trasportato_3_sesso',
            'veicolo_3_trasportato_4_esito', 'veicolo_3_trasportato_4_eta', 'veicolo_3_trasportato_4_sedile',
            'veicolo_3_trasportato_4_sesso'
        );
        
        // ===== STEP 5: BATCH INSERT =====
        $meta_values = array();
        foreach ($meta_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $meta_values[] = $wpdb->prepare(
                    "(%d, %s, %s)",
                    $post_id,
                    $field,
                    sanitize_text_field($data[$field])
                );
            }
        }
        
        // Inserisci tutto in una query sola
        if (!empty($meta_values)) {
            $query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(',', $meta_values);
            $wpdb->query($query);
        }
        
        return $post_id;
    }

    /**
     * Ottieni o crea l'utente per le importazioni
     */
    private function get_or_create_import_user() {
        $user = get_user_by('login', 'importuser');
        
        if ($user) {
            return $user->ID;
        }
        
        $user_id = wp_create_user(
            'importuser',
            wp_generate_password(),
            'import@' . get_bloginfo('url')
        );
        
        if (is_wp_error($user_id)) {
            return get_current_user_id();
        }
        
        $user = new WP_User($user_id);
        $user->set_role('import');
        
        return $user_id;
    }
    
    /**
     * Redirect with error message
     */
    private function redirect_with_error($message) {
        $redirect_url = admin_url('edit.php?post_type=incidente_stradale&page=incidenti-import-istat&error=' . urlencode($message));
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        wp_localize_script('incidenti-admin-js', 'incidenti_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('import_incidenti_nonce')
        ));
    }
}