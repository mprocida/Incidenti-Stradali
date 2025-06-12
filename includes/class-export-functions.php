<?php
/**
 * Export Functions for Incidenti Stradali
 */

class IncidentiExportFunctions {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_export_menu'), 20); // Priority 20 to ensure it runs after other menus
        add_action('admin_post_export_incidenti_istat', array($this, 'export_istat_txt'));
        add_action('admin_post_nopriv_export_incidenti_istat', array($this, 'export_istat_txt'));
        add_action('admin_post_export_incidenti_excel', array($this, 'export_excel'));
        add_action('admin_post_nopriv_export_incidenti_excel', array($this, 'export_excel'));
        add_filter('bulk_actions-edit-incidente_stradale', array($this, 'add_bulk_export_actions'));
        add_filter('handle_bulk_actions-edit-incidente_stradale', array($this, 'handle_bulk_actions'), 10, 3);
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
                                    $comuni_lecce = $this->get_comuni_lecce();
                                    foreach($comuni_lecce as $codice => $nome): ?>
                                        <option value="<?php echo esc_attr($codice); ?>">
                                            <?php echo esc_html($nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Filtra per comune specifico', 'incidenti-stradali'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Esporta TXT ISTAT', 'incidenti-stradali'), 'primary'); ?>
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
        
        // Aumenta il limite di tempo per l'esecuzione
        set_time_limit(300);
        
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
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'page' => 'incidenti-export',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        // Genera il file TXT secondo il tracciato ISTAT
        $filename = 'export_incidenti_' . date('YmdHis') . '.txt';
        $output = $this->generate_istat_txt($incidenti);
        
        // Log dell'esportazione
        $this->log_export('ISTAT_TXT', count($incidenti), $filename);

        // Trigger email notification
        do_action('incidenti_after_export', 'ISTAT_TXT', $filename, count($incidenti), get_current_user_id());

        // Download del file
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
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
        
        // Aumenta il limite di tempo per l'esecuzione
        set_time_limit(300);
        
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
            wp_redirect(add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'page' => 'incidenti-export',
                'export_error' => 'no_data'
            ), admin_url('edit.php')));
            exit;
        }
        
        // Genera il file Excel
        $filename = 'export_incidenti_' . date('YmdHis') . '.csv';
        $output = $this->generate_excel_csv($incidenti);
        
        // Log dell'esportazione
        $this->log_export('Excel_CSV', count($incidenti), $filename);

        // Trigger email notification
        do_action('incidenti_after_export', 'Excel_CSV', $filename, count($incidenti), get_current_user_id());

        // Download del file
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo $output;
        exit;
    }
    
    public function generate_istat_txt($incidenti) {
        $output = '';
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $record = '';
            
            // Posizione 1-2: Anno (2 cifre)
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            $anno = substr($data_incidente, 2, 2);
            $record .= str_pad($anno, 2, '0', STR_PAD_LEFT);
            
            // Posizione 3-4: Mese (2 cifre)
            $mese = substr($data_incidente, 5, 2);
            $record .= str_pad($mese, 2, '0', STR_PAD_LEFT);
            
            // Posizione 5-7: Provincia (3 cifre)
            $provincia = get_post_meta($post_id, 'provincia_incidente', true);
            $record .= str_pad($provincia, 3, '0', STR_PAD_LEFT);
            
            // Posizione 8-10: Comune (3 cifre)
            $comune = get_post_meta($post_id, 'comune_incidente', true);
            $record .= str_pad($comune, 3, '0', STR_PAD_LEFT);
            
            // Posizione 11-14: Numero d'ordine (4 cifre)
            $numero_ordine = str_pad($post_id, 4, '0', STR_PAD_LEFT);
            $record .= $numero_ordine;
            
            // Posizione 15-16: Giorno (2 cifre)
            $giorno = substr($data_incidente, -2);
            $record .= str_pad($giorno, 2, '0', STR_PAD_LEFT);
            
            // Posizione 17-18: Ora (2 cifre) - 25 se sconosciuta
            $ora = get_post_meta($post_id, 'ora_incidente', true);
            $record .= str_pad($ora ?: '25', 2, '0', STR_PAD_LEFT);
            
            // Posizione 19: Organo di rilevazione (1 cifra)
            $organo = get_post_meta($post_id, 'organo_rilevazione', true);
            $record .= $organo ?: '0';
            
            // Posizione 20-24: Numero progressivo nell'anno (5 cifre)
            $record .= str_pad($post_id, 5, '0', STR_PAD_LEFT);
            
            // Posizione 25: Organo coordinatore (1 cifra)
            $organo_coord = get_post_meta($post_id, 'organo_coordinatore', true);
            $record .= $organo_coord ?: '0';
            
            // Posizione 26: Localizzazione incidente (1 cifra)
            $tipo_strada = get_post_meta($post_id, 'tipo_strada', true);
            $record .= $tipo_strada ?: '0';
            
            // Posizione 27-29: Denominazione strada (3 caratteri)
            $numero_strada = get_post_meta($post_id, 'numero_strada', true);
            $record .= str_pad(substr($numero_strada, 0, 3), 3, ' ', STR_PAD_RIGHT);
            
            // Posizione 30-32: Progressiva chilometrica (3 cifre)
            $progressiva_km = get_post_meta($post_id, 'progressiva_km', true);
            $record .= str_pad($progressiva_km ?: '000', 3, '0', STR_PAD_LEFT);
            
            // Posizione 33-34: Tronco di strada (2 cifre)
            $record .= '00';
            
            // Posizione 35: Tipo di strada (1 cifra)
            $geometria = get_post_meta($post_id, 'geometria_strada', true);
            $record .= $geometria ?: '0';
            
            // Posizione 36: Pavimentazione (1 cifra)
            $pavimentazione = get_post_meta($post_id, 'pavimentazione_strada', true);
            $record .= $pavimentazione ?: '0';
            
            // Posizione 37-38: Intersezione (2 cifre)
            $intersezione = get_post_meta($post_id, 'intersezione_tronco', true);
            $record .= str_pad($intersezione ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Posizione 39: Fondo stradale (1 cifra)
            $fondo = get_post_meta($post_id, 'stato_fondo_strada', true);
            $record .= $fondo ?: '0';
            
            // Posizione 40: Segnaletica (1 cifra)
            $segnaletica = get_post_meta($post_id, 'segnaletica_strada', true);
            $record .= $segnaletica ?: '0';
            
            // Posizione 41: Condizioni meteorologiche (1 cifra)
            $meteo = get_post_meta($post_id, 'condizioni_meteo', true);
            $record .= $meteo ?: '0';
            
            // Posizione 42-43: Natura incidente (2 cifre)
            $natura = get_post_meta($post_id, 'dettaglio_natura', true);
            $record .= str_pad($natura ?: '00', 2, '0', STR_PAD_LEFT);
            
            // VEICOLI (3 veicoli, posizioni 44-61)
            $num_veicoli = (int) get_post_meta($post_id, 'numero_veicoli_coinvolti', true) ?: 1;
            
            // Tipo veicolo (2 caratteri per veicolo)
            for ($i = 1; $i <= 3; $i++) {
                if ($i <= $num_veicoli) {
                    $tipo = get_post_meta($post_id, 'veicolo_' . $i . '_tipo', true);
                    $record .= str_pad($tipo ?: '  ', 2, ' ', STR_PAD_LEFT);
                } else {
                    $record .= '  ';
                }
            }
            
            // Cilindrata (4 cifre per veicolo)
            for ($i = 1; $i <= 3; $i++) {
                if ($i <= $num_veicoli) {
                    $cilindrata = get_post_meta($post_id, 'veicolo_' . $i . '_cilindrata', true);
                    $record .= str_pad($cilindrata ?: '    ', 4, ' ', STR_PAD_LEFT);
                } else {
                    $record .= '    ';
                }
            }
            
            // Peso totale (4 cifre per veicolo)
            for ($i = 1; $i <= 3; $i++) {
                if ($i <= $num_veicoli) {
                    $peso = get_post_meta($post_id, 'veicolo_' . $i . '_peso_totale', true);
                    if (is_numeric($peso)) {
                        $peso = round((float) $peso);
                    } else {
                        $peso = 0;
                    }
                    $record .= str_pad($peso ?: '    ', 4, ' ', STR_PAD_LEFT);
                } else {
                    $record .= '    ';
                }
            }

            
            // CONDUCENTI (3 conducenti, posizioni 62-81)
            for ($i = 1; $i <= 3; $i++) {
                if ($i <= $num_veicoli) {
                    // Età (2 cifre)
                    $eta = get_post_meta($post_id, 'conducente_' . $i . '_eta', true);
                    $record .= str_pad($eta ?: '00', 2, '0', STR_PAD_LEFT);
                    
                    // Sesso (1 carattere)
                    $sesso = get_post_meta($post_id, 'conducente_' . $i . '_sesso', true);
                    $record .= $sesso ?: ' ';
                    
                    // Esito (1 carattere)
                    $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
                    $record .= $esito ?: ' ';
                    
                    // Tipo patente (1 carattere)
                    $patente = get_post_meta($post_id, 'conducente_' . $i . '_tipo_patente', true);
                    $record .= $patente ?: ' ';
                    
                    // Anno patente (2 cifre)
                    $anno_patente = get_post_meta($post_id, 'conducente_' . $i . '_anno_patente', true);
                    $anno_patente = $anno_patente ? substr($anno_patente, -2) : '  ';
                    $record .= str_pad($anno_patente, 2, ' ', STR_PAD_LEFT);
                } else {
                    $record .= '00     '; // 7 caratteri vuoti
                }
            }

            // TRASPORTATI (posizioni dopo i conducenti)
            // Per ogni veicolo, esporta i dati dei trasportati
            for ($v = 1; $v <= 3; $v++) {
                $num_trasportati = get_post_meta($post_id, 'veicolo_' . $v . '_numero_trasportati', true) ?: 0;
                
                // Numero trasportati morti maschi
                $morti_maschi = 0;
                // Numero trasportati morti femmine  
                $morti_femmine = 0;
                // Numero trasportati feriti maschi
                $feriti_maschi = 0;
                // Numero trasportati feriti femmine
                $feriti_femmine = 0;
                
                for ($t = 1; $t <= $num_trasportati; $t++) {
                    $prefix = 'veicolo_' . $v . '_trasportato_' . $t . '_';
                    $sesso = get_post_meta($post_id, $prefix . 'sesso', true);
                    $esito = get_post_meta($post_id, $prefix . 'esito', true);
                    
                    if ($esito == '3' || $esito == '4') { // Morto
                        if ($sesso == '1') $morti_maschi++;
                        if ($sesso == '2') $morti_femmine++;
                    } elseif ($esito == '2') { // Ferito
                        if ($sesso == '1') $feriti_maschi++;
                        if ($sesso == '2') $feriti_femmine++;
                    }
                }
                
                // Scrivi i conteggi nel record (2 cifre ciascuno)
                $record .= str_pad($morti_maschi, 2, '0', STR_PAD_LEFT);
                $record .= str_pad($morti_femmine, 2, '0', STR_PAD_LEFT);
                $record .= str_pad($feriti_maschi, 2, '0', STR_PAD_LEFT);
                $record .= str_pad($feriti_femmine, 2, '0', STR_PAD_LEFT);
            }
            
            // CIRCOSTANZE (posizioni 98-103)
            // Circostanze presunte generali
            $circostanza_1 = get_post_meta($post_id, 'circostanza_presunta_1', true);
            $record .= str_pad($circostanza_1 ?: '00', 2, '0', STR_PAD_LEFT);
            
            $circostanza_2 = get_post_meta($post_id, 'circostanza_presunta_2', true);
            $record .= str_pad($circostanza_2 ?: '00', 2, '0', STR_PAD_LEFT);
            
            $circostanza_3 = get_post_meta($post_id, 'circostanza_presunta_3', true);
            $record .= str_pad($circostanza_3 ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Circostanze per veicolo
            for ($v = 1; $v <= 3; $v++) {
                $circostanza_veicolo = get_post_meta($post_id, 'circostanza_veicolo_' . $v, true);
                $record .= str_pad($circostanza_veicolo ?: '00', 2, '0', STR_PAD_LEFT);
            }
            
            // ALTRI CAMPI AGGIUNTIVI (posizioni successive)
            // Altri veicoli coinvolti
            $altri_veicoli = get_post_meta($post_id, 'numero_altri_veicoli', true);
            $record .= str_pad($altri_veicoli ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Altri morti maschi
            $altri_morti_maschi = get_post_meta($post_id, 'altri_morti_maschi', true);
            $record .= str_pad($altri_morti_maschi ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Altri morti femmine
            $altri_morti_femmine = get_post_meta($post_id, 'altri_morti_femmine', true);
            $record .= str_pad($altri_morti_femmine ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Altri feriti maschi
            $altri_feriti_maschi = get_post_meta($post_id, 'altri_feriti_maschi', true);
            $record .= str_pad($altri_feriti_maschi ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Altri feriti femmine
            $altri_feriti_femmine = get_post_meta($post_id, 'altri_feriti_femmine', true);
            $record .= str_pad($altri_feriti_femmine ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Localizzazione extraurbana (se fuori abitato)
            $localizzazione_extra = get_post_meta($post_id, 'localizzazione_extra_ab', true);
            $record .= $localizzazione_extra ?: '0';
            
            // Illuminazione
            $illuminazione = get_post_meta($post_id, 'illuminazione', true);
            $record .= $illuminazione ?: '0';
            
            // Tipo di collisione
            $tipo_collisione = get_post_meta($post_id, 'tipo_collisione', true);
            $record .= str_pad($tipo_collisione ?: '00', 2, '0', STR_PAD_LEFT);
            
            // PEDONI (4 pedoni, posizioni 82-97)
            $num_pedoni = (int) get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
            
            for ($i = 1; $i <= 4; $i++) {
                if ($i <= $num_pedoni) {
                    $esito_pedone = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
                    $eta_pedone = get_post_meta($post_id, 'pedone_' . $i . '_eta', true);
                    $sesso_pedone = get_post_meta($post_id, 'pedone_' . $i . '_sesso', true);
                    
                    $is_morto = ($esito_pedone == '3' || $esito_pedone == '4');
                    $is_ferito = ($esito_pedone == '2');
                    
                    // Sesso pedone morto (1 carattere)
                    $record .= $is_morto ? $sesso_pedone : ' ';
                    
                    // Età pedone morto (2 cifre)
                    $record .= $is_morto ? str_pad($eta_pedone ?: '  ', 2, ' ', STR_PAD_LEFT) : '  ';
                    
                    // Sesso pedone ferito (1 carattere)
                    $record .= $is_ferito ? $sesso_pedone : ' ';
                    
                    // Età pedone ferito (2 cifre)
                    $record .= $is_ferito ? str_pad($eta_pedone ?: '  ', 2, ' ', STR_PAD_LEFT) : '  ';
                } else {
                    $record .= '      '; // 6 caratteri vuoti
                }
            }
            
            // CIRCOSTANZE (posizioni 98-103)
            // Circostanze veicolo A
            $circostanza_a = get_post_meta($post_id, 'circostanza_veicolo_a', true);
            $record .= str_pad($circostanza_a ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Circostanze veicolo B
            $circostanza_b = get_post_meta($post_id, 'circostanza_veicolo_b', true);
            $record .= str_pad($circostanza_b ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Circostanze veicolo C
            $circostanza_c = get_post_meta($post_id, 'circostanza_veicolo_c', true);
            $record .= str_pad($circostanza_c ?: '00', 2, '0', STR_PAD_LEFT);
            
            // ALTRI CAMPI (posizioni 104-150)
            // Altri veicoli coinvolti
            $record .= '00';
            
            // Altri morti maschi
            $record .= '00';
            
            // Altri morti femmine
            $record .= '00';
            
            // Altri feriti maschi
            $record .= '00';
            
            // Altri feriti femmine
            $record .= '00';
            
            // Morti entro 24 ore
            $morti_24h = 0;
            for ($i = 1; $i <= 3; $i++) {
                if (get_post_meta($post_id, 'conducente_' . $i . '_esito', true) == '3') $morti_24h++;
            }
            for ($i = 1; $i <= 4; $i++) {
                if (get_post_meta($post_id, 'pedone_' . $i . '_esito', true) == '3') $morti_24h++;
            }
            $record .= str_pad($morti_24h, 2, '0', STR_PAD_LEFT);
            
            // Morti dal 2° al 30° giorno
            $morti_30g = 0;
            for ($i = 1; $i <= 3; $i++) {
                if (get_post_meta($post_id, 'conducente_' . $i . '_esito', true) == '4') $morti_30g++;
            }
            for ($i = 1; $i <= 4; $i++) {
                if (get_post_meta($post_id, 'pedone_' . $i . '_esito', true) == '4') $morti_30g++;
            }
            $record .= str_pad($morti_30g, 2, '0', STR_PAD_LEFT);
            
            // Feriti totali
            $feriti = 0;
            for ($i = 1; $i <= 3; $i++) {
                if (get_post_meta($post_id, 'conducente_' . $i . '_esito', true) == '2') $feriti++;
            }
            for ($i = 1; $i <= 4; $i++) {
                if (get_post_meta($post_id, 'pedone_' . $i . '_esito', true) == '2') $feriti++;
            }
            $record .= str_pad($feriti, 2, '0', STR_PAD_LEFT);
            
            // Spazi riservati (9 caratteri)
            $record .= str_repeat(' ', 9);
            
            // Denominazione strada completa (57 caratteri)
            $denominazione = get_post_meta($post_id, 'denominazione_strada', true);
            $record .= str_pad(substr($denominazione, 0, 57), 57, ' ', STR_PAD_RIGHT);
            
            // 100 spazi
            $record .= str_repeat(' ', 100);
            
            // Nominativi morti e feriti (32 record di 30 caratteri ciascuno)
            // 4 morti (nome + cognome)
            for ($i = 1; $i <= 4; $i++) {
                $nome_morto = get_post_meta($post_id, 'morto_' . $i . '_nome', true);
                $cognome_morto = get_post_meta($post_id, 'morto_' . $i . '_cognome', true);
                
                $record .= str_pad(substr($nome_morto, 0, 30), 30, ' ', STR_PAD_RIGHT); // nome
                $record .= str_pad(substr($cognome_morto, 0, 30), 30, ' ', STR_PAD_RIGHT); // cognome
            }

            // 8 feriti (nome + cognome + istituto)
            for ($i = 1; $i <= 8; $i++) {
                $nome_ferito = get_post_meta($post_id, 'ferito_' . $i . '_nome', true);
                $cognome_ferito = get_post_meta($post_id, 'ferito_' . $i . '_cognome', true);
                $istituto_ferito = get_post_meta($post_id, 'ferito_' . $i . '_istituto', true);
                
                $record .= str_pad(substr($nome_ferito, 0, 30), 30, ' ', STR_PAD_RIGHT); // nome
                $record .= str_pad(substr($cognome_ferito, 0, 30), 30, ' ', STR_PAD_RIGHT); // cognome
                $record .= str_pad(substr($istituto_ferito, 0, 30), 30, ' ', STR_PAD_RIGHT); // istituto
            }
            
            // Assicurati che il record sia esattamente di 1024 caratteri
            $record = str_pad($record, 1024, ' ', STR_PAD_RIGHT);
            $record = substr($record, 0, 1024);
            
            $output .= $record . "\r\n";
        }
        
        return $output;
    }
    
    public function generate_excel_csv($incidenti) {
        $output = '';
        
        // Header CSV per formato Polizia (basato su Form_Inserimento_Incidenti_PLultimaversione)
        // Header CSV secondo form ISTAT ufficiale
        $headers = array(
            // Sezione 1: Data e Località dell'Incidente
            'Anno',
            'Mese',
            'Giorno',
            'Minuti',
            'Provincia',
            'Comune',
            'Localita',
            'Codice ISTAT',
            
            // Sezione 1.1: Localizzazione dell'incidente
            'Nell_Abitato',
            'Denominazione_Strada',
            'Numero_Strada',
            'Progressiva_KM',
            'Progressiva_MT',
            'Fuori_Abitato',
            'Tipo_Fuori_Abitato',
            'Latitudine',
            'Longitudine',
            
            // Sezione 2: Luogo dell'incidente
            'Tipo_Strada',
            'Pavimentazione',
            'Intersezione',
            'Non_Intersezione',
            'Fondo_Stradale',
            'Segnaletica',
            'Condizioni_Meteorologiche',
            'Illuminazione',
            
            // Sezione 3: Natura dell'incidente
            'Natura_Incidente_A',
            'Natura_Incidente_B',
            'Natura_Incidente_C',
            'Natura_Incidente_D',
            'Natura_Incidente_E',
            'Altro_Natura',
            
            // Sezione 4: Tipo di veicoli coinvolti (A, B, C)
            'Veicolo_A_Tipo',
            'Veicolo_B_Tipo', 
            'Veicolo_C_Tipo',
            
            // Sezione 5: Circostanze presenti dell'incidente
            'Inconvenienti_Circolazione',
            'Difetti_Avarie_Veicolo',
            'Stato_Psicofisico_Conducente',
            
            // Sezione 6: Veicoli coinvolti - Dettagli
            'Veicolo_A_Targa',
            'Veicolo_A_Sigla_Estera',
            'Veicolo_A_Anno_Immatricolazione',
            'Veicolo_B_Targa',
            'Veicolo_B_Sigla_Estera', 
            'Veicolo_B_Anno_Immatricolazione',
            'Veicolo_C_Targa',
            'Veicolo_C_Sigla_Estera',
            'Veicolo_C_Anno_Immatricolazione',
            
            // Organi di rilevazione e coordinamento
            'Organo_Rilevazione',
            'Organo_Coordinatore',
            'Numero_Progressivo_Anno',
            'Tronco_Strada_Autostrada'
        );
        
        // Scrivi header con separatore punto e virgola
        $output .= implode(';', $headers) . "\n";
        
        // Elabora ogni incidente
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $row = array();
            
            // Dati base incidente
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
            $minuti_incidente = get_post_meta($post_id, 'minuti_incidente', true);
            
            // Sezione 1: Data e Località
            $row[] = date('Y', strtotime($data_incidente)); // Anno
            $row[] = date('m', strtotime($data_incidente)); // Mese
            $row[] = date('d', strtotime($data_incidente)); // Giorno
            $row[] = $minuti_incidente ?: '00'; // Minuti
            $row[] = get_post_meta($post_id, 'provincia_incidente', true); // Provincia
            $row[] = get_post_meta($post_id, 'comune_incidente', true); // Comune
            $row[] = get_post_meta($post_id, 'localita_incidente', true); // Località
            $row[] = get_post_meta($post_id, 'codice_istat', true); // Codice ISTAT
            
            // Sezione 1.1: Localizzazione
            $row[] = get_post_meta($post_id, 'nell_abitato', true); // Nell'abitato
            $row[] = get_post_meta($post_id, 'denominazione_strada', true); // Denominazione strada
            $row[] = get_post_meta($post_id, 'numero_strada', true); // Numero strada
            $row[] = get_post_meta($post_id, 'progressiva_km', true); // Progressiva KM
            $row[] = get_post_meta($post_id, 'progressiva_mt', true); // Progressiva MT
            $row[] = get_post_meta($post_id, 'fuori_abitato', true); // Fuori abitato
            $row[] = get_post_meta($post_id, 'tipo_fuori_abitato', true); // Tipo fuori abitato
            $row[] = get_post_meta($post_id, 'latitudine', true); // Latitudine
            $row[] = get_post_meta($post_id, 'longitudine', true); // Longitudine
            
            // Sezione 2: Luogo dell'incidente
            $row[] = get_post_meta($post_id, 'tipo_strada', true); // Tipo strada
            $row[] = get_post_meta($post_id, 'pavimentazione', true); // Pavimentazione
            $row[] = get_post_meta($post_id, 'intersezione', true); // Intersezione
            $row[] = get_post_meta($post_id, 'non_intersezione', true); // Non intersezione
            $row[] = get_post_meta($post_id, 'fondo_stradale', true); // Fondo stradale
            $row[] = get_post_meta($post_id, 'segnaletica', true); // Segnaletica
            $row[] = get_post_meta($post_id, 'condizioni_meteo', true); // Condizioni meteorologiche
            $row[] = get_post_meta($post_id, 'illuminazione', true); // Illuminazione
            
            // Sezione 3: Natura dell'incidente
            $row[] = get_post_meta($post_id, 'natura_incidente_a', true); // Tra veicoli in marcia
            $row[] = get_post_meta($post_id, 'natura_incidente_b', true); // Tra veicolo e pedoni
            $row[] = get_post_meta($post_id, 'natura_incidente_c', true); // Veicolo in marcia che urta veicolo fermo
            $row[] = get_post_meta($post_id, 'natura_incidente_d', true); // Veicolo in marcia senza urto
            $row[] = get_post_meta($post_id, 'natura_incidente_e', true); // Altro
            $row[] = get_post_meta($post_id, 'altro_natura_dettagli', true); // Dettagli altro
            
            // Sezione 4: Tipo veicoli coinvolti
            $row[] = get_post_meta($post_id, 'tipo_veicolo_a', true); // Veicolo A
            $row[] = get_post_meta($post_id, 'tipo_veicolo_b', true); // Veicolo B
            $row[] = get_post_meta($post_id, 'tipo_veicolo_c', true); // Veicolo C
            
            // Sezione 5: Circostanze presenti
            $row[] = get_post_meta($post_id, 'inconvenienti_circolazione', true);
            $row[] = get_post_meta($post_id, 'difetti_avarie_veicolo', true);
            $row[] = get_post_meta($post_id, 'stato_psicofisico_conducente', true);
            
            // Sezione 6: Dettagli veicoli
            $row[] = get_post_meta($post_id, 'targa_veicolo_a', true); // Targa A
            $row[] = get_post_meta($post_id, 'sigla_estera_a', true); // Sigla estera A
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_a', true); // Anno A
            $row[] = get_post_meta($post_id, 'targa_veicolo_b', true); // Targa B
            $row[] = get_post_meta($post_id, 'sigla_estera_b', true); // Sigla estera B
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_b', true); // Anno B
            $row[] = get_post_meta($post_id, 'targa_veicolo_c', true); // Targa C
            $row[] = get_post_meta($post_id, 'sigla_estera_c', true); // Sigla estera C
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_c', true); // Anno C
            
            // Organi
            $row[] = get_post_meta($post_id, 'organo_rilevazione', true);
            $row[] = get_post_meta($post_id, 'organo_coordinatore', true);
            $row[] = get_post_meta($post_id, 'numero_progressivo_anno', true);
            $row[] = get_post_meta($post_id, 'tronco_strada_autostrada', true);
            
            // Sanitizza i valori per il CSV
            $row = array_map(function($value) {
                if (is_null($value) || $value === '') {
                    return '';
                }
                // Escape delle virgolette e wrap in virgolette se contiene separatori
                if (strpos($value, ';') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);
            
            // Aggiungi riga al CSV
            $output .= implode(';', $row) . "\n";
        }
        
        return $output;
    }


    private function get_comuni_lecce() {
        return array(
            '001' => 'Acquarica Del Capo',
            '002' => 'Alessano', 
            '003' => 'Alezio',
            '004' => 'Alliste',
            '005' => 'Andrano',
            '006' => 'Aradeo',
            '007' => 'Arnesano',
            '008' => 'Bagnolo Del Salento',
            '009' => 'Botrugno',
            '010' => 'Calimera Di Lecce',
            '011' => 'Campi Salentina',
            '012' => 'Cannole',
            '013' => 'Caprarica Del Capo',
            '014' => 'Caprarica Di Lecce',
            '015' => 'Carmiano',
            '016' => 'Carpignano Salentino',
            '017' => 'Casarano',
            '018' => 'Castri Di Lecce',
            '019' => 'Castrignano Del Capo',
            '020' => 'Castrignano De` Greci',
            '021' => 'Castro',
            '022' => 'Cavallino',
            '023' => 'Collepasso',
            '024' => 'Copertino',
            '025' => 'Corigliano D`Otranto',
            '026' => 'Corsano',
            '027' => 'Cursi',
            '028' => 'Cutrofiano',
            '029' => 'Diso',
            '030' => 'Gagliano Del Capo',
            '031' => 'Galatina',
            '032' => 'Galatone',
            '033' => 'Gallipoli',
            '034' => 'Giuggianello',
            '035' => 'Giurdignano',
            '036' => 'Guagnano',
            '037' => 'Lecce',
            '038' => 'Lequile',
            '039' => 'Leverano',
            '040' => 'Lizzanello',
            '041' => 'Maglie',
            '042' => 'Martano',
            '043' => 'Martignano',
            '044' => 'Matino',
            '045' => 'Melendugno',
            '046' => 'Melissano',
            '047' => 'Melpignano',
            '048' => 'Miggiano',
            '049' => 'Minervino Di Lecce',
            '050' => 'Monteroni Di Lecce',
            '051' => 'Montesano Salentino',
            '052' => 'Morciano Di Leuca',
            '053' => 'Muro Leccese',
            '054' => 'Nardo`',
            '055' => 'Neviano',
            '056' => 'Nociglia',
            '057' => 'Novoli',
            '058' => 'Ortelle',
            '059' => 'Otranto',
            '060' => 'Palmariggi',
            '061' => 'Parabita',
            '062' => 'Patu`',
            '063' => 'Poggiardo',
            '064' => 'Porto Cesareo',
            '065' => 'Presicce',
            '066' => 'Presicce-Acquarica',
            '067' => 'Racale',
            '068' => 'Ruffano',
            '069' => 'Salice Salentino',
            '070' => 'Salve',
            '071' => 'San Cassiano Di Lecce',
            '072' => 'San Cesario Di Lecce',
            '073' => 'San Donato Di Lecce',
            '074' => 'San Pietro In Lama',
            '075' => 'Sanarica',
            '076' => 'Sannicola',
            '077' => 'Santa Cesarea Terme',
            '078' => 'Scorrano',
            '079' => 'Secli`',
            '080' => 'Sogliano Cavour',
            '081' => 'Soleto',
            '082' => 'Specchia',
            '083' => 'Spongano',
            '084' => 'Squinzano',
            '085' => 'Sternatia',
            '086' => 'Supersano',
            '087' => 'Surano',
            '088' => 'Surbo',
            '089' => 'Taurisano',
            '090' => 'Taviano',
            '091' => 'Tiggiano',
            '092' => 'Trepuzzi',
            '093' => 'Tricase',
            '094' => 'Tuglie',
            '095' => 'Ugento',
            '096' => 'Uggiano La Chiesa',
            '097' => 'Veglie',
            '098' => 'Vernole',
            '099' => 'Zollino'
        );
    }

    // Aggiungi funzioni helper per le conversioni
    private function convert_illuminazione_code($code) {
        $codes = array(
            '1' => 'Luce diurna',
            '2' => 'Crepuscolo alba',
            '3' => 'Buio: luci stradali presenti accese',
            '4' => 'Buio: luci stradali presenti spente',
            '5' => 'Buio: assenza di illuminazione stradale',
            '6' => 'Illuminazione stradale non nota'
        );
        return isset($codes[$code]) ? $codes[$code] : '';
    }

    private function convert_visibilita_code($code) {
        $codes = array(
            '1' => 'Buona',
            '2' => 'Ridotta per condizioni atmosferiche',
            '3' => 'Ridotta per altre cause'
        );
        return isset($codes[$code]) ? $codes[$code] : '';
    }

    private function convert_traffico_code($code) {
        $codes = array(
            '1' => 'Scarso',
            '2' => 'Normale',
            '3' => 'Intenso'
        );
        return isset($codes[$code]) ? $codes[$code] : '';
    }

    private function convert_segnaletica_semaforica_code($code) {
        $codes = array(
            '1' => 'Assente',
            '2' => 'In funzione',
            '3' => 'Lampeggiante',
            '4' => 'Spenta'
        );
        return isset($codes[$code]) ? $codes[$code] : '';
    }

    private function get_circostanza_description($code) {
        // Carica le descrizioni dal file JSON
        $json_file = INCIDENTI_PLUGIN_PATH . 'data/circostanze-incidente.json';
        static $circostanze = null;
        
        if ($circostanze === null && file_exists($json_file)) {
            $circostanze = json_decode(file_get_contents($json_file), true);
        }
        
        // Cerca la descrizione nei vari gruppi
        if ($circostanze && $code) {
            foreach ($circostanze['circostanze_incidente'] as $gruppo) {
                if (isset($gruppo['codici'][$code])) {
                    return $code . ' - ' . $gruppo['codici'][$code]['descrizione'];
                }
            }
        }
        
        return $code ?: '';
    }

    private function convert_localizzazione_extra_code($code) {
        $codes = array(
            '1' => 'Su strada statale fuori dall\'autostrada',
            '2' => 'Su autostrada',
            '3' => 'Su raccordo autostradale'
        );
        return isset($codes[$code]) ? $codes[$code] : '';
    }

    /**
     * Converte codice sesso in formato testo
     */
    private function convert_sesso_code($code) {
        switch ($code) {
            case '1': return 'M';
            case '2': return 'F';
            default: return '';
        }
    }

/**
 * Converte codice esito in formato testo
 */
private function convert_esito_code($code) {
    switch ($code) {
        case '1': return 'Incolume';
        case '2': return 'Ferito';
        case '3': return 'Morto entro 24h';
        case '4': return 'Morto 2-30gg';
        default: return '';
    }
}
    
    private function log_export($type, $count, $filename) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'incidenti_export_logs';
        
        // Verifica se la tabella esiste
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Crea la tabella se non esiste
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                export_type varchar(20) NOT NULL,
                export_date datetime DEFAULT CURRENT_TIMESTAMP,
                file_path varchar(255) NOT NULL,
                records_count int(11) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
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
        
        // Verifica se la tabella esiste
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p>' . __('Nessuna esportazione effettuata.', 'incidenti-stradali') . '</p>';
            return;
        }
        
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
    
    public function add_export_menu() {
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
            // Store post IDs in transient
            set_transient('incidenti_bulk_export_' . get_current_user_id(), array(
                'post_ids' => $post_ids,
                'type' => $doaction
            ), 300);
            
            // Redirect to export processing
            $redirect_to = add_query_arg(array(
                'post_type' => 'incidente_stradale',
                'bulk_export' => $doaction,
                'posts' => count($post_ids)
            ), admin_url('edit.php'));
        }
        
        return $redirect_to;
    }
}