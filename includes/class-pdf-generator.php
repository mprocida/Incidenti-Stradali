<?php
if (!defined('ABSPATH')) {
    exit;
}

class PDF_Generator {
    
    public function generate_incidente_pdf($post_id) {
        if (!class_exists('TCPDF')) {
            require_once(plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php');
        }
        
        // Crea istanza TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configurazione documento
        $pdf->SetCreator('Plugin Incidenti Stradali');
        $pdf->SetAuthor('Sistema Gestione Incidenti');
        $pdf->SetTitle('Modulo Incidente Stradale - ID: ' . $post_id);
        
        // Rimuovi header/footer default
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Margini ottimizzati
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        
        // Aggiungi pagina
        $pdf->AddPage();
        
        // Genera contenuto
        $html = $this->generate_pdf_content($post_id);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Salva file
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/incidenti-pdf/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $filename = 'incidente_' . $post_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = $pdf_dir . $filename;
        
        $pdf->Output($filepath, 'F');
        
        return file_exists($filepath) ? $filepath : false;
    }
    
    private function generate_pdf_content($post_id) {
        $post = get_post($post_id);
        $meta = get_post_meta($post_id);
        
        ob_start();
        ?>
        <style>
        /* STILI GENERALI */
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 10pt; 
            line-height: 1.2; 
            color: #333; 
            margin: 0; 
            padding: 0;
        }
        
        /* HEADER PRINCIPALE */
        .main-header { 
            text-align: center; 
            padding: 15px 0; 
            margin-bottom: 20px; 
            border: 2px solid #2c3e50;
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            border-radius: 5px;
        }
        .main-header h1 { 
            font-size: 16pt; 
            font-weight: bold; 
            margin: 0 0 5px 0; 
            letter-spacing: 1px;
        }
        .main-header h2 { 
            font-size: 10pt; 
            margin: 0; 
            font-weight: normal; 
            opacity: 0.9;
        }
        
        /* SEZIONI PRINCIPALI */
.form-grid {
    display: flex;
    flex-wrap: wrap;
    column-gap: 20px;
}

.form-group {
    width: calc(50% - 10px);
    margin-bottom: 10px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 2px;
}

.form-group span {
    display: block;
    border: 1px solid #ccc;
    padding: 4px 6px;
    border-radius: 3px;
    background: #f9f9f9;
}

        .form-section { 
            margin-bottom: 15px; 
            page-break-inside: avoid; 
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-header { 
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white; 
            padding: 8px 12px; 
            font-weight: bold; 
            font-size: 10pt; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            border-bottom: 2px solid #2980b9;
        }
        
        .section-content { 
            padding: 12px; 
            background: #f8f9fa;
        }
        
        /* CAMPO SINGOLO */
        .form-field { 
            margin-bottom: 8px; 
            border: 1px solid #d5d9dd;
            border-radius: 3px;
            background: #ffffff;
            overflow: hidden;
        }
        
        .field-label { 
            background: #ecf0f1; 
            color: #2c3e50; 
            font-weight: bold; 
            font-size: 7pt; 
            padding: 4px 8px; 
            text-transform: uppercase;
            border-bottom: 1px solid #d5d9dd;
            letter-spacing: 0.3px;
        }
        
        .field-value { 
            padding: 6px 8px; 
            font-size: 10pt; 
            min-height: 12px;
            background: #ffffff;
            /*border-left: 3px solid #3498db;*/
        }
        
        .field-value.empty { 
            color: #95a5a6; 
            font-style: italic; 
            background: #fafbfc;
        }
        
        /* CAMPO EVIDENZIATO */
        .field-highlight .field-label { 
            background: #f39c12; 
            color: white; 
        }
        .field-highlight .field-value { 
            /*border-left-color: #f39c12;*/
            background: #fef9e7;
        }
        
        /* TABELLE */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 8px 0; 
            background: #ffffff;
            border: 1px solid #bdc3c7;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .data-table th { 
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white; 
            font-weight: bold; 
            font-size: 7pt; 
            padding: 6px 8px; 
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .data-table td { 
            border: 1px solid #d5d9dd; 
            padding: 6px 8px; 
            text-align: center; 
            font-size: 10pt;
            background: #f8f9fa;
        }
        
        .data-table tr:nth-child(even) td { 
            background: #ecf0f1; 
        }
        
        /* VEICOLI E PERSONE */
        .vehicle-block, .person-block { 
            margin: 10px 0; 
            border: 2px solid #27ae60;
            border-radius: 5px;
            background: #ffffff;
            overflow: hidden;
        }
        
        .vehicle-header, .person-header { 
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white; 
            padding: 6px 10px; 
            font-weight: bold; 
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .vehicle-content, .person-content { 
            padding: 10px; 
            background: #f1f8e9;
        }
        
        /* RIEPILOGO INFORTUNATI */
        .summary-box { 
            border: 3px solid #e74c3c;
            border-radius: 5px;
            background: #ffffff;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .summary-header { 
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white; 
            padding: 8px 12px; 
            font-weight: bold; 
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-content { 
            padding: 12px; 
            background: #fdf2f2;
        }
        
        .summary-table { 
            width: 100%; 
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e74c3c;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .summary-table th { 
            background: #e74c3c; 
            color: white; 
            padding: 8px; 
            font-weight: bold; 
            font-size: 10pt;
            text-transform: uppercase;
        }
        
        .summary-table td { 
            padding: 10px 8px; 
            text-align: center; 
            font-weight: bold; 
            font-size: 12pt;
            border: 1px solid #e74c3c;
        }
        
        .morti { color: #e74c3c; }
        .feriti { color: #f39c12; }
        .totale { color: #2c3e50; background: #ecf0f1; }
        
        /* LAYOUT RESPONSIVE */
        .two-column { 
            display: table; 
            width: 100%;
            border-spacing: 5px;
        }
        
        .column { 
            display: table-cell; 
            width: 50%; 
            vertical-align: top;
        }
        
        /* NOMINATIVI */
        .nominativi-section { 
            border: 2px solid #8e44ad;
            border-radius: 5px;
            background: #ffffff;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .nominativi-header { 
            background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);
            color: white; 
            padding: 8px 12px; 
            font-weight: bold; 
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nominativi-content { 
            padding: 12px; 
            background: #f8f5ff;
        }
        
        /* AVVISI LEGALI */
        .legal-notice { 
            background: #fff3cd; 
            border: 2px solid #ffc107; 
            border-radius: 5px;
            padding: 12px; 
            margin: 15px 0;
            font-size: 7pt;
            line-height: 1.3;
        }
        
        .legal-notice .title { 
            font-weight: bold; 
            color: #856404; 
            font-size: 10pt;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }
        
        /* FOOTER */
        .document-footer { 
            margin-top: 20px; 
            border-top: 2px solid #bdc3c7; 
            padding-top: 10px;
            text-align: center;
            font-size: 7pt;
            color: #7f8c8d;
        }
        
        /* UTILITY */
        .page-break { page-break-before: always; }
        .no-break { page-break-inside: avoid; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        </style>
        
        <div class="main-header">
            <h1>MODULO INCIDENTE STRADALE</h1>
            <h2>Rilevazione statistica degli incidenti stradali con lesioni a persone</h2>
        </div>
        
        <?php
        // DATI GENERALI
        $this->render_dati_generali_section($meta);
        
        // LOCALIZZAZIONE
        $this->render_localizzazione_section($meta);
        
        // NATURA DELL'INCIDENTE
        $this->render_natura_incidente_section($meta);
        
        // CARATTERISTICHE DEL LUOGO
        $this->render_caratteristiche_luogo_section($meta);
        
        // VEICOLI COINVOLTI
        $this->render_veicoli_section($meta);
        
        // PERSONE COINVOLTE
        $this->render_persone_section($meta);
        
        // PEDONI
        $this->render_pedoni_section($meta);
        
        // CIRCOSTANZE PRESUNTE
        $this->render_circostanze_section($meta);
        
        // RIEPILOGO INFORTUNATI
        $this->render_riepilogo_section($meta);
        
        // NOMINATIVI (se presenti)
        $this->render_nominativi_section($meta);
        ?>
        
        <div class="form-section">
            <div class="section-header">INFORMAZIONI DOCUMENTO</div>
            <div class="section-content">
                <div class="two-column">
                    <div class="column">
                        <?php $this->render_field('Data generazione', date('d/m/Y H:i:s')); ?>
                    </div>
                    <div class="column">
                        <?php $this->render_field('Generato da', 'Sistema Gestione Incidenti Stradali'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="document-footer">
            <p>Documento generato automaticamente dal Sistema di Gestione Incidenti Stradali</p>
            <p>ID Incidente: <?php echo $post_id; ?> | Data: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    // ===== MODIFICA SOLO QUESTO METODO HELPER =====
    private function render_field($label, $value, $highlight = false) {
        $class = $highlight ? 'form-field field-highlight' : 'form-field';
        $value_class = (empty($value) && $value !== '0') ? 'field-value empty' : 'field-value';
        $display_value = (empty($value) && $value !== '0') ? 'Non specificato' : esc_html($value);
        
        echo '<div class="' . $class . '">';
        echo '<div class="field-label">' . esc_html($label) . '</div>';
        echo '<div class="' . $value_class . '">' . $display_value . '</div>';
        echo '</div>';
    }
    
    // ===== COPIA QUI TUTTI I METODI DAL FILE ORIGINALE =====
    // Copia da render_dati_generali_section fino alla fine del file
    private function render_dati_generali_section($meta) {
        ?>
        <div class="section">
            <div class="section-title">DATI GENERALI</div>
            <div class="two-columns">
                <div class="column">
                    <?php $this->render_field('Incidente', $meta['codice__ente'][0] ?? ''); ?>
                    <?php $this->render_field('Data Incidente', $this->format_date($meta['data_incidente'][0] ?? '')); ?>
                    <?php $this->render_field('Ora', $this->format_time($meta['ora_incidente'][0] ?? '', $meta['minuti_incidente'][0] ?? '')); ?>
                    <?php $this->render_field('Provincia', $this->get_provincia_name($meta['provincia_incidente'][0] ?? '')); ?>
                </div>
                <div class="column">
                    <?php $this->render_field('Comune', $this->get_comune_name($meta['comune_incidente'][0] ?? '')); ?>
                    <?php $this->render_field('Località', $meta['localita_incidente'][0] ?? ''); ?>
                    <?php $this->render_field('Ente Rilevatore', $meta['ente_rilevatore'][0] ?? ''); ?>
                    <?php $this->render_field('Rilevatore', $meta['nome_rilevatore'][0] ?? ''); ?>
                </div>
            </div>
            <?php if (!empty($meta['identificativo_comando'][0])): ?>
                <?php $this->render_field('Identificativo Comando Carabinieri', $meta['identificativo_comando'][0]); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    private function render_localizzazione_section($meta) {
        ?>
        <div class="section">
            <div class="section-title">LOCALIZZAZIONE DELL'INCIDENTE</div>
            <div class="two-columns">
                <div class="column">
                    <?php $this->render_field('Tipo di Strada', $this->get_tipo_strada_name($meta['tipo_strada'][0] ?? '')); ?>
                    <?php $this->render_field('Denominazione Strada', $meta['denominazione_strada'][0] ?? ''); ?>
                    <?php if (!empty($meta['numero_strada'][0])): ?>
                        <?php $this->render_field('Numero Strada', $meta['numero_strada'][0]); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['tronco_strada'][0])): ?>
                        <?php $this->render_field('Tronco Strada', $this->get_tronco_strada_name($meta['tronco_strada'][0])); ?>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <?php if (!empty($meta['progressiva_km'][0]) || !empty($meta['progressiva_m'][0])): ?>
                        <?php $this->render_field('Progressiva Chilometrica', 
                            'Km: ' . ($meta['progressiva_km'][0] ?? '0') . ' - Mt: ' . ($meta['progressiva_m'][0] ?? '0')); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['latitudine'][0]) && !empty($meta['longitudine'][0])): ?>
                        <?php $this->render_field('Coordinate GPS', 
                            'Lat: ' . $meta['latitudine'][0] . ' - Long: ' . $meta['longitudine'][0]); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    } 
    private function render_natura_incidente_section($meta) {
        ?>
        <div class="section">
            <div class="section-title">NATURA DELL'INCIDENTE</div>
            <div class="two-columns">
                <div class="column">
                    <?php $this->render_field('Natura Incidente', $this->get_natura_incidente_name($meta['natura_incidente'][0] ?? '')); ?>
                    <?php if (!empty($meta['dettaglio_natura'][0])): ?>
                        <?php $this->render_field('Dettaglio', $this->get_dettaglio_natura_name($meta['dettaglio_natura'][0], $meta['natura_incidente'][0] ?? '')); ?>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <?php $this->render_field('Numero Veicoli Coinvolti', $meta['numero_veicoli_coinvolti'][0] ?? '1'); ?>
                    <?php if (!empty($meta['altro_natura_testo'][0])): ?>
                        <?php $this->render_field('Altro (specificato)', $meta['altro_natura_testo'][0]); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    private function render_caratteristiche_luogo_section($meta) {
        ?>
        <div class="section">
            <div class="section-title">CARATTERISTICHE DEL LUOGO</div>
            <div class="two-columns">
                <div class="column">
                    <?php $this->render_field('Configurazione Carreggiate', $this->get_geometria_strada_name($meta['geometria_strada'][0] ?? '')); ?>
                    <?php $this->render_field('Pavimentazione', $this->get_pavimentazione_name($meta['pavimentazione_strada'][0] ?? '')); ?>
                    <?php $this->render_field('Intersezione/Tronco', $this->get_intersezione_name($meta['intersezione_tronco'][0] ?? '')); ?>
                    <?php $this->render_field('Stato Fondo Strada', $this->get_fondo_strada_name($meta['stato_fondo_strada'][0] ?? '')); ?>
                </div>
                <div class="column">
                    <?php $this->render_field('Segnaletica', $this->get_segnaletica_name($meta['segnaletica_strada'][0] ?? '')); ?>
                    <?php $this->render_field('Condizioni Meteo', $this->get_condizioni_meteo_name($meta['condizioni_meteo'][0] ?? '')); ?>
                    <?php $this->render_field('Illuminazione', $this->get_illuminazione_name($meta['illuminazione'][0] ?? '')); ?>
                    <?php if (!empty($meta['presenza_banchina'][0])): ?>
                        <?php $this->render_field('Banchina', 'Presente'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    private function render_veicoli_section($meta) {
        $numero_veicoli = intval($meta['numero_veicoli_coinvolti'][0] ?? 1);
        
        ?>
        <div class="section">
            <div class="section-title">VEICOLI COINVOLTI</div>
            <?php for ($i = 1; $i <= $numero_veicoli; $i++): ?>
                <?php if (!empty($meta["veicolo_{$i}_tipo"][0])): ?>
                    <div class="section" style="padding-left: 10px; margin-bottom: 15px;">
                        <h4 style="margin: 5px 0; color: #007cba;">Veicolo <?php echo chr(64 + $i); ?></h4>
                        <div class="two-columns">
                            <div class="column">
                                <?php $this->render_field('Tipo Veicolo', $this->get_tipo_veicolo_name($meta["veicolo_{$i}_tipo"][0] ?? '')); ?>
                                <?php $this->render_field('Targa', $meta["veicolo_{$i}_targa"][0] ?? ''); ?>
                                <?php if (!empty($meta["veicolo_{$i}_sigla_estero"][0])): ?>
                                    <?php $this->render_field('Sigla Estero', $meta["veicolo_{$i}_sigla_estero"][0]); ?>
                                <?php endif; ?>
                                <?php $this->render_field('Anno Immatricolazione', $meta["veicolo_{$i}_anno_immatricolazione"][0] ?? ''); ?>
                            </div>
                            <div class="column">
                                <?php if (!empty($meta["veicolo_{$i}_cilindrata"][0])): ?>
                                    <?php $this->render_field('Cilindrata (cc)', $meta["veicolo_{$i}_cilindrata"][0]); ?>
                                <?php endif; ?>
                                <?php if (!empty($meta["veicolo_{$i}_peso_totale"][0])): ?>
                                    <?php $this->render_field('Peso Totale (q)', $meta["veicolo_{$i}_peso_totale"][0]); ?>
                                <?php endif; ?>
                                <?php if (!empty($meta["veicolo_{$i}_tipo_rimorchio"][0])): ?>
                                    <?php $this->render_field('Tipo Rimorchio', $this->get_tipo_rimorchio_name($meta["veicolo_{$i}_tipo_rimorchio"][0])); ?>
                                    <?php if (!empty($meta["veicolo_{$i}_targa_rimorchio"][0])): ?>
                                        <?php $this->render_field('Targa Rimorchio', $meta["veicolo_{$i}_targa_rimorchio"][0]); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($meta["veicolo_{$i}_danni_riportati"][0])): ?>
                            <?php $this->render_field('Danni Riportati', $meta["veicolo_{$i}_danni_riportati"][0]); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php
    }
    private function render_persone_section($meta) {
        $numero_veicoli = intval($meta['numero_veicoli_coinvolti'][0] ?? 1);
        
        ?>
        <div class="section page-break">
            <div class="section-title">CONDUCENTI</div>
            <?php for ($i = 1; $i <= $numero_veicoli; $i++): ?>
                <?php if (!empty($meta["conducente_{$i}_eta"][0])): ?>
                    <div class="section" style="padding-left: 10px; margin-bottom: 12px;">
                        <h4 style="margin: 5px 0; color: #28a745;">Conducente Veicolo <?php echo chr(64 + $i); ?></h4>
                        <div class="two-columns">
                            <div class="column">
                                <?php $this->render_field('Età', $meta["conducente_{$i}_eta"][0] . ' anni'); ?>
                                <?php $this->render_field('Sesso', $this->get_sesso_name($meta["conducente_{$i}_sesso"][0] ?? '')); ?>
                                <?php $this->render_field('Esito', $this->get_esito_conducente_name($meta["conducente_{$i}_esito"][0] ?? '')); ?>
                                <?php $this->render_field('Nazionalità', $this->get_nazionalita_name($meta["conducente_{$i}_nazionalita"][0] ?? '')); ?>
                            </div>
                            <div class="column">
                                <?php $this->render_field('Tipo Patente', $this->get_tipo_patente_names($meta["conducente_{$i}_tipo_patente"][0] ?? array())); ?>
                                <?php if (!empty($meta["conducente_{$i}_anno_patente"][0])): ?>
                                    <?php $this->render_field('Anno Rilascio Patente', $meta["conducente_{$i}_anno_patente"][0]); ?>
                                <?php endif; ?>
                                <?php if (!empty($meta["conducente_{$i}_tipologia_incidente"][0])): ?>
                                    <?php $this->render_field('Tipo Incidente Lavorativo', $this->get_tipologia_incidente_name($meta["conducente_{$i}_tipologia_incidente"][0])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
            
            <!-- TRASPORTATI -->
            <?php $this->render_trasportati_section($meta, $numero_veicoli); ?>
            
            <!-- ALTRI PASSEGGERI -->
            <?php $this->render_altri_passeggeri_section($meta, $numero_veicoli); ?>
        </div>
        <?php
    }
    private function render_trasportati_section($meta, $numero_veicoli) {
        $has_trasportati = false;
        
        // Verifica se ci sono trasportati
        for ($i = 1; $i <= $numero_veicoli; $i++) {
            $num_trasportati = intval($meta["veicolo_{$i}_numero_trasportati"][0] ?? 0);
            if ($num_trasportati > 0) {
                $has_trasportati = true;
                break;
            }
        }
        
        if (!$has_trasportati) return;
        
        ?>
        <div class="section">
            <div class="section-title">TRASPORTATI</div>
            <?php for ($i = 1; $i <= $numero_veicoli; $i++): ?>
                <?php 
                $num_trasportati = intval($meta["veicolo_{$i}_numero_trasportati"][0] ?? 0);
                if ($num_trasportati > 0): 
                ?>
                    <h5 style="color: #6c757d; margin: 10px 0 5px 0;">Veicolo <?php echo chr(64 + $i); ?> - <?php echo $num_trasportati; ?> trasportati</h5>
                    <?php for ($t = 1; $t <= $num_trasportati; $t++): ?>
                        <?php if (!empty($meta["veicolo_{$i}_trasportato_{$t}_eta"][0])): ?>
                            <div style="margin-left: 15px; margin-bottom: 8px; font-size: 10pt;">
                                <strong>Trasportato <?php echo $t; ?>:</strong>
                                Età: <?php echo $meta["veicolo_{$i}_trasportato_{$t}_eta"][0]; ?> anni,
                                Sesso: <?php echo $this->get_sesso_trasportato_name($meta["veicolo_{$i}_trasportato_{$t}_sesso"][0] ?? ''); ?>,
                                <?php if (!empty($meta["veicolo_{$i}_trasportato_{$t}_sedile"][0])): ?>
                                    Posizione: <?php echo $this->get_posizione_sedile_name($meta["veicolo_{$i}_trasportato_{$t}_sedile"][0]); ?>,
                                <?php endif; ?>
                                Esito: <?php echo $this->get_esito_trasportato_name($meta["veicolo_{$i}_trasportato_{$t}_esito"][0] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php
    }
    private function render_altri_passeggeri_section($meta, $numero_veicoli) {
        $has_altri = false;
        
        // Verifica se ci sono altri passeggeri
        for ($i = 1; $i <= $numero_veicoli; $i++) {
            if (!empty($meta["veicolo_{$i}_altri_morti_maschi"][0]) || 
                !empty($meta["veicolo_{$i}_altri_morti_femmine"][0]) ||
                !empty($meta["veicolo_{$i}_altri_feriti_maschi"][0]) || 
                !empty($meta["veicolo_{$i}_altri_feriti_femmine"][0])) {
                $has_altri = true;
                break;
            }
        }
        
        if (!$has_altri) return;
        
        ?>
        <div class="section">
            <div class="section-title">ALTRI PASSEGGERI INFORTUNATI</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Veicolo</th>
                        <th>Morti Maschi</th>
                        <th>Morti Femmine</th>
                        <th>Feriti Maschi</th>
                        <th>Feriti Femmine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i <= $numero_veicoli; $i++): ?>
                        <?php 
                        $morti_m = $meta["veicolo_{$i}_altri_morti_maschi"][0] ?? '';
                        $morti_f = $meta["veicolo_{$i}_altri_morti_femmine"][0] ?? '';
                        $feriti_m = $meta["veicolo_{$i}_altri_feriti_maschi"][0] ?? '';
                        $feriti_f = $meta["veicolo_{$i}_altri_feriti_femmine"][0] ?? '';
                        
                        if ($morti_m || $morti_f || $feriti_m || $feriti_f):
                        ?>
                            <tr>
                                <td><?php echo chr(64 + $i); ?></td>
                                <td><?php echo $morti_m ?: '0'; ?></td>
                                <td><?php echo $morti_f ?: '0'; ?></td>
                                <td><?php echo $feriti_m ?: '0'; ?></td>
                                <td><?php echo $feriti_f ?: '0'; ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    private function render_pedoni_section($meta) {
        $pedoni_feriti = intval($meta['numero_pedoni_feriti'][0] ?? 0);
        $pedoni_morti = intval($meta['numero_pedoni_morti'][0] ?? 0);
        
        if ($pedoni_feriti == 0 && $pedoni_morti == 0) return;
        
        ?>
        <div class="section">
            <div class="section-title">PEDONI COINVOLTI</div>
            <div class="two-columns">
                <div class="column">
                    <?php $this->render_field('Numero Pedoni Feriti', $pedoni_feriti); ?>
                    <?php if ($pedoni_feriti > 0): ?>
                        <?php for ($i = 1; $i <= $pedoni_feriti; $i++): ?>
                            <?php if (!empty($meta["pedone_ferito_{$i}_eta"][0])): ?>
                                <div style="margin-left: 15px; font-size: 10pt;">
                                    <strong>Pedone Ferito <?php echo $i; ?>:</strong>
                                    Età: <?php echo $meta["pedone_ferito_{$i}_eta"][0]; ?> anni,
                                    Sesso: <?php echo $this->get_sesso_pedone_name($meta["pedone_ferito_{$i}_sesso"][0] ?? ''); ?>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <?php $this->render_field('Numero Pedoni Morti', $pedoni_morti); ?>
                    <?php if ($pedoni_morti > 0): ?>
                        <?php for ($i = 1; $i <= $pedoni_morti; $i++): ?>
                            <?php if (!empty($meta["pedone_morto_{$i}_eta"][0])): ?>
                                <div style="margin-left: 15px; font-size: 10pt;">
                                    <strong>Pedone Morto <?php echo $i; ?>:</strong>
                                    Età: <?php echo $meta["pedone_morto_{$i}_eta"][0]; ?> anni,
                                    Sesso: <?php echo $this->get_sesso_pedone_morto_name($meta["pedone_morto_{$i}_sesso"][0] ?? ''); ?>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    private function render_circostanze_section($meta) {
        $has_circostanze = false;
        
        // Verifica se ci sono circostanze da mostrare
        $campi_circostanze = [
            'circostanza_veicolo_a', 'circostanza_veicolo_b', 'circostanza_veicolo_c',
            'difetto_veicolo_a', 'difetto_veicolo_b', 'difetto_veicolo_c',
            'stato_psicofisico_a', 'stato_psicofisico_b', 'stato_psicofisico_c'
        ];
        
        foreach ($campi_circostanze as $campo) {
            if (!empty($meta[$campo][0])) {
                $has_circostanze = true;
                break;
            }
        }
        
        if (!$has_circostanze) return;
        
        ?>
        <div class="section page-break">
            <div class="section-title">CIRCOSTANZE PRESUNTE DELL'INCIDENTE</div>
            
            <?php if (!empty($meta['circostanza_tipo'][0])): ?>
                <?php $this->render_field('Tipo di Incidente', $this->get_circostanza_tipo_name($meta['circostanza_tipo'][0])); ?>
            <?php endif; ?>
            
            <div class="two-columns">
                <div class="column">
                    <?php if (!empty($meta['circostanza_veicolo_a'][0])): ?>
                        <?php $this->render_field('Circostanza Veicolo A', $this->get_circostanza_name($meta['circostanza_veicolo_a'][0])); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['difetto_veicolo_a'][0])): ?>
                        <?php $this->render_field('Difetto Veicolo A', $this->get_difetto_veicolo_name($meta['difetto_veicolo_a'][0])); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['stato_psicofisico_a'][0])): ?>
                        <?php $this->render_field('Stato Psicofisico A', $this->get_stato_psicofisico_name($meta['stato_psicofisico_a'][0])); ?>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <?php if (!empty($meta['circostanza_veicolo_b'][0])): ?>
                        <?php $this->render_field('Circostanza Veicolo B', $this->get_circostanza_name($meta['circostanza_veicolo_b'][0])); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['difetto_veicolo_b'][0])): ?>
                        <?php $this->render_field('Difetto Veicolo B', $this->get_difetto_veicolo_name($meta['difetto_veicolo_b'][0])); ?>
                    <?php endif; ?>
                    <?php if (!empty($meta['stato_psicofisico_b'][0])): ?>
                        <?php $this->render_field('Stato Psicofisico B', $this->get_stato_psicofisico_name($meta['stato_psicofisico_b'][0])); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    private function render_riepilogo_section($meta) {
        $morti_24h = intval($meta['riepilogo_morti_24h'][0] ?? 0);
        $morti_2_30gg = intval($meta['riepilogo_morti_2_30gg'][0] ?? 0);
        $feriti = intval($meta['riepilogo_feriti'][0] ?? 0);
        
        if ($morti_24h == 0 && $morti_2_30gg == 0 && $feriti == 0) return;
        
        ?>
        <div class="section">
            <div class="section-title">RIEPILOGO INFORTUNATI</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Morti entro 24h</th>
                        <th>Morti 2°-30° giorno</th>
                        <th>Feriti</th>
                        <th>Totale Infortunati</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #d63638;"><?php echo $morti_24h; ?></td>
                        <td style="text-align: center; font-weight: bold; color: #d63638;"><?php echo $morti_2_30gg; ?></td>
                        <td style="text-align: center; font-weight: bold; color: #f0b849;"><?php echo $feriti; ?></td>
                        <td style="text-align: center; font-weight: bold;"><?php echo ($morti_24h + $morti_2_30gg + $feriti); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    private function render_nominativi_section($meta) {
        // Verifica se ci sono nominativi da mostrare
        $has_nominativi = false;
        
        // Controlla morti
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($meta["morto_{$i}_nome"][0]) || !empty($meta["morto_{$i}_cognome"][0])) {
                $has_nominativi = true;
                break;
            }
        }
        
        // Controlla feriti se non già trovato
        if (!$has_nominativi) {
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($meta["ferito_{$i}_nome"][0]) || !empty($meta["ferito_{$i}_cognome"][0])) {
                    $has_nominativi = true;
                    break;
                }
            }
        }
        
        if (!$has_nominativi) return;
        
        ?>
        <div class="section page-break">
            <div class="section-title">NOMINATIVI MORTI E FERITI</div>
            
            <!-- MORTI -->
            <?php 
            $morti_presenti = false;
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($meta["morto_{$i}_nome"][0]) || !empty($meta["morto_{$i}_cognome"][0])) {
                    $morti_presenti = true;
                    break;
                }
            }
            
            if ($morti_presenti):
            ?>
                <div class="section">
                    <h4 style="color: #d63638; margin: 10px 0 5px 0;">MORTI</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <?php if (!empty($meta["morto_{$i}_nome"][0]) || !empty($meta["morto_{$i}_cognome"][0])): ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo esc_html($meta["morto_{$i}_nome"][0] ?? ''); ?></td>
                                        <td><?php echo esc_html($meta["morto_{$i}_cognome"][0] ?? ''); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- FERITI -->
            <?php 
            $feriti_presenti = false;
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($meta["ferito_{$i}_nome"][0]) || !empty($meta["ferito_{$i}_cognome"][0])) {
                    $feriti_presenti = true;
                    break;
                }
            }
            
            if ($feriti_presenti):
            ?>
                <div class="section">
                    <h4 style="color: #f0b849; margin: 10px 0 5px 0;">FERITI</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Istituto di Cura</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <?php if (!empty($meta["ferito_{$i}_nome"][0]) || !empty($meta["ferito_{$i}_cognome"][0])): ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo esc_html($meta["ferito_{$i}_nome"][0] ?? ''); ?></td>
                                        <td><?php echo esc_html($meta["ferito_{$i}_cognome"][0] ?? ''); ?></td>
                                        <td><?php echo esc_html($meta["ferito_{$i}_istituto"][0] ?? ''); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="warning" style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <strong>SEGRETO STATISTICO, OBBLIGO DI RISPOSTA, TUTELA DELLA RISERVATEZZA E DIRITTI DEGLI INTERESSATI</strong><br>
                <span style="font-size: 10pt;">
                    Decreto legislativo 6 settembre 1989, n. 322 - Norme sul Sistema statistico nazionale e sulla riorganizzazione dell\'Istituto nazionale di statistica<br/>
                    Decreto legislativo 30 giugno 2003, n. 196 - Codice in materia di protezione dei dati personali<br/>
                    Regolamento UE 2016/679 - Regolamento generale sulla protezione dei dati<br/>
                    I dati raccolti sono tutelati dal segreto statistico e sottoposti alla normativa in materia di protezione dei dati personali e potranno essere utilizzati, anche per successivi trattamenti, esclusivamente per fini statistici dai soggetti del Sistema statistico nazionale ed essere comunicati per finalità di ricerca scientifica alle condizioni e secondo le modalità previste dall\'art 7 del Codice di deontologia e di buona condotta per i trattamenti di dati personali a scopi statistici.<br/>
                    Titolare del trattamento dei dati è l\'ISTAT – Istituto nazionale di statistica - Via Cesare Balbo, 16 – 00184 Roma. Responsabili del trattamento dei dati sono, per le fasi di rispettiva competenza, il Direttore centrale per le statistiche e le indagini sulle istituzioni sociali dell\'Istat e il preposto all\'Ufficio di statistica della Regione o Provincia autonoma<br/>
                    L\'inserimento dei nominativi è OBBLIGATORIO ai sensi dell\'art. 7 del d.lgs. n. 322/1989 e fatto obbligo alle amministrazioni, enti ed organismi pubblici, di fornire tutti i dati e le notizie richieste nel modello di rilevazione.
                </span>
            </div>
        </div>
        <?php
    }
    private function format_date($date) {
        if (empty($date)) return '';
        return date('d/m/Y', strtotime($date));
    }
    
    private function format_time($ora, $minuti) {
        if (empty($ora)) return '';
        return sprintf('%02d:%02d', intval($ora), intval($minuti));
    }
    
    private function get_provincia_name($codice) {
        return $codice === '075' ? 'Lecce (075)' : $codice;
    }
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
        return isset($comuni[$codice]) ? $comuni[$codice] . ' (' . $codice . ')' : $codice;
    }
    private function get_tipo_strada_name($codice) {
        $tipi = array(
            '0' => 'Regionale entro l\'abitato',
            '1' => 'Strada urbana',
            '2' => 'Provinciale entro l\'abitato',
            '3' => 'Statale entro l\'abitato',
            '4' => 'Strada comunale extraurbana',
            '5' => 'Strada provinciale fuori dell\'abitato',
            '6' => 'Strada statale fuori dell\'abitato',
            '7' => 'Autostrada',
            '8' => 'Altra strada',
            '9' => 'Strada regionale fuori l\'abitato'
        );
        return isset($tipi[$codice]) ? $tipi[$codice] : $codice;
    }
    private function get_tronco_strada_name($codice) {
        $tronchi = array(
            '1' => 'diramazione; dir. A',
            '2' => 'dir. B; radd.',
            '3' => 'bis; dir. C',
            '4' => 'ter; bis dir.',
            '5' => 'quater; racc.; bis racc.',
            '6' => 'Autostrada carreggiata sinistra',
            '7' => 'Autostrada carreggiata destra',
            '8' => 'Autostrada svincolo entrata',
            '9' => 'Autostrada svincolo uscita',
            '10' => 'Autostrada svincolo tronco d.c.',
            '11' => 'Autostrada stazione',
            '12' => 'Altri casi'
        );
        return isset($tronchi[$codice]) ? $tronchi[$codice] : $codice;
    }
    private function get_natura_incidente_name($codice) {
        $nature = array(
            'A' => 'Tra veicoli in marcia',
            'B' => 'Tra veicolo e pedoni',
            'C' => 'Veicolo in marcia che urta veicolo fermo o altro',
            'D' => 'Veicolo in marcia senza urto',
            'E' => 'Altro'
        );
        return isset($nature[$codice]) ? $nature[$codice] : $codice;
    }
    private function get_dettaglio_natura_name($codice, $natura) {
        $dettagli = array(
            'A' => array(
                '1' => 'Scontro frontale',
                '2' => 'Scontro frontale-laterale',
                '3' => 'Scontro laterale',
                '4' => 'Tamponamento',
                '5' => 'Salto carreggiata'
            ),
            'B' => array('5' => 'Investimento di pedoni'),
            'C' => array(
                'frontale' => 'Urto frontale',
                'laterale' => 'Urto laterale',
                '6' => 'Urto con veicolo in fermata o in arresto',
                '7' => 'Urto con veicolo in sosta',
                '8' => 'Urto con ostacolo',
                '9' => 'Urto con treno'
            ),
            'D' => array(
                '10' => 'Fuoriuscita (sbandamento, ...)',
                '11' => 'Infortunio per frenata improvvisa',
                '12' => 'Infortunio per caduta da veicolo'
            )
        );
        return isset($dettagli[$natura][$codice]) ? $dettagli[$natura][$codice] : $codice;
    }
    private function get_geometria_strada_name($codice) {
        $geometrie = array(
            '1' => 'Una carreggiata senso unico',
            '2' => 'Una carreggiata doppio senso',
            '3' => 'Due carreggiate',
            '4' => 'Più di 2 carreggiate'
        );
        return isset($geometrie[$codice]) ? $geometrie[$codice] : $codice;
    }
    private function get_pavimentazione_name($codice) {
        $pavimentazioni = array(
            '1' => 'Strada pavimentata',
            '2' => 'Strada pavimentata dissestata',
            '3' => 'Strada non pavimentata'
        );
        return isset($pavimentazioni[$codice]) ? $pavimentazioni[$codice] : $codice;
    }
    private function get_intersezione_name($codice) {
        $intersezioni = array(
            '1' => 'Incrocio',
            '2' => 'Rotatoria',
            '3' => 'Intersezione segnalata',
            '4' => 'Intersezione con semaforo o vigile',
            '5' => 'Intersezione non segnalata',
            '6' => 'Passaggio a livello',
            '7' => 'Rettilineo',
            '8' => 'Curva',
            '9' => 'Dosso, strettoia',
            '10' => 'Pend. - salita',
            '10b' => 'Pend. - discesa',
            '11' => 'Gall. illuminata',
            '12' => 'Gall. non illuminata',
            '14' => 'Cunetta',
            '15' => 'Cavalcavia',
            '16' => 'Trincea',
            '17' => 'Rilevato',
            '18' => 'Accessi laterali'
        );
        return isset($intersezioni[$codice]) ? $intersezioni[$codice] : $codice;
    }
    private function get_fondo_strada_name($codice) {
        $fondi = array(
            '1' => 'Asciutto',
            '2' => 'Bagnato',
            '3' => 'Sdrucciolevole',
            '4' => 'Ghiacciato',
            '5' => 'Innevato'
        );
        return isset($fondi[$codice]) ? $fondi[$codice] : $codice;
    }
    private function get_segnaletica_name($codice) {
        $segnaletica = array(
            '1' => 'Assente',
            '2' => 'Verticale',
            '3' => 'Orizzontale',
            '4' => 'Verticale e orizzontale',
            '5' => 'Temporanea di cantiere'
        );
        return isset($segnaletica[$codice]) ? $segnaletica[$codice] : $codice;
    }
    private function get_condizioni_meteo_name($codice) {
        $meteo = array(
            '1' => 'Sereno',
            '2' => 'Nebbia',
            '3' => 'Pioggia',
            '4' => 'Grandine',
            '5' => 'Neve',
            '6' => 'Vento forte',
            '7' => 'Altro'
        );
        return isset($meteo[$codice]) ? $meteo[$codice] : $codice;
    }
    private function get_illuminazione_name($codice) {
        $illuminazioni = array(
            '1' => 'Luce diurna',
            '2' => 'Crepuscolo alba',
            '3' => 'Buio: luci stradali presenti accese',
            '4' => 'Buio: luci stradali presenti spente',
            '5' => 'Buio: assenza di illuminazione stradale',
            '6' => 'Illuminazione stradale non nota'
        );
        return isset($illuminazioni[$codice]) ? $illuminazioni[$codice] : $codice;
    }
    private function get_tipo_veicolo_name($codice) {
        $tipi = array(
            '1' => 'Autovettura privata',
            '2' => 'Autovettura con rimorchio',
            '3' => 'Autovettura pubblica',
            '4' => 'Autovettura di soccorso o di polizia',
            '5' => 'Autobus o filobus in servizio urbano',
            '6' => 'Autobus di linea o non di linea in extraurbana',
            '7' => 'Tram',
            '8' => 'Autocarro',
            '9' => 'Autotreno con rimorchio',
            '10' => 'Autosnodato o autoarticolato',
            '11' => 'Veicoli speciali',
            '12' => 'Trattore stradale o motrice',
            '13' => 'Macchina agricola',
            '14' => 'Velocipede',
            '15' => 'Ciclomotore',
            '16' => 'Motociclo a solo',
            '17' => 'Motociclo con passeggero',
            '18' => 'Motocarro o motofurgone',
            '19' => 'Veicolo a trazione animale o a braccia',
            '20' => 'Veicolo ignoto perché datosi alla fuga',
            '21' => 'Quadriciclo',
            '22' => 'Monopattino',
            '23' => 'Bicicletta elettrica'
        );
        return isset($tipi[$codice]) ? $tipi[$codice] : $codice;
    }
    private function get_tipo_rimorchio_name($codice) {
        $rimorchi = array(
            '1' => 'Rimorchio',
            '2' => 'Semirimorchio',
            '3' => 'Carrello appendice'
        );
        return isset($rimorchi[$codice]) ? $rimorchi[$codice] : $codice;
    }
    private function get_sesso_name($codice) {
        return $codice === '1' ? 'Maschio' : ($codice === '2' ? 'Femmina' : $codice);
    }
    private function get_sesso_trasportato_name($codice) {
        return $codice === '3' ? 'Maschio' : ($codice === '4' ? 'Femmina' : $codice);
    }
    private function get_sesso_pedone_name($codice) {
        return $codice === '3' ? 'Maschio' : ($codice === '4' ? 'Femmina' : $codice);
    }
    private function get_sesso_pedone_morto_name($codice) {
        return $codice === '1' ? 'Maschio' : ($codice === '2' ? 'Femmina' : $codice);
    }
    private function get_esito_conducente_name($codice) {
        $esiti = array(
            '1' => 'Incolume',
            '2' => 'Ferito',
            '3' => 'Morto entro 24 ore',
            '4' => 'Morto dal 2° al 30° giorno'
        );
        return isset($esiti[$codice]) ? $esiti[$codice] : $codice;
    }
    private function get_esito_trasportato_name($codice) {
        return $codice === '1' ? 'Morto' : ($codice === '2' ? 'Ferito' : $codice);
    }
    private function get_posizione_sedile_name($codice) {
        return $codice === 'anteriore' ? 'Sedile anteriore' : ($codice === 'posteriore' ? 'Sedile posteriore' : $codice);
    }
    private function get_nazionalita_name($codice) {
        if (empty($codice)) return '';
        $parts = explode('-', $codice);
        return count($parts) > 1 ? $parts[1] : $codice;
    }
    private function get_tipo_patente_names($patenti) {
        if (!is_array($patenti)) return '';
        
        $nomi = array(
            '0' => 'Patente ciclomotori',
            '1' => 'Patente tipo A',
            '2' => 'Patente tipo B',
            '3' => 'Patente tipo C',
            '4' => 'Patente tipo D',
            '5' => 'Patente tipo E',
            '6' => 'ABC speciale',
            '7' => 'Non richiesta',
            '8' => 'Foglio rosa',
            '9' => 'Sprovvisto'
        );
        
        $result = array();
        foreach ($patenti as $patente) {
            if (isset($nomi[$patente])) {
                $result[] = $nomi[$patente];
            }
        }
        
        return implode(', ', $result);
    }
    private function get_tipologia_incidente_name($codice) {
        $tipologie = array(
            '1' => 'Incidente durante attività lavorativa',
            '2' => 'Incidente durante tragitto casa-lavoro'
        );
        return isset($tipologie[$codice]) ? $tipologie[$codice] : '';
    }
    private function get_circostanza_tipo_name($codice) {
        $tipi = array(
            'intersezione' => 'Incidente all\'intersezione stradale',
            'non_intersezione' => 'Incidente non all\'intersezione',
            'investimento' => 'Investimento di pedone',
            'urto_fermo' => 'Urto con veicolo fermo/ostacolo',
            'senza_urto' => 'Veicolo senza urto'
        );
        return isset($tipi[$codice]) ? $tipi[$codice] : $codice;
    }
    private function get_circostanza_name($codice) {
        // Mappa semplificata delle circostanze principali
        $circostanze = array(
            '01' => 'Procedeva regolarmente senza svoltare',
            '02' => 'Procedeva con guida distratta e andamento indeciso',
            '03' => 'Procedeva senza mantenere la distanza di sicurezza',
            '04' => 'Procedeva senza dare la precedenza al veicolo da destra',
            '05' => 'Procedeva senza rispettare lo stop',
            '20' => 'Procedeva regolarmente',
            '21' => 'Procedeva con guida distratta e andamento indeciso',
            '22' => 'Procedeva senza mantenere la distanza di sicurezza',
            '23' => 'Procedeva con eccesso di velocità',
            '40' => 'Procedeva regolarmente',
            '41' => 'Procedeva con eccesso di velocità',
            '49' => 'Non dava la precedenza al pedone',
            '70' => 'Sbandamento per evitare urto',
            '71' => 'Sbandamento per guida distratta'
        );
        return isset($circostanze[$codice]) ? $circostanze[$codice] : $codice;
    }
    private function get_difetto_veicolo_name($codice) {
        $difetti = array(
            '80' => 'Rottura o insufficienza dei freni',
            '81' => 'Rottura o guasto allo sterzo',
            '82' => 'Scoppio o eccessiva usura dei pneumatici',
            '83' => 'Mancanza o insufficienza dei fari o delle luci di posizione',
            '84' => 'Mancanza o insufficienza dei lampeggiatori',
            '85' => 'Rottura degli organi di agganciamento dei rimorchi',
            '86' => 'Deficienza delle attrezzature per trasporto merci pericolose',
            '87' => 'Mancanza adattamenti per mutilati o minorati fisici',
            '88' => 'Distacco di ruota',
            '89' => 'Mancanza dispositivi visivi dei velocipedi'
        );
        return isset($difetti[$codice]) ? $difetti[$codice] : $codice;
    }
    private function get_stato_psicofisico_name($codice) {
        $stati = array(
            '90' => 'Anormale per ebbrezza da alcool',
            '91' => 'Anormale per condizioni morbose in atto',
            '92' => 'Anormale per improvviso malore',
            '93' => 'Anormale per sonno',
            '94' => 'Anormale per ingestione sostanze stupefacenti',
            '95' => 'Mancato uso di lenti correttive',
            '96' => 'Abbagliato',
            '97' => 'Per aver superato i periodi di guida prescritti'
        );
        return isset($stati[$codice]) ? $stati[$codice] : $codice;
    }
    
}