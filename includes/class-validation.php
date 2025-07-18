<?php
/**
 * Validation Class for Incidenti Stradali
 */

class IncidentiValidation {
    
    public function __construct() {
        add_action('wp_ajax_validate_incidente_field', array($this, 'ajax_validate_field'));
        add_action('wp_ajax_nopriv_validate_incidente_field', array($this, 'ajax_validate_field'));
        add_filter('wp_insert_post_data', array($this, 'validate_before_save'), 10, 2);
        add_action('admin_notices', array($this, 'display_validation_errors'));
    }
    
    public function validate_before_save($data, $postarr) {
        if ($data['post_type'] !== 'incidente_stradale') {
            return $data;
        }
        
        // Skip validation for auto-drafts
        if ($data['post_status'] === 'auto-draft') {
            return $data;
        }

        // CRITICO: Non interferire con operazioni di eliminazione
        if (isset($_GET['action']) && in_array($_GET['action'], ['trash', 'delete', 'untrash'])) {
            return $data;
        }
        
        if (isset($_POST['action']) && in_array($_POST['action'], ['trash', 'delete', 'untrash'])) {
            return $data;
        }
        
        // Non interferire con bulk actions di eliminazione
        if (isset($_POST['action']) && $_POST['action'] === '-1' && isset($_POST['action2'])) {
            $action = $_POST['action2'];
            if (in_array($action, ['trash', 'delete', 'untrash'])) {
                return $data;
            }
        }
        
        // Skip validation for auto-drafts
        if ($data['post_status'] === 'auto-draft') {
            return $data;
        }
        
        // Skip validation per post già nel cestino
        if ($data['post_status'] === 'trash') {
            return $data;
        }
        
        $errors = array();
        
        // Validate required fields
        $required_fields = array(
            'data_incidente' => __('Data dell\'incidente', 'incidenti-stradali'),
            'ora_incidente' => __('Ora dell\'incidente', 'incidenti-stradali'),
            'provincia_incidente' => __('Provincia', 'incidenti-stradali'),
            'comune_incidente' => __('Comune', 'incidenti-stradali'),
            'tipo_strada' => __('Tipo di strada', 'incidenti-stradali'),
            'natura_incidente' => __('Natura dell\'incidente', 'incidenti-stradali'),
            'circostanza_tipo' => __('Tipo di circostanza', 'incidenti-stradali')
        );
        
        foreach ($required_fields as $field => $label) {
            // Controllo speciale per il campo nell_abitato che può essere 0 o 1
            if ($field === 'nell_abitato') {
                if (!isset($_POST[$field]) || $_POST[$field] === '') {
                    $errors[] = sprintf(__('Il campo "%s" è obbligatorio.', 'incidenti-stradali'), $label);
                }
            } else {
                // Controllo normale per gli altri campi
                if (empty($_POST[$field])) {
                    $errors[] = sprintf(__('Il campo "%s" è obbligatorio.', 'incidenti-stradali'), $label);
                }
            }
        }
        
        // Validate data incidente format
        if (!empty($_POST['data_incidente'])) {
            if (!$this->validate_date($_POST['data_incidente'])) {
                $errors[] = __('La data dell\'incidente non è in un formato valido.', 'incidenti-stradali');
            }
        }
        
        // Validate ora incidente
        if (!empty($_POST['ora_incidente'])) {
            $ora = intval($_POST['ora_incidente']);
            if ($ora < 0 || $ora > 24) {
                $errors[] = __('L\'ora dell\'incidente deve essere compresa tra 0 e 24.', 'incidenti-stradali');
            }
        }
        
        // Validate provincia (ISTAT code)
        if (!empty($_POST['provincia_incidente'])) {
            if (!$this->validate_istat_code($_POST['provincia_incidente'], 3)) {
                $errors[] = __('Il codice ISTAT della provincia deve essere di 3 cifre.', 'incidenti-stradali');
            }
        }
        
        // Validate comune (ISTAT code)
        if (!empty($_POST['comune_incidente'])) {
            $comuni_lecce = array('001', '002', '003', '004', '005', '006', '007', '008', '009', '010',
                                '011', '012', '013', '014', '015', '016', '017', '018', '019', '020',
                                '021', '022', '023', '024', '025', '026', '027', '028', '029', '030',
                                '031', '032', '033', '034', '035', '036', '037', '038', '039', '040',
                                '041', '042', '043', '044', '045', '046', '047', '048', '049', '050',
                                '051', '052', '053', '054', '055', '056', '057', '058', '059', '060',
                                '061', '062', '063', '064', '065', '066', '067', '068', '069', '070',
                                '071', '072', '073', '074', '075', '076', '077', '078', '079', '080',
                                '081', '082', '083', '084', '085', '086', '087', '088', '089', '090',
                                '091', '092', '093', '094', '095', '096', '097', '098', '099');
            
            if (!in_array($_POST['comune_incidente'], $comuni_lecce)) {
                $errors[] = __('Il comune selezionato non è valido.', 'incidenti-stradali');
            }
        }
        
        // Validate coordinates if provided
        if (!empty($_POST['latitudine']) || !empty($_POST['longitudine'])) {
            if (!$this->validate_coordinates($_POST['latitudine'], $_POST['longitudine'])) {
                $errors[] = __('Le coordinate geografiche non sono valide.', 'incidenti-stradali');
            }
        }

        // Validazione circostanze
        if (!empty($_POST['circostanza_tipo'])) {
            $tipi_validi = array('intersezione', 'non_intersezione', 'investimento', 'urto_fermo', 'senza_urto');
            if (!in_array($_POST['circostanza_tipo'], $tipi_validi)) {
                $errors[] = __('Il tipo di circostanza selezionato non è valido.', 'incidenti-stradali');
            }
        }

        // Validazione codici circostanze
        if (!empty($_POST['circostanza_veicolo_a'])) {
            if (!preg_match('/^[0-9]{2}$/', $_POST['circostanza_veicolo_a'])) {
                $errors[] = __('Il codice circostanza Veicolo A deve essere di 2 cifre.', 'incidenti-stradali');
            }
        }

        if (!empty($_POST['circostanza_veicolo_b'])) {
            if (!preg_match('/^[0-9]{2}$/', $_POST['circostanza_veicolo_b'])) {
                $errors[] = __('Il codice circostanza Veicolo B deve essere di 2 cifre.', 'incidenti-stradali');
            }
        }

        // Validate circostanze requirement - almeno una deve essere compilata
        if (empty($_POST['circostanza_veicolo_a']) && empty($_POST['circostanza_veicolo_b']) && 
            empty($_POST['difetto_veicolo_a']) && empty($_POST['difetto_veicolo_b']) && 
            empty($_POST['stato_psicofisico_a']) && empty($_POST['stato_psicofisico_b'])) {
            $errors[] = __('È obbligatorio selezionare almeno una circostanza dell\'incidente.', 'incidenti-stradali');
        }
        
        // Validate natura incidente consistency
        if (!empty($_POST['natura_incidente']) && !empty($_POST['dettaglio_natura'])) {
            if (!$this->validate_natura_consistency($_POST['natura_incidente'], $_POST['dettaglio_natura'])) {
                $errors[] = __('Il dettaglio della natura dell\'incidente non è coerente con la natura selezionata.', 'incidenti-stradali');
            }
        }
        
        // Validate number of vehicles
        if (!empty($_POST['numero_veicoli_coinvolti'])) {
            $num_veicoli = intval($_POST['numero_veicoli_coinvolti']);
            if ($num_veicoli < 1 || $num_veicoli > 3) {
                $errors[] = __('Il numero di veicoli coinvolti deve essere compreso tra 1 e 3.', 'incidenti-stradali');
            }
            
            // Validate vehicle data consistency
            for ($i = 1; $i <= $num_veicoli; $i++) {
                $vehicle_errors = $this->validate_vehicle_data($i);
                $errors = array_merge($errors, $vehicle_errors);
            }
        }
        
        // Validate pedestrian data
        if (!empty($_POST['numero_pedoni_coinvolti'])) {
            $num_pedoni = intval($_POST['numero_pedoni_coinvolti']);
            if ($num_pedoni < 0 || $num_pedoni > 4) {
                $errors[] = __('Il numero di pedoni coinvolti deve essere compreso tra 0 e 4.', 'incidenti-stradali');
            }
            
            // Validate pedestrian data consistency
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $pedestrian_errors = $this->validate_pedestrian_data($i);
                $errors = array_merge($errors, $pedestrian_errors);
            }
        }

        // Validate progressiva chilometrica for extraurbane
        if (!empty($_POST['tipo_strada'])) {
            $tipo_strada = $_POST['tipo_strada'];
            $is_extraurbana = in_array($tipo_strada, ['4', '5', '6', '7', '8', '9']);
            
            if ($is_extraurbana && empty($_POST['progressiva_km'])) {
                $errors[] = __('La progressiva chilometrica è obbligatoria per le strade extraurbane.', 'incidenti-stradali');
            }
        }
        
        // Check date restrictions
        if (!empty($_POST['data_incidente'])) {
            $data_blocco = get_option('incidenti_data_blocco_modifica');
            if ($data_blocco && strtotime($_POST['data_incidente']) < strtotime($data_blocco)) {
                if (!current_user_can('manage_all_incidenti')) {
                    $errors[] = __('Non è possibile inserire incidenti avvenuti prima della data di blocco impostata.', 'incidenti-stradali');
                }
            }
        }
        
        // Check user permissions for comune
        if (!current_user_can('manage_all_incidenti') && !empty($_POST['comune_incidente'])) {
            $user_comune = get_user_meta(get_current_user_id(), 'comune_assegnato', true);
            if ($user_comune && $user_comune !== $_POST['comune_incidente']) {
                $errors[] = __('Non hai i permessi per inserire incidenti in questo comune.', 'incidenti-stradali');
            }
        }

        /* // Validazione Riepilogo Infortunati
        $riepilogo_errors = $this->validate_riepilogo_infortunati($postarr['ID'] ?? 0);
        if (!empty($riepilogo_errors)) {
            $errors = array_merge($errors, $riepilogo_errors);
        } */
        
        // Store errors in transient to display later
        if (!empty($errors)) {
            set_transient('incidenti_validation_errors_' . get_current_user_id(), $errors, 300);
            
            // Prevent saving if there are errors
            $data['post_status'] = 'draft';
            add_filter('redirect_post_location', array($this, 'add_validation_error_query_var'), 99);
        }
        
        return $data;
    }
    
    public function add_validation_error_query_var($location) {
        return add_query_arg('validation_errors', '1', $location);
    }
    
    public function display_validation_errors() {
        if (isset($_GET['validation_errors']) && $_GET['validation_errors'] === '1') {
            $errors = get_transient('incidenti_validation_errors_' . get_current_user_id());
            if ($errors) {
                echo '<div class="notice notice-info is-dismissible" style="border-left: 4px solid #2271b1; background-color: #f0f6fc;">';
                echo '<p><strong>' . __('Errori di validazione:', 'incidenti-stradali') . '</strong></p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                // Clear the errors
                delete_transient('incidenti_validation_errors_' . get_current_user_id());
            }
        }
    }
    
    public function ajax_validate_field() {
        check_ajax_referer('incidenti_nonce', 'nonce');
        
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        $response = array('valid' => true, 'message' => '');
        
        switch ($field) {
            case 'data_incidente':
                if (!$this->validate_date($value)) {
                    $response['valid'] = false;
                    $response['message'] = __('Data non valida.', 'incidenti-stradali');
                }
                break;
                
            case 'provincia_incidente':
                if (!$this->validate_istat_code($value, 3)) {
                    $response['valid'] = false;
                    $response['message'] = __('Codice ISTAT provincia non valido (3 cifre).', 'incidenti-stradali');
                }
                break;
                
            case 'comune_incidente':
                if (!$this->validate_istat_code($value, 3)) {
                    $response['valid'] = false;
                    $response['message'] = __('Codice ISTAT comune non valido (3 cifre).', 'incidenti-stradali');
                }
                break;
                
            case 'latitudine':
                if (!$this->validate_latitude($value)) {
                    $response['valid'] = false;
                    $response['message'] = __('Latitudine non valida.', 'incidenti-stradali');
                }
                break;
                
            case 'longitudine':
                if (!$this->validate_longitude($value)) {
                    $response['valid'] = false;
                    $response['message'] = __('Longitudine non valida.', 'incidenti-stradali');
                }
                break;
        }
        
        wp_send_json($response);
    }
    
    private function validate_date($date) {
        if (empty($date)) return false;
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function validate_istat_code($code, $length) {
        if (empty($code)) return false;
        
        return preg_match('/^\d{' . $length . '}$/', $code);
    }
    
    private function validate_coordinates($lat, $lng) {
        if (empty($lat) && empty($lng)) return true; // Both empty is OK
        if (empty($lat) || empty($lng)) return false; // Only one empty is not OK
        
        return $this->validate_latitude($lat) && $this->validate_longitude($lng);
    }
    
    private function validate_latitude($lat) {
        if (empty($lat)) return false;
        
        $lat = floatval($lat);
        return $lat >= -90 && $lat <= 90;
    }
    
    private function validate_longitude($lng) {
        if (empty($lng)) return false;
        
        $lng = floatval($lng);
        return $lng >= -180 && $lng <= 180;
    }
    
    private function validate_natura_consistency($natura, $dettaglio) {
        $valid_combinations = array(
            'A' => array('1', '2', '3', '4'),
            'B' => array('5'),
            'C' => array('6', '7', '8', '9'),
            'D' => array('10', '11', '12')
        );
        
        if (!isset($valid_combinations[$natura])) {
            return false;
        }
        
        return in_array($dettaglio, $valid_combinations[$natura]);
    }
    
    private function validate_vehicle_data($vehicle_num) {
        $errors = array();
        $prefix = 'veicolo_' . $vehicle_num . '_';
        
        // Check if vehicle type is provided
        if (empty($_POST[$prefix . 'tipo'])) {
            $errors[] = sprintf(__('Il tipo del veicolo %s è obbligatorio.', 'incidenti-stradali'), chr(64 + $vehicle_num));
        }
        
        // Validate year if provided
        if (!empty($_POST[$prefix . 'anno_immatricolazione'])) {
            $anno = intval($_POST[$prefix . 'anno_immatricolazione']);
            $current_year = intval(date('Y'));
            if ($anno < 1900 || $anno > $current_year) {
                $errors[] = sprintf(__('L\'anno di immatricolazione del veicolo %s non è valido.', 'incidenti-stradali'), chr(64 + $vehicle_num));
            }
        }
        
        // Validate engine capacity if provided
        if (!empty($_POST[$prefix . 'cilindrata'])) {
            $cilindrata = intval($_POST[$prefix . 'cilindrata']);
            if ($cilindrata < 0 || $cilindrata > 99999) {
                $errors[] = sprintf(__('La cilindrata del veicolo %s non è valida.', 'incidenti-stradali'), chr(64 + $vehicle_num));
            }
        }
        
        // Validate weight if provided
        if (!empty($_POST[$prefix . 'peso_totale'])) {
            $peso = floatval($_POST[$prefix . 'peso_totale']);
            if ($peso < 0 || $peso > 1000) {
                $errors[] = sprintf(__('Il peso del veicolo %s non è valido.', 'incidenti-stradali'), chr(64 + $vehicle_num));
            }
        }
        
        return $errors;
    }
    
    private function validate_pedestrian_data($pedestrian_num) {
        $errors = array();
        $prefix = 'pedone_' . $pedestrian_num . '_';
        
        // Check if age is valid
        if (!empty($_POST[$prefix . 'eta'])) {
            $eta = intval($_POST[$prefix . 'eta']);
            if ($eta < 0 || $eta > 120) {
                $errors[] = sprintf(__('L\'età del pedone %d non è valida.', 'incidenti-stradali'), $pedestrian_num);
            }
        }
        
        // Check if outcome is provided when other data is present
        if (!empty($_POST[$prefix . 'eta']) || !empty($_POST[$prefix . 'sesso'])) {
            if (empty($_POST[$prefix . 'esito'])) {
                $errors[] = sprintf(__('L\'esito del pedone %d è obbligatorio quando sono forniti altri dati.', 'incidenti-stradali'), $pedestrian_num);
            }
        }
        
        return $errors;
    }
    
    public function validate_driver_data($driver_num) {
        $errors = array();
        $prefix = 'conducente_' . $driver_num . '_';
        
        // Check if age is valid
        if (!empty($_POST[$prefix . 'eta'])) {
            $eta = intval($_POST[$prefix . 'eta']);
            if ($eta < 14 || $eta > 120) {
                $errors[] = sprintf(__('L\'età del conducente %s non è valida (deve essere tra 14 e 120 anni).', 'incidenti-stradali'), chr(64 + $driver_num));
            }
        }
        
        // Check if license year is valid
        if (!empty($_POST[$prefix . 'anno_patente'])) {
            $anno_patente = intval($_POST[$prefix . 'anno_patente']);
            $current_year = intval(date('Y'));
            if ($anno_patente < 1950 || $anno_patente > $current_year) {
                $errors[] = sprintf(__('L\'anno di rilascio della patente del conducente %s non è valido.', 'incidenti-stradali'), chr(64 + $driver_num));
            }
        }
        
        // Check license consistency with age
        if (!empty($_POST[$prefix . 'eta']) && !empty($_POST[$prefix . 'anno_patente'])) {
            $eta = intval($_POST[$prefix . 'eta']);
            $anno_patente = intval($_POST[$prefix . 'anno_patente']);
            $current_year = intval(date('Y'));
            $anno_nascita_approx = $current_year - $eta;
            
            if ($anno_patente < ($anno_nascita_approx + 14)) {
                $errors[] = sprintf(__('L\'anno di rilascio della patente del conducente %s non è coerente con l\'età.', 'incidenti-stradali'), chr(64 + $driver_num));
            }
        }
        
        return $errors;
    }
    
    public function get_validation_rules() {
        return array(
            'data_incidente' => array(
                'required' => true,
                'type' => 'date',
                'message' => __('La data dell\'incidente è obbligatoria e deve essere in formato valido.', 'incidenti-stradali')
            ),
            'ora_incidente' => array(
                'required' => true,
                'type' => 'number',
                'min' => 0,
                'max' => 24,
                'message' => __('L\'ora dell\'incidente è obbligatoria e deve essere compresa tra 0 e 24.', 'incidenti-stradali')
            ),
            'provincia_incidente' => array(
                'required' => true,
                'type' => 'istat_code',
                'length' => 3,
                'message' => __('Il codice ISTAT della provincia è obbligatorio e deve essere di 3 cifre.', 'incidenti-stradali')
            ),
            'comune_incidente' => array(
                'required' => true,
                'type' => 'istat_code',
                'length' => 3,
                'message' => __('Il codice ISTAT del comune è obbligatorio e deve essere di 3 cifre.', 'incidenti-stradali')
            ),
            'latitudine' => array(
                'required' => false,
                'type' => 'latitude',
                'message' => __('La latitudine deve essere compresa tra -90 e 90 gradi.', 'incidenti-stradali')
            ),
            'longitudine' => array(
                'required' => false,
                'type' => 'longitude',
                'message' => __('La longitudine deve essere compresa tra -180 e 180 gradi.', 'incidenti-stradali')
            )
        );
    }

    /**
     * Validazione del riepilogo infortunati
     */
    /* public function validate_riepilogo_infortunati($post_id) {
        $errors = array();
        
        // Conta reali infortunati dai campi del modulo
        $real_morti_24h = $this->count_esito_by_type('3');
        $real_morti_2_30gg = $this->count_esito_by_type('4');
        $real_feriti = $this->count_esito_by_type('2');

        // SEMPRE mostra il riepilogo calcolato automaticamente
        $riepilogo_message = sprintf(
            __('📊 RIEPILOGO AUTOMATICO CALCOLATO: Feriti: %d, Morti entro 24h: %d, Morti dal 2° al 30° giorno: %d', 'incidenti-stradali'),
            $real_feriti,
            $real_morti_24h, 
            $real_morti_2_30gg
        );

        // Aggiungi sempre il messaggio di riepilogo come prima voce
        $errors[] = $riepilogo_message;
        
        // Leggi valori inseriti nel riepilogo
        $riepilogo_morti_24h = isset($_POST['riepilogo_morti_24h']) ? (int) $_POST['riepilogo_morti_24h'] : 0;
        $riepilogo_morti_2_30gg = isset($_POST['riepilogo_morti_2_30gg']) ? (int) $_POST['riepilogo_morti_2_30gg'] : 0;
        $riepilogo_feriti = isset($_POST['riepilogo_feriti']) ? (int) $_POST['riepilogo_feriti'] : 0;
        
        // Confronta i valori
        if ($real_morti_24h !== $riepilogo_morti_24h) {
            $errors[] = sprintf(
                __('Morti entro 24h non corrispondono: rilevati %d dal modulo, inseriti %d nel riepilogo', 'incidenti-stradali'), 
                $real_morti_24h, 
                $riepilogo_morti_24h
            );
        }
        
        if ($real_morti_2_30gg !== $riepilogo_morti_2_30gg) {
            $errors[] = sprintf(
                __('Morti dal 2° al 30° giorno non corrispondono: rilevati %d dal modulo, inseriti %d nel riepilogo', 'incidenti-stradali'), 
                $real_morti_2_30gg, 
                $riepilogo_morti_2_30gg
            );
        }
        
        if ($real_feriti !== $riepilogo_feriti) {
            $errors[] = sprintf(
                __('Feriti non corrispondono: rilevati %d dal modulo, inseriti %d nel riepilogo', 'incidenti-stradali'), 
                $real_feriti, 
                $riepilogo_feriti
            );
        }
        
        return $errors;
    } */
    
    /**
     * Conta le persone per esito specifico dai campi del modulo
     */
    private function count_esito_by_type($esito_type) {
        $count = 0;
        
        // Conta conducenti
        for ($i = 1; $i <= 3; $i++) {
            $esito = isset($_POST["conducente_{$i}_esito"]) ? $_POST["conducente_{$i}_esito"] : '';
            if (!empty($esito) && ($esito == $esito_type || $esito == strval($esito_type))) {
                $count++;
            }
        }
        
        // Conta pedoni
        $num_pedoni = isset($_POST['numero_pedoni_coinvolti']) ? (int) $_POST['numero_pedoni_coinvolti'] : 0;
        for ($i = 1; $i <= $num_pedoni; $i++) {
            $esito = isset($_POST["pedone_{$i}_esito"]) ? $_POST["pedone_{$i}_esito"] : '';
            if (!empty($esito) && ($esito == $esito_type || $esito == strval($esito_type))) {
                $count++;
            }
        }
        
        // Conta trasportati dei veicoli
        $num_veicoli = isset($_POST['numero_veicoli_coinvolti']) ? (int) $_POST['numero_veicoli_coinvolti'] : 0;
        for ($v = 1; $v <= $num_veicoli; $v++) {
            // Conta i trasportati per ogni veicolo (max 4 trasportati per veicolo)
            $num_trasportati = isset($_POST["veicolo_{$v}_numero_trasportati"]) ? (int) $_POST["veicolo_{$v}_numero_trasportati"] : 0;
            for ($t = 1; $t <= $num_trasportati && $t <= 4; $t++) {
                $esito = isset($_POST["veicolo_{$v}_trasportato_{$t}_esito"]) ? $_POST["veicolo_{$v}_trasportato_{$t}_esito"] : '';
                if (!empty($esito) && ($esito == $esito_type || $esito == strval($esito_type))) {
                    $count++;
                }
            }
        }

        // Per morti entro 24h, includi anche esito "1" (Morti sul colpo)
        if ($esito_type == '3' || $esito_type == 3) {
            // Conta conducenti con esito "1" (morti immediati)
            for ($i = 1; $i <= 3; $i++) {
                $esito = isset($_POST["conducente_{$i}_esito"]) ? $_POST["conducente_{$i}_esito"] : '';
                if (!empty($esito) && ($esito == '1' || $esito == 1)) {
                    $count++;
                }
            }
            
            // Conta pedoni con esito "1"
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $esito = isset($_POST["pedone_{$i}_esito"]) ? $_POST["pedone_{$i}_esito"] : '';
                if (!empty($esito) && ($esito == '1' || $esito == 1)) {
                    $count++;
                }
            }
            
            // Conta trasportati con esito "1"
            for ($v = 1; $v <= $num_veicoli; $v++) {
                $num_trasportati = isset($_POST["veicolo_{$v}_numero_trasportati"]) ? (int) $_POST["veicolo_{$v}_numero_trasportati"] : 0;
                for ($t = 1; $t <= $num_trasportati && $t <= 4; $t++) {
                    $esito = isset($_POST["veicolo_{$v}_trasportato_{$t}_esito"]) ? $_POST["veicolo_{$v}_trasportato_{$t}_esito"] : '';
                    if (!empty($esito) && ($esito == '1' || $esito == 1)) {
                        $count++;
                    }
                }
            }
        }

        // Conta altri morti e feriti dei veicoli (solo per morti entro 24h e feriti)
        for ($v = 1; $v <= $num_veicoli; $v++) {
            if ($esito_type == '3' || $esito_type == 3) { // Morti entro 24h
                $altri_morti_m = isset($_POST["veicolo_{$v}_altri_morti_maschi"]) ? (int) $_POST["veicolo_{$v}_altri_morti_maschi"] : 0;
                $altri_morti_f = isset($_POST["veicolo_{$v}_altri_morti_femmine"]) ? (int) $_POST["veicolo_{$v}_altri_morti_femmine"] : 0;
                $count += $altri_morti_m + $altri_morti_f;
            } elseif ($esito_type == '2' || $esito_type == 2) { // Feriti
                $altri_feriti_m = isset($_POST["veicolo_{$v}_altri_feriti_maschi"]) ? (int) $_POST["veicolo_{$v}_altri_feriti_maschi"] : 0;
                $altri_feriti_f = isset($_POST["veicolo_{$v}_altri_feriti_femmine"]) ? (int) $_POST["veicolo_{$v}_altri_feriti_femmine"] : 0;
                $count += $altri_feriti_m + $altri_feriti_f;
            }
        }

        // Conta altri veicoli generali (solo per morti entro 24h e feriti)
        if ($esito_type == '3' || $esito_type == 3) { // Morti entro 24h
            $altri_morti_m_gen = isset($_POST['altri_morti_maschi']) ? (int) $_POST['altri_morti_maschi'] : 0;
            $altri_morti_f_gen = isset($_POST['altri_morti_femmine']) ? (int) $_POST['altri_morti_femmine'] : 0;
            $count += $altri_morti_m_gen + $altri_morti_f_gen;
        } elseif ($esito_type == '2' || $esito_type == 2) { // Feriti
            $altri_feriti_m_gen = isset($_POST['altri_feriti_maschi']) ? (int) $_POST['altri_feriti_maschi'] : 0;
            $altri_feriti_f_gen = isset($_POST['altri_feriti_femmine']) ? (int) $_POST['altri_feriti_femmine'] : 0;
            $count += $altri_feriti_m_gen + $altri_feriti_f_gen;
        }
        
        return $count;
    }
}