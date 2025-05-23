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
        
        $errors = array();
        
        // Validate required fields
        $required_fields = array(
            'data_incidente' => __('Data dell\'incidente', 'incidenti-stradali'),
            'ora_incidente' => __('Ora dell\'incidente', 'incidenti-stradali'),
            'provincia_incidente' => __('Provincia', 'incidenti-stradali'),
            'comune_incidente' => __('Comune', 'incidenti-stradali'),
            'nell_abitato' => __('Nell\'abitato', 'incidenti-stradali'),
            'tipo_strada' => __('Tipo di strada', 'incidenti-stradali'),
            'natura_incidente' => __('Natura dell\'incidente', 'incidenti-stradali')
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('Il campo "%s" è obbligatorio.', 'incidenti-stradali'), $label);
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
            if (!$this->validate_istat_code($_POST['comune_incidente'], 3)) {
                $errors[] = __('Il codice ISTAT del comune deve essere di 3 cifre.', 'incidenti-stradali');
            }
        }
        
        // Validate coordinates if provided
        if (!empty($_POST['latitudine']) || !empty($_POST['longitudine'])) {
            if (!$this->validate_coordinates($_POST['latitudine'], $_POST['longitudine'])) {
                $errors[] = __('Le coordinate geografiche non sono valide.', 'incidenti-stradali');
            }
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
                echo '<div class="notice notice-error is-dismissible">';
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
            if ($cilindrata < 0 || $cilindrata > 10000) {
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
}