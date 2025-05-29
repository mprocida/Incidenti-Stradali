<?php
/**
 * Meta Boxes for Incidenti Stradali
 */

class IncidentiMetaBoxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('edit_form_after_title', array($this, 'move_meta_boxes_after_title'));

        // NUOVO: Gestione operazioni di eliminazione
        add_action('wp_trash_post', array($this, 'on_post_trashed'));
        add_action('before_delete_post', array($this, 'on_post_deleted'));
        add_action('untrash_post', array($this, 'on_post_untrashed'));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'incidente_dati_generali',
            __('Dati Generali', 'incidenti-stradali'),
            array($this, 'render_dati_generali_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_localizzazione',
            __('Localizzazione dell\'Incidente', 'incidenti-stradali'),
            array($this, 'render_localizzazione_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_luogo',
            __('Caratteristiche del Luogo', 'incidenti-stradali'),
            array($this, 'render_luogo_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_natura',
            __('Natura dell\'Incidente', 'incidenti-stradali'),
            array($this, 'render_natura_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_veicoli',
            __('Veicoli Coinvolti', 'incidenti-stradali'),
            array($this, 'render_veicoli_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_persone',
            __('Persone Coinvolte', 'incidenti-stradali'),
            array($this, 'render_persone_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );
        
        add_meta_box(
            'incidente_coordinate',
            __('Coordinate Geografiche', 'incidenti-stradali'),
            array($this, 'render_coordinate_meta_box'),
            'incidente_stradale',
            'side',
            'default'
        );
        
        add_meta_box(
            'incidente_mappa',
            __('Visualizzazione Mappa', 'incidenti-stradali'),
            array($this, 'render_mappa_meta_box'),
            'incidente_stradale',
            'side',
            'default'
        );

        add_meta_box(
            'incidente_circostanze',
            __('Circostanze Presunte dell\'Incidente', 'incidenti-stradali'),
            array($this, 'render_circostanze_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );

        add_meta_box(
            'incidente_dati_aggiuntivi',
            __('Dati Aggiuntivi ISTAT', 'incidenti-stradali'),
            array($this, 'render_dati_aggiuntivi_meta_box'),
            'incidente_stradale',
            'normal',
            'low'
        );

        add_meta_box(
            'incidente_circostanze',
            __('Circostanze Presunte dell\'Incidente', 'incidenti-stradali'),
            array($this, 'render_circostanze_meta_box'),
            'incidente_stradale',
            'normal',
            'high'
        );

        add_meta_box(
            'incidente_nominativi',
            __('Nominativo Morti e Feriti', 'incidenti-stradali'),
            array($this, 'render_nominativi_meta_box'),
            'incidente_stradale',
            'normal',
            'low'   // <-- PRIORITÀ BASSA per visualizzarlo in fondo
        );
    }

    public function render_nominativi_meta_box($post) {
        ?>
        <div class="incidenti-nominativi-container">
            <p class="description"><?php _e('Nome e Cognome dei morti coinvolti nell\'incidente', 'incidenti-stradali'); ?></p>
            
            <div id="nominativi-morti-container">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div id="morto-<?php echo $i; ?>" class="nominativo-morto" style="display: none; margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <h5><?php printf(__('Morto %d', 'incidenti-stradali'), $i); ?></h5>
                        <table class="form-table">
                            <tr>
                                <th><label for="morto_<?php echo $i; ?>_nome"><?php _e('Nome', 'incidenti-stradali'); ?></label></th>
                                <td>
                                    <input type="text" id="morto_<?php echo $i; ?>_nome" name="morto_<?php echo $i; ?>_nome" 
                                        value="<?php echo esc_attr(get_post_meta($post->ID, 'morto_' . $i . '_nome', true)); ?>" 
                                        class="regular-text" maxlength="30">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="morto_<?php echo $i; ?>_cognome"><?php _e('Cognome', 'incidenti-stradali'); ?></label></th>
                                <td>
                                    <input type="text" id="morto_<?php echo $i; ?>_cognome" name="morto_<?php echo $i; ?>_cognome" 
                                        value="<?php echo esc_attr(get_post_meta($post->ID, 'morto_' . $i . '_cognome', true)); ?>" 
                                        class="regular-text" maxlength="30">
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endfor; ?>
            </div>

            <p class="description"><?php _e('Nome e Cognome dei feriti coinvolti nell\'incidente', 'incidenti-stradali'); ?></p>
            
            <div id="nominativi-feriti-container">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div id="ferito-<?php echo $i; ?>" class="nominativo-ferito" style="display: none; margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <h5><?php printf(__('Ferito %d', 'incidenti-stradali'), $i); ?></h5>
                        <table class="form-table">
                            <tr>
                                <th><label for="ferito_<?php echo $i; ?>_nome"><?php _e('Nome', 'incidenti-stradali'); ?></label></th>
                                <td>
                                    <input type="text" id="ferito_<?php echo $i; ?>_nome" name="ferito_<?php echo $i; ?>_nome" 
                                        value="<?php echo esc_attr(get_post_meta($post->ID, 'ferito_' . $i . '_nome', true)); ?>" 
                                        class="regular-text" maxlength="30">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ferito_<?php echo $i; ?>_cognome"><?php _e('Cognome', 'incidenti-stradali'); ?></label></th>
                                <td>
                                    <input type="text" id="ferito_<?php echo $i; ?>_cognome" name="ferito_<?php echo $i; ?>_cognome" 
                                        value="<?php echo esc_attr(get_post_meta($post->ID, 'ferito_' . $i . '_cognome', true)); ?>" 
                                        class="regular-text" maxlength="30">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ferito_<?php echo $i; ?>_istituto"><?php _e('Istituto di cura', 'incidenti-stradali'); ?></label></th>
                                <td>
                                    <input type="text" id="ferito_<?php echo $i; ?>_istituto" name="ferito_<?php echo $i; ?>_istituto" 
                                        value="<?php echo esc_attr(get_post_meta($post->ID, 'ferito_' . $i . '_istituto', true)); ?>" 
                                        class="regular-text" maxlength="30">
                                    <p class="description"><?php _e('Ospedale o struttura sanitaria dove è stato ricoverato', 'incidenti-stradali'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="incidenti-disclaimer-container" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <h4 style="color: #856404; margin-top: 0;"><?php _e('SEGRETO STATISTICO, OBBLIGO DI RISPOSTA, TUTELA DELLA RISERVATEZZA E DIRITTI DEGLI INTERESSATI', 'incidenti-stradali'); ?></h4>
            <div style="font-size: 12px; color: #856404; line-height: 1.4;">
                <p><strong><?php _e('Decreto legislativo 6 settembre 1989, n. 322', 'incidenti-stradali'); ?></strong> - <?php _e('Norme sul Sistema statistico nazionale e sulla riorganizzazione dell\'Istituto nazionale di statistica', 'incidenti-stradali'); ?></p>
                <p><strong><?php _e('Decreto legislativo 30 giugno 2003, n. 196', 'incidenti-stradali'); ?></strong> - <?php _e('Codice in materia di protezione dei dati personali', 'incidenti-stradali'); ?></p>
                <p><strong><?php _e('Regolamento UE 2016/679', 'incidenti-stradali'); ?></strong> - <?php _e('Regolamento generale sulla protezione dei dati', 'incidenti-stradali'); ?></p>
                
                <p><?php _e('I dati raccolti sono tutelati dal segreto statistico e sottoposti alla normativa in materia di protezione dei dati personali e potranno essere utilizzati, anche per successivi trattamenti, esclusivamente per fini statistici dai soggetti del Sistema statistico nazionale ed essere comunicati per finalità di ricerca scientifica alle condizioni e secondo le modalità previste dall\'art 7 del Codice di deontologia e di buona condotta per i trattamenti di dati personali a scopi statistici.', 'incidenti-stradali'); ?></p>
                
                <p><?php _e('Titolare del trattamento dei dati è l\'ISTAT – Istituto nazionale di statistica - Via Cesare Balbo, 16 – 00184 Roma. Responsabili del trattamento dei dati sono, per le fasi di rispettiva competenza, il Direttore centrale per le statistiche e le indagini sulle istituzioni sociali dell\'Istat e il preposto all\'Ufficio di statistica della Regione o Provincia autonoma.', 'incidenti-stradali'); ?></p>
                
                <p><?php _e('L\'inserimento dei nominativi è OBBLIGATORIO ai sensi dell\'art. 7 del d.lgs. n. 322/1989 e fatto obbligo alle amministrazioni, enti ed organismi pubblici, di fornire tutti i dati e le notizie richieste nel modello di rilevazione.', 'incidenti-stradali'); ?></p>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function updateNominativiVisibility() {
                // Conta morti dai conducenti e pedoni
                var totalMorti = 0;
                var totalFeriti = 0;
                
                // Conta morti e feriti da conducenti
                for (var i = 1; i <= 3; i++) {
                    var esito = $('#conducente_' + i + '_esito').val();
                    if (esito == '3' || esito == '4') {
                        totalMorti++;
                    } else if (esito == '2') {
                        totalFeriti++;
                    }
                }
                
                // Conta morti e feriti da pedoni
                var numPedoni = parseInt($('#numero_pedoni_coinvolti').val()) || 0;
                for (var i = 1; i <= numPedoni; i++) {
                    var esito = $('#pedone_' + i + '_esito').val();
                    if (esito == '3' || esito == '4') {
                        totalMorti++;
                    } else if (esito == '2') {
                        totalFeriti++;
                    }
                }
                
                console.log('Morti trovati:', totalMorti, 'Feriti trovati:', totalFeriti); // Debug
                
                // Mostra/nascondi sezioni nominativi morti
                for (var i = 1; i <= 4; i++) {
                    if (i <= totalMorti) {
                        $('#morto-' + i).show();
                    } else {
                        $('#morto-' + i).hide();
                    }
                }
                
                // Mostra/nascondi sezioni nominativi feriti
                for (var i = 1; i <= 8; i++) {
                    if (i <= totalFeriti) {
                        $('#ferito-' + i).show();
                    } else {
                        $('#ferito-' + i).hide();
                    }
                }
                
                // Mostra almeno un campo se ci sono morti/feriti (per test)
                if (totalMorti > 0) {
                    $('#morto-1').show();
                }
                if (totalFeriti > 0) {
                    $('#ferito-1').show();
                }
            }
            
            // Aggiorna visibilità quando cambiano gli esiti
            $(document).on('change', 'select[id*="_esito"]', updateNominativiVisibility);
            $(document).on('change', '#numero_pedoni_coinvolti', updateNominativiVisibility);
            
            // Aggiorna visibilità al caricamento della pagina
            setTimeout(function() {
                updateNominativiVisibility();
            }, 1000); // Ritardo per permettere il caricamento completo
            
            // Forza la visualizzazione di almeno un campo per test
            $('#morto-1, #ferito-1').show();
        });
        </script>
        <?php
    }

    public function render_circostanze_meta_box($post) {
        // Get saved values
        $circostanza_presunta_1 = get_post_meta($post->ID, 'circostanza_presunta_1', true);
        $circostanza_presunta_2 = get_post_meta($post->ID, 'circostanza_presunta_2', true);
        $circostanza_presunta_3 = get_post_meta($post->ID, 'circostanza_presunta_3', true);
        
        $circostanza_veicolo_a = get_post_meta($post->ID, 'circostanza_veicolo_a', true);
        $circostanza_veicolo_b = get_post_meta($post->ID, 'circostanza_veicolo_b', true);
        $circostanza_veicolo_c = get_post_meta($post->ID, 'circostanza_veicolo_c', true);
        
        ?>
        <div class="incidenti-circostanze-container">
            <h4><?php _e('Circostanze Presunte Generali', 'incidenti-stradali'); ?></h4>
            <p class="description"><?php _e('Seleziona fino a 3 circostanze presunte dell\'incidente.', 'incidenti-stradali'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><label for="circostanza_presunta_1"><?php _e('Circostanza Presunta 1', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_presunta_1" name="circostanza_presunta_1" class="circostanza-select">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_options($circostanza_presunta_1); ?>
                        </select>
                    </td>
                </tr>
                <tr id="circostanza_presunta_2_row" style="display: none;">
                    <th><label for="circostanza_presunta_2"><?php _e('Circostanza Presunta 2', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_presunta_2" name="circostanza_presunta_2" class="circostanza-select">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_options($circostanza_presunta_2); ?>
                        </select>
                    </td>
                </tr>
                <tr id="circostanza_presunta_3_row" style="display: none;">
                    <th><label for="circostanza_presunta_3"><?php _e('Circostanza Presunta 3', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_presunta_3" name="circostanza_presunta_3" class="circostanza-select">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_options($circostanza_presunta_3); ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h4><?php _e('Circostanze per Veicolo', 'incidenti-stradali'); ?></h4>
            <p class="description"><?php _e('Specifica le circostanze per ogni veicolo coinvolto.', 'incidenti-stradali'); ?></p>
            
            <table class="form-table">
                <tr id="circostanza_veicolo_a_row">
                    <th><label for="circostanza_veicolo_a"><?php _e('Circostanze Veicolo A', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_a" name="circostanza_veicolo_a">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_veicolo_options($circostanza_veicolo_a); ?>
                        </select>
                    </td>
                </tr>
                <tr id="circostanza_veicolo_b_row" style="display: none;">
                    <th><label for="circostanza_veicolo_b"><?php _e('Circostanze Veicolo B', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_b" name="circostanza_veicolo_b">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_veicolo_options($circostanza_veicolo_b); ?>
                        </select>
                    </td>
                </tr>
                <tr id="circostanza_veicolo_c_row" style="display: none;">
                    <th><label for="circostanza_veicolo_c"><?php _e('Circostanze Veicolo C', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_c" name="circostanza_veicolo_c">
                            <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                            <?php echo $this->get_circostanze_veicolo_options($circostanza_veicolo_c); ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Gestisci la visibilità delle circostanze aggiuntive
                function updateCircostanzeVisibility() {
                    // Mostra seconda circostanza se la prima è selezionata
                    if ($('#circostanza_presunta_1').val()) {
                        $('#circostanza_presunta_2_row').show();
                    } else {
                        $('#circostanza_presunta_2_row').hide();
                        $('#circostanza_presunta_2').val('');
                    }
                    
                    // Mostra terza circostanza se la seconda è selezionata
                    if ($('#circostanza_presunta_2').val()) {
                        $('#circostanza_presunta_3_row').show();
                    } else {
                        $('#circostanza_presunta_3_row').hide();
                        $('#circostanza_presunta_3').val('');
                    }
                }
                
                // Gestisci la visibilità delle circostanze per veicolo
                function updateVeicoliCircostanzeVisibility() {
                    var numVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
                    
                    // Mostra sempre veicolo A
                    $('#circostanza_veicolo_a_row').show();
                    
                    // Mostra/nascondi veicoli B e C
                    if (numVeicoli >= 2) {
                        $('#circostanza_veicolo_b_row').show();
                    } else {
                        $('#circostanza_veicolo_b_row').hide();
                        $('#circostanza_veicolo_b').val('');
                    }
                    
                    if (numVeicoli >= 3) {
                        $('#circostanza_veicolo_c_row').show();
                    } else {
                        $('#circostanza_veicolo_c_row').hide();
                        $('#circostanza_veicolo_c').val('');
                    }
                }
                
                // Event handlers
                $('.circostanza-select').on('change', updateCircostanzeVisibility);
                $('#numero_veicoli_coinvolti').on('change', updateVeicoliCircostanzeVisibility);
                
                // Inizializza visibilità
                updateCircostanzeVisibility();
                updateVeicoliCircostanzeVisibility();
            });
            </script>
        </div>
        <?php
    }

    private function get_circostanze_options($selected = '') {
        $options = array(
            // Inconvenienti di circolazione
            'inconvenienti' => array(
                'label' => __('Inconvenienti di Circolazione', 'incidenti-stradali'),
                'options' => array(
                    '80' => __('Rottura o insufficienza dei freni', 'incidenti-stradali'),
                    '81' => __('Rottura o guasto allo sterzo', 'incidenti-stradali'),
                    '82' => __('Scoppio o eccessiva usura dei pneumatici', 'incidenti-stradali'),
                    '83' => __('Mancanza o insufficienza dei fari o delle luci di posizione', 'incidenti-stradali'),
                    '84' => __('Mancanza o insufficienza dei lampeggiatori', 'incidenti-stradali'),
                    '85' => __('Rottura degli organi di agganciamento dei rimorchi', 'incidenti-stradali'),
                    '86' => __('Deficienza attrezzature per trasporto merci pericolose', 'incidenti-stradali'),
                    '87' => __('Mancanza o insufficienza adattamenti per disabili', 'incidenti-stradali'),
                    '88' => __('Distacco di ruota', 'incidenti-stradali'),
                    '89' => __('Mancanza o insufficienza dei dispositivi visivi dei velocipedi', 'incidenti-stradali')
                )
            ),
            // Stato psicofisico
            'psicofisico' => array(
                'label' => __('Stato Psicofisico', 'incidenti-stradali'),
                'options' => array(
                    '90' => __('Anormale per ebbrezza da alcool', 'incidenti-stradali'),
                    '91' => __('Anormale per condizioni morbose in atto', 'incidenti-stradali'),
                    '92' => __('Anormale per improvviso malore', 'incidenti-stradali'),
                    '93' => __('Anormale per sonno', 'incidenti-stradali'),
                    '94' => __('Anormale per ingestione di sostanze stupefacenti', 'incidenti-stradali'),
                    '95' => __('Mancato uso di lenti correttive o apparecchi di protesi', 'incidenti-stradali'),
                    '96' => __('Abbagliato', 'incidenti-stradali'),
                    '97' => __('Per aver superato i periodi di guida prescritti', 'incidenti-stradali')
                )
            )
        );
        
        $html = '';
        foreach ($options as $group) {
            $html .= '<optgroup label="' . esc_attr($group['label']) . '">';
            foreach ($group['options'] as $value => $label) {
                $html .= '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
            }
            $html .= '</optgroup>';
        }
        
        return $html;
    }

    private function get_circostanze_veicolo_options($selected = '') {
        $options = array(
            // All'intersezione
            'intersezione' => array(
                'label' => __('All\'intersezione', 'incidenti-stradali'),
                'options' => array(
                    '01' => __('Procedeva regolarmente senza svoltare', 'incidenti-stradali'),
                    '02' => __('Procedeva con guida distratta e andamento indeciso', 'incidenti-stradali'),
                    '03' => __('Procedeva senza mantenere la distanza di sicurezza', 'incidenti-stradali'),
                    '04' => __('Procedeva senza dare la precedenza al veicolo proveniente da destra', 'incidenti-stradali'),
                    '05' => __('Procedeva senza rispettare lo stop', 'incidenti-stradali'),
                    '06' => __('Procedeva senza rispettare il segnale di dare precedenza', 'incidenti-stradali'),
                    '07' => __('Procedeva contromano', 'incidenti-stradali'),
                    '08' => __('Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente', 'incidenti-stradali'),
                    '10' => __('Procedeva senza rispettare i segnali di divieto di transito', 'incidenti-stradali'),
                    '11' => __('Procedeva con eccesso di velocità', 'incidenti-stradali'),
                    '12' => __('Procedeva senza rispettare i limiti di velocità', 'incidenti-stradali'),
                    '13' => __('Procedeva con le luci abbaglianti incrociando altri veicoli', 'incidenti-stradali'),
                    '14' => __('Svoltava a destra regolarmente', 'incidenti-stradali'),
                    '15' => __('Svoltava a destra irregolarmente', 'incidenti-stradali'),
                    '16' => __('Svoltava a sinistra regolarmente', 'incidenti-stradali'),
                    '17' => __('Svoltava a sinistra irregolarmente', 'incidenti-stradali'),
                    '18' => __('Sorpassava (all\'incrocio)', 'incidenti-stradali')
                )
            ),
            // Non all'intersezione
            'non_intersezione' => array(
                'label' => __('Non all\'intersezione', 'incidenti-stradali'),
                'options' => array(
                    '20' => __('Procedeva regolarmente', 'incidenti-stradali'),
                    '21' => __('Procedeva con guida distratta e andamento indeciso', 'incidenti-stradali'),
                    '22' => __('Procedeva senza mantenere la distanza di sicurezza', 'incidenti-stradali'),
                    '23' => __('Procedeva con eccesso di velocità', 'incidenti-stradali'),
                    '24' => __('Procedeva senza rispettare i limiti di velocità', 'incidenti-stradali'),
                    '25' => __('Procedeva non in prossimità del margine destro della carreggiata', 'incidenti-stradali'),
                    '26' => __('Procedeva contromano', 'incidenti-stradali'),
                    '27' => __('Procedeva senza rispettare i segnali di divieto di transito', 'incidenti-stradali'),
                    '28' => __('Procedeva con le luci abbaglianti incrociando altri veicoli', 'incidenti-stradali'),
                    '29' => __('Sorpassava regolarmente', 'incidenti-stradali'),
                    '30' => __('Sorpassava irregolarmente a destra', 'incidenti-stradali'),
                    '31' => __('Sorpassava in curva, su dosso o insufficiente visibilità', 'incidenti-stradali'),
                    '32' => __('Sorpassava un veicolo che ne stava sorpassando un altro', 'incidenti-stradali'),
                    '33' => __('Sorpassava senza osservare il segnale di divieto', 'incidenti-stradali'),
                    '34' => __('Manovrava in retrocessione o conversione', 'incidenti-stradali'),
                    '35' => __('Manovrava per immettersi nel flusso della circolazione', 'incidenti-stradali'),
                    '36' => __('Manovrava per voltare a sinistra', 'incidenti-stradali'),
                    '37' => __('Manovrava regolarmente per fermarsi o sostare', 'incidenti-stradali'),
                    '38' => __('Manovrava irregolarmente per fermarsi o sostare', 'incidenti-stradali'),
                    '39' => __('Si affiancava ad altri veicoli a due ruote irregolarmente', 'incidenti-stradali')
                )
            ),
            // Veicolo coinvolto
            'veicolo_coinvolto' => array(
                'label' => __('Veicolo coinvolto', 'incidenti-stradali'),
                'options' => array(
                    '40' => __('Procedeva regolarmente', 'incidenti-stradali'),
                    '41' => __('Procedeva con eccesso di velocità', 'incidenti-stradali'),
                    '42' => __('Procedeva senza rispettare i limiti di velocità', 'incidenti-stradali'),
                    '43' => __('Procedeva contromano', 'incidenti-stradali'),
                    '44' => __('Sorpassava veicolo in marcia', 'incidenti-stradali'),
                    '45' => __('Manovrava', 'incidenti-stradali'),
                    '46' => __('Non rispettava le segnalazioni semaforiche o dell\'agente', 'incidenti-stradali'),
                    '47' => __('Usciva senza precauzioni da passo carrabile', 'incidenti-stradali'),
                    '48' => __('Fuoriusciva dalla carreggiata', 'incidenti-stradali'),
                    '49' => __('Non dava la precedenza al pedone sugli appositi attraversamenti', 'incidenti-stradali'),
                    '50' => __('Sorpassava un veicolo fermatosi per l\'attraversamento dei pedoni', 'incidenti-stradali'),
                    '51' => __('Urtava con il carico il pedone', 'incidenti-stradali'),
                    '52' => __('Superava irregolarmente un tram fermo per salita/discesa', 'incidenti-stradali')
                )
            ),
            // Veicolo fermo
            'veicolo_fermo' => array(
                'label' => __('Veicolo fermo/ostacolo', 'incidenti-stradali'),
                'options' => array(
                    '60' => __('Ostacolo accidentale', 'incidenti-stradali'),
                    '61' => __('Veicolo fermo in posizione regolare', 'incidenti-stradali'),
                    '62' => __('Veicolo fermo in posizione irregolare', 'incidenti-stradali'),
                    '63' => __('Veicolo fermo senza il prescritto segnale', 'incidenti-stradali'),
                    '64' => __('Veicolo fermo regolarmente segnalato', 'incidenti-stradali'),
                    '65' => __('Ostacolo fisso nella carreggiata', 'incidenti-stradali'),
                    '66' => __('Treno in passaggio a livello', 'incidenti-stradali'),
                    '67' => __('Animale domestico o d\'affezione', 'incidenti-stradali'),
                    '68' => __('Animale selvatico', 'incidenti-stradali'),
                    '69' => __('Buca', 'incidenti-stradali')
                )
            ),
            // Veicolo senza urto
            'veicolo_senza_urto' => array(
                'label' => __('Veicolo senza urto', 'incidenti-stradali'),
                'options' => array(
                    '70' => __('Sbandamento con fuoriuscita per evitare l\'urto', 'incidenti-stradali'),
                    '71' => __('Sbandamento per guida distratta e andamento indeciso', 'incidenti-stradali'),
                    '72' => __('Sbandamento con fuoriuscita per eccesso di velocità', 'incidenti-stradali'),
                    '73' => __('Frenata improvvisa con conseguenza ai trasportati', 'incidenti-stradali'),
                    '74' => __('Caduta di persona da veicolo per apertura di portiera', 'incidenti-stradali'),
                    '75' => __('Caduta di persona da veicolo per discesa da veicolo in moto', 'incidenti-stradali'),
                    '76' => __('Caduta di persona da veicolo per essersi aggrappata', 'incidenti-stradali')
                )
            )
        );
        
        $html = '';
        foreach ($options as $group) {
            $html .= '<optgroup label="' . esc_attr($group['label']) . '">';
            foreach ($group['options'] as $value => $label) {
                $html .= '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
            }
            $html .= '</optgroup>';
        }
        
        return $html;
    }

    public function render_dati_aggiuntivi_meta_box($post) {
        ?>
        <table class="form-table">
            <tr>
                <th><label for="altri_morti_maschi"><?php _e('Altri morti maschi', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="altri_morti_maschi" name="altri_morti_maschi" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'altri_morti_maschi', true)); ?>" 
                           min="0" max="99">
                    <p class="description"><?php _e('Morti oltre ai 3 conducenti e 4 pedoni già inseriti', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="altri_morti_femmine"><?php _e('Altri morti femmine', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="altri_morti_femmine" name="altri_morti_femmine" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'altri_morti_femmine', true)); ?>" 
                           min="0" max="99">
                </td>
            </tr>
            
            <tr>
                <th><label for="altri_feriti_maschi"><?php _e('Altri feriti maschi', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="altri_feriti_maschi" name="altri_feriti_maschi" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'altri_feriti_maschi', true)); ?>" 
                           min="0" max="99">
                    <p class="description"><?php _e('Feriti oltre ai 3 conducenti e 4 pedoni già inseriti', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="altri_feriti_femmine"><?php _e('Altri feriti femmine', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="altri_feriti_femmine" name="altri_feriti_femmine" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'altri_feriti_femmine', true)); ?>" 
                           min="0" max="99">
                </td>
            </tr>
            
            <tr>
                <th><label for="numero_altri_veicoli"><?php _e('Numero altri veicoli coinvolti', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="numero_altri_veicoli" name="numero_altri_veicoli" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'numero_altri_veicoli', true)); ?>" 
                           min="0" max="99">
                    <p class="description"><?php _e('Veicoli coinvolti oltre ai 3 già inseriti', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="localizzazione_extra_ab"><?php _e('Localizzazione extraurbana', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="localizzazione_extra_ab" name="localizzazione_extra_ab">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected(get_post_meta($post->ID, 'localizzazione_extra_ab', true), '1'); ?>><?php _e('Su strada statale fuori dall\'autostrada', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected(get_post_meta($post->ID, 'localizzazione_extra_ab', true), '2'); ?>><?php _e('Su autostrada', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected(get_post_meta($post->ID, 'localizzazione_extra_ab', true), '3'); ?>><?php _e('Su raccordo autostradale', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="chilometrica_strada"><?php _e('Chilometrica della strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="chilometrica_strada" name="chilometrica_strada" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, 'chilometrica_strada', true)); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Specificare km e ettometro (esempio: 125+3)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    
    public function render_dati_generali_meta_box($post) {
        wp_nonce_field('incidente_meta_box', 'incidente_meta_box_nonce');
        
        $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
        $ora_incidente = get_post_meta($post->ID, 'ora_incidente', true);
        $minuti_incidente = get_post_meta($post->ID, 'minuti_incidente', true);
        $provincia = get_post_meta($post->ID, 'provincia_incidente', true);
        $comune = get_post_meta($post->ID, 'comune_incidente', true);
        $localita = get_post_meta($post->ID, 'localita_incidente', true);
        $organo_rilevazione = get_post_meta($post->ID, 'organo_rilevazione', true);
        $organo_coordinatore = get_post_meta($post->ID, 'organo_coordinatore', true);
        
        // NUOVI CAMPI
        $ente_rilevatore = get_post_meta($post->ID, 'ente_rilevatore', true);
        $nome_rilevatore = get_post_meta($post->ID, 'nome_rilevatore', true);

        // Carica i comuni di Lecce
        $comuni_lecce = $this->get_comuni_lecce();
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="data_incidente"><?php _e('Data dell\'Incidente', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <input type="date" id="data_incidente" name="data_incidente" value="<?php echo esc_attr($data_incidente); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="ora_incidente"><?php _e('Ora dell\'Incidente', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <select id="ora_incidente" name="ora_incidente" required>
                        <option value=""><?php _e('Seleziona ora', 'incidenti-stradali'); ?></option>
                        <?php for($i = 0; $i <= 24; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>" <?php selected($ora_incidente, sprintf('%02d', $i)); ?>>
                                <?php echo sprintf('%02d', $i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    :
                    <select id="minuti_incidente" name="minuti_incidente">
                        <option value="00" <?php selected($minuti_incidente, '00'); ?>>00</option>
                        <option value="15" <?php selected($minuti_incidente, '15'); ?>>15</option>
                        <option value="30" <?php selected($minuti_incidente, '30'); ?>>30</option>
                        <option value="45" <?php selected($minuti_incidente, '45'); ?>>45</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="provincia_incidente"><?php _e('Provincia', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <input type="hidden" id="provincia_incidente" name="provincia_incidente" value="075">
                    <input type="text" value="Lecce (075)" disabled class="regular-text">
                    <p class="description"><?php _e('Provincia di Lecce - codice ISTAT 075', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="comune_incidente"><?php _e('Comune', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <select id="comune_incidente" name="comune_incidente" required class="regular-text">
                        <option value=""><?php _e('Seleziona comune', 'incidenti-stradali'); ?></option>
                        <?php foreach($comuni_lecce as $codice => $nome): ?>
                            <option value="<?php echo esc_attr($codice); ?>" <?php selected($comune, $codice); ?>>
                                <?php echo esc_html($nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Seleziona il comune dove è avvenuto l\'incidente', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="localita_incidente"><?php _e('Località', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="localita_incidente" name="localita_incidente" value="<?php echo esc_attr($localita); ?>" class="regular-text">
                    <p class="description"><?php _e('Frazione o località specifica (opzionale)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            
            <!-- SEZIONE ORGANO DI RILEVAZIONE -->
            <tr>
                <th colspan="2">
                    <h3 style="margin: 20px 0 10px 0; padding: 10px 0; border-bottom: 1px solid #ccc;">
                        <?php _e('ORGANO DI RILEVAZIONE', 'incidenti-stradali'); ?>
                    </h3>
                </th>
            </tr>
            <tr>
                <th><label for="ente_rilevatore"><?php _e('Ente', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="ente_rilevatore" name="ente_rilevatore" class="regular-text">
                        <option value=""><?php _e('Seleziona ente', 'incidenti-stradali'); ?></option>
                        
                        <optgroup label="<?php _e('Polizia Municipale', 'incidenti-stradali'); ?>">
                            <?php 
                            $polizie_municipali = $this->get_polizie_municipali();
                            foreach($polizie_municipali as $polizia): ?>
                                <option value="<?php echo esc_attr($polizia); ?>" <?php selected($ente_rilevatore, $polizia); ?>>
                                    <?php echo esc_html($polizia); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        
                        <optgroup label="<?php _e('Altri Enti', 'incidenti-stradali'); ?>">
                            <option value="Carabiniere" <?php selected($ente_rilevatore, 'Carabiniere'); ?>><?php _e('Carabiniere', 'incidenti-stradali'); ?></option>
                            <option value="Agente di Polizia Stradale" <?php selected($ente_rilevatore, 'Agente di Polizia Stradale'); ?>><?php _e('Agente di Polizia Stradale', 'incidenti-stradali'); ?></option>
                            <option value="Polizia Provinciale" <?php selected($ente_rilevatore, 'Polizia Provinciale'); ?>><?php _e('Polizia Provinciale', 'incidenti-stradali'); ?></option>
                        </optgroup>
                    </select>
                    <p class="description"><?php _e('Seleziona l\'ente che ha rilevato l\'incidente', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="nome_rilevatore"><?php _e('Rilevatore', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="nome_rilevatore" name="nome_rilevatore" value="<?php echo esc_attr($nome_rilevatore); ?>" class="regular-text">
                    <p class="description"><?php _e('Nome e cognome del rilevatore', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            
            <!-- CAMPI ESISTENTI (mantieni solo per compatibilità ISTAT) -->
            <tr style="display: none;">
                <th><label for="organo_rilevazione"><?php _e('Organo di Rilevazione (ISTAT)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="organo_rilevazione" name="organo_rilevazione">
                        <option value=""><?php _e('Seleziona organo', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($organo_rilevazione, '1'); ?>><?php _e('Agente di Polizia Stradale', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($organo_rilevazione, '2'); ?>><?php _e('Carabiniere', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($organo_rilevazione, '3'); ?>><?php _e('Agente di Pubblica Sicurezza', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($organo_rilevazione, '4'); ?>><?php _e('Agente di Polizia Municipale o Locale', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($organo_rilevazione, '5'); ?>><?php _e('Altri', 'incidenti-stradali'); ?></option>
                        <option value="6" <?php selected($organo_rilevazione, '6'); ?>><?php _e('Agente di Polizia Provinciale', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="organo_coordinatore"><?php _e('Organo Coordinatore (ISTAT)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="organo_coordinatore" name="organo_coordinatore">
                        <option value=""><?php _e('Seleziona organo coordinatore', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($organo_coordinatore, '1'); ?>><?php _e('Sezione Polizia Stradale', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($organo_coordinatore, '2'); ?>><?php _e('Gruppo Carabinieri', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($organo_coordinatore, '3'); ?>><?php _e('Comune con oltre 250.000 abitanti', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($organo_coordinatore, '4'); ?>><?php _e('Altro capoluogo di Provincia', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Sincronizza automaticamente i campi ISTAT con i nuovi campi
                $('#ente_rilevatore').on('change', function() {
                    var ente = $(this).val();
                    var organoValue = '';
                    
                    if (ente.includes('POLIZIA MUNICIPALE')) {
                        organoValue = '4'; // Agente di Polizia Municipale o Locale
                    } else if (ente === 'Carabiniere') {
                        organoValue = '2'; // Carabiniere
                    } else if (ente === 'Agente di Polizia Stradale') {
                        organoValue = '1'; // Agente di Polizia Stradale
                    } else if (ente === 'Polizia Provinciale') {
                        organoValue = '6'; // Agente di Polizia Provinciale
                    } else {
                        organoValue = '5'; // Altri
                    }
                    
                    $('#organo_rilevazione').val(organoValue);
                });
                
                // Imposta il valore iniziale se già selezionato
                $('#ente_rilevatore').trigger('change');
            });
            </script>
        <?php
    }

    /**
    * Lista delle Polizie Municipali della provincia di Lecce
     */
    private function get_polizie_municipali() {
        return array(
            'POLIZIA MUNICIPALE DI ACQUARICA DEL CAPO',
            'POLIZIA MUNICIPALE DI ALESSANO',
            'POLIZIA MUNICIPALE DI ALEZIO',
            'POLIZIA MUNICIPALE DI ALLISTE',
            'POLIZIA MUNICIPALE DI ANDRANO',
            'POLIZIA MUNICIPALE DI ARADEO',
            'POLIZIA MUNICIPALE DI ARNESANO',
            'POLIZIA MUNICIPALE DI BAGNOLO DEL SALENTO',
            'POLIZIA MUNICIPALE DI BOTRUGNO',
            'POLIZIA MUNICIPALE DI CALIMERA',
            'POLIZIA MUNICIPALE DI CAMPI SALENTINA',
            'POLIZIA MUNICIPALE DI CANNOLE',
            'POLIZIA MUNICIPALE DI CAPRARICA DI LECCE',
            'POLIZIA MUNICIPALE DI CARMIANO',
            'POLIZIA MUNICIPALE DI CARPIGNANO SALENTINO',
            'POLIZIA MUNICIPALE DI CASARANO',
            'POLIZIA MUNICIPALE DI CASTRIGNANO DEI GRECI',
            'POLIZIA MUNICIPALE DI CASTRIGNANO DEL CAPO',
            'POLIZIA MUNICIPALE DI CASTRI',
            'POLIZIA MUNICIPALE DI CASTRO',
            'POLIZIA MUNICIPALE DI CAVALLINO',
            'POLIZIA MUNICIPALE DI COLLEPASSO',
            'POLIZIA MUNICIPALE DI COPERTINO',
            'POLIZIA MUNICIPALE DI CORIGLIANO D\'OTRANTO',
            'POLIZIA MUNICIPALE DI CORSANO',
            'POLIZIA MUNICIPALE DI CURSI',
            'POLIZIA MUNICIPALE DI CUTROFIANO',
            'POLIZIA MUNICIPALE DI DISO',
            'POLIZIA MUNICIPALE DI GAGLIANO DEL CAPO',
            'POLIZIA MUNICIPALE DI GALATINA',
            'POLIZIA MUNICIPALE DI GALATONE',
            'POLIZIA MUNICIPALE DI GALLIPOLI',
            'POLIZIA MUNICIPALE DI GIUGGIANELLO',
            'POLIZIA MUNICIPALE DI GIURDIGNANO',
            'POLIZIA MUNICIPALE DI GUAGNANO',
            'POLIZIA MUNICIPALE DI LECCE',
            'POLIZIA MUNICIPALE DI LEQUILE',
            'POLIZIA MUNICIPALE DI LEVERANO',
            'POLIZIA MUNICIPALE DI LIZZANELLO',
            'POLIZIA MUNICIPALE DI MAGLIE',
            'POLIZIA MUNICIPALE DI MARTANO',
            'POLIZIA MUNICIPALE DI MARTIGNANO',
            'POLIZIA MUNICIPALE DI MATINO',
            'POLIZIA MUNICIPALE DI MELENDUGNO',
            'POLIZIA MUNICIPALE DI MELISSANO',
            'POLIZIA MUNICIPALE DI MELPIGNANO',
            'POLIZIA MUNICIPALE DI MIGGIANO',
            'POLIZIA MUNICIPALE DI MINERVINO DI LECCE',
            'POLIZIA MUNICIPALE DI MONTERONI DI LECCE',
            'POLIZIA MUNICIPALE DI MONTESANO SALENTINO',
            'POLIZIA MUNICIPALE DI MORCIANO DI LEUCA',
            'POLIZIA MUNICIPALE DI MURO',
            'POLIZIA MUNICIPALE DI NARDO\'',
            'POLIZIA MUNICIPALE DI NEVIANO',
            'POLIZIA MUNICIPALE DI NOCIGLIA',
            'POLIZIA MUNICIPALE DI NOVOLI',
            'POLIZIA MUNICIPALE DI ORTELLE',
            'POLIZIA MUNICIPALE DI OTRANTO',
            'POLIZIA MUNICIPALE DI PALMARIGGI',
            'POLIZIA MUNICIPALE DI PARABITA',
            'POLIZIA MUNICIPALE DI PATU\'',
            'POLIZIA MUNICIPALE DI POGGIARDO',
            'POLIZIA MUNICIPALE DI PORTO CESAREO',
            'POLIZIA MUNICIPALE DI PRESICCE',
            'POLIZIA MUNICIPALE DI PRESICCE-ACQUARICA',
            'POLIZIA MUNICIPALE DI RACALE',
            'POLIZIA MUNICIPALE DI RUFFANO',
            'POLIZIA MUNICIPALE DI SALICE SALENTINO',
            'POLIZIA MUNICIPALE DI SALVE',
            'POLIZIA MUNICIPALE DI SAN CASSIANO',
            'POLIZIA MUNICIPALE DI SAN CESARIO DI LECCE',
            'POLIZIA MUNICIPALE DI SAN DONATO DI LECCE',
            'POLIZIA MUNICIPALE DI SAN PIETRO IN LAMA',
            'POLIZIA MUNICIPALE DI SANARICA',
            'POLIZIA MUNICIPALE DI SANNICOLA',
            'POLIZIA MUNICIPALE DI SANTA CESAREA TERME',
            'POLIZIA MUNICIPALE DI SCORRANO',
            'POLIZIA MUNICIPALE DI SECLI\'',
            'POLIZIA MUNICIPALE DI SOGLIANO CAVOUR',
            'POLIZIA MUNICIPALE DI SOLETO',
            'POLIZIA MUNICIPALE DI SPECCHIA',
            'POLIZIA MUNICIPALE DI SPONGANO',
            'POLIZIA MUNICIPALE DI SQUINZANO',
            'POLIZIA MUNICIPALE DI STERNATIA',
            'POLIZIA MUNICIPALE DI SUPERSANO',
            'POLIZIA MUNICIPALE DI SURANO',
            'POLIZIA MUNICIPALE DI SURBO',
            'POLIZIA MUNICIPALE DI TAURISANO',
            'POLIZIA MUNICIPALE DI TAVIANO',
            'POLIZIA MUNICIPALE DI TIGGIANO',
            'POLIZIA MUNICIPALE DI TREPUZZI',
            'POLIZIA MUNICIPALE DI TRICASE',
            'POLIZIA MUNICIPALE DI TUGLIE',
            'POLIZIA MUNICIPALE DI UGENTO',
            'POLIZIA MUNICIPALE DI UGGIANO LA CHIESA',
            'POLIZIA MUNICIPALE DI VEGLIE',
            'POLIZIA MUNICIPALE DI VERNOLE',
            'POLIZIA MUNICIPALE DI ZOLLINO'
        );
    }

    /**
     * Metodo per ottenere i comuni di Lecce
     */
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
    
    public function render_localizzazione_meta_box($post) {
        $abitato = get_post_meta($post->ID, 'nell_abitato', true);
        $tipo_strada = get_post_meta($post->ID, 'tipo_strada', true);
        $denominazione_strada = get_post_meta($post->ID, 'denominazione_strada', true);
        $numero_strada = get_post_meta($post->ID, 'numero_strada', true);
        $progressiva_km = get_post_meta($post->ID, 'progressiva_km', true);
        $progressiva_m = get_post_meta($post->ID, 'progressiva_m', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tipo_strada"><?php _e('Tipo di Strada', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <select id="tipo_strada" name="tipo_strada" required>
                        <option value=""><?php _e('Seleziona tipo strada', 'incidenti-stradali'); ?></option>
                        <optgroup label="<?php _e('Nell\'abitato', 'incidenti-stradali'); ?>">
                            <option value="1" <?php selected($tipo_strada, '1'); ?>><?php _e('Strada urbana', 'incidenti-stradali'); ?></option>
                            <option value="2" <?php selected($tipo_strada, '2'); ?>><?php _e('Provinciale entro l\'abitato', 'incidenti-stradali'); ?></option>
                            <option value="3" <?php selected($tipo_strada, '3'); ?>><?php _e('Statale entro l\'abitato', 'incidenti-stradali'); ?></option>
                            <option value="0" <?php selected($tipo_strada, '0'); ?>><?php _e('Regionale entro l\'abitato', 'incidenti-stradali'); ?></option>
                        </optgroup>
                        <optgroup label="<?php _e('Fuori dall\'abitato', 'incidenti-stradali'); ?>">
                            <option value="4" <?php selected($tipo_strada, '4'); ?>><?php _e('Comunale extraurbana', 'incidenti-stradali'); ?></option>
                            <option value="5" <?php selected($tipo_strada, '5'); ?>><?php _e('Provinciale', 'incidenti-stradali'); ?></option>
                            <option value="6" <?php selected($tipo_strada, '6'); ?>><?php _e('Statale', 'incidenti-stradali'); ?></option>
                            <option value="7" <?php selected($tipo_strada, '7'); ?>><?php _e('Autostrada', 'incidenti-stradali'); ?></option>
                            <option value="8" <?php selected($tipo_strada, '8'); ?>><?php _e('Altra strada', 'incidenti-stradali'); ?></option>
                            <option value="9" <?php selected($tipo_strada, '9'); ?>><?php _e('Regionale', 'incidenti-stradali'); ?></option>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="denominazione_strada"><?php _e('Denominazione della Strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="denominazione_strada" name="denominazione_strada" value="<?php echo esc_attr($denominazione_strada); ?>" style="width: 100%;">
                </td>
            </tr>
            <tr>
                <th><label for="numero_strada"><?php _e('Numero Strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="numero_strada" name="numero_strada" value="<?php echo esc_attr($numero_strada); ?>">
                </td>
            </tr>
            <tr id="progressiva_row" style="display: none;">
                <th><label><?php _e('Progressiva Chilometrica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <label for="progressiva_km"><?php _e('Km', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_km" name="progressiva_km" value="<?php echo esc_attr($progressiva_km); ?>" min="0" max="999" style="width: 80px;">
                    
                    <label for="progressiva_m"><?php _e('Mt', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_m" name="progressiva_m" value="<?php echo esc_attr($progressiva_m); ?>" min="0" max="999" style="width: 80px;">
                    
                    <p class="description"><?php _e('Obbligatorio per strade extraurbane', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function updateFieldsVisibility() {
                    var tipoStrada = $('#tipo_strada').val();
                    var isExtraurbana = false;
                    
                    // Determine if extraurbana based on tipo_strada value
                    if (tipoStrada && ['4', '5', '6', '7', '8', '9'].includes(tipoStrada)) {
                        isExtraurbana = true;
                    }
                    
                    // Show/hide progressiva chilometrica
                    if (isExtraurbana) {
                        $('#progressiva_row').show();
                        $('#progressiva_km').attr('required', true);
                    } else {
                        $('#progressiva_row').hide();
                        $('#progressiva_km').removeAttr('required');
                        $('#progressiva_km').val('');
                        $('#progressiva_m').val('');
                    }
                    
                    // Auto-set nell_abitato based on tipo_strada
                    var nellAbitato = isExtraurbana ? '0' : '1';
                    $('input[name="nell_abitato"]').remove();
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'nell_abitato',
                        value: nellAbitato
                    }).appendTo('#tipo_strada').parent();
                }
                
                // Trigger on page load and when tipo_strada changes
                $('#tipo_strada').on('change', updateFieldsVisibility);
                updateFieldsVisibility();
            });
            </script>
        <?php
    }
    
    public function render_luogo_meta_box($post) {
        $geometria_strada = get_post_meta($post->ID, 'geometria_strada', true);
        $pavimentazione = get_post_meta($post->ID, 'pavimentazione_strada', true);
        $intersezione = get_post_meta($post->ID, 'intersezione_tronco', true);
        $fondo_strada = get_post_meta($post->ID, 'stato_fondo_strada', true);
        $segnaletica = get_post_meta($post->ID, 'segnaletica_strada', true);
        $condizioni_meteo = get_post_meta($post->ID, 'condizioni_meteo', true);
        $illuminazione = get_post_meta($post->ID, 'illuminazione', true);
        $visibilita = get_post_meta($post->ID, 'visibilita', true);
        $traffico = get_post_meta($post->ID, 'traffico', true);
        $segnaletica_semaforica = get_post_meta($post->ID, 'segnaletica_semaforica', true);
        $orientamento_conducente = get_post_meta($post->ID, 'orientamento_conducente', true);
        $presenza_banchina = get_post_meta($post->ID, 'presenza_banchina', true);
        $presenza_barriere = get_post_meta($post->ID, 'presenza_barriere', true);
        $tappeto_usura_aperto = get_post_meta($post->ID, 'tappeto_usura_aperto', true);
        $tappeto_usura_chiuso = get_post_meta($post->ID, 'tappeto_usura_chiuso', true);
        $allagato = get_post_meta($post->ID, 'allagato', true);
        $semaforizzazioni = get_post_meta($post->ID, 'semaforizzazioni', true);
        $cartelli_pubblicitari = get_post_meta($post->ID, 'cartelli_pubblicitari', true);
        $leggibilita_alta = get_post_meta($post->ID, 'leggibilita_alta', true);
        $leggibilita_bassa = get_post_meta($post->ID, 'leggibilita_bassa', true);
        $nuvoloso = get_post_meta($post->ID, 'nuvoloso', true);
        $foschia = get_post_meta($post->ID, 'foschia', true);
        
        ?>
        <div class="luogo-incidente-sections">
            <!-- Sezione Tipi di Strada -->
            <div class="sezione-luogo">
                <h4><?php _e('Tipi di Strada', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="geometria_strada"><?php _e('Configurazione Carreggiate', 'incidenti-stradali'); ?></label></th>
                        <td>
                            <label><input type="radio" name="geometria_strada" value="1" <?php checked($geometria_strada, '1'); ?>> <?php _e('Una carreggiata senso unico', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="geometria_strada" value="2" <?php checked($geometria_strada, '2'); ?>> <?php _e('Una carreggiata doppio senso', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="geometria_strada" value="3" <?php checked($geometria_strada, '3'); ?>> <?php _e('Due carreggiate', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="geometria_strada" value="4" <?php checked($geometria_strada, '4'); ?>> <?php _e('Più di 2 carreggiate', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Pavimentazione -->
            <div class="sezione-luogo">
                <h4><?php _e('Pavimentazione', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Tipo Pavimentazione', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="pavimentazione_strada" value="1" <?php checked($pavimentazione, '1'); ?>> <?php _e('Strada pavimentata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="pavimentazione_strada" value="2" <?php checked($pavimentazione, '2'); ?>> <?php _e('Strada pavimentata dissestata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="pavimentazione_strada" value="3" <?php checked($pavimentazione, '3'); ?>> <?php _e('Strada non pavimentata', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Condizioni Manto', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="checkbox" name="tappeto_usura_aperto" value="1" <?php checked($tappeto_usura_aperto, '1'); ?>> <?php _e('Tappeto d\'usura aperto', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="tappeto_usura_chiuso" value="1" <?php checked($tappeto_usura_chiuso, '1'); ?>> <?php _e('Tappeto d\'usura chiuso', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Intersezione/Non Intersezione -->
            <div class="sezione-luogo">
                <h4><?php _e('Intersezione', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Tipo Intersezione', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="intersezione_tronco" value="1" <?php checked($intersezione, '1'); ?>> <?php _e('Incrocio', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="2" <?php checked($intersezione, '2'); ?>> <?php _e('Rotatoria', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="3" <?php checked($intersezione, '3'); ?>> <?php _e('Intersezione segnalata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="4" <?php checked($intersezione, '4'); ?>> <?php _e('Intersezione con semaforo o vigile', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="5" <?php checked($intersezione, '5'); ?>> <?php _e('Intersezione non segnalata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="6" <?php checked($intersezione, '6'); ?>> <?php _e('Passaggio a livello', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Non Intersezione', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="intersezione_tronco" value="7" <?php checked($intersezione, '7'); ?>> <?php _e('Rettilineo', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="8" <?php checked($intersezione, '8'); ?>> <?php _e('Curva', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="9" <?php checked($intersezione, '9'); ?>> <?php _e('Dosso, strettoia', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="10" <?php checked($intersezione, '10'); ?>> <?php _e('Pendenza - salita', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="11" <?php checked($intersezione, '11'); ?>> <?php _e('Pendenza - discesa', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="12" <?php checked($intersezione, '12'); ?>> <?php _e('Galleria illuminata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="13" <?php checked($intersezione, '13'); ?>> <?php _e('Galleria non illuminata', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Caratteristiche Geometriche', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="intersezione_tronco" value="14" <?php checked($intersezione, '14'); ?>> <?php _e('Cunetta', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="15" <?php checked($intersezione, '15'); ?>> <?php _e('Cavalcavia', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="16" <?php checked($intersezione, '16'); ?>> <?php _e('Trincea', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="17" <?php checked($intersezione, '17'); ?>> <?php _e('Rilevato', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="18" <?php checked($intersezione, '18'); ?>> <?php _e('Accessi laterali', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Fondo Stradale -->
            <div class="sezione-luogo">
                <h4><?php _e('Fondo Stradale', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Condizioni Fondo', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="stato_fondo_strada" value="1" <?php checked($fondo_strada, '1'); ?>> <?php _e('Asciutto', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="stato_fondo_strada" value="2" <?php checked($fondo_strada, '2'); ?>> <?php _e('Bagnato', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="stato_fondo_strada" value="3" <?php checked($fondo_strada, '3'); ?>> <?php _e('Sdrucciolevole', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="stato_fondo_strada" value="4" <?php checked($fondo_strada, '4'); ?>> <?php _e('Ghiacciato', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="stato_fondo_strada" value="5" <?php checked($fondo_strada, '5'); ?>> <?php _e('Innevato', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="allagato" value="1" <?php checked($allagato, '1'); ?>> <?php _e('Allagato', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Segnaletica -->
            <div class="sezione-luogo">
                <h4><?php _e('Segnaletica', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Tipo Segnaletica', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="segnaletica_strada" value="1" <?php checked($segnaletica, '1'); ?>> <?php _e('Assente', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="segnaletica_strada" value="2" <?php checked($segnaletica, '2'); ?>> <?php _e('Verticale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="segnaletica_strada" value="3" <?php checked($segnaletica, '3'); ?>> <?php _e('Orizzontale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="segnaletica_strada" value="4" <?php checked($segnaletica, '4'); ?>> <?php _e('Verticale e orizzontale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="segnaletica_strada" value="5" <?php checked($segnaletica, '5'); ?>> <?php _e('Temporanea di cantiere', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Elementi Aggiuntivi', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="checkbox" name="semaforizzazioni" value="1" <?php checked($semaforizzazioni, '1'); ?>> <?php _e('Semaforizzazioni', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="cartelli_pubblicitari" value="1" <?php checked($cartelli_pubblicitari, '1'); ?>> <?php _e('Cartelli pubblicitari', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="leggibilita_alta" value="1" <?php checked($leggibilita_alta, '1'); ?>> <?php _e('Leggibilità alta', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="leggibilita_bassa" value="1" <?php checked($leggibilita_bassa, '1'); ?>> <?php _e('Leggibilità bassa', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Condizioni Meteorologiche -->
            <div class="sezione-luogo">
                <h4><?php _e('Condizioni Meteorologiche', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Condizioni Meteo', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="condizioni_meteo" value="1" <?php checked($condizioni_meteo, '1'); ?>> <?php _e('Sereno', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="2" <?php checked($condizioni_meteo, '2'); ?>> <?php _e('Nebbia', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="3" <?php checked($condizioni_meteo, '3'); ?>> <?php _e('Pioggia', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="4" <?php checked($condizioni_meteo, '4'); ?>> <?php _e('Grandine', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="5" <?php checked($condizioni_meteo, '5'); ?>> <?php _e('Neve', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="6" <?php checked($condizioni_meteo, '6'); ?>> <?php _e('Vento forte', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="condizioni_meteo" value="7" <?php checked($condizioni_meteo, '7'); ?>> <?php _e('Altro', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Condizioni Aggiuntive', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="checkbox" name="nuvoloso" value="1" <?php checked($nuvoloso, '1'); ?>> <?php _e('Nuvoloso', 'incidenti-stradali'); ?></label><br>
                            <label><input type="checkbox" name="foschia" value="1" <?php checked($foschia, '1'); ?>> <?php _e('Foschia', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Orientamento e Infrastrutture -->
            <div class="sezione-luogo">
                <h4><?php _e('Altre Caratteristiche', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Orientamento del conducente', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="orientamento_conducente" value="sole_frontale" <?php checked($orientamento_conducente, 'sole_frontale'); ?>> <?php _e('Sole frontale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="orientamento_conducente" value="sole_laterale" <?php checked($orientamento_conducente, 'sole_laterale'); ?>> <?php _e('Sole laterale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="orientamento_conducente" value="sole_dietro" <?php checked($orientamento_conducente, 'sole_dietro'); ?>> <?php _e('Sole da dietro', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="orientamento_conducente" value="non_rilevabile" <?php checked($orientamento_conducente, 'non_rilevabile'); ?>> <?php _e('Non rilevabile', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Presenza banchina', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="checkbox" name="presenza_banchina" value="1" <?php checked($presenza_banchina, '1'); ?>> <?php _e('Presente', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Presenza barriere', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="presenza_barriere" value="si" <?php checked($presenza_barriere, 'si'); ?>> <?php _e('Sì', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="presenza_barriere" value="no" <?php checked($presenza_barriere, 'no'); ?>> <?php _e('No', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="presenza_barriere" value="danneggiate" <?php checked($presenza_barriere, 'danneggiate'); ?>> <?php _e('Danneggiate', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <style>
        .luogo-incidente-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .sezione-luogo {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .sezione-luogo h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .sezione-luogo label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .sezione-luogo input[type="radio"],
        .sezione-luogo input[type="checkbox"] {
            margin-right: 8px;
        }

        @media (max-width: 782px) {
            .luogo-incidente-sections {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    public function render_natura_meta_box($post) {
        $natura_incidente = get_post_meta($post->ID, 'natura_incidente', true);
        $dettaglio_natura = get_post_meta($post->ID, 'dettaglio_natura', true);
        $numero_veicoli = get_post_meta($post->ID, 'numero_veicoli_coinvolti', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="natura_incidente"><?php _e('Natura dell\'Incidente', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <select id="natura_incidente" name="natura_incidente" required>
                        <option value=""><?php _e('Seleziona natura', 'incidenti-stradali'); ?></option>
                        <option value="A" <?php selected($natura_incidente, 'A'); ?>><?php _e('Tra veicoli in marcia', 'incidenti-stradali'); ?></option>
                        <option value="B" <?php selected($natura_incidente, 'B'); ?>><?php _e('Tra veicolo e pedoni', 'incidenti-stradali'); ?></option>
                        <option value="C" <?php selected($natura_incidente, 'C'); ?>><?php _e('Veicolo in marcia che urta veicolo fermo o altro', 'incidenti-stradali'); ?></option>
                        <option value="D" <?php selected($natura_incidente, 'D'); ?>><?php _e('Veicolo in marcia senza urto', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="dettaglio_natura_row">
                <th><label for="dettaglio_natura"><?php _e('Dettaglio', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="dettaglio_natura" name="dettaglio_natura">
                        <option value=""><?php _e('Seleziona dettaglio', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="numero_veicoli_row">
                <th><label for="numero_veicoli_coinvolti"><?php _e('Numero Veicoli Coinvolti', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="numero_veicoli_coinvolti" name="numero_veicoli_coinvolti">
                        <option value=""><?php _e('Seleziona numero', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($numero_veicoli, '1'); ?>>1</option>
                        <option value="2" <?php selected($numero_veicoli, '2'); ?>>2</option>
                        <option value="3" <?php selected($numero_veicoli, '3'); ?>>3</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var naturaOptions = {
                'A': {
                    '1': 'Scontro frontale',
                    '2': 'Scontro frontale-laterale', 
                    '3': 'Scontro laterale',
                    '4': 'Tamponamento'
                },
                'B': {
                    '5': 'Investimento di pedoni'
                },
                'C': {
                    '6': 'Urto con veicolo in fermata o in arresto',
                    '7': 'Urto con veicolo in sosta',
                    '8': 'Urto con ostacolo',
                    '9': 'Urto con treno'
                },
                'D': {
                    '10': 'Fuoriuscita (sbandamento, ...)',
                    '11': 'Infortunio per frenata improvvisa',
                    '12': 'Infortunio per caduta da veicolo'
                }
            };
            
            $('#natura_incidente').change(function() {
                var natura = $(this).val();
                var dettaglioSelect = $('#dettaglio_natura');
                dettaglioSelect.empty().append('<option value="">Seleziona dettaglio</option>');
                
                if (natura && naturaOptions[natura]) {
                    $.each(naturaOptions[natura], function(value, text) {
                        dettaglioSelect.append('<option value="' + value + '">' + text + '</option>');
                    });
                    dettaglioSelect.val('<?php echo esc_js($dettaglio_natura); ?>');
                }
                
                // Mostra/nascondi campo numero veicoli
                if (natura === 'A' || (natura === 'C' && ['6'].indexOf($('#dettaglio_natura').val()) !== -1)) {
                    $('#numero_veicoli_row').show();
                } else {
                    $('#numero_veicoli_row').hide();
                    $('#numero_veicoli_coinvolti').val('1');
                }
            });
            
            // Trigger change on page load
            $('#natura_incidente').trigger('change');
        });
        </script>
        <?php
    }
    
    public function render_veicoli_meta_box($post) {
        $numero_veicoli = (int) get_post_meta($post->ID, 'numero_veicoli_coinvolti', true) ?: 1;
        
        echo '<div id="veicoli-container">';
        
        for ($i = 1; $i <= 3; $i++) {
            $display = $i <= $numero_veicoli ? 'block' : 'none';
            echo '<div id="veicolo-' . $i . '" class="veicolo-section" style="display: ' . $display . ';">';
            echo '<h3>' . sprintf(__('Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h3>';
            
            $this->render_single_veicolo_fields($post, $i);
            
            echo '</div>';
        }
        
        echo '</div>';
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#numero_veicoli_coinvolti').change(function() {
                var numVeicoli = parseInt($(this).val()) || 1;
                
                for (var i = 1; i <= 3; i++) {
                    if (i <= numVeicoli) {
                        $('#veicolo-' + i).show();
                    } else {
                        $('#veicolo-' + i).hide();
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    private function render_single_veicolo_fields($post, $veicolo_num) {
        $prefix = 'veicolo_' . $veicolo_num . '_';
        
        $tipo_veicolo = get_post_meta($post->ID, $prefix . 'tipo', true);
        $targa = get_post_meta($post->ID, $prefix . 'targa', true);
        $anno_immatricolazione = get_post_meta($post->ID, $prefix . 'anno_immatricolazione', true);
        $cilindrata = get_post_meta($post->ID, $prefix . 'cilindrata', true);
        $peso_totale = get_post_meta($post->ID, $prefix . 'peso_totale', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="<?php echo $prefix; ?>tipo"><?php _e('Tipo Veicolo', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>tipo" name="<?php echo $prefix; ?>tipo">
                        <option value=""><?php _e('Seleziona tipo', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($tipo_veicolo, '1'); ?>><?php _e('Autovettura privata', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($tipo_veicolo, '2'); ?>><?php _e('Autovettura con rimorchio', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($tipo_veicolo, '3'); ?>><?php _e('Autovettura pubblica', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($tipo_veicolo, '4'); ?>><?php _e('Autovettura di soccorso o di polizia', 'incidenti-stradali'); ?></option>
                        <option value="8" <?php selected($tipo_veicolo, '8'); ?>><?php _e('Autocarro', 'incidenti-stradali'); ?></option>
                        <option value="14" <?php selected($tipo_veicolo, '14'); ?>><?php _e('Velocipede', 'incidenti-stradali'); ?></option>
                        <option value="15" <?php selected($tipo_veicolo, '15'); ?>><?php _e('Ciclomotore', 'incidenti-stradali'); ?></option>
                        <option value="16" <?php selected($tipo_veicolo, '16'); ?>><?php _e('Motociclo a solo', 'incidenti-stradali'); ?></option>
                        <option value="17" <?php selected($tipo_veicolo, '17'); ?>><?php _e('Motociclo con passeggero', 'incidenti-stradali'); ?></option>
                        <option value="21" <?php selected($tipo_veicolo, '21'); ?>><?php _e('Quadriciclo', 'incidenti-stradali'); ?></option>
                        <option value="22" <?php selected($tipo_veicolo, '22'); ?>><?php _e('Monopattino elettrico', 'incidenti-stradali'); ?></option>
                        <option value="23" <?php selected($tipo_veicolo, '23'); ?>><?php _e('Bicicletta elettrica', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>targa"><?php _e('Targa', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="<?php echo $prefix; ?>targa" name="<?php echo $prefix; ?>targa" value="<?php echo esc_attr($targa); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>anno_immatricolazione"><?php _e('Anno Prima Immatricolazione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>anno_immatricolazione" name="<?php echo $prefix; ?>anno_immatricolazione" value="<?php echo esc_attr($anno_immatricolazione); ?>" min="1900" max="<?php echo date('Y'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>cilindrata"><?php _e('Cilindrata (cc)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>cilindrata" name="<?php echo $prefix; ?>cilindrata" value="<?php echo esc_attr($cilindrata); ?>" min="0">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>peso_totale"><?php _e('Peso Totale a Pieno Carico (q)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>peso_totale" name="<?php echo $prefix; ?>peso_totale" value="<?php echo esc_attr($peso_totale); ?>" min="0" step="0.1">
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_persone_meta_box($post) {
        echo '<div id="persone-container">';
        echo '<h4>' . __('Conducenti', 'incidenti-stradali') . '</h4>';
        
        // Render conducenti per ogni veicolo
        for ($i = 1; $i <= 3; $i++) {
            $display = 'block'; // Mostreremo/nasconderemo via JS
            echo '<div id="conducente-' . $i . '" class="conducente-section" style="display: ' . $display . ';">';
            echo '<h4>' . sprintf(__('Conducente Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h4>';
            
            $this->render_single_conducente_fields($post, $i);
            
            echo '</div>';
        }
        
        // NUOVO: Sezione Trasportati
        echo '<h4>' . __('Trasportati', 'incidenti-stradali') . '</h4>';
        for ($i = 1; $i <= 3; $i++) {
            echo '<div id="trasportati-veicolo-' . $i . '" class="trasportati-section" style="display: block;">';
            echo '<h5>' . sprintf(__('Trasportati Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h5>';
            $this->render_trasportati_fields($post, $i);
            echo '</div>';
        }
        
        echo '<h4>' . __('Pedoni Coinvolti', 'incidenti-stradali') . '</h4>';
        $this->render_pedoni_fields($post);
        
        echo '</div>';
    }

    private function render_trasportati_fields($post, $veicolo_num) {
        $num_trasportati = get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_numero_trasportati', true) ?: 0;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati"><?php _e('Numero Trasportati', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati" name="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati">
                        <?php for ($j = 0; $j <= 9; $j++): ?>
                            <option value="<?php echo $j; ?>" <?php selected($num_trasportati, $j); ?>><?php echo $j; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <div id="trasportati-<?php echo $veicolo_num; ?>-container">
            <?php for ($i = 1; $i <= 9; $i++): 
                $display = $i <= $num_trasportati ? 'block' : 'none';
                $prefix = 'veicolo_' . $veicolo_num . '_trasportato_' . $i . '_';
            ?>
                <div id="trasportato-<?php echo $veicolo_num; ?>-<?php echo $i; ?>" style="display: <?php echo $display; ?>;">
                    <h6><?php printf(__('Trasportato %d', 'incidenti-stradali'), $i); ?></h6>
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Sesso', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select name="<?php echo $prefix; ?>sesso">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected(get_post_meta($post->ID, $prefix . 'sesso', true), '1'); ?>><?php _e('Maschio', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected(get_post_meta($post->ID, $prefix . 'sesso', true), '2'); ?>><?php _e('Femmina', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Età', 'incidenti-stradali'); ?></label></th>
                            <td><input type="number" name="<?php echo $prefix; ?>eta" value="<?php echo esc_attr(get_post_meta($post->ID, $prefix . 'eta', true)); ?>" min="0" max="120"></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Esito', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select name="<?php echo $prefix; ?>esito">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '1'); ?>><?php _e('Incolume', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '2'); ?>><?php _e('Ferito', 'incidenti-stradali'); ?></option>
                                    <option value="3" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '3'); ?>><?php _e('Morto entro 24 ore', 'incidenti-stradali'); ?></option>
                                    <option value="4" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '4'); ?>><?php _e('Morto dal 2° al 30° giorno', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Posizione', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select name="<?php echo $prefix; ?>posizione">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected(get_post_meta($post->ID, $prefix . 'posizione', true), '1'); ?>><?php _e('Sedile anteriore', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected(get_post_meta($post->ID, $prefix . 'posizione', true), '2'); ?>><?php _e('Sedile posteriore', 'incidenti-stradali'); ?></option>
                                    <option value="3" <?php selected(get_post_meta($post->ID, $prefix . 'posizione', true), '3'); ?>><?php _e('Altra posizione', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Uso cinture/casco', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select name="<?php echo $prefix; ?>uso_dispositivi">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected(get_post_meta($post->ID, $prefix . 'uso_dispositivi', true), '1'); ?>><?php _e('Utilizzati', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected(get_post_meta($post->ID, $prefix . 'uso_dispositivi', true), '2'); ?>><?php _e('Non utilizzati', 'incidenti-stradali'); ?></option>
                                    <option value="3" <?php selected(get_post_meta($post->ID, $prefix . 'uso_dispositivi', true), '3'); ?>><?php _e('Non accertato', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endfor; ?>
        </div>
        
        <script>
        jQuery('#veicolo_<?php echo $veicolo_num; ?>_numero_trasportati').on('change', function() {
            var num = parseInt(jQuery(this).val()) || 0;
            for (var i = 1; i <= 9; i++) {
                if (i <= num) {
                    jQuery('#trasportato-<?php echo $veicolo_num; ?>-' + i).show();
                } else {
                    jQuery('#trasportato-<?php echo $veicolo_num; ?>-' + i).hide();
                }
            }
        });
        </script>
        <?php
    }
    
    private function render_single_conducente_fields($post, $conducente_num) {
        $prefix = 'conducente_' . $conducente_num . '_';
        
        $eta = get_post_meta($post->ID, $prefix . 'eta', true);
        $sesso = get_post_meta($post->ID, $prefix . 'sesso', true);
        $esito = get_post_meta($post->ID, $prefix . 'esito', true);
        $tipo_patente = get_post_meta($post->ID, $prefix . 'tipo_patente', true);
        $anno_patente = get_post_meta($post->ID, $prefix . 'anno_patente', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="<?php echo $prefix; ?>eta"><?php _e('Età', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>eta" name="<?php echo $prefix; ?>eta" value="<?php echo esc_attr($eta); ?>" min="14" max="120">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>sesso"><?php _e('Sesso', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>sesso" name="<?php echo $prefix; ?>sesso">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($sesso, '1'); ?>><?php _e('Maschio', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($sesso, '2'); ?>><?php _e('Femmina', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>esito"><?php _e('Esito', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>esito" name="<?php echo $prefix; ?>esito">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($esito, '1'); ?>><?php _e('Incolume', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($esito, '2'); ?>><?php _e('Ferito', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($esito, '3'); ?>><?php _e('Morto entro 24 ore', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($esito, '4'); ?>><?php _e('Morto dal 2° al 30° giorno', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>tipo_patente"><?php _e('Tipo Patente', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>tipo_patente" name="<?php echo $prefix; ?>tipo_patente">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="0" <?php selected($tipo_patente, '0'); ?>><?php _e('Patente ciclomotori', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($tipo_patente, '1'); ?>><?php _e('Patente tipo A', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($tipo_patente, '2'); ?>><?php _e('Patente tipo B', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($tipo_patente, '3'); ?>><?php _e('Patente tipo C', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($tipo_patente, '4'); ?>><?php _e('Patente tipo D', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($tipo_patente, '5'); ?>><?php _e('Patente tipo E', 'incidenti-stradali'); ?></option>
                        <option value="6" <?php selected($tipo_patente, '6'); ?>><?php _e('ABC speciale', 'incidenti-stradali'); ?></option>
                        <option value="7" <?php selected($tipo_patente, '7'); ?>><?php _e('Non richiesta', 'incidenti-stradali'); ?></option>
                        <option value="8" <?php selected($tipo_patente, '8'); ?>><?php _e('Foglio rosa', 'incidenti-stradali'); ?></option>
                        <option value="9" <?php selected($tipo_patente, '9'); ?>><?php _e('Sprovvisto', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>anno_patente"><?php _e('Anno Rilascio Patente', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>anno_patente" name="<?php echo $prefix; ?>anno_patente" value="<?php echo esc_attr($anno_patente); ?>" min="1950" max="<?php echo date('Y'); ?>">
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function render_pedoni_fields($post) {
        $numero_pedoni = get_post_meta($post->ID, 'numero_pedoni_coinvolti', true) ?: 0;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="numero_pedoni_coinvolti"><?php _e('Numero Pedoni Coinvolti', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="numero_pedoni_coinvolti" name="numero_pedoni_coinvolti">
                        <option value="0" <?php selected($numero_pedoni, '0'); ?>>0</option>
                        <option value="1" <?php selected($numero_pedoni, '1'); ?>>1</option>
                        <option value="2" <?php selected($numero_pedoni, '2'); ?>>2</option>
                        <option value="3" <?php selected($numero_pedoni, '3'); ?>>3</option>
                        <option value="4" <?php selected($numero_pedoni, '4'); ?>>4</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <div id="pedoni-container">
            <?php for ($i = 1; $i <= 4; $i++): 
                $display = $i <= $numero_pedoni ? 'block' : 'none';
                $prefix = 'pedone_' . $i . '_';
                $eta = get_post_meta($post->ID, $prefix . 'eta', true);
                $sesso = get_post_meta($post->ID, $prefix . 'sesso', true);
                $esito = get_post_meta($post->ID, $prefix . 'esito', true);
            ?>
                <div id="pedone-<?php echo $i; ?>" class="pedone-section" style="display: <?php echo $display; ?>;">
                    <h5><?php printf(__('Pedone %d', 'incidenti-stradali'), $i); ?></h5>
                    <table class="form-table">
                        <tr>
                            <th><label for="<?php echo $prefix; ?>eta"><?php _e('Età', 'incidenti-stradali'); ?></label></th>
                            <td><input type="number" id="<?php echo $prefix; ?>eta" name="<?php echo $prefix; ?>eta" value="<?php echo esc_attr($eta); ?>" min="0" max="120"></td>
                        </tr>
                        <tr>
                            <th><label for="<?php echo $prefix; ?>sesso"><?php _e('Sesso', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select id="<?php echo $prefix; ?>sesso" name="<?php echo $prefix; ?>sesso">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected($sesso, '1'); ?>><?php _e('Maschio', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected($sesso, '2'); ?>><?php _e('Femmina', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="<?php echo $prefix; ?>esito"><?php _e('Esito', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select id="<?php echo $prefix; ?>esito" name="<?php echo $prefix; ?>esito">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected($esito, '2'); ?>><?php _e('Ferito', 'incidenti-stradali'); ?></option>
                                    <option value="3" <?php selected($esito, '3'); ?>><?php _e('Morto entro 24 ore', 'incidenti-stradali'); ?></option>
                                    <option value="4" <?php selected($esito, '4'); ?>><?php _e('Morto dal 2° al 30° giorno', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endfor; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#numero_pedoni_coinvolti').change(function() {
                var numPedoni = parseInt($(this).val()) || 0;
                
                for (var i = 1; i <= 4; i++) {
                    if (i <= numPedoni) {
                        $('#pedone-' + i).show();
                    } else {
                        $('#pedone-' + i).hide();
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    public function render_coordinate_meta_box($post) {
        $latitudine = get_post_meta($post->ID, 'latitudine', true);
        $longitudine = get_post_meta($post->ID, 'longitudine', true);
        $tipo_coordinata = get_post_meta($post->ID, 'tipo_coordinata', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tipo_coordinata"><?php _e('Tipo di Coordinata', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="tipo_coordinata" name="tipo_coordinata">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="ED50" <?php selected($tipo_coordinata, 'ED50'); ?>>ED50</option>
                        <option value="WGS84" <?php selected($tipo_coordinata, 'WGS84'); ?>>WGS84</option>
                        <option value="Monte Mario" <?php selected($tipo_coordinata, 'Monte Mario'); ?>>Monte Mario (Gauss Boaga)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="latitudine"><?php _e('Latitudine', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="latitudine" name="latitudine" value="<?php echo esc_attr($latitudine); ?>" placeholder="es. 41.902783">
                </td>
            </tr>
            <tr>
                <th><label for="longitudine"><?php _e('Longitudine', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="longitudine" name="longitudine" value="<?php echo esc_attr($longitudine); ?>" placeholder="es. 12.496366">
                </td>
            </tr>
        </table>
        
        <div id="coordinate-map" style="height: 200px; margin-top: 10px;"></div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var map = L.map('coordinate-map').setView([41.9028, 12.4964], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            
            var marker = null;
            var lat = parseFloat($('#latitudine').val());
            var lng = parseFloat($('#longitudine').val());
            
            if (lat && lng) {
                marker = L.marker([lat, lng]).addTo(map);
                map.setView([lat, lng], 15);
            }
            
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                
                $('#latitudine').val(lat.toFixed(6));
                $('#longitudine').val(lng.toFixed(6));
                
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker([lat, lng]).addTo(map);
            });
            
            $('#latitudine, #longitudine').on('change', function() {
                var lat = parseFloat($('#latitudine').val());
                var lng = parseFloat($('#longitudine').val());
                
                if (lat && lng) {
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    marker = L.marker([lat, lng]).addTo(map);
                    map.setView([lat, lng], 15);
                }
            });
        });
        </script>
        <?php
    }
    
    public function render_mappa_meta_box($post) {
        $mostra_in_mappa = get_post_meta($post->ID, 'mostra_in_mappa', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mostra_in_mappa"><?php _e('Mostra nella Mappa Pubblica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="mostra_in_mappa" name="mostra_in_mappa" value="1" <?php checked($mostra_in_mappa, '1'); ?>>
                        <?php _e('Sì, includi questo incidente nella mappa pubblica', 'incidenti-stradali'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function move_meta_boxes_after_title() {
        global $post, $wp_meta_boxes;
        
        if ('incidente_stradale' === $post->post_type) {
            // Check if user can edit based on date restrictions
            $data_blocco = get_option('incidenti_data_blocco_modifica');
            $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
            
            if ($data_blocco && $data_incidente && strtotime($data_incidente) < strtotime($data_blocco)) {
                if (!current_user_can('manage_all_incidenti')) {
                    echo '<div class="notice notice-warning"><p>' . __('Questo incidente non può essere modificato perché avvenuto prima della data di blocco impostata.', 'incidenti-stradali') . '</p></div>';
                }
            }
        }
    }
    
    /**
     * METODO MODIFICATO: Save meta boxes - NON interferisce con eliminazioni
     */
    public function save_meta_boxes($post_id) {
        // Verifica nonce
        if (!isset($_POST['incidente_meta_box_nonce']) || !wp_verify_nonce($_POST['incidente_meta_box_nonce'], 'incidente_meta_box')) {
            return;
        }
        
        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Don't save on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ('incidente_stradale' !== get_post_type($post_id)) {
            return;
        }
        
        // CRITICO: Non interferire con operazioni di eliminazione
        if (isset($_GET['action']) && in_array($_GET['action'], ['trash', 'delete', 'untrash'])) {
            return;
        }
        
        if (isset($_POST['action']) && in_array($_POST['action'], ['trash', 'delete', 'untrash'])) {
            return;
        }
        
        // Non interferire con bulk actions di eliminazione
        if (isset($_POST['action']) && $_POST['action'] === '-1' && isset($_POST['action2'])) {
            $action = $_POST['action2'];
            if (in_array($action, ['trash', 'delete', 'untrash'])) {
                return;
            }
        }
        
        // Non interferire con post già nel cestino
        $post = get_post($post_id);
        if ($post && $post->post_status === 'trash') {
            return;
        }
        
        // IMPORTANTE: Previeni loop infiniti
        remove_action('save_post', array($this, 'save_meta_boxes'));
        
        // Check date restrictions SOLO per operazioni di salvataggio normale
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        if ($data_blocco && isset($_POST['data_incidente'])) {
            if (strtotime($_POST['data_incidente']) < strtotime($data_blocco)) {
                if (!current_user_can('manage_all_incidenti')) {
                    // Re-aggiungi l'action prima di uscire
                    add_action('save_post', array($this, 'save_meta_boxes'));
                    wp_die(__('Non è possibile modificare incidenti avvenuti prima della data di blocco.', 'incidenti-stradali'));
                }
            }
        }
        
        // Array of all meta fields to save
        $meta_fields = array(
            'data_incidente', 'ora_incidente', 'minuti_incidente', 'provincia_incidente', 'comune_incidente',
            'localita_incidente', 'organo_rilevazione', 'organo_coordinatore', 'nell_abitato', 'tipo_strada', 'denominazione_strada',
            'numero_strada', 'progressiva_km', 'progressiva_m', 'geometria_strada', 'pavimentazione_strada',
            'intersezione_tronco', 'stato_fondo_strada', 'segnaletica_strada', 'condizioni_meteo',
            'natura_incidente', 'dettaglio_natura', 'numero_veicoli_coinvolti', 'numero_pedoni_coinvolti',
            'latitudine', 'longitudine', 'tipo_coordinata', 'mostra_in_mappa', 'ente_rilevatore', 'nome_rilevatore',
            // Campi nominativi morti
            'morto_1_nome', 'morto_1_cognome',
            'morto_2_nome', 'morto_2_cognome',
            'morto_3_nome', 'morto_3_cognome',
            'morto_4_nome', 'morto_4_cognome',
            // Campi nominativi feriti
            'ferito_1_nome', 'ferito_1_cognome', 'ferito_1_istituto',
            'ferito_2_nome', 'ferito_2_cognome', 'ferito_2_istituto',
            'ferito_3_nome', 'ferito_3_cognome', 'ferito_3_istituto',
            'ferito_4_nome', 'ferito_4_cognome', 'ferito_4_istituto',
            'ferito_5_nome', 'ferito_5_cognome', 'ferito_5_istituto',
            'ferito_6_nome', 'ferito_6_cognome', 'ferito_6_istituto',
            'ferito_7_nome', 'ferito_7_cognome', 'ferito_7_istituto',
            'ferito_8_nome', 'ferito_8_cognome', 'ferito_8_istituto'
        );
        
        // Save all meta fields
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                if ($field === 'mostra_in_mappa') {
                    delete_post_meta($post_id, $field);
                }
            }
        }

        // Gestione speciale per i campi checkbox
        $checkbox_fields = array(
            'presenza_banchina', 'tappeto_usura_aperto', 'tappeto_usura_chiuso', 
            'allagato', 'semaforizzazioni', 'cartelli_pubblicitari', 
            'leggibilita_alta', 'leggibilita_bassa', 'nuvoloso', 'foschia'
        );

        foreach ($checkbox_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, '1');
            } else {
                delete_post_meta($post_id, $field);
            }
        }
        
        // Save vehicle and driver fields
        $numero_veicoli = isset($_POST['numero_veicoli_coinvolti']) ? intval($_POST['numero_veicoli_coinvolti']) : 1;
        for ($i = 1; $i <= 3; $i++) {
            if ($i <= $numero_veicoli) {
                $vehicle_fields = array('tipo', 'targa', 'anno_immatricolazione', 'cilindrata', 'peso_totale');
                $driver_fields = array('eta', 'sesso', 'esito', 'tipo_patente', 'anno_patente');
                
                foreach ($vehicle_fields as $field) {
                    $key = 'veicolo_' . $i . '_' . $field;
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                    }
                }
                
                foreach ($driver_fields as $field) {
                    $key = 'conducente_' . $i . '_' . $field;
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                    }
                }
            } else {
                $all_fields = array(
                    'veicolo_' . $i . '_tipo', 'veicolo_' . $i . '_targa', 
                    'veicolo_' . $i . '_anno_immatricolazione', 'veicolo_' . $i . '_cilindrata', 
                    'veicolo_' . $i . '_peso_totale',
                    'conducente_' . $i . '_eta', 'conducente_' . $i . '_sesso', 
                    'conducente_' . $i . '_esito', 'conducente_' . $i . '_tipo_patente', 
                    'conducente_' . $i . '_anno_patente'
                );
                
                foreach ($all_fields as $field) {
                    delete_post_meta($post_id, $field);
                }
            }
        }
        
        // Save pedestrian fields
        $numero_pedoni = isset($_POST['numero_pedoni_coinvolti']) ? intval($_POST['numero_pedoni_coinvolti']) : 0;
        for ($i = 1; $i <= 4; $i++) {
            if ($i <= $numero_pedoni) {
                $pedestrian_fields = array('eta', 'sesso', 'esito');
                foreach ($pedestrian_fields as $field) {
                    $key = 'pedone_' . $i . '_' . $field;
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                    }
                }
            } else {
                $fields_to_remove = array('pedone_' . $i . '_eta', 'pedone_' . $i . '_sesso', 'pedone_' . $i . '_esito');
                foreach ($fields_to_remove as $field) {
                    delete_post_meta($post_id, $field);
                }
            }
        }
        
        // Update post title only if needed
        if (isset($_POST['data_incidente'])) {
            $current_title = get_the_title($post_id);
            $denominazione = isset($_POST['denominazione_strada']) ? $_POST['denominazione_strada'] : __('Strada non specificata', 'incidenti-stradali');
            $new_title = sprintf(__('Incidente del %s - %s', 'incidenti-stradali'), 
                            $_POST['data_incidente'], 
                            $denominazione);
            
            if ($current_title !== $new_title) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    array('post_title' => $new_title),
                    array('ID' => $post_id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        // Re-aggiungi l'action
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    /**
     * NUOVO: Gestisce quando un post viene spostato nel cestino
     */
    public function on_post_trashed($post_id) {
        if (get_post_type($post_id) === 'incidente_stradale') {
            error_log("Incidente {$post_id} spostato nel cestino da utente " . get_current_user_id());
        }
    }

    /**
     * NUOVO: Gestisce quando un post viene eliminato definitivamente
     */
    public function on_post_deleted($post_id) {
        if (get_post_type($post_id) === 'incidente_stradale') {
            error_log("Incidente {$post_id} eliminato definitivamente da utente " . get_current_user_id());
        }
    }
    
    /**
     * NUOVO: Gestisce quando un post viene ripristinato dal cestino
     */
    public function on_post_untrashed($post_id) {
        if (get_post_type($post_id) === 'incidente_stradale') {
            error_log("Incidente {$post_id} ripristinato dal cestino da utente " . get_current_user_id());
        }
    }
}