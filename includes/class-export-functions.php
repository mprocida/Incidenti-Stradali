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
        
        // Validazione lunghezza record
        $lines = explode("\r\n", trim($output));
        foreach ($lines as $line_num => $line) {
            if (strlen($line) !== 1024) {
                error_log("ATTENZIONE: Record " . ($line_num + 1) . " ha lunghezza " . strlen($line) . " invece di 1024 caratteri");
            }
        }
        
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
        
        // Assicura encoding UTF-8 senza BOM per compatibilità ISTAT
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
            
            // Recupera i dati dell'incidente
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            $data_parts = explode('-', $data_incidente);
            $anno = isset($data_parts[0]) ? substr($data_parts[0], -2) : '24'; // Ultime 2 cifre
            $mese = isset($data_parts[1]) ? $data_parts[1] : '01';
            $giorno = isset($data_parts[2]) ? $data_parts[2] : '01';
            
            // POSIZIONI 1-2: Anno (2 cifre)
            $record .= str_pad($anno, 2, "0", STR_PAD_LEFT);
            
            // POSIZIONI 3-4: Mese (2 cifre) 
            $record .= str_pad($mese, 2, "0", STR_PAD_LEFT);
            
            // POSIZIONI 5-7: Provincia (3 cifre)
            $provincia = get_post_meta($post_id, 'provincia_incidente', true);
            $record .= str_pad($provincia ?: '000', 3, "0", STR_PAD_LEFT);
            
            // POSIZIONI 8-10: Comune (3 cifre)
            $comune = get_post_meta($post_id, 'comune_incidente', true);
            $record .= str_pad($comune ?: '000', 3, "0", STR_PAD_LEFT);
            
            // POSIZIONI 11-14: Numero d'ordine (4 cifre)
            $record .= str_pad($post_id, 4, "0", STR_PAD_LEFT);
            
            // POSIZIONI 15-16: Giorno (2 cifre)
            $record .= str_pad($giorno, 2, "0", STR_PAD_LEFT);
            
            // POSIZIONI 17-18: 2 SPAZI (campo sostituito)
            $record .= '  ';
            
            // POSIZIONE 19: Organo di rilevazione (1 cifra)
            $organo = get_post_meta($post_id, 'organo_rilevazione', true);
            $record .= $organo ?: '1';
            
            // POSIZIONI 20-24: 5 SPAZI (campo sostituito)
            $record .= '     ';
            
            // POSIZIONE 25: Organo coordinatore (1 cifra)
            $organo_coordinatore = get_post_meta($post_id, 'organo_coordinatore', true);
            $record .= $organo_coordinatore ?: '0';
            
            // POSIZIONE 26: Localizzazione (1 cifra)
            $localizzazione = get_post_meta($post_id, 'localizzazione', true);
            $record .= $localizzazione ?: '1';
            
            // POSIZIONI 27-29: Denominazione strada numero (3 cifre)
            $denominazione_numero = get_post_meta($post_id, 'denominazione_numero', true);
            $record .= str_pad($denominazione_numero ?: '000', 3, "0", STR_PAD_LEFT);
            
            // POSIZIONE 30: Illuminazione (1 cifra)
            $illuminazione = get_post_meta($post_id, 'illuminazione', true);
            $record .= $illuminazione ?: '1';
            
            // POSIZIONI 31-37: Tipo strada, pavimentazione, intersezione, etc. (7 cifre)
            $tipo_strada = get_post_meta($post_id, 'tipo_strada', true);
            $record .= $tipo_strada ?: '1';
            
            $pavimentazione = get_post_meta($post_id, 'pavimentazione', true);
            $record .= $pavimentazione ?: '1';
            
            $intersezione = get_post_meta($post_id, 'intersezione', true);
            $record .= $intersezione ?: '0';
            
            $tipo_intersezione = get_post_meta($post_id, 'tipo_intersezione', true);
            $record .= $tipo_intersezione ?: '0';
            
            $fondo_stradale = get_post_meta($post_id, 'fondo_stradale', true);
            $record .= $fondo_stradale ?: '1';
            
            $segnaletica = get_post_meta($post_id, 'segnaletica', true);
            $record .= $segnaletica ?: '0';
            
            $condizioni_meteo = get_post_meta($post_id, 'condizioni_meteo', true);
            $record .= $condizioni_meteo ?: '1';
            
            // POSIZIONI 38-75: Natura e tipo veicoli (38 cifre)
            $natura_incidente = get_post_meta($post_id, 'natura_incidente', true);
            $record .= $natura_incidente ?: '1';
            
            // Veicoli A, B, C - tipo veicolo (3 * 2 = 6 cifre)
            for ($i = 1; $i <= 3; $i++) {
                $suffix = ($i == 1) ? 'a' : ($i == 2 ? 'b' : 'c');
                $tipo_veicolo = get_post_meta($post_id, "veicolo_{$suffix}_tipo", true);
                $record .= str_pad($tipo_veicolo ?: '00', 2, "0", STR_PAD_LEFT);
            }
            
            // Altri dati veicoli fino alla posizione 75 (31 cifre rimanenti)
            for ($i = 0; $i < 31; $i++) {
                $record .= '0';
            }
            
            // POSIZIONI 76-293: Dati dettagliati veicoli, persone, circostanze
            // Veicolo A - targa (8 caratteri)
            $targa_a = get_post_meta($post_id, 'veicolo_a_targa', true);
            $record .= str_pad(substr($targa_a ?: '', 0, 8), 8, " ", STR_PAD_RIGHT);
            
            // Sigla estero veicolo A (2 cifre)
            $sigla_a = get_post_meta($post_id, 'veicolo_a_sigla_estero', true);
            $record .= str_pad($sigla_a ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Anno immatricolazione veicolo A (4 cifre)
            $anno_a = get_post_meta($post_id, 'veicolo_a_anno_immatricolazione', true);
            $record .= str_pad($anno_a ?: '0000', 4, "0", STR_PAD_LEFT);
            
            // Continua con dati conducenti, passeggeri...
            // Per ora aggiungiamo dati di default per arrivare alla lunghezza corretta
            
            // CONDUCENTI VEICOLI A, B, C (posizioni variabili)
            for ($veicolo = 1; $veicolo <= 3; $veicolo++) {
                $suffix = ($veicolo == 1) ? 'a' : ($veicolo == 2 ? 'b' : 'c');
                
                // Nascita conducente (2 cifre)
                $nascita = get_post_meta($post_id, "conducente_{$suffix}_nascita", true);
                $record .= str_pad($nascita ?: '00', 2, "0", STR_PAD_LEFT);
                
                // Patente conducente (8 cifre)
                $patente = get_post_meta($post_id, "conducente_{$suffix}_patente", true);
                $record .= str_pad($patente ?: '00000000', 8, "0", STR_PAD_LEFT);
                
                // Altri dati conducente (14 cifre)
                for ($i = 0; $i < 14; $i++) {
                    $record .= '0';
                }
            }
            
            // PASSEGGERI (posizioni variabili)
            // 3 veicoli * 3 passeggeri max * dati per passeggero
            for ($veicolo = 1; $veicolo <= 3; $veicolo++) {
                for ($passeggero = 1; $passeggero <= 3; $passeggero++) {
                    // Dati passeggero (circa 10 caratteri per passeggero)
                    for ($i = 0; $i < 10; $i++) {
                        $record .= '0';
                    }
                }
            }
            
            // PEDONI (4 pedoni max)
            for ($pedone = 1; $pedone <= 4; $pedone++) {
                // Sesso pedone morto (1 cifra)
                $sesso_morto = get_post_meta($post_id, "pedone_{$pedone}_sesso_morto", true);
                $record .= $sesso_morto ?: '0';
                
                // Età pedone morto (2 cifre)
                $eta_morto = get_post_meta($post_id, "pedone_{$pedone}_eta_morto", true);
                $record .= str_pad($eta_morto ?: '00', 2, "0", STR_PAD_LEFT);
                
                // Sesso pedone ferito (1 cifra)
                $sesso_ferito = get_post_meta($post_id, "pedone_{$pedone}_sesso_ferito", true);
                $record .= $sesso_ferito ?: '0';
                
                // Età pedone ferito (2 cifre)
                $eta_ferito = get_post_meta($post_id, "pedone_{$pedone}_eta_ferito", true);
                $record .= str_pad($eta_ferito ?: '00', 2, "0", STR_PAD_LEFT);
            }
            
            // RIEPILOGO INFORTUNATI (posizioni 269-293)
            // Altri veicoli coinvolti (2 cifre)
            $altri_veicoli = get_post_meta($post_id, 'altri_veicoli_numero', true);
            $record .= str_pad($altri_veicoli ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Morti maschi altri veicoli (2 cifre)
            $record .= str_pad('00', 2, "0", STR_PAD_LEFT);
            
            // Morti femmine altri veicoli (2 cifre)
            $record .= str_pad('00', 2, "0", STR_PAD_LEFT);
            
            // Feriti maschi altri veicoli (2 cifre)
            $record .= str_pad('00', 2, "0", STR_PAD_LEFT);
            
            // Feriti femmine altri veicoli (2 cifre)
            $record .= str_pad('00', 2, "0", STR_PAD_LEFT);
            
            // Totale morti 24h (2 cifre)
            $morti_24h = get_post_meta($post_id, 'totale_morti_24h', true);
            $record .= str_pad($morti_24h ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Totale morti 2-30 giorni (2 cifre)
            $record .= str_pad('00', 2, "0", STR_PAD_LEFT);
            
            // Totale feriti (2 cifre)
            $totale_feriti = get_post_meta($post_id, 'totale_feriti', true);
            $record .= str_pad($totale_feriti ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Spazi (9 caratteri)
            $record .= str_repeat(' ', 9);
            
            // NOME STRADA (posizioni 294-350: 57 caratteri)
            $nome_strada = get_post_meta($post_id, 'denominazione_strada', true);
            $record .= str_pad(substr($nome_strada ?: '', 0, 57), 57, " ", STR_PAD_RIGHT);
            
            // SPAZI (posizioni 351-450: 100 caratteri)
            $record .= str_repeat(' ', 100);
            
            // NOMI E COGNOMI MORTI (posizioni 451-690)
            for ($morto = 1; $morto <= 4; $morto++) {
                // Nome morto (30 caratteri)
                $nome_morto = get_post_meta($post_id, "morto_{$morto}_nome", true);
                $record .= str_pad(substr($nome_morto ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
                
                // Cognome morto (30 caratteri)
                $cognome_morto = get_post_meta($post_id, "morto_{$morto}_cognome", true);
                $record .= str_pad(substr($cognome_morto ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
            }
            
            // NOMI, COGNOMI E OSPEDALI FERITI (posizioni 691-1410)
            for ($ferito = 1; $ferito <= 8; $ferito++) {
                // Nome ferito (30 caratteri)
                $nome_ferito = get_post_meta($post_id, "ferito_{$ferito}_nome", true);
                $record .= str_pad(substr($nome_ferito ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
                
                // Cognome ferito (30 caratteri)
                $cognome_ferito = get_post_meta($post_id, "ferito_{$ferito}_cognome", true);
                $record .= str_pad(substr($cognome_ferito ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
                
                // Ospedale ferito (30 caratteri)
                $ospedale_ferito = get_post_meta($post_id, "ferito_{$ferito}_ospedale", true);
                $record .= str_pad(substr($ospedale_ferito ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
            }
            
            // SPAZIO RISERVATO ISTAT (posizioni 1411-1420: 10 caratteri)
            $record .= str_repeat(' ', 10);
            
            // GEOREFERENZIAZIONE (posizioni 1421-1522: 102 caratteri)
            // Tipo coordinata (1 carattere)
            $tipo_coordinata = get_post_meta($post_id, 'tipo_coordinata', true);
            $record .= $tipo_coordinata ?: '2'; // WGS84
            
            // Sistema proiezione (1 carattere)
            $sistema_proiezione = get_post_meta($post_id, 'sistema_proiezione', true);
            $record .= $sistema_proiezione ?: '2'; // geografiche
            
            // Longitudine X (50 caratteri)
            $longitudine = get_post_meta($post_id, 'longitudine', true);
            $record .= str_pad(substr($longitudine ?: '', 0, 50), 50, " ", STR_PAD_RIGHT);
            
            // Latitudine Y (50 caratteri)
            $latitudine = get_post_meta($post_id, 'latitudine', true);
            $record .= str_pad(substr($latitudine ?: '', 0, 50), 50, " ", STR_PAD_RIGHT);
            
            // CAMPI AGGIUNTIVI (posizioni 1523-1939)
            // Spazio riservato ISTAT (8 caratteri)
            $record .= str_repeat(' ', 8);
            
            // Ora precisa (2 caratteri)
            $ora = get_post_meta($post_id, 'ora_incidente', true);
            $record .= str_pad($ora ?: '25', 2, "0", STR_PAD_LEFT);
            
            // Minuti (2 caratteri)
            $minuti = get_post_meta($post_id, 'minuti_incidente', true);
            $record .= str_pad($minuti ?: '00', 2, "0", STR_PAD_LEFT);
            
            // Codice identificativo Carabinieri (30 caratteri)
            $codice_carabinieri = get_post_meta($post_id, 'codice_carabinieri', true);
            $record .= str_pad(substr($codice_carabinieri ?: '', 0, 30), 30, " ", STR_PAD_RIGHT);
            
            // Progressiva chilometrica precisa (4 caratteri)
            $chilometrica = get_post_meta($post_id, 'chilometrica', true);
            $record .= str_pad($chilometrica ?: '0000', 4, "0", STR_PAD_LEFT);
            
            // Ettometrica (3 caratteri)
            $ettometrica = get_post_meta($post_id, 'ettometrica', true);
            $record .= str_pad($ettometrica ?: '000', 3, "0", STR_PAD_LEFT);
            
            // Cilindrata veicoli A, B, C (3 * 5 = 15 caratteri)
            for ($i = 1; $i <= 3; $i++) {
                $suffix = ($i == 1) ? 'a' : ($i == 2 ? 'b' : 'c');
                $cilindrata = get_post_meta($post_id, "veicolo_{$suffix}_cilindrata", true);
                $record .= str_pad($cilindrata ?: '00000', 5, "0", STR_PAD_LEFT);
            }
            
            // Spazio riservato ISTAT (4 caratteri)
            $record .= str_repeat(' ', 4);
            
            // Altra strada (100 caratteri)
            $altra_strada = get_post_meta($post_id, 'altra_strada', true);
            $record .= str_pad(substr($altra_strada ?: '', 0, 100), 100, " ", STR_PAD_RIGHT);
            
            // Località (40 caratteri)
            $localita = get_post_meta($post_id, 'localita', true);
            $record .= str_pad(substr($localita ?: '', 0, 40), 40, " ", STR_PAD_RIGHT);
            
            // Codice identificativo ente (40 caratteri)
            $codice_ente = get_post_meta($post_id, 'codice_ente', true);
            $record .= str_pad(substr($codice_ente ?: '', 0, 40), 40, " ", STR_PAD_RIGHT);
            
            // Spazio riservato ISTAT finale (10 caratteri)
            $record .= str_repeat(' ', 10);
            
            // CITTADINANZA CONDUCENTI (posizioni finali)
            // Completare fino a 1939 caratteri
            $lunghezza_attuale = strlen($record);
            $caratteri_mancanti = 1939 - $lunghezza_attuale;
            
            if ($caratteri_mancanti > 0) {
                $record .= str_repeat(' ', $caratteri_mancanti);
            } elseif ($caratteri_mancanti < 0) {
                $record = substr($record, 0, 1939);
                error_log("ATTENZIONE: Record incidente ID {$post_id} troncato a 1939 caratteri");
            }
            
            // Assicurati che il record sia esattamente 1939 caratteri
            if (strlen($record) !== 1939) {
                error_log("ERRORE: Record incidente ID {$post_id} ha lunghezza " . strlen($record) . " invece di 1939");
                $record = str_pad(substr($record, 0, 1939), 1939, ' ', STR_PAD_RIGHT);
            }
            
            $output .= $record . "\r\n";
        }
        
        return $output;
    }
    
    private function complete_istat_record($record, $post_id) {
        // Questa funzione completa il record con tutti i campi richiesti dal tracciato ISTAT
        // fino a raggiungere i 1024 caratteri totali
        
        // Recupera dati veicoli
        for ($i = 1; $i <= 3; $i++) {
            // Tipo veicolo (2 cifre)
            $tipo_veicolo = get_post_meta($post_id, "veicolo_{$i}_tipo", true);
            $record .= str_pad($tipo_veicolo ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Targa nazionale (7 caratteri)
            $targa = get_post_meta($post_id, "veicolo_{$i}_targa", true);
            $record .= str_pad(substr($targa ?: '', 0, 7), 7, ' ', STR_PAD_RIGHT);
            
            // Sigla se estero (3 caratteri)
            $sigla_estero = get_post_meta($post_id, "veicolo_{$i}_sigla_estero", true);
            $record .= str_pad(substr($sigla_estero ?: '', 0, 3), 3, ' ', STR_PAD_RIGHT);
            
            // Anno prima immatricolazione (2 cifre)
            $anno_immatric = get_post_meta($post_id, "veicolo_{$i}_anno_immatricolazione", true);
            $record .= str_pad(substr($anno_immatric ?: '00', -2), 2, '0', STR_PAD_LEFT);
        }
        
        // Circostanze presunte dell'incidente per ogni veicolo (2 cifre ciascuno)
        for ($i = 1; $i <= 3; $i++) {
            $circostanza = get_post_meta($post_id, "veicolo_{$i}_circostanza", true);
            $record .= str_pad($circostanza ?: '00', 2, '0', STR_PAD_LEFT);
        }
        
        // Cilindrata o peso per ogni veicolo (4 cifre ciascuno)
        for ($i = 1; $i <= 3; $i++) {
            $cilindrata = get_post_meta($post_id, "veicolo_{$i}_cilindrata", true);
            $record .= str_pad($cilindrata ?: '0000', 4, '0', STR_PAD_LEFT);
        }
        
        // Dati conducenti
        for ($i = 1; $i <= 3; $i++) {
            // Nascita (1 cifra)
            $nascita = get_post_meta($post_id, "conducente_{$i}_nascita", true);
            $record .= $nascita ?: '0';
            
            // Età (2 cifre)
            $eta = get_post_meta($post_id, "conducente_{$i}_eta", true);
            $record .= str_pad($eta ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Sesso (1 cifra)
            $sesso = get_post_meta($post_id, "conducente_{$i}_sesso", true);
            $record .= $sesso ?: '0';
            
            // Esito (1 cifra)
            $esito = get_post_meta($post_id, "conducente_{$i}_esito", true);
            $record .= $esito ?: '1';
            
            // Patente (5 caratteri)
            $patente_info = '';
            $patente_a = get_post_meta($post_id, "conducente_{$i}_patente_a", true);
            $patente_b = get_post_meta($post_id, "conducente_{$i}_patente_b", true);
            $patente_c = get_post_meta($post_id, "conducente_{$i}_patente_c", true);
            $patente_d = get_post_meta($post_id, "conducente_{$i}_patente_d", true);
            $patente_e = get_post_meta($post_id, "conducente_{$i}_patente_e", true);
            
            $patente_info .= $patente_a ? '1' : '0';
            $patente_info .= $patente_b ? '1' : '0';
            $patente_info .= $patente_c ? '1' : '0';
            $patente_info .= $patente_d ? '1' : '0';
            $patente_info .= $patente_e ? '1' : '0';
            
            $record .= $patente_info;
        }
        
        // Passeggeri (per ogni veicolo, max 4 per veicolo)
        for ($veicolo = 1; $veicolo <= 3; $veicolo++) {
            for ($pass = 1; $pass <= 4; $pass++) {
                // Sede (1 cifra): 1=anteriore, 2=posteriore
                $sede = get_post_meta($post_id, "veicolo_{$veicolo}_passeggero_{$pass}_sede", true);
                $record .= $sede ?: '0';
                
                // Età (2 cifre)
                $eta_pass = get_post_meta($post_id, "veicolo_{$veicolo}_passeggero_{$pass}_eta", true);
                $record .= str_pad($eta_pass ?: '00', 2, '0', STR_PAD_LEFT);
                
                // Sesso (1 cifra)
                $sesso_pass = get_post_meta($post_id, "veicolo_{$veicolo}_passeggero_{$pass}_sesso", true);
                $record .= $sesso_pass ?: '0';
                
                // Esito (1 cifra)
                $esito_pass = get_post_meta($post_id, "veicolo_{$veicolo}_passeggero_{$pass}_esito", true);
                $record .= $esito_pass ?: '1';
            }
        }
        
        // Pedoni coinvolti (max 6)
        for ($i = 1; $i <= 6; $i++) {
            // Età (2 cifre)
            $eta_pedone = get_post_meta($post_id, "pedone_{$i}_eta", true);
            $record .= str_pad($eta_pedone ?: '00', 2, '0', STR_PAD_LEFT);
            
            // Sesso (1 cifra)
            $sesso_pedone = get_post_meta($post_id, "pedone_{$i}_sesso", true);
            $record .= $sesso_pedone ?: '0';
            
            // Esito (1 cifra)
            $esito_pedone = get_post_meta($post_id, "pedone_{$i}_esito", true);
            $record .= $esito_pedone ?: '1';
        }
        
        // Riepiloghi informativi morti (3 sezioni da 4 cifre ciascuna)
        $morti_24h = get_post_meta($post_id, 'morti_entro_24h', true);
        $record .= str_pad($morti_24h ?: '0000', 4, '0', STR_PAD_LEFT);
        
        $morti_30gg = get_post_meta($post_id, 'morti_dal_2_al_30', true);
        $record .= str_pad($morti_30gg ?: '0000', 4, '0', STR_PAD_LEFT);
        
        $feriti = get_post_meta($post_id, 'feriti_totali', true);
        $record .= str_pad($feriti ?: '0000', 4, '0', STR_PAD_LEFT);
        
        // Nominativi morti (max 3, 60 caratteri ciascuno: 30 nome + 30 cognome)
        for ($i = 1; $i <= 3; $i++) {
            $nome_morto = get_post_meta($post_id, "morto_{$i}_nome", true);
            $cognome_morto = get_post_meta($post_id, "morto_{$i}_cognome", true);
            
            $record .= str_pad(substr($nome_morto ?: '', 0, 30), 30, ' ', STR_PAD_RIGHT);
            $record .= str_pad(substr($cognome_morto ?: '', 0, 30), 30, ' ', STR_PAD_RIGHT);
        }
        
        // Nominativi feriti e istituto di ricovero (max 8, 90 caratteri ciascuno: 30 nome + 30 cognome + 30 istituto)
        for ($i = 1; $i <= 8; $i++) {
            $nome_ferito = get_post_meta($post_id, "ferito_{$i}_nome", true);
            $cognome_ferito = get_post_meta($post_id, "ferito_{$i}_cognome", true);
            $istituto = get_post_meta($post_id, "ferito_{$i}_istituto", true);
            
            $record .= str_pad(substr($nome_ferito ?: '', 0, 30), 30, ' ', STR_PAD_RIGHT);
            $record .= str_pad(substr($cognome_ferito ?: '', 0, 30), 30, ' ', STR_PAD_RIGHT);
            $record .= str_pad(substr($istituto ?: '', 0, 30), 30, ' ', STR_PAD_RIGHT);
        }
        
        return $record;
    }
    
    public function generate_excel_csv($incidenti) {
        $output = '';
        
        // Header CSV per formato Polizia (basato su Form_Inserimento_Incidenti_PLultimaversione)
        // Header CSV secondo form ISTAT ufficiale
        $headers = array(
            'Incidente ID',
            'Data',
            'Ora',
            'Minuti',
            'Giorni',
            'KM/Deputazione',
            'km/Deputazione',
            'Provincia',
            'Comune',
            'Località',
            'Denominazione',
            'Numero',
            'KM',
            'MT',
            'N° N°',
            'SP N°',
            'SS N°',
            'A N°',
            'Strada',
            'Ora',
            'Denominazione strada',
            'Numero strada indicata',
            'Km strada',
            'Mt strada',
            'N strada',
            'SP strada',
            'SS strada',
            'A strada',
            'Comune Extra Provincia',
            'Latitudine',
            'Longitudine',
            'Pavimentazione',
            'Intersezione',
            'Segnaletica orizzontale',
            'Illuminazione',
            'Condizioni meteo',
            'Fondo stradale',
            'Natura incidente A',
            'Natura incidente B', 
            'Natura incidente C',
            'Natura incidente D',
            'Natura incidente E',
            'Altro',
            'Tipo veicolo A',
            'Tipo veicolo B',
            'Tipo veicolo C',
            'Targa A',
            'Targa B',
            'Targa C',
            'Anno immatricolazione A',
            'Anno immatricolazione B',
            'Anno immatricolazione C',
            'Cilindrata A',
            'Cilindrata B',
            'Cilindrata C',
            'Peso A',
            'Peso B',
            'Peso C',
            'Circostanza A',
            'Circostanza B',
            'Circostanza C',
            'Conducente A età',
            'Conducente A sesso',
            'Conducente A nascita',
            'Conducente A patente',
            'Conducente A anni patente',
            'Conducente A nazione',
            'Conducente A provincia',
            'Conducente A comune',
            'Conducente A cittadinanza',
            'Conducente A professione',
            'Conducente B età',
            'Conducente B sesso',
            'Conducente B nascita',
            'Conducente B patente',
            'Conducente B anni patente',
            'Conducente B nazione',
            'Conducente B provincia',
            'Conducente B comune',
            'Conducente B cittadinanza',
            'Conducente B professione',
            'Conducente C età',
            'Conducente C sesso',
            'Conducente C nascita',
            'Conducente C patente',
            'Conducente C anni patente',
            'Conducente C nazione',
            'Conducente C provincia',
            'Conducente C comune',
            'Conducente C cittadinanza',
            'Conducente C professione',
            'Feriti',
            'Morti',
            'Pedoni feriti',
            'Pedoni morti',
            'Danneggiamento'
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
            
            // Compilazione array secondo le colonne definite nella screenshot
            $row[] = $post_id; // Incidente ID
            $row[] = $data_incidente; // Data
            $row[] = $ora_incidente ?: ''; // Ora
            $row[] = $minuti_incidente ?: ''; // Minuti
            $row[] = date('w', strtotime($data_incidente)); // Giorni (giorno settimana)
            $row[] = get_post_meta($post_id, 'km_deputazione', true); // KM/Deputazione
            $row[] = get_post_meta($post_id, 'km_deputazione_value', true); // km/Deputazione
            $row[] = get_post_meta($post_id, 'provincia_incidente', true); // Provincia
            $row[] = get_post_meta($post_id, 'comune_incidente', true); // Comune
            $row[] = get_post_meta($post_id, 'localita_incidente', true); // Località
            $row[] = get_post_meta($post_id, 'denominazione_strada', true); // Denominazione
            $row[] = get_post_meta($post_id, 'numero_strada', true); // Numero
            $row[] = get_post_meta($post_id, 'progressiva_km', true); // KM
            $row[] = get_post_meta($post_id, 'progressiva_mt', true); // MT
            $row[] = get_post_meta($post_id, 'strada_n', true); // N° N°
            $row[] = get_post_meta($post_id, 'strada_sp', true); // SP N°
            $row[] = get_post_meta($post_id, 'strada_ss', true); // SS N°
            $row[] = get_post_meta($post_id, 'strada_a', true); // A N°
            $row[] = get_post_meta($post_id, 'tipo_strada_dettaglio', true); // Strada
            $row[] = $ora_incidente ?: ''; // Ora (ripetuta)
            $row[] = get_post_meta($post_id, 'denominazione_strada', true); // Denominazione strada
            $row[] = get_post_meta($post_id, 'numero_strada', true); // Numero strada indicata
            $row[] = get_post_meta($post_id, 'km_strada', true); // Km strada
            $row[] = get_post_meta($post_id, 'mt_strada', true); // Mt strada
            $row[] = get_post_meta($post_id, 'n_strada', true); // N strada
            $row[] = get_post_meta($post_id, 'sp_strada', true); // SP strada
            $row[] = get_post_meta($post_id, 'ss_strada', true); // SS strada
            $row[] = get_post_meta($post_id, 'a_strada', true); // A strada
            $row[] = get_post_meta($post_id, 'comune_extra_provincia', true); // Comune Extra Provincia
            $row[] = get_post_meta($post_id, 'latitudine', true); // Latitudine
            $row[] = get_post_meta($post_id, 'longitudine', true); // Longitudine
            $row[] = get_post_meta($post_id, 'pavimentazione', true); // Pavimentazione
            $row[] = get_post_meta($post_id, 'intersezione', true); // Intersezione
            $row[] = get_post_meta($post_id, 'segnaletica_orizzontale', true); // Segnaletica orizzontale
            $row[] = get_post_meta($post_id, 'illuminazione', true); // Illuminazione
            $row[] = get_post_meta($post_id, 'condizioni_meteo', true); // Condizioni meteo
            $row[] = get_post_meta($post_id, 'fondo_stradale', true); // Fondo stradale
            $row[] = get_post_meta($post_id, 'natura_incidente_a', true); // Natura incidente A
            $row[] = get_post_meta($post_id, 'natura_incidente_b', true); // Natura incidente B
            $row[] = get_post_meta($post_id, 'natura_incidente_c', true); // Natura incidente C
            $row[] = get_post_meta($post_id, 'natura_incidente_d', true); // Natura incidente D
            $row[] = get_post_meta($post_id, 'natura_incidente_e', true); // Natura incidente E
            $row[] = get_post_meta($post_id, 'altro_natura', true); // Altro
            $row[] = get_post_meta($post_id, 'tipo_veicolo_a', true); // Tipo veicolo A
            $row[] = get_post_meta($post_id, 'tipo_veicolo_b', true); // Tipo veicolo B
            $row[] = get_post_meta($post_id, 'tipo_veicolo_c', true); // Tipo veicolo C
            $row[] = get_post_meta($post_id, 'targa_veicolo_a', true); // Targa A
            $row[] = get_post_meta($post_id, 'targa_veicolo_b', true); // Targa B
            $row[] = get_post_meta($post_id, 'targa_veicolo_c', true); // Targa C
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_a', true); // Anno immatricolazione A
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_b', true); // Anno immatricolazione B
            $row[] = get_post_meta($post_id, 'anno_immatricolazione_c', true); // Anno immatricolazione C
            $row[] = get_post_meta($post_id, 'cilindrata_a', true); // Cilindrata A
            $row[] = get_post_meta($post_id, 'cilindrata_b', true); // Cilindrata B
            $row[] = get_post_meta($post_id, 'cilindrata_c', true); // Cilindrata C
            $row[] = get_post_meta($post_id, 'peso_a', true); // Peso A
            $row[] = get_post_meta($post_id, 'peso_b', true); // Peso B
            $row[] = get_post_meta($post_id, 'peso_c', true); // Peso C
            $row[] = get_post_meta($post_id, 'circostanza_a', true); // Circostanza A
            $row[] = get_post_meta($post_id, 'circostanza_b', true); // Circostanza B
            $row[] = get_post_meta($post_id, 'circostanza_c', true); // Circostanza C
            
            // Dati conducenti
            $row[] = get_post_meta($post_id, 'conducente_a_eta', true); // Conducente A età
            $row[] = get_post_meta($post_id, 'conducente_a_sesso', true); // Conducente A sesso
            $row[] = get_post_meta($post_id, 'conducente_a_nascita', true); // Conducente A nascita
            $row[] = get_post_meta($post_id, 'conducente_a_patente', true); // Conducente A patente
            $row[] = get_post_meta($post_id, 'conducente_a_anni_patente', true); // Conducente A anni patente
            $row[] = get_post_meta($post_id, 'conducente_a_nazione', true); // Conducente A nazione
            $row[] = get_post_meta($post_id, 'conducente_a_provincia', true); // Conducente A provincia
            $row[] = get_post_meta($post_id, 'conducente_a_comune', true); // Conducente A comune
            $row[] = get_post_meta($post_id, 'conducente_a_cittadinanza', true); // Conducente A cittadinanza
            $row[] = get_post_meta($post_id, 'conducente_a_professione', true); // Conducente A professione
            
            $row[] = get_post_meta($post_id, 'conducente_b_eta', true); // Conducente B età
            $row[] = get_post_meta($post_id, 'conducente_b_sesso', true); // Conducente B sesso
            $row[] = get_post_meta($post_id, 'conducente_b_nascita', true); // Conducente B nascita
            $row[] = get_post_meta($post_id, 'conducente_b_patente', true); // Conducente B patente
            $row[] = get_post_meta($post_id, 'conducente_b_anni_patente', true); // Conducente B anni patente
            $row[] = get_post_meta($post_id, 'conducente_b_nazione', true); // Conducente B nazione
            $row[] = get_post_meta($post_id, 'conducente_b_provincia', true); // Conducente B provincia
            $row[] = get_post_meta($post_id, 'conducente_b_comune', true); // Conducente B comune
            $row[] = get_post_meta($post_id, 'conducente_b_cittadinanza', true); // Conducente B cittadinanza
            $row[] = get_post_meta($post_id, 'conducente_b_professione', true); // Conducente B professione
            
            $row[] = get_post_meta($post_id, 'conducente_c_eta', true); // Conducente C età
            $row[] = get_post_meta($post_id, 'conducente_c_sesso', true); // Conducente C sesso
            $row[] = get_post_meta($post_id, 'conducente_c_nascita', true); // Conducente C nascita
            $row[] = get_post_meta($post_id, 'conducente_c_patente', true); // Conducente C patente
            $row[] = get_post_meta($post_id, 'conducente_c_anni_patente', true); // Conducente C anni patente
            $row[] = get_post_meta($post_id, 'conducente_c_nazione', true); // Conducente C nazione
            $row[] = get_post_meta($post_id, 'conducente_c_provincia', true); // Conducente C provincia
            $row[] = get_post_meta($post_id, 'conducente_c_comune', true); // Conducente C comune
            $row[] = get_post_meta($post_id, 'conducente_c_cittadinanza', true); // Conducente C cittadinanza
            $row[] = get_post_meta($post_id, 'conducente_c_professione', true); // Conducente C professione
            
            // Statistiche incidente
            $row[] = get_post_meta($post_id, 'numero_feriti', true); // Feriti
            $row[] = get_post_meta($post_id, 'numero_morti', true); // Morti
            $row[] = get_post_meta($post_id, 'pedoni_feriti', true); // Pedoni feriti
            $row[] = get_post_meta($post_id, 'pedoni_morti', true); // Pedoni morti
            $row[] = get_post_meta($post_id, 'danneggiamento', true); // Danneggiamento
            
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