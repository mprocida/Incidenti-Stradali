<?php
/**
 * Export Functions for Incidenti Stradali
 */

class IncidentiExportFunctions {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_export_menu'));
        add_action('wp_ajax_export_incidenti_istat', array($this, 'export_istat_txt'));
        add_action('wp_ajax_export_incidenti_excel', array($this, 'export_excel'));
        add_action('admin_post_bulk_export_incidenti', array($this, 'handle_bulk_export'));
        add_filter('bulk_actions-edit-incidente_stradale', array($this, 'add_bulk_export_actions'));
        add_filter('handle_bulk_actions-edit-incidente_stradale', array($this, 'handle_bulk_actions'), 10, 3);
        add_action('admin_notices', array($this, 'bulk_export_admin_notices'));
    }
    
    public function add_export_menu() {
        // Verifica che l'utente abbia i permessi necessari
        if (!current_user_can('export_incidenti')) {
            return;
        }
        
        add_submenu_page(
            'edit.php?post_type=incidente_stradale',
            __('Esporta Dati', 'incidenti-stradali'),
            __('Esporta Dati', 'incidenti-stradali'),
            'export_incidenti',
            'incidenti-export',
            array($this, 'export_page')
        );
    }
    
    public function export_page() {
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'incidenti-stradali'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Esporta Dati Incidenti', 'incidenti-stradali'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Esportazione Formato ISTAT (TXT)', 'incidenti-stradali'); ?></h2>
                <p><?php _e('Esporta i dati nel formato richiesto da ISTAT per la trasmissione ufficiale.', 'incidenti-stradali'); ?></p>
                
                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
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
                                <input type="text" name="comune_filtro" placeholder="<?php _e('Codice ISTAT comune (lascia vuoto per tutti)', 'incidenti-stradali'); ?>">
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Esporta TXT ISTAT', 'incidenti-stradali'), 'primary'); ?>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Esportazione Formato Excel (XLS)', 'incidenti-stradali'); ?></h2>
                <p><?php _e('Esporta i dati nel formato Excel per la Polizia Stradale.', 'incidenti-stradali'); ?></p>
                
                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
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
                                <input type="text" name="comune_filtro" placeholder="<?php _e('Codice ISTAT comune (lascia vuoto per tutti)', 'incidenti-stradali'); ?>">
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Esporta Excel', 'incidenti-stradali'), 'primary'); ?>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Log Esportazioni', 'incidenti-stradali'); ?></h2>
                <?php $this->display_export_logs(); ?>
            </div>
        </div>
        <?php
    }
    
    public function export_istat_txt() {
        // Verifica permessi
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        // Verifica nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_incidenti_nonce')) {
            wp_die(__('Nonce verification failed.', 'incidenti-stradali'));
        }
        
        $data_inizio = sanitize_text_field($_POST['data_inizio']);
        $data_fine = sanitize_text_field($_POST['data_fine']);
        $comune_filtro = sanitize_text_field($_POST['comune_filtro']);
        
        // Query per ottenere gli incidenti
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
        
        if (empty($incidenti)) {
            // Redirect con messaggio di errore invece di wp_die
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        // Genera il file TXT secondo il tracciato ISTAT
        $filename = 'export_incidenti_' . date('YmdHis') . '.txt';
        $output = $this->generate_istat_txt($incidenti);
        
        // Log dell'esportazione
        $this->log_export('ISTAT_TXT', count($incidenti), $filename);
        
        // Download del file
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        echo $output;
        exit;
    }
    
    public function export_excel() {
        // Verifica permessi
        if (!current_user_can('export_incidenti')) {
            wp_die(__('Permessi insufficienti.', 'incidenti-stradali'));
        }
        
        // Verifica nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_incidenti_nonce')) {
            wp_die(__('Nonce verification failed.', 'incidenti-stradali'));
        }
        
        $data_inizio = sanitize_text_field($_POST['data_inizio']);
        $data_fine = sanitize_text_field($_POST['data_fine']);
        $comune_filtro = sanitize_text_field($_POST['comune_filtro']);
        
        // Query per ottenere gli incidenti
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
        
        if (empty($incidenti)) {
            // Redirect con messaggio di errore invece di wp_die
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        // Genera il file Excel
        $filename = 'export_incidenti_' . date('YmdHis') . '.csv';
        $output = $this->generate_excel_csv($incidenti);
        
        // Log dell'esportazione
        $this->log_export('Excel_CSV', count($incidenti), $filename);
        
        // Download del file
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo $output;
        exit;
    }
    
    public function generate_istat_txt($incidenti) {
        $output = '';
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $esitoTXT = array();
            $indTXT = -1;
            
            // Anno (ultime 2 cifre della data rilevazione)
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            $indTXT++;
            $esitoTXT[$indTXT] = substr($data_incidente, 2, 2);
            $esitoTXT[$indTXT] = str_pad($esitoTXT[$indTXT], 2, "0", STR_PAD_LEFT);
            
            // Mese
            $indTXT++;
            $esitoTXT[$indTXT] = substr($data_incidente, 5, 2);
            $esitoTXT[$indTXT] = str_pad($esitoTXT[$indTXT], 2, "0", STR_PAD_LEFT);
            
            // Provincia
            $indTXT++;
            $provincia = get_post_meta($post_id, 'provincia_incidente', true);
            $esitoTXT[$indTXT] = str_pad($provincia, 3, "0", STR_PAD_LEFT);
            
            // Comune
            $indTXT++;
            $comune = get_post_meta($post_id, 'comune_incidente', true);
            $esitoTXT[$indTXT] = str_pad($comune, 3, "0", STR_PAD_LEFT);
            
            // Numero d'ordine (incrementale)
            $indTXT++;
            $numero_ordine = str_pad($post_id, 4, "0", STR_PAD_LEFT);
            $esitoTXT[$indTXT] = $numero_ordine;
            
            // Giorno
            $indTXT++;
            $esitoTXT[$indTXT] = substr($data_incidente, -2);
            $esitoTXT[$indTXT] = str_pad($esitoTXT[$indTXT], 2, "0", STR_PAD_LEFT);
            
            // Ora
            $indTXT++;
            $ora = get_post_meta($post_id, 'ora_incidente', true);
            $esitoTXT[$indTXT] = str_pad($ora ?: '25', 2, "0", STR_PAD_LEFT);
            
            // Organo di rilevazione
            $indTXT++;
            $organo = get_post_meta($post_id, 'organo_rilevazione', true);
            $esitoTXT[$indTXT] = $organo ?: '0';
            if ((int)$esitoTXT[$indTXT] > 4) {
                $esitoTXT[$indTXT] = '5'; // Altri=5
            }
            
            // Numero progressivo nell'anno
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad($post_id, 5, "0", STR_PAD_LEFT);
            
            // Organo coordinatore
            $indTXT++;
            $organo_coord = get_post_meta($post_id, 'organo_coordinatore', true);
            $esitoTXT[$indTXT] = $organo_coord ?: '0';
            
            // Localizzazione dell'incidente
            $indTXT++;
            $tipo_strada = get_post_meta($post_id, 'tipo_strada', true);
            $esitoTXT[$indTXT] = $tipo_strada ?: '0';
            
            // Denominazione strada
            $indTXT++;
            $numero_strada = get_post_meta($post_id, 'numero_strada', true);
            $esitoTXT[$indTXT] = str_pad(substr($numero_strada, 0, 3), 3, "~", STR_PAD_LEFT);
            if (empty(trim($esitoTXT[$indTXT]))) {
                $esitoTXT[$indTXT] = '~~~';
            }
            
            // Progressiva chilometrica
            $indTXT++;
            $progressiva_km = get_post_meta($post_id, 'progressiva_km', true);
            $esitoTXT[$indTXT] = str_pad(substr($progressiva_km, 0, 3), 3, "0", STR_PAD_LEFT);
            if (empty($progressiva_km)) {
                $esitoTXT[$indTXT] = '000';
            }
            
            // Tronco di strada (placeholder)
            $indTXT++;
            $esitoTXT[$indTXT] = '00';
            
            // Tipo di strada (geometria)
            $indTXT++;
            $geometria = get_post_meta($post_id, 'geometria_strada', true);
            $esitoTXT[$indTXT] = $geometria ?: '0';
            
            // Pavimentazione
            $indTXT++;
            $pavimentazione = get_post_meta($post_id, 'pavimentazione_strada', true);
            $esitoTXT[$indTXT] = $pavimentazione ?: '0';
            
            // Intersezione
            $indTXT++;
            $intersezione = get_post_meta($post_id, 'intersezione_tronco', true);
            $esitoTXT[$indTXT] = str_pad($intersezione ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Fondo stradale
            $indTXT++;
            $fondo = get_post_meta($post_id, 'stato_fondo_strada', true);
            $esitoTXT[$indTXT] = $fondo ?: '0';
            
            // Segnaletica
            $indTXT++;
            $segnaletica = get_post_meta($post_id, 'segnaletica_strada', true);
            $esitoTXT[$indTXT] = $segnaletica ?: '0';
            
            // Condizioni meteorologiche
            $indTXT++;
            $meteo = get_post_meta($post_id, 'condizioni_meteo', true);
            $esitoTXT[$indTXT] = $meteo ?: '0';
            
            // Natura dell'incidente
            $indTXT++;
            $natura = get_post_meta($post_id, 'dettaglio_natura', true);
            $esitoTXT[$indTXT] = str_pad($natura ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Dati veicoli (fino a 3 veicoli)
            $num_veicoli = (int) get_post_meta($post_id, 'numero_veicoli_coinvolti', true) ?: 1;
            
            // Tipo veicolo per 3 veicoli
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $indTXT++;
                $tipo_veicolo = '';
                if ($numVeicolo <= $num_veicoli) {
                    $tipo_veicolo = get_post_meta($post_id, 'veicolo_' . $numVeicolo . '_tipo', true);
                }
                $esitoTXT[$indTXT] = str_pad($tipo_veicolo ?: '~~', 2, '~', STR_PAD_LEFT);
            }
            
            // Cilindrata per 3 veicoli
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $indTXT++;
                $cilindrata = '';
                if ($numVeicolo <= $num_veicoli) {
                    $cilindrata = get_post_meta($post_id, 'veicolo_' . $numVeicolo . '_cilindrata', true);
                    $cilindrata = round($cilindrata);
                }
                $esitoTXT[$indTXT] = str_pad($cilindrata ?: '~~~~', 4, $cilindrata ? '0' : '~', STR_PAD_LEFT);
            }
            
            // Peso totale per 3 veicoli
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                $indTXT++;
                $peso = '';
                if ($numVeicolo <= $num_veicoli) {
                    $peso = get_post_meta($post_id, 'veicolo_' . $numVeicolo . '_peso_totale', true);
                    $peso = round($peso);
                }
                $esitoTXT[$indTXT] = str_pad($peso ?: '~~~~', 4, $peso ? '0' : '~', STR_PAD_LEFT);
            }
            
            // Dati conducenti per 3 veicoli
            for ($numVeicolo = 1; $numVeicolo <= 3; $numVeicolo++) {
                // Età
                $indTXT++;
                $eta = '';
                if ($numVeicolo <= $num_veicoli) {
                    $eta = get_post_meta($post_id, 'conducente_' . $numVeicolo . '_eta', true);
                }
                $esitoTXT[$indTXT] = str_pad($eta ?: '00', 2, '0', STR_PAD_LEFT);
                
                // Sesso
                $indTXT++;
                $sesso = '';
                if ($numVeicolo <= $num_veicoli) {
                    $sesso = get_post_meta($post_id, 'conducente_' . $numVeicolo . '_sesso', true);
                }
                $esitoTXT[$indTXT] = $sesso ?: '~';
                
                // Esito
                $indTXT++;
                $esito = '';
                if ($numVeicolo <= $num_veicoli) {
                    $esito = get_post_meta($post_id, 'conducente_' . $numVeicolo . '_esito', true);
                }
                $esitoTXT[$indTXT] = $esito ?: '~';
                
                // Tipo patente
                $indTXT++;
                $patente = '';
                if ($numVeicolo <= $num_veicoli) {
                    $patente = get_post_meta($post_id, 'conducente_' . $numVeicolo . '_tipo_patente', true);
                }
                $esitoTXT[$indTXT] = $patente ?: '~';
                
                // Anno patente
                $indTXT++;
                $anno_patente = '';
                if ($numVeicolo <= $num_veicoli) {
                    $anno_patente = get_post_meta($post_id, 'conducente_' . $numVeicolo . '_anno_patente', true);
                    $anno_patente = substr($anno_patente, -2);
                }
                $esitoTXT[$indTXT] = str_pad($anno_patente ?: '~~', 2, '~', STR_PAD_LEFT);
            }
            
            // Dati pedoni (fino a 4)
            $num_pedoni = (int) get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
            
            for ($numPedone = 1; $numPedone <= 4; $numPedone++) {
                $esito_pedone = '';
                $eta_pedone = '';
                $sesso_pedone = '';
                
                if ($numPedone <= $num_pedoni) {
                    $esito_pedone = get_post_meta($post_id, 'pedone_' . $numPedone . '_esito', true);
                    $eta_pedone = get_post_meta($post_id, 'pedone_' . $numPedone . '_eta', true);
                    $sesso_pedone = get_post_meta($post_id, 'pedone_' . $numPedone . '_sesso', true);
                }
                
                $is_morto = ($esito_pedone == '3' || $esito_pedone == '4');
                $is_ferito = ($esito_pedone == '2');
                
                // Sesso pedone morto
                $indTXT++;
                $esitoTXT[$indTXT] = $is_morto ? $sesso_pedone : '~';
                
                // Età pedone morto
                $indTXT++;
                $esitoTXT[$indTXT] = $is_morto ? str_pad($eta_pedone ?: '~~', 2, '~', STR_PAD_LEFT) : '~~';
                
                // Sesso pedone ferito
                $indTXT++;
                $esitoTXT[$indTXT] = $is_ferito ? $sesso_pedone : '~';
                
                // Età pedone ferito
                $indTXT++;
                $esitoTXT[$indTXT] = $is_ferito ? str_pad($eta_pedone ?: '~~', 2, '~', STR_PAD_LEFT) : '~~';
            }
            
            // Altri campi richiesti dal tracciato ISTAT (placeholder con valori di default)
            for ($i = 0; $i < 20; $i++) {
                $indTXT++;
                $esitoTXT[$indTXT] = '00';
            }
            
            // Spazi
            $indTXT++;
            $esitoTXT[$indTXT] = '         ';
            
            // Denominazione strada completa
            $indTXT++;
            $denominazione_completa = get_post_meta($post_id, 'denominazione_strada', true);
            $esitoTXT[$indTXT] = str_pad(substr($denominazione_completa, 0, 57), 57, '~', STR_PAD_RIGHT);
            
            // 100 spazi
            $indTXT++;
            $esitoTXT[$indTXT] = str_pad('', 100, '~', STR_PAD_RIGHT);
            
            // Nomi deceduti e feriti (placeholder)
            for ($i = 0; $i < 32; $i++) {
                $indTXT++;
                $esitoTXT[$indTXT] = str_pad('', 30, '~', STR_PAD_RIGHT);
            }
            
            // Converti tilde in spazi e aggiungi alla output
            $esitoTXTstr = str_replace('~', ' ', implode('', $esitoTXT));
            $output .= $esitoTXTstr . "\r\n";
        }
        
        return $output;
    }
    
    public function generate_excel_csv($incidenti) {
        $output = '';
        
        // Header CSV
        $headers = array(
            'ID', 'Data', 'Ora', 'Provincia', 'Comune', 'Tipo Strada', 'Denominazione Strada',
            'Natura Incidente', 'Num Veicoli', 'Tipo Veicolo A', 'Tipo Veicolo B', 'Tipo Veicolo C',
            'Età Conducente A', 'Sesso Conducente A', 'Esito Conducente A',
            'Età Conducente B', 'Sesso Conducente B', 'Esito Conducente B',
            'Età Conducente C', 'Sesso Conducente C', 'Esito Conducente C',
            'Num Pedoni', 'Latitudine', 'Longitudine'
        );
        
        $output .= implode(';', $headers) . "\n";
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            
            $row = array();
            $row[] = $post_id;
            $row[] = get_post_meta($post_id, 'data_incidente', true);
            $row[] = get_post_meta($post_id, 'ora_incidente', true) . ':' . get_post_meta($post_id, 'minuti_incidente', true);
            $row[] = get_post_meta($post_id, 'provincia_incidente', true);
            $row[] = get_post_meta($post_id, 'comune_incidente', true);
            $row[] = get_post_meta($post_id, 'tipo_strada', true);
            $row[] = get_post_meta($post_id, 'denominazione_strada', true);
            $row[] = get_post_meta($post_id, 'natura_incidente', true);
            $row[] = get_post_meta($post_id, 'numero_veicoli_coinvolti', true);
            
            // Veicoli
            for ($i = 1; $i <= 3; $i++) {
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_tipo', true);
            }
            
            // Conducenti
            for ($i = 1; $i <= 3; $i++) {
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_eta', true);
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_sesso', true);
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
            }
            
            $row[] = get_post_meta($post_id, 'numero_pedoni_coinvolti', true);
            $row[] = get_post_meta($post_id, 'latitudine', true);
            $row[] = get_post_meta($post_id, 'longitudine', true);
            
            $output .= implode(';', $row) . "\n";
        }
        
        return $output;
    }
    
    public function add_bulk_export_actions($bulk_actions) {
        if (current_user_can('export_incidenti')) {
            $bulk_actions['export_istat'] = __('Esporta ISTAT (TXT)', 'incidenti-stradali');
            $bulk_actions['export_excel'] = __('Esporta Excel (CSV)', 'incidenti-stradali');
        }
        return $bulk_actions;
    }
    
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (!current_user_can('export_incidenti')) {
            return $redirect_to;
        }
        
        if ($doaction === 'export_istat' || $doaction === 'export_excel') {
            // Verifica che ci siano post selezionati
            if (empty($post_ids)) {
                $redirect_to = add_query_arg('bulk_export_error', 'no_posts', $redirect_to);
                return $redirect_to;
            }
            
            // Store post IDs e tipo in transient per processing
            set_transient('incidenti_bulk_export_ids', $post_ids, 300);
            set_transient('incidenti_bulk_export_type', $doaction, 300);
            
            // Redirect alla pagina di processing
            $redirect_to = admin_url('admin-post.php?action=bulk_export_incidenti');
            return $redirect_to;
        }
        
        return $redirect_to;
    }
    
    public function bulk_export_admin_notices() {
        // Controlla errori di export
        if (isset($_GET['export_error'])) {
            $error = $_GET['export_error'];
            
            switch ($error) {
                case 'no_data':
                    $message = __('Nessun incidente trovato per il periodo selezionato.', 'incidenti-stradali');
                    break;
                case 'permission_denied':
                    $message = __('Non hai i permessi per esportare i dati.', 'incidenti-stradali');
                    break;
                default:
                    $message = __('Errore durante l\'esportazione.', 'incidenti-stradali');
            }
            
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
        
        // Controlla errori bulk export
        if (isset($_GET['bulk_export_error'])) {
            $error = $_GET['bulk_export_error'];
            
            switch ($error) {
                case 'no_posts':
                    $message = __('Nessun incidente selezionato per l\'esportazione.', 'incidenti-stradali');
                    break;
                case 'permission_denied':
                    $message = __('Non hai i permessi per esportare i dati.', 'incidenti-stradali');
                    break;
                default:
                    $message = __('Errore durante l\'esportazione.', 'incidenti-stradali');
            }
            
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
        
        // Controlla successo export
        if (isset($_GET['bulk_export_success'])) {
            $count = intval($_GET['bulk_export_success']);
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(__('Esportazione completata: %d incidenti esportati.', 'incidenti-stradali'), $count) . '</p>';
            echo '</div>';
        }
    }
    
    private function log_export($type, $count, $filename) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'incidenti_export_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'export_type' => $type,
                'export_date' => current_time('mysql'),
                'file_path' => $filename,
                'records_count' => $count
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }
    
    private function display_export_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'incidenti_export_logs';
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY export_date DESC LIMIT 20"
        );
        
        if (empty($logs)) {
            echo '<p>' . __('Nessuna esportazione effettuata.', 'incidenti-stradali') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Data', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Utente', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Tipo', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('Records', 'incidenti-stradali') . '</th>';
        echo '<th>' . __('File', 'incidenti-stradali') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($logs as $log) {
            $user = get_user_by('id', $log->user_id);
            echo '<tr>';
            echo '<td>' . mysql2date('d/m/Y H:i', $log->export_date) . '</td>';
            echo '<td>' . ($user ? $user->display_name : __('Utente eliminato', 'incidenti-stradali')) . '</td>';
            echo '<td>' . esc_html($log->export_type) . '</td>';
            echo '<td>' . esc_html($log->records_count) . '</td>';
            echo '<td>' . esc_html($log->file_path) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
}

// Inizializza la classe
new IncidentiExportFunctions();