<?php
/**
 * Incidenti Import Functions
 * 
 * @package IncidentiStradali
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class IncidentiImportFunctions {
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
        add_action('admin_menu', array($this, 'add_import_menu'), 21);
        add_action('admin_post_import_incidenti_txt', array($this, 'handle_txt_import'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts')); 
    }
    
    /**
     * Aggiungi menu import
     */
    public function add_import_menu() {
        add_submenu_page(
            'edit.php?post_type=incidente_stradale',
            __('Importa Dati', 'incidenti-stradali'),
            __('Importa Dati', 'incidenti-stradali'),
            'edit_posts',
            'incidenti-import',
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
        // Messaggi di risultato
        $message = '';
        $message_type = '';
        
        if (isset($_GET['imported'])) {
            $imported = intval($_GET['imported']);
            $errors = intval($_GET['errors']);
            $message = sprintf(__('Importazione completata: %d incidenti importati, %d errori.', 'incidenti-stradali'), $imported, $errors);
            $message_type = $errors > 0 ? 'warning' : 'success';
        }
        
        if (isset($_GET['error'])) {
            $message = sanitize_text_field($_GET['error']);
            $message_type = 'error';
        }
        
        include INCIDENTI_PLUGIN_PATH . 'templates/admin-import-page.php';
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
        
        $separator = sanitize_text_field($_POST['separator']);
        $result = $this->process_txt_file($uploaded_file);
        
        // Cleanup uploaded file
        wp_delete_file($uploaded_file);
        
        $redirect_url = admin_url('edit.php?post_type=incidente_stradale&page=incidenti-import');
        if ($result['success']) {
            $redirect_url .= '&imported=' . $result['imported'] . '&errors=' . $result['errors'];
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
     * Handle AJAX file upload
     */
    private function handle_file_upload_ajax() {
        if (!isset($_FILES['txt_file'])) {
            return new WP_Error('no_file', __('Nessun file selezionato.', 'incidenti-stradali'));
        }
        
        return $this->handle_file_upload();
    }
    
    /**
     * Process full TXT file
     */
    private function process_txt_file($file_path) {      
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => __('Impossibile leggere il file.', 'incidenti-stradali'));
        }
        
        // Leggi header
        /*
        $required_fields = array('data_incidente', 'ora_incidente', 'comune_incidente', 'denominazione_strada', 'numero_veicoli_coinvolti');
        */
        $imported = 0;
        $errors = 0;
        $line_number = 1;
        
        while (($row = fgets($handle)) !== false) {
            // Pulisci la riga
            $row = trim($row);
            // Salta le righe vuote
            if (empty($row)) {
                continue;
            }
            
            $mapped_data = $this->map_row_data($row);   
            /*
            $validation_result = $this->validate_row_data($mapped_data);
            */
            /* *
            if ($validation_result['valid']) {
           */ /***/    
           $post_id = $this->create_incidente_from_data($mapped_data);
                if ($post_id) {
                    $imported++;
                } else {
                    $errors++;
                    error_log("Errore creazione incidente riga $line_number");
                }

                /***/
        /*    } else {
                $errors++;
                error_log("Errore validazione riga $line_number: " . implode(', ', $validation_result['errors']));
            }
           * */
             $line_number++;

        }

        fclose($handle);
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
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
           // "post_id" => "0000",
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
            //4. Tipo di veicoli coinvolti
            "veicolo_1_tipo" => "  ",
            "veicolo_2_tipo" => "  ",
            "veicolo_3_tipo" => "  ",
            "veicolo_1_peso_totale" => "    ",
            "veicolo_2_peso_totale" => "    ",
            "veicolo_3_peso_totale" => "    ",
            //5. Circostanze accertate o presunte dell'incidente
            "circostanza_veicolo_a" => "  ",
            "difetto_veicolo_a" => "  ",
            "stato_psicofisico_a" => "  ",
            "circostanza_veicolo_b" => "  ",
            "difetto_veicolo_b" => "  ",
            "stato_psicofisico_b" => "  ",
            //6. Veicoli coinvolti
            "veicolo_1_targa" => "        ",
            "veicolo_1_sigla_estero" => "   ",
            "veicolo_1_anno_immatricolazione" => "  ",
            "veicolo_2_targa" => "        ",
            "veicolo_2_sigla_estero" => "   ",
            "veicolo_2_anno_immatricolazione" => "  ",
            "veicolo_3_targa" => "        ",
            "veicolo_3_sigla_estero" => "   ",
            "veicolo_3_anno_immatricolazione" => "  ",
            // 7. Conseguenze dell'incidente alle persone
            // Veicolo A: conducente
            "conducente_1_eta" => "  ",
            "conducente_1_sesso" => " ",
            "conducente_1_esito" => " ",
            "conducente_1_tipo_patente" => " ",
            "conducente_1_anno_patente" => "  ",
            "conducente_1_tipologia_incidente" => " ",
            // Passeggeri veicolo A
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
            // Altri passeggeri infortunati sul veicolo A
            "veicolo_1_altri_morti_maschi" => "  ",
            "veicolo_1_altri_morti_femmine" => "  ",
            "veicolo_1_altri_feriti_maschi" => "  ",
            "veicolo_1_altri_feriti_femmine" => "  ",
            // Veicolo B: conducente
            "conducente_2_eta" => "  ",
            "conducente_2_sesso" => " ",
            "conducente_2_esito" => " ",
            "conducente_2_tipo_patente" => " ",
            "conducente_2_anno_patente" => "  ",
            "conducente_2_tipologia_incidente" => " ",
            // Passeggeri veicolo B
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
            // Altri passeggeri infortunati sul veicolo B
            "veicolo_2_altri_morti_maschi" => "  ",
            "veicolo_2_altri_morti_femmine" => "  ",
            "veicolo_2_altri_feriti_maschi" => "  ",
            "veicolo_2_altri_feriti_femmine" => "  ",
            // Veicolo C: conducente
            "conducente_3_eta" => "  ",
            "conducente_3_sesso" => " ",
            "conducente_3_esito" => " ",
            "conducente_3_tipo_patente" => " ",
            "conducente_3_anno_patente" => "  ",
            "conducente_3_tipologia_incidente" => " ",
            // Passeggeri veicolo C
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
            // Altri passeggeri infortunati sul veicolo C
            "veicolo_3_altri_morti_maschi" => "  ",
            "veicolo_3_altri_morti_femmine" => "  ",
            "veicolo_3_altri_feriti_maschi" => "  ",
            "veicolo_3_altri_feriti_femmine" => "  ",

            // Pedoni coinvolti
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

            // Altri veicoli coinvolti altre ai veicoli A, B e C, e persone infortunate
            "numero_altri_veicoli" => "  ",
            "altri_morti_maschi" => "  ",
            "altri_morti_femmine" => "  ",
            "altri_feriti_maschi" => "  ",
            "altri_feriti_femmine" => "  ",

            // Riepilogo infortunati
            "riepilogo_morti_24h" => "  ",
            "riepilogo_morti_2_30gg" => "  ",
            "riepilogo_feriti" => "  ",
            //$mapped_data[""] = trim(mb_substr($row, 284, 9));

            // Specifiche sulla denominazione della strada
            "denominazione_strada" => "",

            // Specifiche per l'inserimento del nome e cognome dei morti
            "morto_1_nome" => "",
            "morto_1_cognome" => "",
            "morto_2_nome" => "",   
            "morto_2_cognome" => "",
            "morto_3_nome" => "",
            "morto_3_cognome" => "",
            "morto_4_nome" => "",
            "morto_4_cognome" => "",

            //Specifiche per l'inserimento del nome, cognome e luogo di ricovero dei feriti
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
            //"spazio_istat_1" => "",

            // Specifiche per la georeferenziazione 
            "tipo_coordinata" => "1",
            "sistema_di_proiezione" => "2",
            "longitudine" => "",
            "latitudine" => "",
            //"spazio_istat_2" => " ",
            "ora_incidente" => "",
            "minuti_incidente" => "",
            "codice_carabinieri" => "",
            "progressiva_km" => "",
            "progressiva_m" => "",
            "veicolo_1_cilindrata" => "",
            "veicolo_2_cilindrata" => "",
            "veicolo_3_cilindrata" => "",
            //"spazio_istat_3" => "",
            "localizzazione_extra_ab" => "",
            "localita_incidente" => "",

            // Riservato agli Enti in convenzione con Istat
            "codice__ente" => "",
            //"spazio_istat_4" => "",

            // Specifiche per la registrazione delle informazioni sulla Cittadinanza dei conducenti dei veicoli A, B e C 1781-1882 
            "conducente_1_nazionalita" => "",
            "conducente_1_nazionalita_altro" => "",
            "conducente_2_nazionalita" => "",
            "conducente_2_nazionalita_altro" => "",
            "conducente_3_nazionalita" => "",
            "conducente_3_nazionalita_altro" => "",

            // Nuove variabili 2020
            "veicolo_1_tipo_rimorchio" => "",
            "veicolo_1_targa_rimorchio" => "",
            "veicolo_2_tipo_rimorchio" => "",
            "veicolo_2_targa_rimorchio" => "",
            "veicolo_3_tipo_rimorchio" => "",
            "veicolo_3_targa_rimorchio" => "",
            "codice_strada_aci" => "",
            // DA CALCOLARE
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
        

        // Estrai i campi dal tracciato (esempio con campi fissi)
        $data_incidente_anno = trim(mb_substr($row, 0, 2));
        $data_incidente_mese = trim(mb_substr($row, 2, 2));
        $mapped_data["provincia_incidente"] = trim(mb_substr($row, 4, 3));
        $mapped_data["comune_incidente"] = trim(mb_substr($row, 7, 3));
        //$mapped_data["post_id"] = trim(mb_substr($row, 10, 4));
        $data_incidente_giorno = trim(mb_substr($row, 14, 2));
        //$mapped_data[""] = trim(mb_substr($row, 16, 2));
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
        //$mapped_data[""] = trim(mb_substr($row, 19, 5));
        $mapped_data["organo_coordinatore"] = trim(mb_substr($row, 24, 1));
        $mapped_data["tipo_strada"] = trim(mb_substr($row, 25, 1));
        $mapped_data["numero_strada"] = trim(mb_substr($row, 26, 3));
        $mapped_data["illuminazione"] = trim(mb_substr($row, 29, 1));
        //$mapped_data[""] = trim(mb_substr($row, 30, 2));
        $mapped_data["tronco_strada"] =  str_replace("0","",trim(mb_substr($row, 32, 2)));    
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
        //4. Tipo di veicoli coinvolti
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
        //$mapped_data[""] = trim(mb_substr($row, 49, 4));
        //$mapped_data[""] = trim(mb_substr($row, 53, 4));
        //$mapped_data[""] = trim(mb_substr($row, 57, 4));
        $mapped_data["veicolo_1_peso_totale"] = trim(mb_substr($row, 61, 4));
        $mapped_data["veicolo_2_peso_totale"] = trim(mb_substr($row, 65, 4));
        $mapped_data["veicolo_3_peso_totale"] = trim(mb_substr($row, 69, 4));
        // calcola il numero di veicoli coinvolti
        $mapped_data["numero_veicoli_coinvolti"] =  $numero_veicoli_coinvolti;

        //5. Circostanze accertate o presunte dell'incidente
        $mapped_data["circostanza_veicolo_a"] = trim(mb_substr($row, 73, 2));
        $mapped_data["difetto_veicolo_a"] = trim(mb_substr($row, 75, 2));
        $mapped_data["stato_psicofisico_a"] = trim(mb_substr($row, 77, 2));
        $mapped_data["circostanza_veicolo_b"] = trim(mb_substr($row, 79, 2));
        $mapped_data["difetto_veicolo_b"] = trim(mb_substr($row, 81, 2));
        $mapped_data["stato_psicofisico_b"] = trim(mb_substr($row, 83, 2));

        //6. Veicoli coinvolti
        $mapped_data["veicolo_1_targa"] = trim(mb_substr($row, 85, 8));
        $mapped_data["veicolo_1_sigla_estero"] = trim(mb_substr($row, 93, 3));
        $mapped_data["veicolo_1_anno_immatricolazione"] = trim(mb_substr($row, 96, 2));
        //$mapped_data[""] = trim(mb_substr($row, 98, 2));
        //$mapped_data[""] = trim(mb_substr($row, 100, 3));
        $mapped_data["veicolo_2_targa"] = trim(mb_substr($row, 103, 8));
        $mapped_data["veicolo_2_sigla_estero"] = trim(mb_substr($row, 111, 3));
        $mapped_data["veicolo_2_anno_immatricolazione"] = trim(mb_substr($row, 114, 2));
        //$mapped_data[""] = trim(mb_substr($row, 116, 2));
        //$mapped_data[""] = trim(mb_substr($row, 118, 3));
        $mapped_data["veicolo_3_targa"] = trim(mb_substr($row, 121, 8));
        $mapped_data["veicolo_3_sigla_estero"] = trim(mb_substr($row, 129, 3));
        $mapped_data["veicolo_3_anno_immatricolazione"] = trim(mb_substr($row, 132, 2));
        //$mapped_data[""] = trim(mb_substr($row, 134, 2));
        //$mapped_data[""] = trim(mb_substr($row, 136, 3));

        // 7. Conseguenze dell'incidente alle persone
        // Veicolo A: conducente
        $mapped_data["conducente_1_eta"] = trim(mb_substr($row, 139, 2));
        $mapped_data["conducente_1_sesso"] = trim(mb_substr($row, 141, 1));
        $mapped_data["conducente_1_esito"] = trim(mb_substr($row, 142, 1));
        $mapped_data["conducente_1_tipo_patente"] = trim(mb_substr($row, 143, 1));
        $mapped_data["conducente_1_anno_patente"] = trim(mb_substr($row, 144, 2));
        $mapped_data["conducente_1_tipologia_incidente"] = trim(mb_substr($row, 146, 1));
        //$mapped_data[""] = trim(mb_substr($row, 147, 1));
        //$mapped_data[""] = trim(mb_substr($row, 148, 1));
        //$mapped_data[""] = trim(mb_substr($row, 149, 1));
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
        //$mapped_data[""] = trim(mb_substr($row, 182, 1));
        //$mapped_data[""] = trim(mb_substr($row, 183, 1));
        //$mapped_data[""] = trim(mb_substr($row, 184, 1));
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
        //$mapped_data[""] = trim(mb_substr($row, 217, 1));
        //$mapped_data[""] = trim(mb_substr($row, 218, 1));
        //$mapped_data[""] = trim(mb_substr($row, 219, 1));

        // Controlla i pedoni morti
        for ($i = 1; $i <=  $numero_veicoli_coinvolti; $i++) {
            if (empty($mapped_data["conducente_{$i}_tipologia_incidente"])) {
                $mapped_data["conducente_{$i}_tipologia_incidente"]="0";
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
        // Controlla i pedoni morti
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($mapped_data["pedone_morto_{$i}_sesso"]) or !empty($mapped_data["pedone_morto_{$i}_eta"])) {
                $numero_pedoni_morti++;
            }
        }

        // Controlla i pedoni feriti
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($mapped_data["pedone_ferito_{$i}_sesso"]) or !empty($mapped_data["pedone_ferito_{$i}_eta"])) {
                $numero_pedoni_feriti++;
            }
        }
        $mapped_data["numero_pedoni_feriti"] =  $numero_pedoni_feriti;
        $mapped_data["numero_pedoni_morti"] =  $numero_pedoni_morti;
        $veicolo_numero_trasportati = 0;
        // Controllo i Passeggeri dei veicoli A, B e C
        for ($numveicolo = 1; $numveicolo <= $numero_veicoli_coinvolti; $numveicolo++) {
            $veicolo_numero_trasportati = 0;
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_esito"]) or !empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sesso"]) or !empty($mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_eta"])) {
                    $veicolo_numero_trasportati++;
                    if($i == 1){
                        $mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sedile"] = "anteriore";
                    } else {
                        $mapped_data["veicolo_{$numveicolo}_trasportato_{$i}_sedile"] = "posteriore";
                    }
                }
                $mapped_data["veicolo_{$numveicolo}_numero_trasportati"] =  $veicolo_numero_trasportati;
            }
        }
        // Altri veicoli coinvolti altre ai veicoli A, B e C, e persone infortunate Da 01 a 99
        $mapped_data["numero_altri_veicoli"] = trim(mb_substr($row, 268, 2));
        $mapped_data["altri_morti_maschi"] = trim(mb_substr($row, 270, 2));
        $mapped_data["altri_morti_femmine"] = trim(mb_substr($row, 272, 2));
        $mapped_data["altri_feriti_maschi"] = trim(mb_substr($row, 274, 2));
        $mapped_data["altri_feriti_femmine"] = trim(mb_substr($row, 276, 2));

        // Riepilogo infortunati 00
        $mapped_data["riepilogo_morti_24h"] = trim(mb_substr($row, 278, 2));
        $mapped_data["riepilogo_morti_2_30gg"] = trim(mb_substr($row, 280, 2));
        $mapped_data["riepilogo_feriti"] = trim(mb_substr($row, 282, 2));

        // Specifiche sulla denominazione della strada
        $mapped_data["denominazione_strada"] = trim(mb_substr($row, 293, 57));
        //$mapped_data[""] = trim(mb_substr($row, 350, 100));

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
        //$mapped_data["spazio_istat_1"] = trim(mb_substr($row, 1410, 10));

        // Specifiche per la georeferenziazione 
        $mapped_data["tipo_coordinata"] = trim(mb_substr($row, 1420, 1));
        $mapped_data["sistema_di_proiezione"] = trim(mb_substr($row, 1421, 1));
        $mapped_data["longitudine"] = str_replace(",", ".", trim(mb_substr($row, 1422, 50)));
        $mapped_data["latitudine"] = str_replace(",", ".", trim(mb_substr($row, 1472, 50)));
        //$mapped_data["spazio_istat_2"] = trim(mb_substr($row, 1522, 8));
        $mapped_data["ora_incidente"] = trim(mb_substr($row, 1530, 2));
        $mapped_data["minuti_incidente"] = trim(mb_substr($row, 1532, 2));
        $mapped_data["codice_carabinieri"] = trim(mb_substr($row, 1534, 30));
        $mapped_data["progressiva_km"] = trim(mb_substr($row, 1564, 4));
        $mapped_data["progressiva_m"] = trim(mb_substr($row, 1568, 3));
        $mapped_data["veicolo_1_cilindrata"] = trim(mb_substr($row, 1571, 5));
        $mapped_data["veicolo_2_cilindrata"] = trim(mb_substr($row, 1576, 5));
        $mapped_data["veicolo_3_cilindrata"] = trim(mb_substr($row, 1581, 5));
        //$mapped_data["spazio_istat_3"] = trim(mb_substr($row, 1586, 4));
        $mapped_data["localizzazione_extra_ab"] = trim(mb_substr($row, 1590, 100));
        $mapped_data["localita_incidente"] = trim(mb_substr($row, 1690, 40));

        // Riservato agli Enti in convenzione con Istat
        $mapped_data["codice__ente"] = trim(mb_substr($row, 1730, 40));
        //$mapped_data["spazio_istat_4"] = trim(mb_substr($row, 1770, 10));

        // Specifiche per la registrazione delle informazioni sulla Cittadinanza dei conducenti dei veicoli A, B e C 1781-1882 
        //$mapped_data["conducente_1_italiano"] = trim(mb_substr($row, 1780, 1));
        $mapped_data["conducente_1_nazionalita"] = trim(mb_substr($row, 1781, 3));
        $mapped_data["conducente_1_nazionalita_altro"] = trim(mb_substr($row, 1784, 30));
        $mapped_data["conducente_1_nazionalita"] = $mapped_data["conducente_1_nazionalita"] ."-" . $mapped_data["conducente_1_nazionalita_altro"];
        //$mapped_data["conducente_2_italiano"] = trim(mb_substr($row, 1814, 1));
        $mapped_data["conducente_2_nazionalita"] = trim(mb_substr($row, 1815, 3));
        $mapped_data["conducente_2_nazionalita_altro"] = trim(mb_substr($row, 1818, 30));
        $mapped_data["conducente_2_nazionalita"] = $mapped_data["conducente_2_nazionalita"] ."-" . $mapped_data["conducente_2_nazionalita_altro"];
        //$mapped_data["conducente_3_italiano"] = trim(mb_substr($row, 1848, 1));
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
     * Validate row data
     */
    private function validate_row_data($data) {
        $errors = array();
        /*
        $required_fields = array('data_incidente', 'ora_incidente', 'comune_incidente', 'denominazione_strada');
        
        // Check required fields
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('Campo obbligatorio mancante: %s', 'incidenti-stradali'), $field);
            }
        }
        
        // Validate date format
        if (!empty($data['data_incidente'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['data_incidente']);
            if (!$date || $date->format('Y-m-d') !== $data['data_incidente']) {
                $errors[] = __('Formato data non valido. Usare YYYY-MM-DD', 'incidenti-stradali');
            }
        }
        
        // Validate time format
        if (!empty($data['ora_incidente'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['ora_incidente'])) {
                $errors[] = __('Formato ora non valido. Usare HH:MM', 'incidenti-stradali');
            }
        }
        
        // Validate comune code
        if (!empty($data['comune_incidente'])) {
            if (!preg_match('/^\d{3}$/', $data['comune_incidente'])) {
                $errors[] = __('Codice comune deve essere di 3 cifre', 'incidenti-stradali');
            }
        }
        
        // Validate numero veicoli
        if (!empty($data['numero_veicoli_coinvolti'])) {
            $num_veicoli = intval($data['numero_veicoli_coinvolti']);
            if ($num_veicoli < 1 || $num_veicoli > 3) {
                $errors[] = __('Numero veicoli deve essere tra 1 e 3', 'incidenti-stradali');
            }
        }
        */
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Create incidente from TXT data
     */
    private function create_incidente_from_data($data) {
        // Crea il post
        $post_data = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'post_title' => sprintf(
                __('Incidente del %s in %s', 'incidenti-stradali'),
                $data['data_incidente'],
                $data['denominazione_strada']
            ),
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Salva meta data usando gli stessi campi della classe MetaBoxes
        $meta_fields = array(
            'data_incidente',
            'provincia_incidente',
            'comune_incidente',
            'organo_rilevazione',
            'organo_coordinatore',
            'tipo_strada',
            'numero_strada',
            'illuminazione',
            'tronco_strada',
            'geometria_strada',
            'pavimentazione_strada',
            'intersezione_tronco',
            'stato_fondo_strada',
            'segnaletica_strada',
            'condizioni_meteo',
            'dettaglio_natura',
            'veicolo_1_tipo',
            'veicolo_2_tipo',
            'veicolo_3_tipo',
            'veicolo_1_peso_totale',
            'veicolo_2_peso_totale',
            'veicolo_3_peso_totale',
            'circostanza_veicolo_a',
            'difetto_veicolo_a',
            'stato_psicofisico_a',
            'circostanza_veicolo_b',
            'difetto_veicolo_b',
            'stato_psicofisico_b',
            'veicolo_1_targa',
            'veicolo_1_sigla_estero',
            'veicolo_1_anno_immatricolazione',
            'veicolo_2_targa',
            'veicolo_2_sigla_estero',
            'veicolo_2_anno_immatricolazione',
            'veicolo_3_targa',
            'veicolo_3_sigla_estero',
            'veicolo_3_anno_immatricolazione',
            'conducente_1_eta',
            'conducente_1_sesso',
            'conducente_1_esito',
            'conducente_1_tipo_patente',
            'conducente_1_anno_patente',
            'conducente_1_tipologia_incidente',
            'veicolo_1_trasportato_1_esito',
            'veicolo_1_trasportato_1_eta',
            'veicolo_1_trasportato_1_sesso',
            'veicolo_1_trasportato_2_esito',
            'veicolo_1_trasportato_2_eta',
            'veicolo_1_trasportato_2_sesso',
            'veicolo_1_trasportato_3_esito',
            'veicolo_1_trasportato_3_eta',
            'veicolo_1_trasportato_3_sesso',
            'veicolo_1_trasportato_4_esito',
            'veicolo_1_trasportato_4_eta',
            'veicolo_1_trasportato_4_sesso',
            'veicolo_1_altri_morti_maschi',
            'veicolo_1_altri_morti_femmine',
            'veicolo_1_altri_feriti_maschi',
            'veicolo_1_altri_feriti_femmine',
            'conducente_2_eta',
            'conducente_2_sesso',
            'conducente_2_esito',
            'conducente_2_tipo_patente',
            'conducente_2_anno_patente',
            'conducente_2_tipologia_incidente',
            'veicolo_2_trasportato_1_esito',
            'veicolo_2_trasportato_1_eta',
            'veicolo_2_trasportato_1_sesso',
            'veicolo_2_trasportato_2_esito',
            'veicolo_2_trasportato_2_eta',
            'veicolo_2_trasportato_2_sesso',
            'veicolo_2_trasportato_3_esito',
            'veicolo_2_trasportato_3_eta',
            'veicolo_2_trasportato_3_sesso',
            'veicolo_2_trasportato_4_esito',
            'veicolo_2_trasportato_4_eta',
            'veicolo_2_trasportato_4_sesso',
            'veicolo_2_altri_morti_maschi',
            'veicolo_2_altri_morti_femmine',
            'veicolo_2_altri_feriti_maschi',
            'veicolo_2_altri_feriti_femmine',
            'conducente_3_eta',
            'conducente_3_sesso',
            'conducente_3_esito',
            'conducente_3_tipo_patente',
            'conducente_3_anno_patente',
            'conducente_3_tipologia_incidente',
            'veicolo_3_trasportato_1_esito',
            'veicolo_3_trasportato_1_eta',
            'veicolo_3_trasportato_1_sesso',
            'veicolo_3_trasportato_2_esito',
            'veicolo_3_trasportato_2_eta',
            'veicolo_3_trasportato_2_sesso',
            'veicolo_3_trasportato_3_esito',
            'veicolo_3_trasportato_3_eta',
            'veicolo_3_trasportato_3_sesso',
            'veicolo_3_trasportato_4_esito',
            'veicolo_3_trasportato_4_eta',
            'veicolo_3_trasportato_4_sesso',
            'veicolo_3_altri_morti_maschi',
            'veicolo_3_altri_morti_femmine',
            'veicolo_3_altri_feriti_maschi',
            'veicolo_3_altri_feriti_femmine',
            'pedone_morto_1_sesso',
            'pedone_morto_1_eta',
            'pedone_ferito_1_sesso',
            'pedone_ferito_1_eta',
            'pedone_morto_2_sesso',
            'pedone_morto_2_eta',
            'pedone_ferito_2_sesso',
            'pedone_ferito_2_eta',
            'pedone_morto_3_sesso',
            'pedone_morto_3_eta',
            'pedone_ferito_3_sesso',
            'pedone_ferito_3_eta',
            'pedone_morto_4_sesso',
            'pedone_morto_4_eta',
            'pedone_ferito_4_sesso',
            'pedone_ferito_4_eta',
            'numero_altri_veicoli',
            'altri_morti_maschi',
            'altri_morti_femmine',
            'altri_feriti_maschi',
            'altri_feriti_femmine',
            'riepilogo_morti_24h',
            'riepilogo_morti_2_30gg',
            'riepilogo_feriti',
            'denominazione_strada',
            'morto_1_nome',
            'morto_1_cognome',
            'morto_2_nome',
            'morto_2_cognome',
            'morto_3_nome',
            'morto_3_cognome',
            'morto_4_nome',
            'morto_4_cognome',
            'ferito_1_nome',
            'ferito_1_cognome',
            'ferito_1_istituto',
            'ferito_2_nome',
            'ferito_2_cognome',
            'ferito_2_istituto',
            'ferito_3_nome',
            'ferito_3_cognome',
            'ferito_3_istituto',
            'ferito_4_nome',
            'ferito_4_cognome',
            'ferito_4_istituto',
            'ferito_5_nome',
            'ferito_5_cognome',
            'ferito_5_istituto',
            'ferito_6_nome',
            'ferito_6_cognome',
            'ferito_6_istituto',
            'ferito_7_nome',
            'ferito_7_cognome',
            'ferito_7_istituto',
            'ferito_8_nome',
            'ferito_8_cognome',
            'ferito_8_istituto',
            'spazio_istat_1',
            'tipo_coordinata',
            'sistema_di_proiezione',
            'longitudine',
            'latitudine',
            'spazio_istat_2',
            'ora_incidente',
            'minuti_incidente',
            'codice_carabinieri',
            'progressiva_km',
            'progressiva_m',
            'veicolo_1_cilindrata',
            'veicolo_2_cilindrata',
            'veicolo_3_cilindrata',
            'spazio_istat_3',
            'localizzazione_extra_ab',
            'localita_incidente',
            'codice__ente',
            'spazio_istat_4',
            'conducente_1_italiano',
            'conducente_1_nazionalita',
            //'conducente_1_nazionalita_altro',
            'conducente_2_italiano',
            'conducente_2_nazionalita',
            //'conducente_2_nazionalita_altro',
            'conducente_3_italiano',
            'conducente_3_nazionalita',
            //'conducente_3_nazionalita_altro',
            'veicolo_1_tipo_rimorchio',
            'veicolo_1_targa_rimorchio',
            'veicolo_2_tipo_rimorchio',
            'veicolo_2_targa_rimorchio',
            'veicolo_3_tipo_rimorchio',
            'veicolo_3_targa_rimorchio',
            'codice_strada_aci',
            // CAMPI CALCOLATI
            'ente_rilevatore',
            'natura_incidente',
            'comune_incidente',
            'numero_veicoli_coinvolti',
            'numero_pedoni_feriti',
            'numero_pedoni_morti',
            'veicolo_1_numero_trasportati',
            'veicolo_2_numero_trasportati',
            'veicolo_3_numero_trasportati',
            'veicolo_1_trasportato_1_sedile',
            'veicolo_1_trasportato_2_sedile',
            'veicolo_1_trasportato_3_sedile',
            'veicolo_1_trasportato_4_sedile',
            'veicolo_2_trasportato_1_sedile',
            'veicolo_2_trasportato_2_sedile',
            'veicolo_2_trasportato_3_sedile',
            'veicolo_2_trasportato_4_sedile',
            'veicolo_3_trasportato_1_sedile',
            'veicolo_3_trasportato_2_sedile',
            'veicolo_3_trasportato_3_sedile',
            'veicolo_3_trasportato_4_sedile',
        );
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($data[$field]));
            }
        }
        
        // Set default values for missing required fields
        if (empty($data['provincia_incidente'])) {
            update_post_meta($post_id, 'provincia_incidente', '000');
        }
        
        if (empty($data['numero_veicoli_coinvolti'])) {
            update_post_meta($post_id, 'numero_veicoli_coinvolti', '1');
        }
        
        return $post_id;
    }
    
    /**
     * Redirect with error message
     */
    private function redirect_with_error($message) {
        $redirect_url = admin_url('edit.php?post_type=incidente_stradale&page=incidenti-import&error=' . urlencode($message));
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Carica solo nella pagina di import
        /* if ($hook !== 'incidente_stradale_page_incidenti-import') {
            return;
        }
        
        wp_enqueue_script(
            'incidenti-admin-js',
            INCIDENTI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        ); */
        
        // Passa variabili JavaScript
        wp_localize_script('incidenti-admin-js', 'incidenti_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('import_incidenti_nonce')
        ));
    }
}