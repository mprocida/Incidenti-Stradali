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
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_import_menu'), 21);
        add_action('admin_post_import_incidenti_csv', array($this, 'handle_csv_import'));
        add_action('wp_ajax_preview_csv_import', array($this, 'preview_csv_import'));
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
     * Preview CSV import
     */
    public function preview_csv_import() {
        check_ajax_referer('import_incidenti_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $uploaded_file = $this->handle_file_upload_ajax();
        if (is_wp_error($uploaded_file)) {
            wp_send_json_error($uploaded_file->get_error_message());
        }
        
        $separator = sanitize_text_field($_POST['separator']);
        $preview_data = $this->parse_csv_preview($uploaded_file, $separator);
        
        wp_send_json_success($preview_data);
    }
    
    /**
     * Gestisce l'importazione CSV
     */
    public function handle_csv_import() {
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
        $result = $this->process_csv_file($uploaded_file, $separator);
        
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
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Errore durante l\'upload del file.', 'incidenti-stradali'));
        }
        
        $file = $_FILES['csv_file'];
        
        // Validazione tipo file
        $allowed_types = array('text/csv', 'application/csv', 'text/plain');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) && $file_type['ext'] !== 'csv') {
            return new WP_Error('invalid_file', __('Il file deve essere in formato CSV.', 'incidenti-stradali'));
        }
        
        // Validazione dimensione (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Il file è troppo grande. Dimensione massima: 10MB.', 'incidenti-stradali'));
        }
        
        // Sposta file nella directory temporanea
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/temp_import_' . uniqid() . '.csv';
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            return new WP_Error('move_error', __('Impossibile salvare il file temporaneo.', 'incidenti-stradali'));
        }
        
        return $temp_file;
    }
    
    /**
     * Handle AJAX file upload
     */
    private function handle_file_upload_ajax() {
        if (!isset($_FILES['csv_file'])) {
            return new WP_Error('no_file', __('Nessun file selezionato.', 'incidenti-stradali'));
        }
        
        return $this->handle_file_upload();
    }
    
    /**
     * Parse CSV for preview
     */
    private function parse_csv_preview($file_path, $separator = ',') {
        if (!file_exists($file_path)) {
            return array('error' => __('File non trovato.', 'incidenti-stradali'));
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('error' => __('Impossibile leggere il file.', 'incidenti-stradali'));
        }
        
        // Leggi header
        $header = fgetcsv($handle, 0, $separator);
        if (!$header) {
            fclose($handle);
            return array('error' => __('Header del CSV non valido.', 'incidenti-stradali'));
        }
        
        // Mappa campi richiesti
        $required_fields = array(
            'data_incidente',
            'ora_incidente', 
            'comune_incidente',
            'denominazione_strada',
            'numero_veicoli_coinvolti'
        );
        
        $field_mapping = $this->map_csv_fields($header, $required_fields);
        
        $preview_data = array();
        $total_rows = 0;
        $valid_rows = 0;
        $error_rows = 0;
        $errors = array();
        
        // Leggi prime 10 righe per preview
        while (($row = fgetcsv($handle, 0, $separator)) !== FALSE && $total_rows < 10) {
            $total_rows++;
            
            $mapped_data = $this->map_row_data($row, $field_mapping, $header);
            $validation_result = $this->validate_row_data($mapped_data);
            
            if ($validation_result['valid']) {
                $valid_rows++;
            } else {
                $error_rows++;
                $errors = array_merge($errors, $validation_result['errors']);
            }
            
            $preview_data[] = array(
                'data' => $mapped_data,
                'valid' => $validation_result['valid'],
                'errors' => $validation_result['errors']
            );
        }
        
        fclose($handle);
        
        return array(
            'total_rows' => $total_rows,
            'valid_rows' => $valid_rows,
            'error_rows' => $error_rows,
            'preview' => $preview_data,
            'errors' => array_unique($errors),
            'field_mapping' => $field_mapping
        );
    }
    
    /**
     * Process full CSV file
     */
    private function process_csv_file($file_path, $separator = ',') {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => __('Impossibile leggere il file.', 'incidenti-stradali'));
        }
        
        // Leggi header
        $header = fgetcsv($handle, 0, $separator);
        $required_fields = array('data_incidente', 'ora_incidente', 'comune_incidente', 'denominazione_strada', 'numero_veicoli_coinvolti');
        $field_mapping = $this->map_csv_fields($header, $required_fields);
        
        $imported = 0;
        $errors = 0;
        $line_number = 1;
        
        while (($row = fgetcsv($handle, 0, $separator)) !== FALSE) {
            $line_number++;
            
            $mapped_data = $this->map_row_data($row, $field_mapping, $header);
            $validation_result = $this->validate_row_data($mapped_data);
            
            if ($validation_result['valid']) {
                $post_id = $this->create_incidente_from_data($mapped_data);
                if ($post_id) {
                    $imported++;
                } else {
                    $errors++;
                    error_log("Errore creazione incidente riga $line_number");
                }
            } else {
                $errors++;
                error_log("Errore validazione riga $line_number: " . implode(', ', $validation_result['errors']));
            }
        }
        
        fclose($handle);
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        );
    }
    
    /**
     * Map CSV fields to internal fields
     */
    private function map_csv_fields($header, $required_fields) {
        $mapping = array();
        
        foreach ($header as $index => $field_name) {
            $clean_field = strtolower(trim($field_name));
            $clean_field = str_replace(array(' ', '-'), '_', $clean_field);
            
            if (in_array($clean_field, $required_fields)) {
                $mapping[$clean_field] = $index;
            }
        }
        
        return $mapping;
    }
    
    /**
     * Map row data using field mapping
     */
    private function map_row_data($row, $field_mapping, $header) {
        $mapped_data = array();
        
        foreach ($field_mapping as $field_name => $column_index) {
            $mapped_data[$field_name] = isset($row[$column_index]) ? trim($row[$column_index]) : '';
        }
        
        // Map additional fields if present
        foreach ($header as $index => $field_name) {
            $clean_field = strtolower(trim(str_replace(array(' ', '-'), '_', $field_name)));
            if (!isset($mapped_data[$clean_field]) && isset($row[$index])) {
                $mapped_data[$clean_field] = trim($row[$index]);
            }
        }
        
        return $mapped_data;
    }
    
    /**
     * Validate row data
     */
    private function validate_row_data($data) {
        $errors = array();
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
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Create incidente from CSV data
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
            'ora_incidente',
            'comune_incidente',
            'provincia_incidente',
            'denominazione_strada',
            'numero_veicoli_coinvolti',
            'latitudine',
            'longitudine',
            'tipo_strada',
            'natura_incidente',
            'localizzazione_incidente',
            'fondo_stradale',
            'condizioni_meteo',
            'visibilita',
            'illuminazione'
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
        if ($hook !== 'incidente_stradale_page_incidenti-import') {
            return;
        }
        
        wp_enqueue_script(
            'incidenti-admin-js',
            INCIDENTI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Passa variabili JavaScript
        wp_localize_script('incidenti-admin-js', 'incidenti_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('import_incidenti_nonce')
        ));
    }
}