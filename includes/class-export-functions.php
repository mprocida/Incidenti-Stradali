<?php
/**
 * Export Functions for Incidenti Stradali
 */

class IncidentiExportFunctions {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_export_menu'));
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
                                <input type="text" name="comune_filtro" placeholder="<?php _e('Codice ISTAT comune (lascia vuoto per tutti)', 'incidenti-stradali'); ?>">
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
                $record .= str_repeat(' ', 30); // nome
                $record .= str_repeat(' ', 30); // cognome
            }
            
            // 8 feriti (nome + cognome + istituto)
            for ($i = 1; $i <= 8; $i++) {
                $record .= str_repeat(' ', 30); // nome
                $record .= str_repeat(' ', 30); // cognome
                $record .= str_repeat(' ', 30); // istituto
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
        $headers = array(
            // Identificativi
            'Anno',
            'Mese', 
            'Provincia',
            'Comune',
            'Numero Ordine',
            'Giorno',
            'Ora',
            'Minuti',
            'Organo Rilevazione',
            'Numero Progressivo Anno',
            'Organo Coordinatore',
            
            // Localizzazione
            'Localizzazione Incidente',
            'Denominazione Strada',
            'Numero Strada',
            'Progressiva KM',
            'Progressiva MT',
            'Tronco Strada',
            
            // Caratteristiche luogo
            'Tipo Strada',
            'Pavimentazione',
            'Intersezione',
            'Fondo Stradale',
            'Segnaletica',
            'Condizioni Meteo',
            'Illuminazione',
            
            // Natura incidente
            'Natura Incidente',
            'Dettaglio Natura',
            
            // Veicoli (3 veicoli)
            'Tipo Veicolo A',
            'Targa Veicolo A',
            'Anno Immatricolazione A',
            'Cilindrata A',
            'Peso Totale A',
            'Circostanza A',
            
            'Tipo Veicolo B',
            'Targa Veicolo B', 
            'Anno Immatricolazione B',
            'Cilindrata B',
            'Peso Totale B',
            'Circostanza B',
            
            'Tipo Veicolo C',
            'Targa Veicolo C',
            'Anno Immatricolazione C',
            'Cilindrata C',
            'Peso Totale C',
            'Circostanza C',
            
            // Conducenti (3 conducenti)
            'Età Conducente A',
            'Sesso Conducente A',
            'Esito Conducente A',
            'Tipo Patente A',
            'Anno Patente A',
            'Nazionalità A',
            
            'Età Conducente B',
            'Sesso Conducente B',
            'Esito Conducente B',
            'Tipo Patente B',
            'Anno Patente B',
            'Nazionalità B',
            
            'Età Conducente C',
            'Sesso Conducente C',
            'Esito Conducente C',
            'Tipo Patente C',
            'Anno Patente C',
            'Nazionalità C',
            
            // Pedoni (4 pedoni)
            'Sesso Pedone 1',
            'Età Pedone 1',
            'Esito Pedone 1',
            'Circostanza Pedone 1',
            
            'Sesso Pedone 2',
            'Età Pedone 2',
            'Esito Pedone 2',
            'Circostanza Pedone 2',
            
            'Sesso Pedone 3',
            'Età Pedone 3',
            'Esito Pedone 3',
            'Circostanza Pedone 3',
            
            'Sesso Pedone 4',
            'Età Pedone 4',
            'Esito Pedone 4',
            'Circostanza Pedone 4',
            
            // Coordinate
            'Tipo Coordinata',
            'Latitudine',
            'Longitudine',
            
            // Riepilogo
            'Numero Veicoli Coinvolti',
            'Numero Pedoni Coinvolti',
            'Totale Morti',
            'Totale Feriti',
            'Morti Entro 24 Ore',
            'Morti 2-30 Giorni',
            
            // Altri dati
            'Note',
            'Mostra in Mappa'
        );
        
        // Scrivi header con separatore punto e virgola
        $output .= implode(';', $headers) . "\n";
        
        // Elabora ogni incidente
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $row = array();
            
            // Data incidente
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            
            // Identificativi
            $row[] = $data_incidente ? substr($data_incidente, 0, 4) : ''; // Anno
            $row[] = $data_incidente ? substr($data_incidente, 5, 2) : ''; // Mese
            $row[] = get_post_meta($post_id, 'provincia_incidente', true);
            $row[] = get_post_meta($post_id, 'comune_incidente', true);
            $row[] = str_pad($post_id, 4, '0', STR_PAD_LEFT); // Numero ordine
            $row[] = $data_incidente ? substr($data_incidente, -2) : ''; // Giorno
            $row[] = get_post_meta($post_id, 'ora_incidente', true);
            $row[] = get_post_meta($post_id, 'minuti_incidente', true) ?: '00';
            $row[] = get_post_meta($post_id, 'organo_rilevazione', true);
            $row[] = str_pad($post_id, 5, '0', STR_PAD_LEFT); // Numero progressivo anno
            $row[] = get_post_meta($post_id, 'organo_coordinatore', true);
            
            // Localizzazione
            $row[] = get_post_meta($post_id, 'tipo_strada', true);
            $row[] = get_post_meta($post_id, 'denominazione_strada', true);
            $row[] = get_post_meta($post_id, 'numero_strada', true);
            $row[] = get_post_meta($post_id, 'progressiva_km', true);
            $row[] = get_post_meta($post_id, 'progressiva_m', true);
            $row[] = get_post_meta($post_id, 'tronco_strada', true);
            
            // Caratteristiche luogo
            $row[] = get_post_meta($post_id, 'geometria_strada', true);
            $row[] = get_post_meta($post_id, 'pavimentazione_strada', true);
            $row[] = get_post_meta($post_id, 'intersezione_tronco', true);
            $row[] = get_post_meta($post_id, 'stato_fondo_strada', true);
            $row[] = get_post_meta($post_id, 'segnaletica_strada', true);
            $row[] = get_post_meta($post_id, 'condizioni_meteo', true);
            $row[] = get_post_meta($post_id, 'illuminazione', true);
            
            // Natura incidente
            $row[] = get_post_meta($post_id, 'natura_incidente', true);
            $row[] = get_post_meta($post_id, 'dettaglio_natura', true);
            
            // Veicoli e conducenti (fino a 3)
            for ($i = 1; $i <= 3; $i++) {
                // Dati veicolo
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_tipo', true);
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_targa', true);
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_anno_immatricolazione', true);
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_cilindrata', true);
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_peso_totale', true);
                $row[] = get_post_meta($post_id, 'veicolo_' . $i . '_circostanza', true);
            }
            
            // Dati conducenti (fino a 3)
            for ($i = 1; $i <= 3; $i++) {
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_eta', true);
                $row[] = $this->convert_sesso_code(get_post_meta($post_id, 'conducente_' . $i . '_sesso', true));
                $row[] = $this->convert_esito_code(get_post_meta($post_id, 'conducente_' . $i . '_esito', true));
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_tipo_patente', true);
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_anno_patente', true);
                $row[] = get_post_meta($post_id, 'conducente_' . $i . '_nazionalita', true) ?: 'IT';
            }
            
            // Dati pedoni (fino a 4)
            for ($i = 1; $i <= 4; $i++) {
                $row[] = $this->convert_sesso_code(get_post_meta($post_id, 'pedone_' . $i . '_sesso', true));
                $row[] = get_post_meta($post_id, 'pedone_' . $i . '_eta', true);
                $row[] = $this->convert_esito_code(get_post_meta($post_id, 'pedone_' . $i . '_esito', true));
                $row[] = get_post_meta($post_id, 'pedone_' . $i . '_circostanza', true);
            }
            
            // Coordinate
            $row[] = get_post_meta($post_id, 'tipo_coordinata', true);
            $row[] = get_post_meta($post_id, 'latitudine', true);
            $row[] = get_post_meta($post_id, 'longitudine', true);
            
            // Riepilogo
            $num_veicoli = get_post_meta($post_id, 'numero_veicoli_coinvolti', true) ?: 1;
            $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
            
            // Conta morti e feriti
            $totale_morti = 0;
            $totale_feriti = 0;
            $morti_24h = 0;
            $morti_30g = 0;
            
            // Conta da conducenti
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
                if ($esito == '2') $totale_feriti++;
                if ($esito == '3') { $totale_morti++; $morti_24h++; }
                if ($esito == '4') { $totale_morti++; $morti_30g++; }
            }
            
            // Conta da pedoni
            for ($i = 1; $i <= 4; $i++) {
                $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
                if ($esito == '2') $totale_feriti++;
                if ($esito == '3') { $totale_morti++; $morti_24h++; }
                if ($esito == '4') { $totale_morti++; $morti_30g++; }
            }
            
            $row[] = $num_veicoli;
            $row[] = $num_pedoni;
            $row[] = $totale_morti;
            $row[] = $totale_feriti;
            $row[] = $morti_24h;
            $row[] = $morti_30g;
            
            // Altri dati
            $row[] = get_post_meta($post_id, 'note_incidente', true);
            $row[] = get_post_meta($post_id, 'mostra_in_mappa', true) ? 'SI' : 'NO';
            
            // Pulisci e formatta i valori
            $row = array_map(function($value) {
                // Rimuovi caratteri speciali che potrebbero rompere il CSV
                $value = str_replace(array(';', "\n", "\r", '"'), array(',', ' ', ' ', '\''), $value);
                // Se il valore contiene virgole o spazi, racchiudilo tra virgolette
                if (strpos($value, ',') !== false || strpos($value, ' ') !== false) {
                    $value = '"' . $value . '"';
                }
                return $value;
            }, $row);
            
            // Aggiungi riga al CSV
            $output .= implode(';', $row) . "\n";
        }
        
        return $output;
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

// Inizializza la classe
new IncidentiExportFunctions();