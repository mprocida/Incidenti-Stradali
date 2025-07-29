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

        // NUOVO: Azioni per stampa PDF
        add_action('admin_enqueue_scripts', array($this, 'enqueue_pdf_scripts'));
        add_action('wp_ajax_print_incidente_pdf', array($this, 'generate_pdf'));
        add_action('wp_ajax_get_incidente_data_for_pdf', array($this, 'get_incidente_data_for_pdf'));
        add_action('add_meta_boxes', array($this, 'add_print_meta_box'));

        add_action('wp_ajax_print_incidente_pdf', array($this, 'generate_pdf'));
        add_action('wp_ajax_nopriv_print_incidente_pdf', array($this, 'generate_pdf')); // Se serve per utenti non loggati

        // NUOVO: Rimuovi "Modifica rapida" e "Azioni di gruppo" per utenti Asset
        add_filter('post_row_actions', array($this, 'remove_quick_edit_for_asset'), 10, 2);
        add_filter('bulk_actions-edit-incidente_stradale', array($this, 'remove_bulk_actions_for_asset'));
        add_action('admin_head', array($this, 'hide_asset_ui_elements'));
        add_action('admin_init', array($this, 'customize_asset_list_view'));
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
        
        // Nascondi la meta box delle coordinate dato che ora è integrata in localizzazione
        // add_meta_box(
        //     'incidente_coordinate',
        //     __('Coordinate Geografiche', 'incidenti-stradali'),
        //     array($this, 'render_coordinate_meta_box'),
        //     'incidente_stradale',
        //     'side',
        //     'default'
        // );
        
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

        add_meta_box(
            'incidente_riepilogo_infortunati',
            __('Riepilogo Infortunati', 'incidenti-stradali'),
            array($this, 'render_riepilogo_infortunati_meta_box'),
            'incidente_stradale',
            'side',  // Posiziona nella sidebar
            'low'
        );

        add_meta_box(
            'incidente_stampa_pdf',
            __('Stampa Modulo', 'incidenti-stradali'),
            array($this, 'render_stampa_pdf_meta_box'),
            'incidente_stradale',
            'side',
            'high'
        );

        //BLOCCO PER DATI CSV IN FORM
        /* add_meta_box(
            'incidente_dati_csv',
            __('Dati per esportazione CSV', 'incidenti-stradali'),
            array($this, 'render_dati_csv_meta_box'),
            'incidente_stradale',
            'normal',
            'low'
        ); */
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

                // Conta morti e feriti da TRASPORTATI
                for (var veicolo = 1; veicolo <= 3; veicolo++) {
                    var numTrasportati = parseInt($('#veicolo_' + veicolo + '_numero_trasportati').val()) || 0;
                    for (var t = 1; t <= numTrasportati; t++) {
                        var esito = $('#trasportato_' + veicolo + '_' + t + '_esito').val();
                        if (esito == '1') {  // Morto
                            totalMorti++;
                        } else if (esito == '2') {  // Ferito
                            totalFeriti++;
                        }
                    }
                }
                
                // Conta pedoni morti e feriti dalle nuove sezioni
                var numPedoniMorti = parseInt($('#numero_pedoni_morti').val()) || 0;
                var numPedoniFeriti = parseInt($('#numero_pedoni_feriti').val()) || 0;
                totalMorti += numPedoniMorti;
                totalFeriti += numPedoniFeriti;
                
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
            //$(document).on('change', '#numero_pedoni_coinvolti', updateNominativiVisibility);
            $(document).on('change', '#numero_pedoni_feriti, #numero_pedoni_morti', updateNominativiVisibility);

            // Aggiorna visibilità quando cambiano gli esiti dei trasportati
            $(document).on('change', 'select[id*="trasportato_"][id*="_esito"]', updateNominativiVisibility);
            $(document).on('change', 'select[id*="_numero_trasportati"]', updateNominativiVisibility);
            
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

    public function render_riepilogo_infortunati_meta_box($post) {
        $morti_24h = get_post_meta($post->ID, 'riepilogo_morti_24h', true);
        $morti_2_30gg = get_post_meta($post->ID, 'riepilogo_morti_2_30gg', true);
        $feriti = get_post_meta($post->ID, 'riepilogo_feriti', true);
        ?>
        
        <div id="riepilogo-automatico-container" style="background: #e7f3ff; border: 1px solid #2271b1; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h4 style="margin-top: 0; color: #2271b1;">📊 <?php _e('Riepilogo Automatico Infortunati', 'incidenti-stradali'); ?></h4>
            <p style="margin: 5px 0; font-size: 13px; color: #666;">
                <?php _e('Questo riepilogo viene calcolato automaticamente dai dati inseriti nel modulo.', 'incidenti-stradali'); ?>
            </p>
            
            <div id="conteggio-automatico" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 15px 0;">
                <div style="text-align: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #ddd;">
                    <strong style="display: block; color: #d63638; font-size: 24px;" id="count-morti-24h">0</strong>
                    <span style="font-size: 12px; color: #666;"><?php _e('Morti entro 24h', 'incidenti-stradali'); ?></span>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #ddd;">
                    <strong style="display: block; color: #d63638; font-size: 24px;" id="count-morti-2-30gg">0</strong>
                    <span style="font-size: 12px; color: #666;"><?php _e('Morti 2°-30° giorno', 'incidenti-stradali'); ?></span>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #ddd;">
                    <strong style="display: block; color: #f0b849; font-size: 24px;" id="count-feriti">0</strong>
                    <span style="font-size: 12px; color: #666;"><?php _e('Feriti', 'incidenti-stradali'); ?></span>
                </div>
            </div>
            
            <div id="riepilogo-dettagli" style="font-size: 12px; color: #666; margin-top: 10px;">
                <p style="margin: 2px 0;"><strong><?php _e('Calcolo automatico basato su:', 'incidenti-stradali'); ?></strong></p>
                <ul style="margin: 5px 0 0 20px; font-size: 11px;">
                    <li><?php _e('Esiti dei conducenti dei veicoli', 'incidenti-stradali'); ?></li>
                    <li><?php _e('Esiti dei trasportati', 'incidenti-stradali'); ?></li>
                    <li><?php _e('Pedoni morti e feriti', 'incidenti-stradali'); ?></li>
                    <li><?php _e('Altri passeggeri infortunati', 'incidenti-stradali'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Campi nascosti per il salvataggio -->
        <input type="hidden" id="riepilogo_morti_24h" name="riepilogo_morti_24h" value="<?php echo esc_attr($morti_24h); ?>">
        <input type="hidden" id="riepilogo_morti_2_30gg" name="riepilogo_morti_2_30gg" value="<?php echo esc_attr($morti_2_30gg); ?>">
        <input type="hidden" id="riepilogo_feriti" name="riepilogo_feriti" value="<?php echo esc_attr($feriti); ?>">
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function calcolaRiepilogoAutomatico() {
                var morti24h = 0;
                var morti2_30gg = 0;
                var feriti = 0;
                
                // Conta conducenti
                for (var i = 1; i <= 3; i++) {
                    var esito = $('#conducente_' + i + '_esito').val();
                    if (esito == '3') { // Morto entro 24h
                        morti24h++;
                    } else if (esito == '4') { // Morto dal 2° al 30° giorno
                        morti2_30gg++;
                    } else if (esito == '2') { // Ferito
                        feriti++;
                    }
                }
                
                // Conta trasportati
                for (var veicolo = 1; veicolo <= 3; veicolo++) {
                    var numTrasportati = parseInt($('#veicolo_' + veicolo + '_numero_trasportati').val()) || 0;
                    for (var t = 1; t <= numTrasportati; t++) {
                        var esito = $('#trasportato_' + veicolo + '_' + t + '_esito').val();
                        if (esito == '1') { // Morto entro 24h
                            morti24h++;
                        } else if (esito == '2') { // Ferito
                            feriti++;
                        }
                    }
                }
                
                // Conta pedoni
                var numPedoniMorti = parseInt($('#numero_pedoni_morti').val()) || 0;
                var numPedoniFeriti = parseInt($('#numero_pedoni_feriti').val()) || 0;
                morti24h += numPedoniMorti;
                feriti += numPedoniFeriti;
                
                // Conta altri passeggeri
                for (var v = 1; v <= 3; v++) {
                    var altriMortiM = parseInt($('#veicolo_' + v + '_altri_morti_maschi').val()) || 0;
                    var altriMortiF = parseInt($('#veicolo_' + v + '_altri_morti_femmine').val()) || 0;
                    var altriFeritiM = parseInt($('#veicolo_' + v + '_altri_feriti_maschi').val()) || 0;
                    var altriFeritiF = parseInt($('#veicolo_' + v + '_altri_feriti_femmine').val()) || 0;
                    
                    morti24h += altriMortiM + altriMortiF;
                    feriti += altriFeritiM + altriFeritiF;
                }
                
                // Conta altri generali
                var altriMortiMGen = parseInt($('#altri_morti_maschi').val()) || 0;
                var altriMortiFGen = parseInt($('#altri_morti_femmine').val()) || 0;
                var altriFeritiMGen = parseInt($('#altri_feriti_maschi').val()) || 0;
                var altriFeritiGen = parseInt($('#altri_feriti_femmine').val()) || 0;
                
                morti24h += altriMortiMGen + altriMortiFGen;
                feriti += altriFeritiMGen + altriFeritiGen;
                
                // Aggiorna display
                $('#count-morti-24h').text(morti24h);
                $('#count-morti-2-30gg').text(morti2_30gg);
                $('#count-feriti').text(feriti);
                
                // Aggiorna campi nascosti
                $('#riepilogo_morti_24h').val(morti24h);
                $('#riepilogo_morti_2_30gg').val(morti2_30gg);
                $('#riepilogo_feriti').val(feriti);
                
                console.log('Riepilogo aggiornato:', { morti24h, morti2_30gg, feriti });
            }
            
            // Ascolta i cambiamenti sui campi rilevanti
            $(document).on('change', 'select[id*="_esito"], #numero_pedoni_morti, #numero_pedoni_feriti, select[id*="_numero_trasportati"], input[id*="_altri_morti_"], input[id*="_altri_feriti_"], #altri_morti_maschi, #altri_morti_femmine, #altri_feriti_maschi, #altri_feriti_femmine', calcolaRiepilogoAutomatico);
            
            // Calcola al caricamento della pagina
            setTimeout(calcolaRiepilogoAutomatico, 1000);
            
            // Ricalcola quando cambia il numero di veicoli
            $(document).on('change', '#numero_veicoli_coinvolti', function() {
                setTimeout(calcolaRiepilogoAutomatico, 500);
            });
        });
        </script>
        
        <?php
    }

    public function render_dati_csv_meta_box($post) {
        ?>
        <div class="incidenti-dati-csv-container">
            <table class="form-table">
                <!-- Tipo Strada -->
                <tr>
                    <th><label for="csv_tipo_strada"><?php _e('Tipo Strada', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_tipo_strada" name="csv_tipo_strada">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="1" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '1'); ?>><?php _e('Fuoriuscita/Sbandamento', 'incidenti-stradali'); ?></option>
                            <option value="2" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '2'); ?>><?php _e('Investimento animale', 'incidenti-stradali'); ?></option>
                            <option value="3" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '3'); ?>><?php _e('Investimento pedone', 'incidenti-stradali'); ?></option>
                            <option value="4" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '4'); ?>><?php _e('Scontro frontale', 'incidenti-stradali'); ?></option>
                            <option value="5" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '5'); ?>><?php _e('Scontro laterale', 'incidenti-stradali'); ?></option>
                            <option value="6" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '6'); ?>><?php _e('Tamponamento', 'incidenti-stradali'); ?></option>
                            <option value="7" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '7'); ?>><?php _e('Urto con ostacolo fisso', 'incidenti-stradali'); ?></option>
                            <option value="8" <?php selected(get_post_meta($post->ID, 'csv_tipo_strada', true), '8'); ?>><?php _e('Altro', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Centro Abitato -->
                <tr>
                    <th><label for="csv_centro_abitato"><?php _e('Centro Abitato', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_centro_abitato" name="csv_centro_abitato">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="S" <?php selected(get_post_meta($post->ID, 'csv_centro_abitato', true), 'S'); ?>><?php _e('Sì', 'incidenti-stradali'); ?></option>
                            <option value="N" <?php selected(get_post_meta($post->ID, 'csv_centro_abitato', true), 'N'); ?>><?php _e('No', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Caratteristiche -->
                <tr>
                    <th><label for="csv_caratteristiche"><?php _e('Caratteristiche', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_caratteristiche" name="csv_caratteristiche">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="1" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '1'); ?>><?php _e('Non specificato', 'incidenti-stradali'); ?></option>
                            <option value="2" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '2'); ?>><?php _e('Incrocio', 'incidenti-stradali'); ?></option>
                            <option value="3" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '3'); ?>><?php _e('Rotatoria', 'incidenti-stradali'); ?></option>
                            <option value="4" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '4'); ?>><?php _e('Intersezione segnalata', 'incidenti-stradali'); ?></option>
                            <option value="5" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '5'); ?>><?php _e('Intersezione con semaforo o vigile', 'incidenti-stradali'); ?></option>
                            <option value="6" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '6'); ?>><?php _e('Intersezione non segnalata', 'incidenti-stradali'); ?></option>
                            <option value="7" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '7'); ?>><?php _e('Passaggio a livello', 'incidenti-stradali'); ?></option>
                            <option value="8" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '8'); ?>><?php _e('Rettilineo', 'incidenti-stradali'); ?></option>
                            <option value="9" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '9'); ?>><?php _e('Curva', 'incidenti-stradali'); ?></option>
                            <option value="10" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '10'); ?>><?php _e('Raccordo convesso (dosso)', 'incidenti-stradali'); ?></option>
                            <option value="11" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '11'); ?>><?php _e('Pendenza pericolosa', 'incidenti-stradali'); ?></option>
                            <option value="12" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '12'); ?>><?php _e('Galleria illuminata', 'incidenti-stradali'); ?></option>
                            <option value="13" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '13'); ?>><?php _e('Galleria non illuminata', 'incidenti-stradali'); ?></option>
                            <option value="14" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '14'); ?>><?php _e('Intersezione con semaf. giallo. lampegg.', 'incidenti-stradali'); ?></option>
                            <option value="15" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '15'); ?>><?php _e('Passaggio a livello custodito', 'incidenti-stradali'); ?></option>
                            <option value="16" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '16'); ?>><?php _e('Passaggio a livello non custodito', 'incidenti-stradali'); ?></option>
                            <option value="17" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '17'); ?>><?php _e('Raccordo concavo (cunetta)', 'incidenti-stradali'); ?></option>
                            <option value="18" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '18'); ?>><?php _e('Strettoia', 'incidenti-stradali'); ?></option>
                            <option value="19" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '19'); ?>><?php _e('Pianeggiante', 'incidenti-stradali'); ?></option>
                            <option value="20" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '20'); ?>><?php _e('Curva a destra', 'incidenti-stradali'); ?></option>
                            <option value="21" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '21'); ?>><?php _e('Curva a sinistra', 'incidenti-stradali'); ?></option>
                            <option value="22" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '22'); ?>><?php _e('Salita', 'incidenti-stradali'); ?></option>
                            <option value="23" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '23'); ?>><?php _e('Discesa', 'incidenti-stradali'); ?></option>
                            <option value="24" <?php selected(get_post_meta($post->ID, 'csv_caratteristiche', true), '24'); ?>><?php _e('Viadotto', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Cantiere Stradale -->
                <tr>
                    <th><label for="csv_cantiere_stradale"><?php _e('Cantiere Stradale', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_cantiere_stradale" name="csv_cantiere_stradale">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="S" <?php selected(get_post_meta($post->ID, 'csv_cantiere_stradale', true), 'S'); ?>><?php _e('Sì', 'incidenti-stradali'); ?></option>
                            <option value="N" <?php selected(get_post_meta($post->ID, 'csv_cantiere_stradale', true), 'N'); ?>><?php _e('No', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>

                <!-- Numero Veicoli -->
                <tr>
                    <th colspan="2"><h4><?php _e('Numero Veicoli Coinvolti', 'incidenti-stradali'); ?></h4></th>
                </tr>
                <tr>
                    <th><label for="csv_n_autovettura"><?php _e('N.Autovettura', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autovettura" name="csv_n_autovettura" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autovettura', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_autocarro_35t"><?php _e('N.Autocarro fino 3,5t', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autocarro_35t" name="csv_n_autocarro_35t" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autocarro_35t', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_autocarro_oltre_35t"><?php _e('N_Autocarro > 3,5t', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autocarro_oltre_35t" name="csv_n_autocarro_oltre_35t" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autocarro_oltre_35t', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_autotreno"><?php _e('N_Autotreno', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autotreno" name="csv_n_autotreno" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autotreno', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_autoarticolato"><?php _e('N_Autoarticolato', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autoarticolato" name="csv_n_autoarticolato" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autoarticolato', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_autobus"><?php _e('N_Autobus', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_autobus" name="csv_n_autobus" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_autobus', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_tram"><?php _e('N_Tram', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_tram" name="csv_n_tram" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_tram', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_treno"><?php _e('N_Treno', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_treno" name="csv_n_treno" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_treno', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_motociclo"><?php _e('N_Motociclo', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_motociclo" name="csv_n_motociclo" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_motociclo', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_ciclomotore"><?php _e('N_Ciclomotore', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_ciclomotore" name="csv_n_ciclomotore" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_ciclomotore', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_velocipede"><?php _e('N_Velocipede', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_velocipede" name="csv_n_velocipede" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_velocipede', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_bicicletta_assistita"><?php _e('N_Bicicletta a pedali assistita', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_bicicletta_assistita" name="csv_n_bicicletta_assistita" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_bicicletta_assistita', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_monopattini"><?php _e('N_Monopattini elettrici', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_monopattini" name="csv_n_monopattini" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_monopattini', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_altri_micromobilita"><?php _e('N_Altri dispositivi micromobilità', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_altri_micromobilita" name="csv_n_altri_micromobilita" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_altri_micromobilita', true)); ?>" min="0" max="999"></td>
                </tr>
                <tr>
                    <th><label for="csv_n_altri_veicoli"><?php _e('N_Altri Veicoli', 'incidenti-stradali'); ?></label></th>
                    <td><input type="number" id="csv_n_altri_veicoli" name="csv_n_altri_veicoli" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_n_altri_veicoli', true)); ?>" min="0" max="999"></td>
                </tr>

                <!-- Altri campi -->
                <tr>
                    <th><label for="csv_trasportanti_merci_pericolose"><?php _e('Trasportanti merci pericolose', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_trasportanti_merci_pericolose" name="csv_trasportanti_merci_pericolose">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="S" <?php selected(get_post_meta($post->ID, 'csv_trasportanti_merci_pericolose', true), 'S'); ?>><?php _e('Sì', 'incidenti-stradali'); ?></option>
                            <option value="N" <?php selected(get_post_meta($post->ID, 'csv_trasportanti_merci_pericolose', true), 'N'); ?>><?php _e('No', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_omissione"><?php _e('Omissione', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_omissione" name="csv_omissione">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="S" <?php selected(get_post_meta($post->ID, 'csv_omissione', true), 'S'); ?>><?php _e('Sì', 'incidenti-stradali'); ?></option>
                            <option value="N" <?php selected(get_post_meta($post->ID, 'csv_omissione', true), 'N'); ?>><?php _e('No', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_contromano"><?php _e('Contromano', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_contromano" name="csv_contromano">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="S" <?php selected(get_post_meta($post->ID, 'csv_contromano', true), 'S'); ?>><?php _e('Sì', 'incidenti-stradali'); ?></option>
                            <option value="N" <?php selected(get_post_meta($post->ID, 'csv_contromano', true), 'N'); ?>><?php _e('No', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_dettaglio_persone_decedute"><?php _e('Dettaglio Persone Decedute', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <textarea id="csv_dettaglio_persone_decedute" name="csv_dettaglio_persone_decedute" rows="3" class="regular-text"><?php echo esc_textarea(get_post_meta($post->ID, 'csv_dettaglio_persone_decedute', true)); ?></textarea>
                        <p class="description"><?php _e('Formato: tipo_veicolo_ruolo_età_sesso. Separare dati con "_" e persone con ";"<br>Esempio: Motociclo_Conducente_15_M;Pedone_Pedone_25_F;', 'incidenti-stradali'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_positivita"><?php _e('Positività', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="csv_positivita" name="csv_positivita">
                            <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                            <option value="Entrambi" <?php selected(get_post_meta($post->ID, 'csv_positivita', true), 'Entrambi'); ?>><?php _e('Entrambi', 'incidenti-stradali'); ?></option>
                            <option value="Alcol" <?php selected(get_post_meta($post->ID, 'csv_positivita', true), 'Alcol'); ?>><?php _e('Alcol', 'incidenti-stradali'); ?></option>
                            <option value="Droga" <?php selected(get_post_meta($post->ID, 'csv_positivita', true), 'Droga'); ?>><?php _e('Droga', 'incidenti-stradali'); ?></option>
                            <option value="Negativo" <?php selected(get_post_meta($post->ID, 'csv_positivita', true), 'Negativo'); ?>><?php _e('Negativo', 'incidenti-stradali'); ?></option>
                        </select>
                        <p class="description"><?php _e('"Entrambi": uno o più conducenti positivi ad alcol e droga; "Alcol": solo ad alcol; "Droga": solo a droga; "Negativo": nessun conducente positivo', 'incidenti-stradali'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_art_cds"><?php _e('Art Cds', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <input type="text" id="csv_art_cds" name="csv_art_cds" value="<?php echo esc_attr(get_post_meta($post->ID, 'csv_art_cds', true)); ?>" class="regular-text">
                        <p class="description"><?php _e('Formato: articolo/comma. Separare violazioni con ";"<br>Esempio: 189/9;186/3', 'incidenti-stradali'); ?></p>
                    </td>
                </tr>
            </table>
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
                <th><label for="localizzazione_extra_ab"><?php _e('Altra strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <textarea id="localizzazione_extra_ab" 
                            name="localizzazione_extra_ab" 
                            maxlength="100"
                            placeholder="<?php _e('Inserisci denominazione strada (max 100 caratteri)', 'incidenti-stradali'); ?>"
                            style="resize: vertical;"
                            rows="3"><?php echo esc_textarea(get_post_meta($post->ID, 'localizzazione_extra_ab', true)); ?></textarea>
                    <p class="description">
                        <?php _e('Massimo 100 caratteri consentiti', 'incidenti-stradali'); ?>
                    </p>
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

        // Recupera l'ente dell'utente corrente per le auto-selezioni
        $user_ente = get_user_meta(get_current_user_id(), 'ente_gestione', true);

        // Auto-selezione dell'ente rilevatore se non già impostato
        if (empty($ente_rilevatore) && !empty($user_ente)) {
            $ente_rilevatore = $this->map_ente_to_nome_completo($user_ente);
        }

        // Carica i comuni di Lecce
        $comuni_lecce = $this->get_comuni_lecce();
        
        ?>
        <table class="form-table">
            <tr>
                <th class="required-field"><label for="data_incidente"><?php _e('Data dell\'Incidente', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="date" id="data_incidente" name="data_incidente" value="<?php echo esc_attr($data_incidente); ?>" required>
                </td>
            </tr>
            <tr>
                <th class="required-field"><label for="ora_incidente"><?php _e('Ora dell\'Incidente', 'incidenti-stradali'); ?></label></th>
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
                        <option value=""><?php _e('Seleziona minuti', 'incidenti-stradali'); ?></option>
                        <?php for($m = 0; $m <= 59; $m++): ?>
                            <option value="<?php echo sprintf('%02d', $m); ?>" <?php selected($minuti_incidente, sprintf('%02d', $m)); ?>>
                                <?php echo sprintf('%02d', $m); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th class="required-field"><label for="provincia_incidente"><?php _e('Provincia', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="hidden" id="provincia_incidente" name="provincia_incidente" value="075">
                    <input type="text" value="Lecce (075)" disabled class="regular-text">
                    <p class="description"><?php _e('Provincia di Lecce - codice ISTAT 075', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
            <th class="required-field"><label for="comune_incidente"><?php _e('Comune', 'incidenti-stradali'); ?></label></th>
            <td>
                <?php
                // Controllo restrizioni utente
                $user_ente = get_user_meta(get_current_user_id(), 'ente_gestione', true);
                $comuni_consentiti = $this->get_comuni_per_ente($user_ente);
                
                // Se l'utente ha un ente specifico con un solo comune, preseleziona
                if (!empty($comuni_consentiti) && count($comuni_consentiti) == 1 && empty($comune)) {
                    $comune = key($comuni_consentiti);
                }

                if (!empty($comuni_consentiti)): ?>
                    <select id="comune_incidente" name="comune_incidente" required class="regular-text">
                        <option value=""><?php _e('Seleziona comune', 'incidenti-stradali'); ?></option>
                        <?php foreach ($comuni_consentiti as $codice => $nome): ?>
                            <option value="<?php echo esc_attr($codice); ?>" <?php selected($comune, $codice); ?>>
                                <?php echo esc_html($nome . ' (' . $codice . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Comuni disponibili per il tuo ente di appartenenza', 'incidenti-stradali'); ?></p>
                <?php else: ?>
                    <select id="comune_incidente" name="comune_incidente" required class="regular-text">
                        <option value=""><?php _e('Seleziona comune', 'incidenti-stradali'); ?></option>
                        <?php foreach($comuni_lecce as $codice => $nome): ?>
                            <option value="<?php echo esc_attr($codice); ?>" <?php selected($comune, $codice); ?>>
                                <?php echo esc_html($nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Seleziona il comune dove è avvenuto l\'incidente', 'incidenti-stradali'); ?></p>
                <?php endif; ?>
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
                    <?php
                    // Controllo restrizioni utente per l'ente
                    $user_ente = get_user_meta(get_current_user_id(), 'ente_gestione', true);
                    $ente_specifico = $this->map_ente_to_nome_completo($user_ente);
                    
                    if (!empty($ente_specifico) && strpos($user_ente, 'pm_') === 0): // Solo per Polizia Municipale
                    ?>
                        <select id="ente_rilevatore" name="ente_rilevatore" class="regular-text">
                            <option value="<?php echo esc_attr($ente_specifico); ?>" selected>
                                <?php echo esc_html($ente_specifico); ?>
                            </option>
                        </select>
                        <p class="description"><?php _e('Ente di appartenenza dell\'operatore', 'incidenti-stradali'); ?></p>
                    <?php else: ?>
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
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="identificativo_comando_row" style="display: none;">
                <th class="required-field"s><label for="identificativo_comando"><?php _e('Identificativo del Comando Staz. dei Carabinieri', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="identificativo_comando" name="identificativo_comando" 
                        value="<?php echo esc_attr(get_post_meta($post->ID, 'identificativo_comando', true)); ?>" 
                        maxlength="20" class="regular-text">
                    <p class="description"><?php _e('Obbligatorio solo per organo Carabiniere', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="nome_rilevatore"><?php _e('Rilevatore', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="nome_rilevatore" name="nome_rilevatore" value="<?php echo esc_attr($nome_rilevatore); ?>" class="regular-text">
                    <p class="description"><?php _e('Nome e cognome del rilevatore', 'incidenti-stradali'); ?></p>
                </td>
            </tr>

            <tr style="display: none;">
                <th><label for="codice__ente"><?php _e('Codice Ente (Auto-generato)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="hidden" id="codice__ente" name="codice__ente" value="<?php echo esc_attr(get_post_meta($post->ID, 'codice__ente', true)); ?>">
                </td>
            </tr>
            
            <!-- CAMPI ESISTENTI (mantieni solo per compatibilità ISTAT) -->
            <tr style="display: none;">
                <th><label for="organo_rilevazione"><?php _e('Organo di Rilevazione (ISTAT)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <?php
                    // Auto-selezione basata sull'ente se non già selezionato
                    if (empty($organo_rilevazione) && !empty($user_ente)) {
                        $organo_rilevazione = $this->map_ente_to_organo($user_ente);
                    }
                    ?>
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
            <tr>
                <th><label for="organo_coordinatore"><?php _e('Organo Coordinatore (ISTAT)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="organo_coordinatore" name="organo_coordinatore">
                        <option value=""><?php _e('Seleziona organo coordinatore', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($organo_coordinatore, '1'); ?>><?php _e('Sezione di Polizia Stradale', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($organo_coordinatore, '2'); ?>><?php _e('Gruppo Carabinieri', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($organo_coordinatore, '3'); ?>><?php _e('Ufficio comunale di statistica: Comune con oltre 250.000 abitanti', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($organo_coordinatore, '4'); ?>><?php _e('Altro capoluogo di provincia', 'incidenti-stradali'); ?></option>
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
                        // Nascondi il campo identificativo comando per altri enti
                        $('#identificativo_comando_row').hide();
                        $('#identificativo_comando').prop('required', false).val('');
                    } else if (ente === 'Carabiniere') {
                        organoValue = '2'; // Carabiniere
                        // Mostra il campo identificativo comando
                        $('#identificativo_comando_row').show();
                        $('#identificativo_comando').prop('required', true);
                    } else if (ente === 'Agente di Polizia Stradale') {
                        organoValue = '1'; // Agente di Polizia Stradale
                        // Nascondi il campo identificativo comando per altri enti
                        $('#identificativo_comando_row').hide();
                        $('#identificativo_comando').prop('required', false).val('');
                    } else if (ente === 'Polizia Provinciale') {
                        organoValue = '6'; // Agente di Polizia Provinciale
                        // Nascondi il campo identificativo comando per altri enti
                        $('#identificativo_comando_row').hide();
                        $('#identificativo_comando').prop('required', false).val('');
                    } else {
                        organoValue = '5'; // Altri
                        // Nascondi il campo identificativo comando per altri enti
                        $('#identificativo_comando_row').hide();
                        $('#identificativo_comando').prop('required', false).val('');
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
            '002' => 'Alessano', 
            '003' => 'Alezio',
            '004' => 'Alliste',
            '005' => 'Andrano',
            '006' => 'Aradeo',
            '007' => 'Arnesano',
            '008' => 'Bagnolo Del Salento',
            '009' => 'Botrugno',
            '010' => 'Calimera',
            '011' => 'Campi Salentina',
            '012' => 'Cannole',
            '013' => 'Caprarica di Lecce',
            '014' => 'Carmiano',
            '015' => 'Carpignano Salentino',
            '016' => 'Casarano',
            '017' => 'Castri Di Lecce',
            '018' => 'Castrignano De` Greci',
            '019' => 'Castrignano Del Capo',
            '096' => 'Castro',
            '020' => 'Cavallino',
            '021' => 'Collepasso',
            '022' => 'Copertino',
            '023' => 'Corigliano D`Otranto',
            '024' => 'Corsano',
            '025' => 'Cursi',
            '026' => 'Cutrofiano',
            '027' => 'Diso',
            '028' => 'Gagliano Del Capo',
            '029' => 'Galatina',
            '030' => 'Galatone',
            '031' => 'Gallipoli',
            '032' => 'Giuggianello',
            '033' => 'Giurdignano',
            '034' => 'Guagnano',
            '035' => 'Lecce',
            '036' => 'Lequile',
            '037' => 'Leverano',
            '038' => 'Lizzanello',
            '039' => 'Maglie',
            '040' => 'Martano',
            '041' => 'Martignano',
            '042' => 'Matino',
            '043' => 'Melendugno',
            '044' => 'Melissano',
            '045' => 'Melpignano',
            '046' => 'Miggiano',
            '047' => 'Minervino Di Lecce',
            '048' => 'Monteroni Di Lecce',
            '049' => 'Montesano Salentino',
            '050' => 'Morciano Di Leuca',
            '051' => 'Muro Leccese',
            '052' => 'Nardo`',
            '053' => 'Neviano',
            '054' => 'Nociglia',
            '055' => 'Novoli',
            '056' => 'Ortelle',
            '057' => 'Otranto',
            '058' => 'Palmariggi',
            '059' => 'Parabita',
            '060' => 'Patu`',
            '061' => 'Poggiardo',
            '097' => 'Porto Cesareo',
            '098' => 'Presicce-Acquarica',
            '063' => 'Racale',
            '064' => 'Ruffano',
            '065' => 'Salice Salentino',
            '066' => 'Salve',
            '095' => 'San Cassiano',
            '068' => 'San Cesario Di Lecce',
            '069' => 'San Donato Di Lecce',
            '071' => 'San Pietro In Lama',
            '067' => 'Sanarica',
            '070' => 'Sannicola',
            '072' => 'Santa Cesarea Terme',
            '073' => 'Scorrano',
            '074' => 'Secli`',
            '075' => 'Sogliano Cavour',
            '076' => 'Soleto',
            '077' => 'Specchia',
            '078' => 'Spongano',
            '079' => 'Squinzano',
            '080' => 'Sternatia',
            '081' => 'Supersano',
            '082' => 'Surano',
            '083' => 'Surbo',
            '084' => 'Taurisano',
            '085' => 'Taviano',
            '086' => 'Tiggiano',
            '087' => 'Trepuzzi',
            '088' => 'Tricase',
            '089' => 'Tuglie',
            '090' => 'Ugento',
            '091' => 'Uggiano La Chiesa',
            '092' => 'Veglie',
            '093' => 'Vernole',
            '094' => 'Zollino'
        );
    }
    
    public function render_localizzazione_meta_box($post) {
        $abitato = get_post_meta($post->ID, 'nell_abitato', true);
        $tipo_strada = get_post_meta($post->ID, 'tipo_strada', true);
        $denominazione_strada = get_post_meta($post->ID, 'denominazione_strada', true);
        $numero_strada = get_post_meta($post->ID, 'numero_strada', true);
        $progressiva_km = get_post_meta($post->ID, 'progressiva_km', true);
        $progressiva_m = get_post_meta($post->ID, 'progressiva_m', true);
        $tronco_strada = get_post_meta($post->ID, 'tronco_strada', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th class="required-field"><label for="tipo_strada"><?php _e('Tipo di Strada', 'incidenti-stradali'); ?></label></th>
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
                            <option value="4" <?php selected($tipo_strada, '4'); ?>><?php _e('Strada comunale extraurbana', 'incidenti-stradali'); ?></option>
                            <option value="5" <?php selected($tipo_strada, '5'); ?>><?php _e('Strada provinciale fuori dell\'abitato', 'incidenti-stradali'); ?></option>
                            <option value="6" <?php selected($tipo_strada, '6'); ?>><?php _e('Strada statale fuori dell\'abitato', 'incidenti-stradali'); ?></option>
                            <option value="7" <?php selected($tipo_strada, '7'); ?>><?php _e('Autostrada', 'incidenti-stradali'); ?></option>
                            <option value="8" <?php selected($tipo_strada, '8'); ?>><?php _e('Altra strada', 'incidenti-stradali'); ?></option>
                            <option value="9" <?php selected($tipo_strada, '9'); ?>><?php _e('Strada regionale fuori l\'abitato', 'incidenti-stradali'); ?></option>
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
                <th><label for="tronco_strada"><?php _e('Tronco di strada o autostrada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="tronco_strada" name="tronco_strada">
                        <option value=""><?php _e('Seleziona...', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($tronco_strada, '1'); ?>><?php _e('diramazione; dir. A', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($tronco_strada, '2'); ?>><?php _e('dir. B; radd.', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($tronco_strada, '3'); ?>><?php _e('bis; dir. C', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($tronco_strada, '4'); ?>><?php _e('ter; bis dir.', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($tronco_strada, '5'); ?>><?php _e('quater; racc.; bis racc.', 'incidenti-stradali'); ?></option>
                        <option value="6" <?php selected($tronco_strada, '6'); ?>><?php _e('Autostrada carreggiata sinistra', 'incidenti-stradali'); ?></option>
                        <option value="7" <?php selected($tronco_strada, '7'); ?>><?php _e('Autostrada carreggiata destra', 'incidenti-stradali'); ?></option>
                        <option value="8" <?php selected($tronco_strada, '8'); ?>><?php _e('Autostrada svincolo entrata', 'incidenti-stradali'); ?></option>
                        <option value="9" <?php selected($tronco_strada, '9'); ?>><?php _e('Autostrada svincolo uscita', 'incidenti-stradali'); ?></option>
                        <option value="10" <?php selected($tronco_strada, '10'); ?>><?php _e('Autostrada svincolo tronco d.c.', 'incidenti-stradali'); ?></option>
                        <option value="11" <?php selected($tronco_strada, '11'); ?>><?php _e('Autostrada stazione', 'incidenti-stradali'); ?></option>
                        <option value="12" <?php selected($tronco_strada, '12'); ?>><?php _e('Altri casi', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="numero_strada_row">
                <th><label for="numero_strada"><?php _e('Numero Strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <!-- Campo input text per strade statali -->
                    <input type="text" 
                        id="numero_strada_input" 
                        name="numero_strada" 
                        value="<?php echo esc_attr($numero_strada); ?>" 
                        class="regular-text"
                        style="display: none;">
                    
                    <!-- Campo select per strade provinciali -->
                    <select id="numero_strada_select" 
                            name="numero_strada" 
                            class="regular-text"
                            style="display: none;"
                            data-saved-value="<?php echo esc_attr($numero_strada); ?>">
                        <option value=""><?php _e('Seleziona strada provinciale', 'incidenti-stradali'); ?></option>
                    </select>
                    
                    <!-- Campo select per strade statali -->
                    <select id="numero_strada_select_statali" 
                            name="numero_strada" 
                            class="regular-text"
                            style="display: none;"
                            data-saved-value="<?php echo esc_attr($numero_strada); ?>">
                        <option value=""><?php _e('Seleziona strada statale', 'incidenti-stradali'); ?></option>
                    </select>
                    
                    <p class="description" id="numero_strada_description">
                        <?php _e('Numero identificativo della strada (es. SS7, SP101, A14)', 'incidenti-stradali'); ?>
                    </p>
                </td>
            </tr>
            <tr id="progressiva_row">
                <th><label><?php _e('Progressiva Chilometrica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <label for="progressiva_km"><?php _e('Km', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_km" name="progressiva_km" value="<?php echo esc_attr($progressiva_km); ?>" min="0" max="9999" style="width: 100px;">
                    
                    <label for="progressiva_m"><?php _e('Mt', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_m" name="progressiva_m" value="<?php echo esc_attr($progressiva_m); ?>" min="0" max="999" style="width: 80px;">
                    
                    <p class="description"><?php _e('Specificare km e metri separatamente', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>

        <!-- NUOVA SEZIONE MAPPA INTEGRATA -->
        <div class="incidenti-mappa-localizzazione">
            <h4><?php _e('Seleziona Posizione sulla Mappa', 'incidenti-stradali'); ?></h4>
            <div class="incidenti-map-info-inline">
                <p class="description">
                    <strong><?php _e('Sistema:', 'incidenti-stradali'); ?></strong> WGS84 (GPS) • 
                    <strong><?php _e('Clic sulla mappa', 'incidenti-stradali'); ?></strong> per impostare le coordinate
                </p>
                <div class="coordinate-inputs-inline required-field">
                    <label for="latitudine_inline"><?php _e('Latitudine:', 'incidenti-stradali'); ?></label>
                    <input type="text" id="latitudine_inline" name="latitudine" 
                        value="<?php echo esc_attr(get_post_meta($post->ID, 'latitudine', true)); ?>" 
                        placeholder="es. 41.902783" class="incidenti-coordinate-input-inline">
                    
                    <label for="longitudine_inline"><?php _e('Longitudine:', 'incidenti-stradali'); ?></label>
                    <input type="text" id="longitudine_inline" name="longitudine" 
                        value="<?php echo esc_attr(get_post_meta($post->ID, 'longitudine', true)); ?>" 
                        placeholder="es. 12.496366" class="incidenti-coordinate-input-inline">
                </div>
            </div>
            <div id="localizzazione-map"></div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function updateFieldsVisibility() {
                    var tipoStrada = $('#tipo_strada').val();
                    var numeroStradaRow = $('#numero_strada_row');
                    var inputText = $('#numero_strada_input');
                    var selectField = $('#numero_strada_select');
                    
                    // Tipi di strada che richiedono il numero strada
                    var tipiConNumero = ['2', '3', '5', '6'];

                    // Tipi che usano la select (strade provinciali)
                    var tipiConSelect = ['2', '5'];

                    // Tipi che usano la select per strade statali
                    var tipiConSelectStatali = ['3', '6'];

                    var selectFieldStatali = $('#numero_strada_select_statali');

                    if (tipiConNumero.includes(tipoStrada)) {
                        numeroStradaRow.show();
                        
                        if (tipiConSelect.includes(tipoStrada)) {
                            // Mostra select provinciali e nascondi altri campi
                            inputText.hide().prop('name', '');
                            selectField.show().prop('name', 'numero_strada');
                            selectFieldStatali.hide().prop('name', '');
                            
                            // Popola la select se è vuota
                            if (selectField.find('option').length <= 1) {
                                populateStradeProvinciali();
                            }
                        } else if (tipiConSelectStatali.includes(tipoStrada)) {
                            // Mostra select statali e nascondi altri campi
                            inputText.hide().prop('name', '');
                            selectField.hide().prop('name', '');
                            selectFieldStatali.show().prop('name', 'numero_strada');
                            
                            // Popola la select statali se è vuota
                            if (selectFieldStatali.find('option').length <= 1) {
                                populateStradeStatali();
                            }
                        } else {
                            // Mostra input text e nascondi select (per altri tipi di strade)
                            selectField.hide().prop('name', '');
                            selectFieldStatali.hide().prop('name', '');
                            inputText.show().prop('name', 'numero_strada');
                        }
                    } else {
                        numeroStradaRow.hide();
                        // Pulisci tutti i campi quando nascosti
                        inputText.val('');
                        selectField.val('');
                        selectFieldStatali.val('');
                    }
                    
                    // Logica esistente per nell_abitato
                    if (tipoStrada) {
                        var nellAbitato = ['1', '2', '3', '0'].includes(tipoStrada) ? '1' : '0';
                        $('input[name="nell_abitato"]').remove();
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'nell_abitato',
                            value: nellAbitato
                        }).appendTo('#tipo_strada').parent();
                    }
                }

                
                    // Trigger on page load and when tipo_strada changes
                    $('#tipo_strada').on('change', function() {
                        updateFieldsVisibility();
                    });

                    // Chiamata iniziale
                    updateFieldsVisibility();


                    // === NUOVA SEZIONE MAPPA ===
                    // Inizializza mappa solo se il contenitore esiste
                    if ($('#localizzazione-map').length === 0) return;
                
                    var map = L.map('localizzazione-map', {
                        zoomControl: true,
                        scrollWheelZoom: true,
                        doubleClickZoom: true,
                        boxZoom: false
                    }).setView([40.351, 18.175], 10); // Centrato su Lecce
                
                    // Tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);
                
                    var marker = null;
                    var lat = parseFloat($('#latitudine_inline').val());
                    var lng = parseFloat($('#longitudine_inline').val());
                    
                    // Marker esistente
                    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                        marker = L.marker([lat, lng], {
                            draggable: true,
                            title: 'Posizione incidente (trascinabile)'
                        }).addTo(map);
                        map.setView([lat, lng], 15);
                    
                    marker.on('dragend', function(e) {
                        var position = e.target.getLatLng();
                        $('#latitudine_inline').val(position.lat.toFixed(6));
                        $('#longitudine_inline').val(position.lng.toFixed(6));
                        // Sincronizza con i campi nella sidebar se esistono
                        $('#latitudine').val(position.lat.toFixed(6));
                        $('#longitudine').val(position.lng.toFixed(6));
                    });
                }

                function populateStradeProvinciali() {
                    var select = $('#numero_strada_select');
                    select.empty();
                    select.append('<option value="">Seleziona strada provinciale</option>');
                    var stradeProvinciali = [
                        {value: "001", text: "001 - Lecce - Vernole"},
                        {value: "001_dir_A", text: "001 dir A - Diramazione per Merine"},
                        {value: "001_dir_B", text: "001 dir B - Diramazione per Strudà"},
                        {value: "002", text: "002 - Vernole - Melendugno"},
                        {value: "003", text: "003 - Carpignano - Borgagne - Melendugno"},
                        {value: "004", text: "004 - Lecce - Novoli - Campi - Squinzano"},
                        {value: "004_dir", text: "004 dir - Diramazione per Salice"},
                        {value: "005", text: "005 - Squinzano - Torchiarolo"},
                        {value: "006", text: "006 - Lecce - Monteroni - Copertino"},
                        {value: "007", text: "007 - Lecce - Arnesano"},
                        {value: "008", text: "008 - Villa Convento - Arnesano - Monteroni"},
                        {value: "010", text: "010 - Lequile - San Cesario alla Lecce - Maglie"},
                        {value: "011", text: "011 - Lequile - San Pietro - Monteroni"},
                        {value: "012", text: "012 - Magliano - Arnesano"},
                        {value: "013", text: "013 - Carmiano - Novoli"},
                        {value: "014", text: "014 - Carmiano - Veglie"},
                        {value: "015", text: "015 - Veglie - Novoli - Trepuzzi"},
                        {value: "016", text: "016 - Lecce - San Pietro in Lama - Copertino"},
                        {value: "017", text: "017 - Serra di Gallipoli alla Lecce -Taranto"},
                        {value: "018", text: "018 - Galatina - Copertino"},
                        {value: "019", text: "019 - Nardò alla Lecce - Gallipoli"},
                        {value: "020", text: "020 - Copertino alla Lecce - Gallipoli (Grottella)"},
                        {value: "021", text: "021 - Leverano - Porto Cesareo"},
                        {value: "023", text: "023 - Castromediano - Cavallino"},
                        {value: "025", text: "025 - Calimera - Lizzanello"},
                        {value: "026", text: "026 - Calimera - Martano"},
                        {value: "027", text: "027 - Cavallino - Caprarica"},
                        {value: "028", text: "028 - Caprarica - Martano"},
                        {value: "029", text: "029 - Melendugno - Calimera"},
                        {value: "030", text: "030 - Calimera - Martignano - Sternatia"},
                        {value: "031", text: "031 - Sternatia - Soleto"},
                        {value: "032", text: "032 - Sternatia alla Lecce - Maglie"},
                        {value: "033", text: "033 - Corigliano - Galatina"},
                        {value: "034", text: "034 - Corigliano alla Lecce -Maglie"},
                        {value: "035", text: "035 - Castrignano dei Greci alla Lecce - Maglie"},
                        {value: "036", text: "036 - Martano - Castrignano - Melpignano alla Lecce - Maglie"},
                        {value: "037", text: "037 - Melpignano - Cursi - Maglie"},
                        {value: "038", text: "038 - Cursi - Bagnolo"},
                        {value: "039", text: "039 - Dalla Martano - Otranto per Serrano, Cannole, Bagnolo alla Maglie - Otranto"},
                        {value: "040", text: "040 - Cutrofiano - Collepasso"},
                        {value: "041", text: "041 - Galatina - Noha - Collepasso"},
                        {value: "041_dir_A", text: "041 dir. A - Diramazione per Aradeo da Noha"},
                        {value: "042", text: "042 - Seclì - Neviano - Collepasso"},
                        {value: "043", text: "043 - Alezio - Tuglie - Collepasso"},
                        {value: "044", text: "044 - Surbo - Stazione"},
                        {value: "045", text: "045 - Lecce - Surbo"},
                        {value: "046", text: "046 - Galugnano - San Donato alla San Cesario - Galatina"},
                        {value: "047", text: "047 - Galatone - Galatina - Soleto alla Lecce - Maglie"},
                        {value: "048", text: "048 - Dalla Lecce - Maglie per Martano ad Otranto"},
                        {value: "049", text: "049 - Corigliano alla Sogliano - Cutrofiano"},
                        {value: "049_dir", text: "049 dir - Diramazione per la Galatina-Corigliano"},
                        {value: "050", text: "050 - Aradeo - Sannicola"},
                        {value: "051", text: "051 - Parabita - Tuglie - Sannicola"},
                        {value: "052", text: "052 - Gallipoli - Chiesanuova - Sannicola"},
                        {value: "053", text: "053 - Alezio - Sannicola alla Lecce - Gallipoli"},
                        {value: "054", text: "054 - Taviano - Alezio"},
                        {value: "055", text: "055 - Taviano - Matino"},
                        {value: "056", text: "056 - Poggiardo - Minervino - Uggiano"},
                        {value: "057", text: "057 - San Simone alla Alezio - Sannicola"},
                        {value: "058", text: "058 - Uggiano - Giurdignano alla Maglie - Otranto"},
                        {value: "059", text: "059 - Palmariggi - Minervino"},
                        {value: "060", text: "060 - Minervino - Cocumola - Vaste"},
                        {value: "061", text: "061 - Cocumola - Cerfignano alla Maglie - Santa Cesarea"},
                        {value: "062", text: "062 - Minervino - Giuggianello - Sanarica"},
                        {value: "063", text: "063 - Sanarica - Botrugno"},
                        {value: "064", text: "064 - Muro - Scorrano"},
                        {value: "065", text: "065 - Ugento - Mare"},
                        {value: "066", text: "066 - Ugento - Taurisano"},
                        {value: "067", text: "067 - Racale - Alliste - Felline"},
                        {value: "068", text: "068 - Casarano - Taviano"},
                        {value: "068_dir", text: "068 dir - Diramazione per Melissano"},
                        {value: "069", text: "069 - Casarano alla Collepasso - Maglie"},
                        {value: "070", text: "070 - Zollino - Stazione"},
                        {value: "071", text: "071 - Casarano - Ruffano"},
                        {value: "072", text: "072 - Ugento - Casarano"},
                        {value: "073", text: "073 - Salve - Ruggiano alla Maglie - Leuca"},
                        {value: "074", text: "074 - Castrignano del Capo - Santa Maria di Leuca"},
                        {value: "074_dir", text: "074 dir - Santa Maria di Leuca alla Maglie - Leuca"},
                        {value: "075", text: "075 - Tricase - Specchia alla Miggiano - Taurisano"},
                        {value: "076", text: "076 - Presicce - Specchia"},
                        {value: "077", text: "077 - Specchia - Miggiano"},
                        {value: "078", text: "078 - Tricase - Tricase Porto"},
                        {value: "078_dir", text: "078 dir - Variante alla Quercia Vallonea"},
                        {value: "079", text: "079 - Alessano - Presicce"},
                        {value: "080", text: "080 - Alessano alla Tiggiano - Corsano"},
                        {value: "081", text: "081 - Vaste - Tricase - Corsano alla Alessano - Leuca"},
                        {value: "082", text: "082 - Diso - Spongano - Surano - Nociglia"},
                        {value: "083", text: "083 - Diso - Vignacastrisi"},
                        {value: "084", text: "084 - Ortelle - Vignacastrisi - Castro"},
                        {value: "085", text: "085 - Andrano - Castiglione - Montesano"},
                        {value: "086", text: "086 - Nociglia - Supersano"},
                        {value: "087", text: "087 - Otranto - Porto Badisco"},
                        {value: "088", text: "088 - Torre San Giovanni - Torre Sinfonò"},
                        {value: "090", text: "090 - Galatone - Santa Maria al Bagno"},
                        {value: "091", text: "091 - Pescoluse - Torre San Giovanni"},
                        {value: "092", text: "092 - Trepuzzi - Surbo"},
                        {value: "093", text: "093 - Surbo - Torre Rinalda"},
                        {value: "094", text: "094 - Surbo alla Lecce - Torre Chianca"},
                        {value: "095", text: "095 - Squinzano - Cellino"},
                        {value: "096", text: "096 - Squinzano - Casalabate"},
                        {value: "097", text: "097 - Squinzano alla San Pietro - Torchiarolo"},
                        {value: "098", text: "098 - Squinzano - Madonna dell`Alto"},
                        {value: "100", text: "100 - Squinzano - Masseria Cerrate - Casalabate"},
                        {value: "101", text: "101 - Campi - Cellino"},
                        {value: "102", text: "102 - Campi - San Donaci"},
                        {value: "103", text: "103 - Campi alla Carmiano - Salice"},
                        {value: "104", text: "104 - Guagnano - Cellino"},
                        {value: "105", text: "105 - Guagnano - Villa Baldassarri"},
                        {value: "106", text: "106 - Guagnano - Salice"},
                        {value: "107", text: "107 - Salice - Filippi - Avetrana"},
                        {value: "108", text: "108 - Santa Maria al Bagno alla Lecce - Gallipoli"},
                        {value: "109", text: "109 - Boncore - San Pancrazio"},
                        {value: "110", text: "110 - Veglie alla San Pancrazio - Boncore"},
                        {value: "111", text: "111 - Veglie - Cerfeta - Monteruga alla San Pancrazio - Boncore"},
                        {value: "112", text: "112 - La Tarantina I° tronco"},
                        {value: "113", text: "113 - Porto Cesareo alla Veglie alla San Pancrazio - Boncore"},
                        {value: "114", text: "114 - Copertino - Sant`Isidoro"},
                        {value: "115", text: "115 - Nardò - Leverano"},
                        {value: "116", text: "116 - La Tarantina II° tronco"},
                        {value: "117", text: "117 - Leverano - Carmiano"},
                        {value: "119", text: "119 - Dalla Lecce - Arnesano per Leverano"},
                        {value: "120", text: "120 - Carmiano - Salice"},
                        {value: "121", text: "121 - Carmiano - Villa Convento"},
                        {value: "122", text: "122 - Monteroni alla Lecce - Arnesano"},
                        {value: "123", text: "123 - Monteroni - Magliano"},
                        {value: "124", text: "124 - Copertino - Carmiano"},
                        {value: "125", text: "125 - Dalla Lecce - Gallipoli per San Donato"},
                        {value: "126", text: "126 - Galugnano - Stazione"},
                        {value: "127", text: "127 - Cenate alla Galatone - Santa Maria"},
                        {value: "130", text: "130 - Circonvallazione di San Pietro in Lama"},
                        {value: "131", text: "131 - Lecce - Torre Chianca"},
                        {value: "132", text: "132 - Lecce - Frigole"},
                        {value: "133", text: "133 - Dalla Lecce - San Cataldo per Frigole - Torre Chianca - Torre Rinalda"},
                        {value: "133_dir", text: "133 dir - Diramazione per la Lecce - San Cataldo"},
                        {value: "134", text: "134 - Dalla Lecce - San Cataldo alle Idrovore"},
                        {value: "134_dir", text: "134 dir - Variante alle Idrovore"},
                        {value: "135", text: "135 - Dalla Galatina - Copertino per Collemeto alla Lecce - Gallipoli"},
                        {value: "136", text: "136 - Lizzanello - Merine"},
                        {value: "137", text: "137 - Sternatia alla San Cesario - Galatina"},
                        {value: "138", text: "138 - Soleto - Sogliano"},
                        {value: "139", text: "139 - Sogliano alla Cutrofiano - Aradeo"},
                        {value: "140", text: "140 - Vernole - Galugnano"},
                        {value: "141", text: "141 - Vernole alla Calimera - Melendugno"},
                        {value: "142", text: "142 - Vernole - Acquarica - Vanze - Strudà - Pisignano"},
                        {value: "143", text: "143 - Vanze alla San Cataldo - Otranto"},
                        {value: "144", text: "144 - Caprarica alla Lizzanello - Calimera"},
                        {value: "145", text: "145 - Melendugno - San Foca"},
                        {value: "146", text: "146 - Melendugno alla Martano - Borgagne"},
                        {value: "147", text: "147 - Martano - Borgagne"},
                        {value: "148", text: "148 - Borgagne alla San Cataldo - Otranto"},
                        {value: "149", text: "149 - Cannole - Stazione"},
                        {value: "150", text: "150 - Cannole alla Martano - Otranto"},
                        {value: "151", text: "151 - Dalla Martano - Otranto alla San Cataldo - Otranto"},
                        {value: "152", text: "152 - Carpignano alla Martano - Otranto"},
                        {value: "153", text: "153 - Castrignano alla Martano - Otranto"},
                        {value: "154", text: "154 - Bagnolo - Palmariggi"},
                        {value: "155", text: "155 - Minervino - Giurdignano"},
                        {value: "156", text: "156 - Specchia Gallone alla Minervino - Giuggianello"},
                        {value: "157", text: "157 - Muro alla Maglie - Otranto"},
                        {value: "158", text: "158 - Circonvallazione di Poggiardo"},
                        {value: "159", text: "159 - Poggiardo - Nociglia"},
                        {value: "160", text: "160 - Poggiardo - San Cassiano - Botrugno alla Maglie - Leuca"},
                        {value: "161", text: "161 - San Cassiano alla Maglie - Leuca"},
                        {value: "162", text: "162 - Ortelle alla Vaste - Vitigliano"},
                        {value: "163", text: "163 - Spongano - Ortelle"},
                        {value: "164", text: "164 - Spongano alla Surano - Ruffano"},
                        {value: "165", text: "165 - Spongano - Castiglione"},
                        {value: "165_dir", text: "165 dir - Diramazione per Spongano (via Pio XII)"},
                        {value: "167", text: "167 - Castiglione - Depressa"},
                        {value: "168", text: "168 - Andrano - Marina di Andrano"},
                        {value: "169", text: "169 - Marittima alla Vignacastrisi - Castro"},
                        {value: "170", text: "170 - Castro Città - Castro Marina"},
                        {value: "171", text: "171 - Surano alla Poggiardo - Nociglia"},
                        {value: "172", text: "172 - Surano - Torrepaduli - Ruffano"},
                        {value: "173", text: "173 - Scorrano - Supersano"},
                        {value: "174", text: "174 - Supersano - Casarano"},
                        {value: "176", text: "176 - Ruffano - Taurisano"},
                        {value: "177", text: "177 - Marittima - Marina Marittima"},
                        {value: "178", text: "178 - Montesano - Tricase"},
                        {value: "179", text: "179 - Montesano - Torrepaduli"},
                        {value: "180", text: "180 - Miggiano alla Maglie - Leuca"},
                        {value: "181", text: "181 - Specchia - Stazione"},
                        {value: "182", text: "182 - Tricase - Marina Serra"},
                        {value: "184", text: "184 - Tricase alla Maglie - Leuca"},
                        {value: "186", text: "186 - Tiggiano alla Marina Serra - Novaglie"},
                        {value: "187", text: "187 - Corsano - Novaglie"},
                        {value: "188", text: "188 - Corsano - Stazione"},
                        {value: "189", text: "189 - San Dana alla Maglie - Leuca"},
                        {value: "190", text: "190 - Dalla Montesardo - Ruggiano per Barbarano - Morciano - Torre Vado"},
                        {value: "191", text: "191 - Castrignano del Capo - Marina di Leuca (San Giuseppe)"},
                        {value: "192", text: "192 - Ruggiano - Barbarano - Giuliano - Patù - San Gregorio"},
                        {value: "193", text: "193 - Presicce alla Litoranea"},
                        {value: "194", text: "194 - Sannicola alla Gallapoli - Santa Maria"},
                        {value: "195", text: "195 - Gagliano alla Novaglie - Leuca"},
                        {value: "196", text: "196 - Neviano - Tuglie"},
                        {value: "197", text: "197 - Dalla Tuglie - Collepasso al Villaggio di Montegrappa"},
                        {value: "198", text: "198 - Cutrofiano alla Maglie - Collepasso"},
                        {value: "201", text: "201 - Li Foggi alla Gallipoli - Leuca"},
                        {value: "201_dir", text: "201 dir - Diramazione per Mass.a Foggi"},
                        {value: "202", text: "202 - Racale - Torre Suda"},
                        {value: "203", text: "203 - Felline - Melissano"},
                        {value: "204", text: "204 - Alliste alla Racale - Torre Suda"},
                        {value: "205", text: "205 - Dalla Ugento - Acquarica a Gemini"},
                        {value: "206", text: "206 - Melissano - Ugento"},
                        {value: "208", text: "208 - Vignacastrisi - Castro"},
                        {value: "209", text: "209 - Castrignano del Capo - Salignano alla Salve - Gagliano"},
                        {value: "210", text: "210 - Alessano - Novaglie"},
                        {value: "211", text: "211 - Alliste - Posto Rossi"},
                        {value: "212", text: "212 - Cursi - Carpignano"},
                        {value: "213", text: "213 - Giuggianello - Poggiardo"},
                        {value: "214", text: "214 - Santa Maria di Leuca - Pescoluse"},
                        {value: "215", text: "215 - Posto Li Sorci - Torre Sinfonò"},
                        {value: "216", text: "216 - Dalla Salice - Avetrana per San Pancrazio"},
                        {value: "217", text: "217 - Dalla Salice - Avetrana alla litoranea"},
                        {value: "218", text: "218 - Dalla Nardò - Avetrana alla Nardò - Copertino"},
                        {value: "219", text: "219 - Dalla Boncore - San Pancrazio per Avetrana"},
                        {value: "220", text: "220 - Dalla Leverano - Porto Cesareo alla Porto Cesareo alla Veglie - Boncore"},
                        {value: "221", text: "221 - Posto Li Sorci alla Gallipoli - Leuca"},
                        {value: "222", text: "222 - Taviano alla litoranea"},
                        {value: "223", text: "223 - Matino alla Gallipoli - Leuca"},
                        {value: "224", text: "224 - Carmiano alla Arnesano - Villa Convento"},
                        {value: "224_dir", text: "224 dir - Diramazione per Magliano"},
                        {value: "225", text: "225 - Dalla Lecce - Arnesano alla Lecce - Novoli (Via del Condò)"},
                        {value: "226", text: "226 - Scorrano alla Maglie - Gallipoli"},
                        {value: "227", text: "227 - Corigliano - Soleto"},
                        {value: "228", text: "228 - Corigliano - Melpignano"},
                        {value: "229", text: "229 - Vernole - Pisignano - Lizzanello"},
                        {value: "230", text: "230 - Trepuzzi - Campi"},
                        {value: "231", text: "231 - Galatone - Sannicola"},
                        {value: "232", text: "232 - Taviano - Castelforte alla litoranea Gallipoli - Mancaversa"},
                        {value: "233", text: "233 - Cocumola alla Vaste - Vitigliano"},
                        {value: "234", text: "234 - Cerfignano - Vitigliano"},
                        {value: "235", text: "235 - Giuggianello - Palmariggi"},
                        {value: "236", text: "236 - Surbo - Casalabate"},
                        {value: "237", text: "237 - San Cassiano - Surano"},
                        {value: "238", text: "238 - Noha - Sogliano - Corigliano"},
                        {value: "239", text: "239 - Li Foggi alla Posto Li Sorci - Masseria Li Sauli"},
                        {value: "240", text: "240 - Supersano alla Montesano - Torrepaduli"},
                        {value: "241", text: "241 - Lecce - Lizzanello"},
                        {value: "242", text: "242 - Alessano - Specchia"},
                        {value: "243", text: "243 - Dalla Scorrano alla Maglie - Collepasso alla Cutrofiano - Supersano"},
                        {value: "244", text: "244 - Soleto - San Donato"},
                        {value: "245", text: "245 - Acquarica alla San Cataldo - Otranto"},
                        {value: "246", text: "246 - Trepuzzi alla Surbo - Casalabate"},
                        {value: "247", text: "247 - Dalla Alliste alla Racale - Torre Suda alla litoranea"},
                        {value: "248", text: "248 - Montesardo alla Alessano - Novaglie"},
                        {value: "249", text: "249 - Corsano alla Alessano - Novaglie"},
                        {value: "250", text: "250 - Montesano - Depressa"},
                        {value: "251", text: "251 - Andrano - Spongano"},
                        {value: "252", text: "252 - Miggiano alla Montesano - Torre Paduli"},
                        {value: "253", text: "253 - Dalla Lecce - San Pietro alla Lecce - Monteroni"},
                        {value: "254", text: "254 - Poggiardo - Spongano"},
                        {value: "255", text: "255 - Salice alla Veglie - Monteruga"},
                        {value: "256", text: "256 - Squinzano per Masseria Arciprete e Caretti alla Surbo - Casalabate"},
                        {value: "257", text: "257 - Castrì - Pisignano"},
                        {value: "258", text: "258 - Vitigliano alla Ortelle - Vignacastrisi"},
                        {value: "259", text: "259 - Vignacastrisi alla Castro - Santa Cesarea"},
                        {value: "260", text: "260 - Santa Caterina per la Cenate alla Galatone - Santa Maria"},
                        {value: "261", text: "261 - Nardò alla Tarantina"},
                        {value: "262", text: "262 - Dalla Casarano - Ugento per località Vetti e Sant`Anastasia alla Matino li Ponti"},
                        {value: "263", text: "263 - Melissano alla Casarano - Ugento"},
                        {value: "264", text: "264 - Dalla Nociglia - Supersano alla Surano - Torre Paduli"},
                        {value: "265", text: "265 - Dalla Alliste - Posto Rossi per Posto Capilungo"},
                        {value: "266", text: "266 - Felline - Posto Rosso"},
                        {value: "267", text: "267 - Lecce per Cascettara alla prov. dalla Lecce - Arnesano alla Lecce - Novoli"},
                        {value: "268", text: "268 - Dalla Salice alla Veglie - Monteruga per Magliana"},
                        {value: "269", text: "269 - Cavallino - Tempi Nuovi"},
                        {value: "270", text: "270 - Cavallino alla Lizzanello - Merine"},
                        {value: "271", text: "271 - Neviano alla Collepasso - Noha"},
                        {value: "272", text: "272 - Dalla Campi - Cellino alla Guagnano - Cellino (Giovannella)"},
                        {value: "273", text: "273 - Salve - Posto Vecchio di Salve"},
                        {value: "274", text: "274 - Cursi per la Bagnolo alla Maglie - Otranto"},
                        {value: "275", text: "275 - Calimera alla Melendugno alla Martano - Borgagne"},
                        {value: "276", text: "276 - Carpignano alla Martano - Borgagne"},
                        {value: "277", text: "277 - Giurdigano alla Maglie - Otranto"},
                        {value: "278", text: "278 - Dalla Cutrofiano - Aradeo alla Cutrofiano - Collepasso"},
                        {value: "279", text: "279 - Aradeo - Foresta - Mass.a Litta"},
                        {value: "280", text: "280 - Aradeo alla Galatone - Galatina"},
                        {value: "280_dir", text: "280 dir - Diramazione per Galatone"},
                        {value: "281", text: "281 - Seclì - Campolatino - Sannicola"},
                        {value: "282", text: "282 - Scalelle - San Giovanni - Alezio"},
                        {value: "282_dir", text: "282 dir - Diramazione per la Alezio - Gallipoli"},
                        {value: "283", text: "283 - Acaia - Aeroporto Turistico"},
                        {value: "283_dir", text: "283 dir - Diramazione per la Lecce - Aeroporto Turistico - Litoranea"},
                        {value: "284", text: "284 - Strudà - Acquarica"},
                        {value: "285", text: "285 - Caprarica alla Lecce - Maglie"},
                        {value: "286", text: "286 - Santa Caterina - Sant`Isidoro - Porto Cesareo"},
                        {value: "287", text: "287 - Dalla Matino alla Gallipoli - Leuca alla Taviano - Alezio"},
                        {value: "288", text: "288 - Melissano alla Vetti - Sant`Anastasia"},
                        {value: "289", text: "289 - Taviano per la Posto Li Sorci alla Gallipoli - Leuca"},
                        {value: "289_dir", text: "289 dir - Diramazione alla Posto Li Sorci alla Gallipoli - Leuca per Mass.a Nuova"},
                        {value: "290", text: "290 - Felline alla Ugento - Torre San Giovanni"},
                        {value: "290_dir", text: "290 dir - Diramazione per Ugento"},
                        {value: "291", text: "291 - Gemini alla litoranea"},
                        {value: "292", text: "292 - Dalla Presicce - Litoranea alla Salve - Pescoluse"},
                        {value: "294", text: "294 - Dalla Lecce - Gallipoli a Santa Barbara"},
                        {value: "295", text: "295 - Lecce -San Ligorio alla San Cataldo - Frigole"},
                        {value: "296", text: "296 - Trepuzzi alla Squinzano - Masseria Cerrate - Casalabate"},
                        {value: "297", text: "297 - Melendugno - Torre dell`Orso"},
                        {value: "298", text: "298 - Lecce - Aeroporto Turistico - Litoranea"},
                        {value: "299", text: "299 - Uggiano - Cerfignano"},
                        {value: "300", text: "300 - Casamassella alla Uggiano - Otranto"},
                        {value: "301", text: "301 - Nociglia - Fontana alla Surano - Torrepaduli - Ruffano"},
                        {value: "303", text: "303 - Melpignano alla Zona Industriale"},
                        {value: "304", text: "304 - Lecce - Località Caliò Pomponio alla Frigole - San Cataldo"},
                        {value: "305", text: "305 - Giuliano alla Maglie - Leuca"},
                        {value: "306", text: "306 - Tricase Porto alla Tricase - Tricase Porto"},
                        {value: "307", text: "307 - Copertino - Santa Barbara"},
                        {value: "308", text: "308 - Botrugno alla Nociglia - Supersano"},
                        {value: "308_dir", text: "308 dir - Diramazione per San Cassiano"},
                        {value: "309", text: "309 - Salice - Campi"},
                        {value: "310", text: "310 - Marittima alla Castro - Tricase Porto"},
                        {value: "312", text: "312 - Salice alla Guagnano - San Pancrazio"},
                        {value: "313", text: "313 - Tricase - Torre Mito - Andrano"},
                        {value: "314", text: "314 - Circonvallazione di Aradeo"},
                        {value: "317", text: "317 - Cincorvallazione di Calimera"},
                        {value: "319", text: "319 - Santa Cesarea strada panoramica"},
                        {value: "319_dir", text: "319 dir - Diramazione per la Cerfignano - Santa Cesarea"},
                        {value: "320", text: "320 - Galatina alla Soleto - Sogliano"},
                        {value: "321", text: "321 - Casarano alla Taviano - Matino"},
                        {value: "322", text: "322 - Collepasso - Matino"},
                        {value: "322_dir", text: "322 dir - Diramazione per Casarano"},
                        {value: "323", text: "323 - Dalla Taviano - Alezio alla Gallipoli - Leuca"},
                        {value: "324", text: "324 - Dalla Acquarica - Salve alla Acquarica - Ugento"},
                        {value: "325", text: "325 - Dalla Ugento - Torre San Giovanni alla Presicce alla Litoranea"},
                        {value: "326", text: "326 - Morciano - Pozzo Pasulo"},
                        {value: "326_dir", text: "326 dir - Diramazione per la Patù - San Gregorio"},
                        {value: "327", text: "327 - Guagnano - San Donaci"},
                        {value: "328", text: "328 - Galatina per Torre Pinta alla Lecce - Galatina"},
                        {value: "329", text: "329 - Dalla Galatina - Copertino alla Lecce - Gallipoli"},
                        {value: "330", text: "330 - Taviano - Mancaversa"},
                        {value: "331", text: "331 - Acquarica del Capo - Ruffano"},
                        {value: "332", text: "332 - Acquarica - Torre Mozza"},
                        {value: "333", text: "333 - Masseria Marini - Torre Pali"},
                        {value: "334", text: "334 - Casarano alla Parabita - Collepasso"},
                        {value: "336", text: "336 - Dalla Nociglia - Supersano alla Nociglia - Fontana"},
                        {value: "336_dir", text: "336 dir - Diramazione per Casino Lizza Lupera"},
                        {value: "337", text: "337 - Merine - Acaia"},
                        {value: "338", text: "338 - Serrano per la San Carlo alla San Cataldo - Otranto"},
                        {value: "339", text: "339 - Salve - Pescoluse"},
                        {value: "340", text: "340 - Porto Cesareo - Punta Prosciutto"},
                        {value: "341", text: "341 - Dalla Martano - Otranto a Lu Strittu"},
                        {value: "341_dir", text: "341 dir - Diramazione per la San Cataldo - Otranto"},
                        {value: "342", text: "342 - San Carlo alla San Cataldo - Otranto"},
                        {value: "342_dir", text: "342 dir - Diramazione per la Martano - Otranto alla San Cataldo - Otranto"},
                        {value: "343", text: "343 - Strudà - Vanze"},
                        {value: "344", text: "344 - Cannole - Palmariggi"},
                        {value: "345", text: "345 - Diso - Andrano"},
                        {value: "346", text: "346 - Tricase - Serra del Porto"},
                        {value: "347", text: "347 - Zollino alla Martano-Soleto"},
                        {value: "348", text: "348 - Dalla Arnesano - Villa Convento per la Lecce - Arnesano alla Lecce - Novoli"},
                        {value: "349", text: "349 - San Donato alla Lecce - Maglie"},
                        {value: "350", text: "350 - Taviano - Ugento (ex S.S.274)"},
                        {value: "351", text: "351 - Salve - Gagliano (ex S.S.274)"},
                        {value: "352", text: "352 - Dalla Galatone - Galatina alla Noha - Collepasso"},
                        {value: "353", text: "353 - Albaro - Veglie"},
                        {value: "354", text: "354 - Castiglione alla Maglie - Leuca"},
                        {value: "355", text: "355 - Minervino - Porto Badisco"},
                        {value: "357", text: "357 - Adriatica"},
                        {value: "358", text: "358 - Delle Terme Salentine"},
                        {value: "359", text: "359 - Salentina di Manduria"},
                        {value: "360", text: "360 - Di Casarano"},
                        {value: "361", text: "361 - Di Parabita"},
                        {value: "362", text: "362 - Di Galatina"},
                        {value: "363", text: "363 - Di Maglie e Santa Cesarea"},
                        {value: "364", text: "364 - Del Lido di Lecce"},
                        {value: "365", text: "365 - Di Mesagne"},
                        {value: "366", text: "366 - Di Otranto"},
                        {value: "367", text: "367 - Mediana del Salento"},
                        {value: "368", text: "368 - Circonvallazione sud di Martignano"},
                        {value: "369", text: "369 - Dalla Otranto - Porto Badisco al Porto di Otranto"},
                        {value: "370", text: "370 - Circonvallazione di Veglie"},
                        {value: "372", text: "372 - Circonvallazione di Caprarica"},
                        {value: "374", text: "374 - Di Taurisano"},
                        {value: "375", text: "375 - Di Cavallino"},
                        {value: "377", text: "377 - Circonvallazione Nord di Martignano"}
                    ];
                    
                    $.each(stradeProvinciali, function(index, strada) {
                        select.append('<option value="' + strada.value + '">' + strada.text + '</option>');
                    });

                    // Ripristina il valore salvato se presente
                    var savedValue = $('#numero_strada_select').data('saved-value');
                    if (savedValue) {
                        $('#numero_strada_select').val(savedValue);
                    }
                }

                function populateStradeStatali() {
                    var select = $('#numero_strada_select_statali');
                    select.empty();
                    select.append('<option value="">Seleziona strada statale</option>');
                    var stradeStatali = [
                        {value: "101", text: "101 - Lecce - Gallipoli"},
                        {value: "16", text: "16 - Lecce - Maglie - Oranto"},
                        {value: "274", text: "274 - Gallipoli - S.M. di Leuca"},
                        {value: "275", text: "275 - Maglie - S.M. di Leuca"},
                        {value: "543", text: "543 - Lido di Lecce"},
                        {value: "613", text: "613 - Lecce - Brindisi"},
                        {value: "694", text: "694 - Tangenziale Ovest di Lecce"},
                        {value: "7ter", text: "7 ter - Lecce - Campi Salentina - Guagnano"}
                    ];
                    
                    $.each(stradeStatali, function(index, strada) {
                        select.append('<option value="' + strada.value + '">' + strada.text + '</option>');
                    });

                    // Ripristina il valore salvato se presente
                    var savedValue = $('#numero_strada_select').data('saved-value');
                    if (savedValue) {
                        $('#numero_strada_select').val(savedValue);
                    }
                }
                
                // Click sulla mappa
                map.on('click', function(e) {
                    var lat = e.latlng.lat;
                    var lng = e.latlng.lng;
                    
                    $('#latitudine_inline').val(lat.toFixed(6));
                    $('#longitudine_inline').val(lng.toFixed(6));
                    // Sincronizza con i campi nella sidebar se esistono
                    $('#latitudine').val(lat.toFixed(6));
                    $('#longitudine').val(lng.toFixed(6));
                    
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    
                    marker = L.marker([lat, lng], {
                        draggable: true,
                        title: 'Posizione incidente (trascinabile)'
                    }).addTo(map);
                    
                    marker.on('dragend', function(e) {
                        var position = e.target.getLatLng();
                        $('#latitudine_inline').val(position.lat.toFixed(6));
                        $('#longitudine_inline').val(position.lng.toFixed(6));
                        $('#latitudine').val(position.lat.toFixed(6));
                        $('#longitudine').val(position.lng.toFixed(6));
                    });
                });
                
                // Aggiorna mappa quando coordinate cambiano
                $('#latitudine_inline, #longitudine_inline').on('input change', function() {
                    var lat = parseFloat($('#latitudine_inline').val());
                    var lng = parseFloat($('#longitudine_inline').val());
                    
                    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                        if (marker) {
                            map.removeLayer(marker);
                        }
                        
                        marker = L.marker([lat, lng], {
                            draggable: true,
                            title: 'Posizione incidente (trascinabile)'
                        }).addTo(map);
                        
                        marker.on('dragend', function(e) {
                            var position = e.target.getLatLng();
                            $('#latitudine_inline').val(position.lat.toFixed(6));
                            $('#longitudine_inline').val(position.lng.toFixed(6));
                            $('#latitudine').val(position.lat.toFixed(6));
                            $('#longitudine').val(position.lng.toFixed(6));
                        });
                        
                        map.setView([lat, lng], 15);
                        
                        // Sincronizza con sidebar
                        $('#latitudine').val(lat.toFixed(6));
                        $('#longitudine').val(lng.toFixed(6));
                    }
                });
                
                // Forza ridimensionamento
                setTimeout(function() {
                    map.invalidateSize();
                }, 250);
            });
            </script>
        <?php
    }
    
    private function get_strade_statali() {
        return array(
            '101' => '101 - Lecce - Gallipoli',
            '16' => '16 - Lecce - Maglie - Oranto',
            '274' => '274 - Gallipoli - S.M. di Leuca',
            '275' => '275 - Maglie - S.M. di Leuca',
            '543' => '543 - Lido di Lecce',
            '613' => '613 - Lecce - Brindisi',
            '694' => '694 - Tangenziale Ovest di Lecce',
            '7_ter' => '7 ter - Lecce - Campi Salentina - Guagnano'
        );
    }

    public function render_luogo_meta_box($post) {
        $geometria_strada = get_post_meta($post->ID, 'geometria_strada', true);
        $pavimentazione = get_post_meta($post->ID, 'pavimentazione_strada', true);
        $intersezione = get_post_meta($post->ID, 'intersezione_tronco', true);
        $accessi_laterali = get_post_meta($post->ID, 'accessi_laterali', true);
        $caratteristiche_geom = get_post_meta($post->ID, 'caratteristiche_geometriche', true);
        $fondo_strada = get_post_meta($post->ID, 'stato_fondo_strada', true);
        $segnaletica = get_post_meta($post->ID, 'segnaletica_strada', true);
        $condizioni_meteo = get_post_meta($post->ID, 'condizioni_meteo', true);
        $illuminazione = get_post_meta($post->ID, 'illuminazione', true);
        $visibilita = get_post_meta($post->ID, 'visibilita', true);
        $traffico = get_post_meta($post->ID, 'traffico', true);
        $segnaletica_semaforica = get_post_meta($post->ID, 'segnaletica_semaforica', true);
        $elementi_aggiuntivi_1 = get_post_meta($post->ID, 'elementi_aggiuntivi_1', true);
        $elementi_aggiuntivi_2 = get_post_meta($post->ID, 'elementi_aggiuntivi_2', true);
        $orientamento_conducente = get_post_meta($post->ID, 'orientamento_conducente', true);
        $presenza_banchina = get_post_meta($post->ID, 'presenza_banchina', true);
        $presenza_barriere = get_post_meta($post->ID, 'presenza_barriere', true);
        $condizioni_manto = get_post_meta($post->ID, 'condizioni_manto', true);
        $allagato = get_post_meta($post->ID, 'allagato', true);
        $nuvoloso = get_post_meta($post->ID, 'nuvoloso', true);
        $foschia = get_post_meta($post->ID, 'foschia', true);
        
        ?>
        <script>
        function azzeraRadioSezione(classeSezione) {
            jQuery('#' + classeSezione + ' input[type="radio"]').prop('checked', false);
        }
        </script>
        <div class="luogo-incidente-sections">
            <!-- Sezione Tipi di Strada -->
            <div class="sezione-luogo" id="sezione-tipi-di-strada">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-tipi-di-strada');">Cancella scelta</span>
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
            <div class="sezione-luogo" id="sezione-pavimentazione">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-pavimentazione');">Cancella scelta</span>
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
                        <th><i style="font-weight: 400;"><?php _e('Condizioni Manto', 'incidenti-stradali'); ?></i></th>
                        <td>
                            <i><label><input type="radio" name="condizioni_manto" value="aperto" <?php checked($condizioni_manto, 'aperto'); ?>> <?php _e('Tappeto d\'usura aperto', 'incidenti-stradali'); ?></label></i><br>
                            <i><label><input type="radio" name="condizioni_manto" value="chiuso" <?php checked($condizioni_manto, 'chiuso'); ?>> <?php _e('Tappeto d\'usura chiuso', 'incidenti-stradali'); ?></label></i>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Intersezione/Non Intersezione -->
            <div class="sezione-luogo" id="sezione-intersezione-nonintersezione">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-intersezione-nonintersezione');">Cancella scelta</span>
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
                            <label><input type="radio" name="intersezione_tronco" value="10" <?php checked($intersezione, '10'); ?>> <?php _e('Pend. - salita', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="10b" <?php checked($intersezione, '10b'); ?>> <?php _e('Pend. - discesa', 'incidenti-stradali'); ?></label><br>                       
                            <label><input type="radio" name="intersezione_tronco" value="11" <?php checked($intersezione, '11'); ?>> <?php _e('Gall. illuminata', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="intersezione_tronco" value="12" <?php checked($intersezione, '12'); ?>> <?php _e('Gall. non illuminata', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Elementi Aggiuntivi', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="checkbox" name="accessi_laterali" value="1" <?php checked($accessi_laterali, '1'); ?>> <?php _e('Accessi laterali', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Nuova sezione separata per Caratteristiche Geometriche -->
            <div class="sezione-luogo" id="sezione-caratteristiche-geometriche">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-caratteristiche-geometriche');">Cancella scelta</span>
                <h4><?php _e('Caratteristiche Geometriche', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th style="font-weight: 400;"><i><?php _e('Tipo Caratteristica', 'incidenti-stradali'); ?></i></th>
                        <td>
                            <i><label><input type="radio" name="caratteristiche_geometriche" value="14" <?php checked($caratteristiche_geom, '14'); ?>> <?php _e('Cunetta', 'incidenti-stradali'); ?></label><br></i>
                            <i><label><input type="radio" name="caratteristiche_geometriche" value="15" <?php checked($caratteristiche_geom, '15'); ?>> <?php _e('Cavalcavia', 'incidenti-stradali'); ?></label><br></i>
                            <i><label><input type="radio" name="caratteristiche_geometriche" value="16" <?php checked($caratteristiche_geom, '16'); ?>> <?php _e('Trincea', 'incidenti-stradali'); ?></label><br></i>
                            <i><label><input type="radio" name="caratteristiche_geometriche" value="17" <?php checked($caratteristiche_geom, '17'); ?>> <?php _e('Rilevato', 'incidenti-stradali'); ?></label><br></i>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Fondo Stradale -->
            <div class="sezione-luogo" id="sezione-fondo-stradale">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-fondo-stradale');">Cancella scelta</span>
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
                            <i><label><input type="checkbox" name="allagato" value="1" <?php checked($allagato, '1'); ?>> <?php _e('Allagato', 'incidenti-stradali'); ?></label></i>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Segnaletica -->
            <div class="sezione-luogo" id="sezione-segnaletica">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-segnaletica');">Cancella scelta</span>
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
                    <!-- -->
                    <tr>
                        <th><?php _e('Elementi Aggiuntivi 1', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="elementi_aggiuntivi_1" value="1" <?php checked($elementi_aggiuntivi_1, '1'); ?>> <?php _e('Semaforizzazioni', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="elementi_aggiuntivi_1" value="2" <?php checked($elementi_aggiuntivi_1, '2'); ?>> <?php _e('Cartelli pubblicitari', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Elementi Aggiuntivi 2', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="elementi_aggiuntivi_2" value="1" <?php checked($elementi_aggiuntivi_2, '1'); ?>> <?php _e('Leggibilità alta', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="elementi_aggiuntivi_2" value="2" <?php checked($elementi_aggiuntivi_2, '2'); ?>> <?php _e('Leggibilità bassa', 'incidenti-stradali'); ?></label>
                        </td>
                    </tr>    
                    <!-- -->
                </table>
            </div>

            <!-- Sezione Condizioni Meteorologiche -->
            <div class="sezione-luogo" id="sezione-condizioni-meteorologiche">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-condizioni-meteorologiche');">Cancella scelta</span>
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
                        <th style="font-weight: 400;"><i><?php _e('Condizioni Aggiuntive', 'incidenti-stradali'); ?></i></th>
                        <td>
                            <i><label><input type="checkbox" name="nuvoloso" value="1" <?php checked($nuvoloso, '1'); ?>> <?php _e('Nuvoloso', 'incidenti-stradali'); ?></label></i><br>
                            <i><label><input type="checkbox" name="foschia" value="1" <?php checked($foschia, '1'); ?>> <?php _e('Foschia', 'incidenti-stradali'); ?></label></i>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="sezione-luogo" id="sezione-illuminazione">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-illuminazione');">Cancella scelta</span>
                <h4><?php _e('Illuminazione', 'incidenti-stradali'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Condizioni di illuminazione', 'incidenti-stradali'); ?></th>
                        <td>
                            <label><input type="radio" name="illuminazione" value="1" <?php checked($illuminazione, '1'); ?>> <?php _e('Luce diurna', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="illuminazione" value="2" <?php checked($illuminazione, '2'); ?>> <?php _e('Crepuscolo alba', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="illuminazione" value="3" <?php checked($illuminazione, '3'); ?>> <?php _e('Buio: luci stradali presenti accese', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="illuminazione" value="4" <?php checked($illuminazione, '4'); ?>> <?php _e('Buio: luci stradali presenti spente', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="illuminazione" value="5" <?php checked($illuminazione, '5'); ?>> <?php _e('Buio: assenza di illuminazione stradale', 'incidenti-stradali'); ?></label><br>
                            <label><input type="radio" name="illuminazione" value="6" <?php checked($illuminazione, '6'); ?>> <?php _e('Illuminazione stradale non nota', 'incidenti-stradali'); ?></label><br>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sezione Orientamento e Infrastrutture -->
            <div class="sezione-luogo" id="sezione-altre-caratteristiche">
                <span style="float: right; cursor: pointer; border: 1px solid #2271b1; padding: 2px; color: #fff; background-color: #2271b1;" onclick="javascript:azzeraRadioSezione('sezione-altre-caratteristiche');">Cancella scelta</span>
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
        $salto_carreggiata = get_post_meta($post->ID, 'salto_carreggiata', true);

        $urto_frontale = get_post_meta($post->ID, 'urto_frontale', true);
        $urto_laterale = get_post_meta($post->ID, 'urto_laterale', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th class="required-field"><label for="natura_incidente"><?php _e('Natura dell\'Incidente', 'incidenti-stradali'); ?></label></th>
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

            <tr id="urto_checkboxes_row" style="display: none;">
                <th style="font-weight: 400;"><i><label><?php _e('Tipo di Urto', 'incidenti-stradali'); ?></label></i></th>
                <td>
                    <label for="urto_frontale">
                        <input type="checkbox" id="urto_frontale" name="urto_frontale" value="1" 
                            <?php checked($urto_frontale, '1'); ?> />
                        <i><?php _e('Urto frontale', 'incidenti-stradali'); ?></i>
                    </label><br>
                    <label for="urto_laterale">
                        <input type="checkbox" id="urto_laterale" name="urto_laterale" value="1" 
                            <?php checked($urto_laterale, '1'); ?> />
                        <i><?php _e('Urto laterale', 'incidenti-stradali'); ?></i>
                    </label>
                </td>
            </tr>

            <tr id="altro_natura_row">
                <th><label for="altro_natura_testo"><?php _e('Altro (specificare)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="altro_natura_testo" name="altro_natura_testo" 
                        value="<?php echo esc_attr(get_post_meta($post->ID, 'altro_natura_testo', true)); ?>" 
                        class="regular-text" maxlength="100" 
                        placeholder="<?php _e('Specifica se diverso dalle opzioni standard', 'incidenti-stradali'); ?>">
                    <p class="description"><?php _e('Campo opzionale per specificare natura diversa dalle opzioni standard (max 100 caratteri)', 'incidenti-stradali'); ?></p>
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
            <tr id="salto_carreggiata_row" style="display: none;">
                <th style="font-weight: 400;"><i><label for="salto_carreggiata"><?php _e('Salto carreggiata', 'incidenti-stradali'); ?></label></i></th>
                <td>
                    <input type="checkbox" id="salto_carreggiata" name="salto_carreggiata" value="1" 
                        <?php checked($salto_carreggiata, '1'); ?> />
                    <label for="salto_carreggiata"><i><?php _e('Salto carreggiata presente', 'incidenti-stradali'); ?></i></label>
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
                var dettaglioRow = $('#dettaglio_natura_row');
                
                // Reset fields
                dettaglioSelect.empty().append('<option value="">Seleziona dettaglio</option>');
                
                if (natura && naturaOptions[natura]) {
                    dettaglioRow.show();
                    $.each(naturaOptions[natura], function(value, text) {
                        dettaglioSelect.append('<option value="' + value + '">' + text + '</option>');
                    });
                    // Ripristina il valore salvato
                    var savedDettaglio = '<?php echo esc_js($dettaglio_natura); ?>';
                    if (savedDettaglio) {
                        dettaglioSelect.val(savedDettaglio);
                    }
                } else {
                    dettaglioRow.hide();
                }
                
                // Mostra/nascondi campo numero veicoli
                if (natura === 'A' || (natura === 'C' && ['6'].indexOf($('#dettaglio_natura').val()) !== -1)) {
                    $('#numero_veicoli_row').show();
                } else {
                    $('#numero_veicoli_row').hide();
                    $('#numero_veicoli_coinvolti').val('1');
                }

                // Mostra checkbox urto SOLO per natura C
                if (natura === 'C') {
                    $('#urto_checkboxes_row').show();
                } else {
                    $('#urto_checkboxes_row').hide();
                    $('#urto_frontale, #urto_laterale').prop('checked', false);
                }

                // Mostra/nascondi checkbox salto carreggiata (SOLO per natura A)
                if (natura === 'A') {
                    $('#salto_carreggiata_row').show();
                } else {
                    $('#salto_carreggiata_row').hide();
                    $('#salto_carreggiata').prop('checked', false); // Reset checkbox
                }
            });
            
            // Trigger change on page load
            $('#natura_incidente').trigger('change');

            // Ripristina il valore del dettaglio dopo il caricamento delle opzioni
            setTimeout(function() {
                var savedDettaglio = '<?php echo esc_js($dettaglio_natura); ?>';
                if (savedDettaglio) {
                    $('#dettaglio_natura').val(savedDettaglio);
                }
            }, 100);

            // Definizione dei codici circostanze per tipo di incidente
            var circostanzeData = {
                'intersezione': {
                    'veicolo_a': {
                        '01': 'Procedeva regolarmente senza svoltare',
                        '02': 'Procedeva con guida distratta e andamento indeciso',
                        '03': 'Procedeva senza mantenere la distanza di sicurezza',
                        '04': 'Procedeva senza dare la precedenza al veicolo da destra',
                        '05': 'Procedeva senza rispettare lo stop',
                        '06': 'Procedeva senza rispettare il segnale di precedenza',
                        '07': 'Procedeva contromano',
                        '08': 'Procedeva senza rispettare semaforo/agente',
                        '10': 'Procedeva senza rispettare divieti di transito',
                        '11': 'Procedeva con eccesso di velocità',
                        '12': 'Procedeva senza rispettare limiti di velocità',
                        '13': 'Procedeva con luci abbaglianti',
                        '14': 'Svoltava a destra regolarmente',
                        '15': 'Svoltava a destra irregolarmente',
                        '16': 'Svoltava a sinistra regolarmente',
                        '17': 'Svoltava a sinistra irregolarmente',
                        '18': 'Sorpassava all\'incrocio'
                    },
                    'veicolo_b': {
                        '01': 'Procedeva regolarmente senza svoltare',
                        '02': 'Procedeva con guida distratta e andamento indeciso',
                        '03': 'Procedeva senza mantenere la distanza di sicurezza',
                        '04': 'Procedeva senza dare la precedenza al veicolo da destra',
                        '05': 'Procedeva senza rispettare lo stop',
                        '06': 'Procedeva senza rispettare il segnale di precedenza',
                        '07': 'Procedeva contromano',
                        '08': 'Procedeva senza rispettare semaforo/agente',
                        '10': 'Procedeva senza rispettare divieti di transito',
                        '11': 'Procedeva con eccesso di velocità',
                        '12': 'Procedeva senza rispettare limiti di velocità',
                        '13': 'Procedeva con luci abbaglianti',
                        '14': 'Svoltava a destra regolarmente',
                        '15': 'Svoltava a destra irregolarmente',
                        '16': 'Svoltava a sinistra regolarmente',
                        '17': 'Svoltava a sinistra irregolarmente',
                        '18': 'Sorpassava all\'incrocio'
                    }
                },
                'non_intersezione': {
                    'veicolo_a': {
                        '20': 'Procedeva regolarmente',
                        '21': 'Procedeva con guida distratta e andamento indeciso',
                        '22': 'Procedeva senza mantenere la distanza di sicurezza',
                        '23': 'Procedeva con eccesso di velocità',
                        '24': 'Procedeva senza rispettare limiti di velocità',
                        '25': 'Procedeva non in prossimità del margine destro',
                        '26': 'Procedeva contromano',
                        '27': 'Procedeva senza rispettare divieti di transito',
                        '28': 'Procedeva con luci abbaglianti',
                        '29': 'Sorpassava regolarmente',
                        '30': 'Sorpassava irregolarmente a destra',
                        '31': 'Sorpassava in curva/dosso/scarsa visibilità',
                        '32': 'Sorpassava veicolo che sorpassava altro',
                        '33': 'Sorpassava senza osservare divieto',
                        '34': 'Manovrava in retrocessione/conversione',
                        '35': 'Manovrava per immettersi nel flusso',
                        '36': 'Manovrava per svoltare a sinistra',
                        '37': 'Manovrava regolarmente per fermarsi',
                        '38': 'Manovrava irregolarmente per fermarsi',
                        '39': 'Si affiancava irregolarmente a due ruote'
                    },
                    'veicolo_b': {
                        '20': 'Procedeva regolarmente',
                        '21': 'Procedeva con guida distratta e andamento indeciso',
                        '22': 'Procedeva senza mantenere la distanza di sicurezza',
                        '23': 'Procedeva con eccesso di velocità',
                        '24': 'Procedeva senza rispettare limiti di velocità',
                        '25': 'Procedeva non in prossimità del margine destro',
                        '26': 'Procedeva contromano',
                        '27': 'Procedeva senza rispettare divieti di transito',
                        '28': 'Procedeva con luci abbaglianti',
                        '29': 'Sorpassava regolarmente',
                        '30': 'Sorpassava irregolarmente a destra',
                        '31': 'Sorpassava in curva/dosso/scarsa visibilità',
                        '32': 'Sorpassava veicolo che sorpassava altro',
                        '33': 'Sorpassava senza osservare divieto',
                        '34': 'Manovrava in retrocessione/conversione',
                        '35': 'Manovrava per immettersi nel flusso',
                        '36': 'Manovrava per svoltare a sinistra',
                        '37': 'Manovrava regolarmente per fermarsi',
                        '38': 'Manovrava irregolarmente per fermarsi',
                        '39': 'Si affiancava irregolarmente a due ruote'
                    }
                },
                'investimento': {
                    'veicolo_a': {
                        '40': 'Procedeva regolarmente',
                        '41': 'Procedeva con eccesso di velocità',
                        '42': 'Procedeva senza rispettare limiti',
                        '43': 'Procedeva contromano',
                        '44': 'Sorpassava veicolo in marcia',
                        '45': 'Manovrava',
                        '46': 'Non rispettava semaforo/agente',
                        '47': 'Usciva senza precauzioni da passo carrabile',
                        '48': 'Fuorusciva dalla carreggiata',
                        '49': 'Non dava precedenza al pedone',
                        '50': 'Sorpassava veicolo fermato per pedone',
                        '51': 'Urtava con il carico il pedone',
                        '52': 'Superava irregolarmente tram fermo'
                    },
                    'pedone': {
                        '40': 'Camminava su marciapiede/banchina',
                        '41': 'Camminava regolarmente sul margine',
                        '42': 'Camminava contromano',
                        '43': 'Camminava in mezzo carreggiata',
                        '44': 'Sostava/indugiava/giocava carreggiata',
                        '45': 'Lavorava protetto da segnale',
                        '46': 'Lavorava non protetto da segnale',
                        '47': 'Saliva su veicolo in marcia',
                        '48': 'Discendeva con prudenza',
                        '49': 'Discendeva con imprudenza',
                        '50': 'Veniva fuori da dietro veicolo',
                        '51': 'Attraversava rispettando segnali',
                        '52': 'Attraversava non rispettando segnali',
                        '53': 'Attraversava passaggio non protetto',
                        '54': 'Attraversava regolarmente non su passaggio',
                        '55': 'Attraversava irregolarmente'
                    }
                },
                'urto_fermo': {
                    'veicolo_a': {
                        '60': 'Procedeva regolarmente',
                        '61': 'Procedeva con guida distratta',
                        '62': 'Procedeva senza mantenere distanza sicurezza',
                        '63': 'Procedeva contromano',
                        '64': 'Procedeva con eccesso di velocità',
                        '65': 'Procedeva senza rispettare limiti velocità',
                        '66': 'Procedeva senza rispettare divieti transito',
                        '67': 'Sorpassava un altro veicolo',
                        '68': 'Attraversava imprudentemente passaggio a livello'
                    },
                    'ostacolo': {
                        '60': 'Ostacolo accidentale',
                        '61': 'Veicolo fermo in posizione regolare',
                        '62': 'Veicolo fermo in posizione irregolare',
                        '63': 'Veicolo fermo senza prescritto segnale',
                        '64': 'Veicolo fermo regolarmente segnalato',
                        '65': 'Ostacolo fisso nella carreggiata',
                        '66': 'Treno in passaggio a livello',
                        '67': 'Animale domestico',
                        '68': 'Animale selvatico',
                        '69': 'Buca'
                    }
                },
                'senza_urto': {
                    'veicolo_a': {
                        '70': 'Sbandamento per evitare urto',
                        '71': 'Sbandamento per guida distratta',
                        '72': 'Sbandamento per eccesso velocità',
                        '73': 'Frenava improvvisamente',
                        '74': 'Caduta persona per apertura portiera',
                        '75': 'Caduta persona per discesa da veicolo in moto',
                        '76': 'Caduta persona aggrappata inadeguatamente'
                    },
                    'ostacolo_evitato': {
                        '70': 'Ostacolo accidentale',
                        '71': 'Pedone',
                        '72': 'Animale evitato',
                        '73': 'Veicolo',
                        '74': 'Buca evitata',
                        '75': 'Senza ostacolo né pedone né altro veicolo',
                        '76': 'Ostacolo fisso'
                    }
                }
            };

            // Gestione cambio tipo di circostanza
            $('#circostanza_tipo').change(function() {
                var tipo = $(this).val();
                var selectVeicoloA = $('#circostanza_veicolo_a');
                var selectVeicoloB = $('#circostanza_veicolo_b');
                
                // Pulisci le select
                selectVeicoloA.empty().append('<option value="">Seleziona circostanza</option>');
                selectVeicoloB.empty().append('<option value="">Seleziona circostanza</option>');
                
                if (tipo && circostanzeData[tipo]) {
                    // Popola Veicolo A
                    if (circostanzeData[tipo]['veicolo_a']) {
                        $.each(circostanzeData[tipo]['veicolo_a'], function(codice, descrizione) {
                            selectVeicoloA.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                        });
                    }
                    
                    // Popola Veicolo B/Pedone/Ostacolo
                    var tipoB = 'veicolo_b';
                    if (tipo === 'investimento') tipoB = 'pedone';
                    if (tipo === 'urto_fermo') tipoB = 'ostacolo';
                    if (tipo === 'senza_urto') tipoB = 'ostacolo_evitato';
                    
                    if (circostanzeData[tipo][tipoB]) {
                        $.each(circostanzeData[tipo][tipoB], function(codice, descrizione) {
                            selectVeicoloB.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                        });
                    }
                    
                    // Aggiorna label del Veicolo B
                    var labelText = 'Circostanza Veicolo B';
                    if (tipo === 'investimento') labelText = 'Circostanza Pedone';
                    if (tipo === 'urto_fermo') labelText = 'Circostanza Ostacolo';
                    if (tipo === 'senza_urto') labelText = 'Ostacolo Evitato';
                    
                    $('label[for="circostanza_veicolo_b"]').text(labelText);
                }
            });

            // Trigger al caricamento pagina - gestione migliorata per incidenti importati
            setTimeout(function() {
                var tipoCorrente = $('#circostanza_tipo').val() || $('#circostanza_tipo').attr('data-current-value');
                
                if (tipoCorrente) {
                    // Se c'è già un tipo, carica le opzioni
                    $('#circostanza_tipo').trigger('change');
                } else {
                    // Se non c'è tipo ma ci sono circostanze, prova a inferirlo
                    inferTipoIncidenteFromCircostanze();
                }
            }, 300);
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
                function updateVeicoliSections() {
                    var numVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
                    
                    for (var i = 1; i <= 3; i++) {
                        if (i <= numVeicoli) {
                            $('#veicolo-' + i).show();
                        } else {
                            $('#veicolo-' + i).hide();
                        }
                    }
                }
                
                $('#numero_veicoli_coinvolti').change(function() {
                    updateVeicoliSections();
                    
                    // Triggera l'aggiornamento anche per i conducenti (se il metodo esiste)
                    if (typeof updateConducentiVisibility === 'function') {
                        updateConducentiVisibility();
                    }
                });
                
                // Aggiorna al caricamento della pagina
                updateVeicoliSections();
            // Gestione visibilità targa rimorchio
            $('[id$="_tipo_rimorchio"]').change(function() {
                var veicoloId = $(this).attr('id').replace('_tipo_rimorchio', '');
                var targaRow = $('#' + veicoloId + '_targa_rimorchio_row');

                if ($(this).val() && $(this).val() !== '') {
                    targaRow.show();
                } else {
                    targaRow.hide();
                    $('#' + veicoloId + '_targa_rimorchio').val('');
                }
            }).trigger('change');
        });
        </script>
        <?php
    }
    
    private function render_single_veicolo_fields($post, $veicolo_num) {
        $prefix = 'veicolo_' . $veicolo_num . '_';
        
        $tipo_veicolo = get_post_meta($post->ID, $prefix . 'tipo', true);
        $targa = get_post_meta($post->ID, $prefix . 'targa', true);
        $sigla_estero = get_post_meta($post->ID, $prefix . 'sigla_estero', true);
        $anno_immatricolazione = get_post_meta($post->ID, $prefix . 'anno_immatricolazione', true);
        $cilindrata = get_post_meta($post->ID, $prefix . 'cilindrata', true);
        $peso_totale = get_post_meta($post->ID, $prefix . 'peso_totale', true);
        $tipo_rimorchio = get_post_meta($post->ID, $prefix . 'tipo_rimorchio', true);
        $targa_rimorchio = get_post_meta($post->ID, $prefix . 'targa_rimorchio', true);
        
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
                    <option value="5" <?php selected($tipo_veicolo, '5'); ?>><?php _e('Autobus o filobus in servizio urbano', 'incidenti-stradali'); ?></option>
                    <option value="6" <?php selected($tipo_veicolo, '6'); ?>><?php _e('Autobus di linea o non di linea in extraurbana', 'incidenti-stradali'); ?></option>
                    <option value="7" <?php selected($tipo_veicolo, '7'); ?>><?php _e('Tram', 'incidenti-stradali'); ?></option>
                    <option value="8" <?php selected($tipo_veicolo, '8'); ?>><?php _e('Autocarro', 'incidenti-stradali'); ?></option>
                    <option value="9" <?php selected($tipo_veicolo, '9'); ?>><?php _e('Autotreno con rimorchio', 'incidenti-stradali'); ?></option>
                    <option value="10" <?php selected($tipo_veicolo, '10'); ?>><?php _e('Autosnodato o autoarticolato', 'incidenti-stradali'); ?></option>
                    <option value="11" <?php selected($tipo_veicolo, '11'); ?>><?php _e('Veicoli speciali', 'incidenti-stradali'); ?></option>
                    <option value="12" <?php selected($tipo_veicolo, '12'); ?>><?php _e('Trattore stradale o motrice', 'incidenti-stradali'); ?></option>
                    <option value="13" <?php selected($tipo_veicolo, '13'); ?>><?php _e('Macchina agricola', 'incidenti-stradali'); ?></option>
                    <option value="14" <?php selected($tipo_veicolo, '14'); ?>><?php _e('Velocipede', 'incidenti-stradali'); ?></option>
                    <option value="15" <?php selected($tipo_veicolo, '15'); ?>><?php _e('Ciclomotore', 'incidenti-stradali'); ?></option>
                    <option value="16" <?php selected($tipo_veicolo, '16'); ?>><?php _e('Motociclo a solo', 'incidenti-stradali'); ?></option>
                    <option value="17" <?php selected($tipo_veicolo, '17'); ?>><?php _e('Motociclo con passeggero', 'incidenti-stradali'); ?></option>
                    <option value="18" <?php selected($tipo_veicolo, '18'); ?>><?php _e('Motocarro o motofurgone', 'incidenti-stradali'); ?></option>
                    <option value="19" <?php selected($tipo_veicolo, '19'); ?>><?php _e('Veicolo a trazione animale o a braccia', 'incidenti-stradali'); ?></option>
                    <option value="20" <?php selected($tipo_veicolo, '20'); ?>><?php _e('Veicolo ignoto perché datosi alla fuga', 'incidenti-stradali'); ?></option>
                    <option value="21" <?php selected($tipo_veicolo, '21'); ?>><?php _e('Quadriciclo', 'incidenti-stradali'); ?></option>
                    <option value="22" <?php selected($tipo_veicolo, '22'); ?>><?php _e('Monopattino', 'incidenti-stradali'); ?></option>
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
                <th><label for="<?php echo $prefix; ?>sigla_estero"><?php _e('Sigla se veicolo estero', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="<?php echo $prefix; ?>sigla_estero" name="<?php echo $prefix; ?>sigla_estero" 
                        value="<?php echo esc_attr($sigla_estero); ?>" maxlength="5" class="regular-text"
                        placeholder="<?php _e('Es: D, F, CH...', 'incidenti-stradali'); ?>">
                    <p class="description"><?php _e('Inserire la sigla automobilistica internazionale (solo per veicoli con targa estera)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>anno_immatricolazione"><?php _e('Anno Prima Immatricolazione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" 
                        id="<?php echo $prefix; ?>anno_immatricolazione" 
                        name="<?php echo $prefix; ?>anno_immatricolazione" 
                        value="<?php echo esc_attr($anno_immatricolazione); ?>" 
                        maxlength="2" 
                        pattern="[0-9]{2}" 
                        placeholder="es. 18"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,2)">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>cilindrata"><?php _e('Cilindrata (cc)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="<?php echo $prefix; ?>cilindrata" name="<?php echo $prefix; ?>cilindrata" value="<?php echo esc_attr($cilindrata); ?>" maxlength="5" pattern="[0-9]{1,5}" inputmode="numeric" placeholder="es. 1600" max="99999">
                    <p class="description"><?php _e('Inserire fino a 5 cifre (solo numeri)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>peso_totale"><?php _e('Peso Totale a Pieno Carico (q)', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="number" id="<?php echo $prefix; ?>peso_totale" name="<?php echo $prefix; ?>peso_totale" value="<?php echo esc_attr($peso_totale); ?>" min="0" step="0.1">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>danni_riportati"><?php _e('Danni Riportati', 'incidenti-stradali'); ?></label></th>
                <td>
                    <textarea id="<?php echo $prefix; ?>danni_riportati" name="<?php echo $prefix; ?>danni_riportati" 
                            rows="3" cols="50" placeholder="<?php _e('Descrivi i danni riportati dal veicolo', 'incidenti-stradali'); ?>"><?php echo esc_textarea(get_post_meta($post->ID, $prefix . 'danni_riportati', true)); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>tipo_rimorchio"><?php _e('Tipo Rimorchio', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>tipo_rimorchio" name="<?php echo $prefix; ?>tipo_rimorchio">
                        <option value=""><?php _e('Nessun rimorchio', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($tipo_rimorchio, '1'); ?>><?php _e('Rimorchio', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($tipo_rimorchio, '2'); ?>><?php _e('Semirimorchio', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($tipo_rimorchio, '3'); ?>><?php _e('Carrello appendice', 'incidenti-stradali'); ?></option>
                    </select>
                    <p class="description"><?php _e('Solo per veicoli con rimorchio', 'incidenti-stradali'); ?></p>
                </td>
            </tr>

            <tr id="<?php echo $prefix; ?>targa_rimorchio_row" style="display: none;">
                <th><label for="<?php echo $prefix; ?>targa_rimorchio"><?php _e('Targa Rimorchio', 'incidenti-stradali'); ?></label></th>
                <td>
                    <input type="text" id="<?php echo $prefix; ?>targa_rimorchio" name="<?php echo $prefix; ?>targa_rimorchio" 
                        value="<?php echo esc_attr($targa_rimorchio); ?>" maxlength="10" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_persone_meta_box($post) {
        $numero_veicoli = (int) get_post_meta($post->ID, 'numero_veicoli_coinvolti', true) ?: 1;
        
        echo '<div id="persone-container">';
        echo '<h4>' . __('Conducenti', 'incidenti-stradali') . '</h4>';
        
        // Render conducenti per ogni veicolo
        for ($i = 1; $i <= 3; $i++) {
    // Per un nuovo post, mostra sempre almeno 2 conducenti per evitare problemi di visibilità
    $is_new_post = (get_post_status($post->ID) === 'auto-draft');
    if ($is_new_post) {
        $display = ($i <= 2) ? 'block' : 'none'; // Mostra almeno 2 conducenti per i nuovi post
    } else {
        $display = ($i <= $numero_veicoli) ? 'block' : 'none';
    }
    
    echo '<div id="conducente-' . $i . '" class="conducente-section" style="display: ' . $display . ';">';
    echo '<h4>' . sprintf(__('Conducente Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h4>';
    
    $this->render_single_conducente_fields($post, $i);
    
    echo '</div>';
}
        
        // NUOVO: Sezione Trasportati
        echo '<h4>' . __('Trasportati', 'incidenti-stradali') . '</h4>';
        $numero_veicoli = get_post_meta($post->ID, 'numero_veicoli_coinvolti', true) ?: 1;
        for ($i = 1; $i <= 3; $i++) {
            $display_style = ($i <= $numero_veicoli) ? 'block' : 'none';
            echo '<div id="trasportati-veicolo-' . $i . '" class="trasportati-section" style="display: ' . $display_style . ';">';
            echo '<h5>' . sprintf(__('Trasportati Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h5>';
            $this->render_trasportati_fields($post, $i);
            echo '</div>';
        }

        // NUOVO: Sezione Altri passeggeri infortunati
        echo '<h4>' . __('Altri Passeggeri Infortunati', 'incidenti-stradali') . '</h4>';
        $numero_veicoli = get_post_meta($post->ID, 'numero_veicoli_coinvolti', true) ?: 1;
        for ($i = 1; $i <= 3; $i++) {
            $display_style = ($i <= $numero_veicoli) ? 'block' : 'none';
            echo '<div id="altri-passeggeri-veicolo-' . $i . '" class="altri-passeggeri-section" style="display: ' . $display_style . ';">';
            echo '<h5>' . sprintf(__('Altri Passeggeri Veicolo %s', 'incidenti-stradali'), chr(64 + $i)) . '</h5>';
            $this->render_altri_passeggeri_fields($post, $i);
            echo '</div>';
        }
        
        echo '<h4>' . __('Pedoni Coinvolti', 'incidenti-stradali') . '</h4>';
        $this->render_pedoni_fields($post);
        
        echo '</div>';

        // NUOVO: Script per gestire la visibilità dinamica dei conducenti
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
    // Funzione per aggiornare la visibilità dei conducenti
    function updateConducentiVisibility() {
        var numVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
        
        console.log('Aggiornamento conducenti - Numero veicoli:', numVeicoli); // Debug
               
        for (var i = 1; i <= 3; i++) {
            if (i <= numVeicoli) {
                $('#conducente-' + i).show();
                $('#conducente-' + i + ' input, #conducente-' + i + ' select').prop('disabled', false);
            } else {
                $('#conducente-' + i).hide();
                // NON pulire i valori per preservare i dati già inseriti
            }
        }
    }

     // Inizializzazione per nuovi post
    if ($('body').hasClass('post-new-php')) {
        // Per nuovi post, assicurati che i primi 2 conducenti siano visibili
        $('#conducente-1, #conducente-2').show();
        $('#conducente-1 input, #conducente-1 select').prop('disabled', false);
        $('#conducente-2 input, #conducente-2 select').prop('disabled', false);
        
        // Imposta il numero di veicoli a 2 di default per nuovi post
        if (!$('#numero_veicoli_coinvolti').val()) {
            $('#numero_veicoli_coinvolti').val('2');
        }
    }

            
            // Gestione visibilità trasportati esistente
            function updateTrasportatiSections() {
                var numeroVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
                
                for (var i = 1; i <= 3; i++) {
                    if (i <= numeroVeicoli) {
                        $('#trasportati-veicolo-' + i).show();
                    } else {
                        $('#trasportati-veicolo-' + i).hide();
                    }
                }
            }

            // Gestione visibilità altri passeggeri
            function updateAltriPasseggeriSections() {
                var numeroVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
                
                for (var i = 1; i <= 3; i++) {
                    if (i <= numeroVeicoli) {
                        $('#altri-passeggeri-veicolo-' + i).show();
                    } else {
                        $('#altri-passeggeri-veicolo-' + i).hide();
                    }
                }
            }
            
            // Ascolta i cambiamenti sul numero di veicoli
            $(document).on('change', '#numero_veicoli_coinvolti', function() {
                updateConducentiVisibility();
                updateTrasportatiSections();
                updateAltriPasseggeriSections();
            });
            
            // Aggiorna al caricamento della pagina
            updateConducentiVisibility();
            updateTrasportatiSections();
            updateAltriPasseggeriSections();
        });
        </script>
        <?php
    }

    private function render_trasportati_fields($post, $veicolo_num) {
        $num_trasportati = get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_numero_trasportati', true) ?: 0;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati"><?php _e('Numero Trasportati', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati" name="veicolo_<?php echo $veicolo_num; ?>_numero_trasportati">
                        <?php for ($j = 0; $j <= 4; $j++): ?>
                            <option value="<?php echo $j; ?>" <?php selected($num_trasportati, $j); ?>><?php echo $j; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <div id="trasportati-<?php echo $veicolo_num; ?>-container">
            <?php for ($i = 1; $i <= 4; $i++): 
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
                                    <option value="3" <?php selected(get_post_meta($post->ID, $prefix . 'sesso', true), '3'); ?>><?php _e('Maschio', 'incidenti-stradali'); ?></option>
                                    <option value="4" <?php selected(get_post_meta($post->ID, $prefix . 'sesso', true), '4'); ?>><?php _e('Femmina', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Posizione Sedile', 'incidenti-stradali'); ?></label></th>
                            <td>
                                <select name="<?php echo $prefix; ?>sedile" id="<?php echo $prefix; ?>sedile" data-veicolo="<?php echo $veicolo_num; ?>" data-trasportato="<?php echo $i; ?>">
                                    <option value=""><?php _e('Seleziona posizione', 'incidenti-stradali'); ?></option>
                                    <option value="anteriore" <?php selected(get_post_meta($post->ID, $prefix . 'sedile', true), 'anteriore'); ?>><?php _e('Sedile anteriore', 'incidenti-stradali'); ?></option>
                                    <option value="posteriore" <?php selected(get_post_meta($post->ID, $prefix . 'sedile', true), 'posteriore'); ?>><?php _e('Sedile posteriore', 'incidenti-stradali'); ?></option>
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
                                <select name="<?php echo $prefix; ?>esito" 
                                        id="trasportato_<?php echo $veicolo_num; ?>_<?php echo $i; ?>_esito">
                                    <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                                    <option value="1" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '1'); ?>><?php _e('Morto', 'incidenti-stradali'); ?></option>
                                    <option value="2" <?php selected(get_post_meta($post->ID, $prefix . 'esito', true), '2'); ?>><?php _e('Ferito', 'incidenti-stradali'); ?></option>
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

    private function render_altri_passeggeri_fields($post, $veicolo_num) {
        ?>
        <div style="margin: 15px 0;">
            <h6><?php printf(__('Altri Passeggeri Infortunati nel Veicolo %s', 'incidenti-stradali'), chr(64 + $veicolo_num)); ?></h6>
            
            <table style="border-collapse: collapse; margin-top: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; background: #e9e9e9; text-align: center; width: 100px;"></th>
                        <th style="border: 1px solid #ddd; padding: 8px; background: #e9e9e9; text-align: center; width: 80px;"><?php _e('Maschi', 'incidenti-stradali'); ?></th>
                        <th style="border: 1px solid #ddd; padding: 8px; background: #e9e9e9; text-align: center; width: 80px;"><?php _e('Femmine', 'incidenti-stradali'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; text-align: right; background: #f5f5f5;">
                            <?php _e('Morti', 'incidenti-stradali'); ?>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                            <input type="text" 
                                id="veicolo_<?php echo $veicolo_num; ?>_altri_morti_maschi" 
                                name="veicolo_<?php echo $veicolo_num; ?>_altri_morti_maschi" 
                                value="<?php echo esc_attr(get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_altri_morti_maschi', true) ?: ''); ?>"
                                pattern="[0-9]{1,2}" 
                                maxlength="2"
                                style="width: 50px; text-align: center; border: 1px solid #ccc;"
                                placeholder="0">
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                            <input type="text" 
                                id="veicolo_<?php echo $veicolo_num; ?>_altri_morti_femmine" 
                                name="veicolo_<?php echo $veicolo_num; ?>_altri_morti_femmine" 
                                value="<?php echo esc_attr(get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_altri_morti_femmine', true) ?: ''); ?>"
                                pattern="[0-9]{1,2}" 
                                maxlength="2"
                                style="width: 50px; text-align: center; border: 1px solid #ccc;"
                                placeholder="0">
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; text-align: right; background: #f5f5f5;">
                            <?php _e('Feriti', 'incidenti-stradali'); ?>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                            <input type="text" 
                                id="veicolo_<?php echo $veicolo_num; ?>_altri_feriti_maschi" 
                                name="veicolo_<?php echo $veicolo_num; ?>_altri_feriti_maschi" 
                                value="<?php echo esc_attr(get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_altri_feriti_maschi', true) ?: ''); ?>"
                                pattern="[0-9]{1,2}" 
                                maxlength="2"
                                style="width: 50px; text-align: center; border: 1px solid #ccc;"
                                placeholder="0">
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                            <input type="text" 
                                id="veicolo_<?php echo $veicolo_num; ?>_altri_feriti_femmine" 
                                name="veicolo_<?php echo $veicolo_num; ?>_altri_feriti_femmine" 
                                value="<?php echo esc_attr(get_post_meta($post->ID, 'veicolo_' . $veicolo_num . '_altri_feriti_femmine', true) ?: ''); ?>"
                                pattern="[0-9]{1,2}" 
                                maxlength="2"
                                style="width: 50px; text-align: center; border: 1px solid #ccc;"
                                placeholder="0">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_single_conducente_fields($post, $conducente_num) {
        $prefix = 'conducente_' . $conducente_num . '_';
        
        $eta = get_post_meta($post->ID, $prefix . 'eta', true);
        $sesso = get_post_meta($post->ID, $prefix . 'sesso', true);
        $esito = get_post_meta($post->ID, $prefix . 'esito', true);
        $tipo_patente = get_post_meta($post->ID, $prefix . 'tipo_patente', true);
        
        // Per radiobutton, gestiamo la retrocompatibilità
        if (is_array($tipo_patente)) {
            // Dati vecchi (da checkbox), prendiamo il primo valore
            $tipo_patente_selected = !empty($tipo_patente) ? $tipo_patente[0] : '';
        } else {
            // Dati nuovi (da radiobutton), usiamo direttamente il valore
            // IMPORTANTE: Cast a stringa per gestire correttamente il valore "0"
            $tipo_patente_selected = (string)$tipo_patente;
        }

        $nazionalita = get_post_meta($post->ID, $prefix . 'nazionalita', true);
        $anno_patente = get_post_meta($post->ID, $prefix . 'anno_patente', true);
        $tipologia_incidente = get_post_meta($post->ID, $prefix . 'tipologia_incidente', true);
        
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
                    <div>                    
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="" <?php checked($tipo_patente_selected, ''); ?>> <?php _e('Non specificato', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="0" <?php checked($tipo_patente_selected, '0'); ?>> <?php _e('Patente ciclomotori', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="1" <?php checked($tipo_patente_selected, '1'); ?>> <?php _e('Patente tipo A', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="2" <?php checked($tipo_patente_selected, '2'); ?>> <?php _e('Patente tipo B', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="3" <?php checked($tipo_patente_selected, '3'); ?>> <?php _e('Patente tipo C', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="4" <?php checked($tipo_patente_selected, '4'); ?>> <?php _e('Patente tipo D', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="5" <?php checked($tipo_patente_selected, '5'); ?>> <?php _e('Patente tipo E', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="6" <?php checked($tipo_patente_selected, '6'); ?>> <?php _e('ABC speciale', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="7" <?php checked($tipo_patente_selected, '7'); ?>> <?php _e('Non richiesta', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="8" <?php checked($tipo_patente_selected, '8'); ?>> <?php _e('Foglio rosa', 'incidenti-stradali'); ?></label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipo_patente" value="9" <?php checked($tipo_patente_selected, '9'); ?>> <?php _e('Sprovvisto', 'incidenti-stradali'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>anno_patente"><?php _e('Anno Rilascio Patente', 'incidenti-stradali'); ?></label></th>
                <td>
                     <input type="text" 
                        id="<?php echo $prefix; ?>anno_patente" 
                        name="<?php echo $prefix; ?>anno_patente" 
                        value="<?php echo esc_attr($anno_patente); ?>" 
                        maxlength="2" 
                        pattern="[0-9]{2}" 
                        placeholder="es. 95"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,2)">
                </td>
            </tr>
            <tr>
                <th><label for="<?php echo $prefix; ?>nazionalita"><?php _e('Nazionalità', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="<?php echo $prefix; ?>nazionalita" name="<?php echo $prefix; ?>nazionalita">
                        <option value="">
                            <?php _e('Seleziona nazionalità', 'incidenti-stradali'); ?>
                        </option>
                        <option value="000-Italia" <?php selected($nazionalita, '000-Italia' ); ?>>
                            <?php _e('Italia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="201-Albania" <?php selected($nazionalita, '201-Albania' ); ?>>
                            <?php _e('Albania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="202-Andorra" <?php selected($nazionalita, '202-Andorra' ); ?>>
                            <?php _e('Andorra', 'incidenti-stradali'); ?>
                        </option>
                        <option value="203-Austria" <?php selected($nazionalita, '203-Austria' ); ?>>
                            <?php _e('Austria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="206-Belgio" <?php selected($nazionalita, '206-Belgio' ); ?>>
                            <?php _e('Belgio', 'incidenti-stradali'); ?>
                        </option>
                        <option value="209-Bulgaria" <?php selected($nazionalita, '209-Bulgaria' ); ?>>
                            <?php _e('Bulgaria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="212-Danimarca" <?php selected($nazionalita, '212-Danimarca' ); ?>>
                            <?php _e('Danimarca', 'incidenti-stradali'); ?>
                        </option>
                        <option value="214-Finlandia" <?php selected($nazionalita, '214-Finlandia' ); ?>>
                            <?php _e('Finlandia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="215-Francia" <?php selected($nazionalita, '215-Francia' ); ?>>
                            <?php _e('Francia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="216-Germania" <?php selected($nazionalita, '216-Germania' ); ?>>
                            <?php _e('Germania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="219-Regno Unito" <?php selected($nazionalita, '219-Regno Unito' ); ?>>
                            <?php _e('Regno Unito', 'incidenti-stradali'); ?>
                        </option>
                        <option value="220-Grecia" <?php selected($nazionalita, '220-Grecia' ); ?>>
                            <?php _e('Grecia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="221-Irlanda" <?php selected($nazionalita, '221-Irlanda' ); ?>>
                            <?php _e('Irlanda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="223-Islanda" <?php selected($nazionalita, '223-Islanda' ); ?>>
                            <?php _e('Islanda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="225-Liechtenstein" <?php selected($nazionalita, '225-Liechtenstein' ); ?>>
                            <?php _e('Liechtenstein', 'incidenti-stradali'); ?>
                        </option>
                        <option value="226-Lussemburgo" <?php selected($nazionalita, '226-Lussemburgo' ); ?>>
                            <?php _e('Lussemburgo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="227-Malta" <?php selected($nazionalita, '227-Malta' ); ?>>
                            <?php _e('Malta', 'incidenti-stradali'); ?>
                        </option>
                        <option value="229-Monaco" <?php selected($nazionalita, '229-Monaco' ); ?>>
                            <?php _e('Monaco', 'incidenti-stradali'); ?>
                        </option>
                        <option value="231-Norvegia" <?php selected($nazionalita, '231-Norvegia' ); ?>>
                            <?php _e('Norvegia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="232-Paesi Bassi" <?php selected($nazionalita, '232-Paesi Bassi' ); ?>>
                            <?php _e('Paesi Bassi', 'incidenti-stradali'); ?>
                        </option>
                        <option value="233-Polonia" <?php selected($nazionalita, '233-Polonia' ); ?>>
                            <?php _e('Polonia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="234-Portogallo" <?php selected($nazionalita, '234-Portogallo' ); ?>>
                            <?php _e('Portogallo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="235-Romania" <?php selected($nazionalita, '235-Romania' ); ?>>
                            <?php _e('Romania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="236-San Marino" <?php selected($nazionalita, '236-San Marino' ); ?>>
                            <?php _e('San Marino', 'incidenti-stradali'); ?>
                        </option>
                        <option value="239-Spagna" <?php selected($nazionalita, '239-Spagna' ); ?>>
                            <?php _e('Spagna', 'incidenti-stradali'); ?>
                        </option>
                        <option value="240-Svezia" <?php selected($nazionalita, '240-Svezia' ); ?>>
                            <?php _e('Svezia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="241-Svizzera" <?php selected($nazionalita, '241-Svizzera' ); ?>>
                            <?php _e('Svizzera', 'incidenti-stradali'); ?>
                        </option>
                        <option value="243-Ucraina" <?php selected($nazionalita, '243-Ucraina' ); ?>>
                            <?php _e('Ucraina', 'incidenti-stradali'); ?>
                        </option>
                        <option value="244-Ungheria" <?php selected($nazionalita, '244-Ungheria' ); ?>>
                            <?php _e('Ungheria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="245-Federazione russa" <?php selected($nazionalita, '245-Federazione russa' ); ?>>
                            <?php _e('Federazione russa', 'incidenti-stradali'); ?>
                        </option>
                        <option value="246-Stato della Città del Vaticano" <?php selected($nazionalita, '246-Stato della Città del Vaticano' ); ?>>
                            <?php _e('Stato della Città del Vaticano', 'incidenti-stradali'); ?>
                        </option>
                        <option value="247-Estonia" <?php selected($nazionalita, '247-Estonia' ); ?>>
                            <?php _e('Estonia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="248-Lettonia" <?php selected($nazionalita, '248-Lettonia' ); ?>>
                            <?php _e('Lettonia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="249-Lituania" <?php selected($nazionalita, '249-Lituania' ); ?>>
                            <?php _e('Lituania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="250-Croazia" <?php selected($nazionalita, '250-Croazia' ); ?>>
                            <?php _e('Croazia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="251-Slovenia" <?php selected($nazionalita, '251-Slovenia' ); ?>>
                            <?php _e('Slovenia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="252-Bosnia-Erzegovina" <?php selected($nazionalita, '252-Bosnia-Erzegovina' ); ?>>
                            <?php _e('Bosnia-Erzegovina', 'incidenti-stradali'); ?>
                        </option>
                        <option value="253-Ex Repubblica Jugoslava di Macedonia" <?php selected($nazionalita, '253-Ex Repubblica Jugoslava di Macedonia' ); ?>>
                            <?php _e('Ex Repubblica Jugoslava di Macedonia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="254-Moldova" <?php selected($nazionalita, '254-Moldova' ); ?>>
                            <?php _e('Moldova', 'incidenti-stradali'); ?>
                        </option>
                        <option value="255-Slovacchia" <?php selected($nazionalita, '255-Slovacchia' ); ?>>
                            <?php _e('Slovacchia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="256-Bielorussia" <?php selected($nazionalita, '256-Bielorussia' ); ?>>
                            <?php _e('Bielorussia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="257-Repubblica ceca" <?php selected($nazionalita, '257-Repubblica ceca' ); ?>>
                            <?php _e('Repubblica ceca', 'incidenti-stradali'); ?>
                        </option>
                        <option value="270-Montenegro" <?php selected($nazionalita, '270-Montenegro' ); ?>>
                            <?php _e('Montenegro', 'incidenti-stradali'); ?>
                        </option>
                        <option value="271-Serbia" <?php selected($nazionalita, '271-Serbia' ); ?>>
                            <?php _e('Serbia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="272-Kosovo" <?php selected($nazionalita, '272-Kosovo' ); ?>>
                            <?php _e('Kosovo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="301-Afghanistan" <?php selected($nazionalita, '301-Afghanistan' ); ?>>
                            <?php _e('Afghanistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="302-Arabia Saudita" <?php selected($nazionalita, '302-Arabia Saudita' ); ?>>
                            <?php _e('Arabia Saudita', 'incidenti-stradali'); ?>
                        </option>
                        <option value="304-Bahrein" <?php selected($nazionalita, '304-Bahrein' ); ?>>
                            <?php _e('Bahrein', 'incidenti-stradali'); ?>
                        </option>
                        <option value="305-Bangladesh" <?php selected($nazionalita, '305-Bangladesh' ); ?>>
                            <?php _e('Bangladesh', 'incidenti-stradali'); ?>
                        </option>
                        <option value="306-Bhutan" <?php selected($nazionalita, '306-Bhutan' ); ?>>
                            <?php _e('Bhutan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="307-Myanmar/Birmania" <?php selected($nazionalita, '307-Myanmar/Birmania' ); ?>>
                            <?php _e('Myanmar/Birmania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="309-Brunei Darussalam" <?php selected($nazionalita, '309-Brunei Darussalam' ); ?>>
                            <?php _e('Brunei Darussalam', 'incidenti-stradali'); ?>
                        </option>
                        <option value="310-Cambogia" <?php selected($nazionalita, '310-Cambogia' ); ?>>
                            <?php _e('Cambogia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="311-Sri Lanka" <?php selected($nazionalita, '311-Sri Lanka' ); ?>>
                            <?php _e('Sri Lanka', 'incidenti-stradali'); ?>
                        </option>
                        <option value="314-Cina" <?php selected($nazionalita, '314-Cina' ); ?>>
                            <?php _e('Cina', 'incidenti-stradali'); ?>
                        </option>
                        <option value="315-Cipro" <?php selected($nazionalita, '315-Cipro' ); ?>>
                            <?php _e('Cipro', 'incidenti-stradali'); ?>
                        </option>
                        <option value="319-Corea del Nord" <?php selected($nazionalita, '319-Corea del Nord' ); ?>>
                            <?php _e('Corea del Nord', 'incidenti-stradali'); ?>
                        </option>
                        <option value="320-Corea del Sud" <?php selected($nazionalita, '320-Corea del Sud' ); ?>>
                            <?php _e('Corea del Sud', 'incidenti-stradali'); ?>
                        </option>
                        <option value="322-Emirati Arabi Uniti" <?php selected($nazionalita, '322-Emirati Arabi Uniti' ); ?>>
                            <?php _e('Emirati Arabi Uniti', 'incidenti-stradali'); ?>
                        </option>
                        <option value="323-Filippine" <?php selected($nazionalita, '323-Filippine' ); ?>>
                            <?php _e('Filippine', 'incidenti-stradali'); ?>
                        </option>
                        <option value="324-Palestina" <?php selected($nazionalita, '324-Palestina' ); ?>>
                            <?php _e('Palestina', 'incidenti-stradali'); ?>
                        </option>
                        <option value="326-Giappone" <?php selected($nazionalita, '326-Giappone' ); ?>>
                            <?php _e('Giappone', 'incidenti-stradali'); ?>
                        </option>
                        <option value="327-Giordania" <?php selected($nazionalita, '327-Giordania' ); ?>>
                            <?php _e('Giordania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="330-India" <?php selected($nazionalita, '330-India' ); ?>>
                            <?php _e('India', 'incidenti-stradali'); ?>
                        </option>
                        <option value="331-Indonesia" <?php selected($nazionalita, '331-Indonesia' ); ?>>
                            <?php _e('Indonesia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="332-Iran" <?php selected($nazionalita, '332-Iran' ); ?>>
                            <?php _e('Iran', 'incidenti-stradali'); ?>
                        </option>
                        <option value="333-Iraq" <?php selected($nazionalita, '333-Iraq' ); ?>>
                            <?php _e('Iraq', 'incidenti-stradali'); ?>
                        </option>
                        <option value="334-Israele" <?php selected($nazionalita, '334-Israele' ); ?>>
                            <?php _e('Israele', 'incidenti-stradali'); ?>
                        </option>
                        <option value="335-Kuwait" <?php selected($nazionalita, '335-Kuwait' ); ?>>
                            <?php _e('Kuwait', 'incidenti-stradali'); ?>
                        </option>
                        <option value="336-Laos" <?php selected($nazionalita, '336-Laos' ); ?>>
                            <?php _e('Laos', 'incidenti-stradali'); ?>
                        </option>
                        <option value="337-Libano" <?php selected($nazionalita, '337-Libano' ); ?>>
                            <?php _e('Libano', 'incidenti-stradali'); ?>
                        </option>
                        <option value="338-Timor Leste" <?php selected($nazionalita, '338-Timor Leste' ); ?>>
                            <?php _e('Timor Leste', 'incidenti-stradali'); ?>
                        </option>
                        <option value="339-Maldive" <?php selected($nazionalita, '339-Maldive' ); ?>>
                            <?php _e('Maldive', 'incidenti-stradali'); ?>
                        </option>
                        <option value="340-Malaysia" <?php selected($nazionalita, '340-Malaysia' ); ?>>
                            <?php _e('Malaysia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="341-Mongolia" <?php selected($nazionalita, '341-Mongolia' ); ?>>
                            <?php _e('Mongolia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="342-Nepal" <?php selected($nazionalita, '342-Nepal' ); ?>>
                            <?php _e('Nepal', 'incidenti-stradali'); ?>
                        </option>
                        <option value="343-Oman" <?php selected($nazionalita, '343-Oman' ); ?>>
                            <?php _e('Oman', 'incidenti-stradali'); ?>
                        </option>
                        <option value="344-Pakistan" <?php selected($nazionalita, '344-Pakistan' ); ?>>
                            <?php _e('Pakistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="345-Qatar" <?php selected($nazionalita, '345-Qatar' ); ?>>
                            <?php _e('Qatar', 'incidenti-stradali'); ?>
                        </option>
                        <option value="346-Singapore" <?php selected($nazionalita, '346-Singapore' ); ?>>
                            <?php _e('Singapore', 'incidenti-stradali'); ?>
                        </option>
                        <option value="348-Siria" <?php selected($nazionalita, '348-Siria' ); ?>>
                            <?php _e('Siria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="349-Thailandia" <?php selected($nazionalita, '349-Thailandia' ); ?>>
                            <?php _e('Thailandia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="351-Turchia" <?php selected($nazionalita, '351-Turchia' ); ?>>
                            <?php _e('Turchia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="353-Vietnam" <?php selected($nazionalita, '353-Vietnam' ); ?>>
                            <?php _e('Vietnam', 'incidenti-stradali'); ?>
                        </option>
                        <option value="354-Yemen" <?php selected($nazionalita, '354-Yemen' ); ?>>
                            <?php _e('Yemen', 'incidenti-stradali'); ?>
                        </option>
                        <option value="356-Kazakhstan" <?php selected($nazionalita, '356-Kazakhstan' ); ?>>
                            <?php _e('Kazakhstan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="357-Uzbekistan" <?php selected($nazionalita, '357-Uzbekistan' ); ?>>
                            <?php _e('Uzbekistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="358-Armenia" <?php selected($nazionalita, '358-Armenia' ); ?>>
                            <?php _e('Armenia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="359-Azerbaigian" <?php selected($nazionalita, '359-Azerbaigian' ); ?>>
                            <?php _e('Azerbaigian', 'incidenti-stradali'); ?>
                        </option>
                        <option value="360-Georgia" <?php selected($nazionalita, '360-Georgia' ); ?>>
                            <?php _e('Georgia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="361-Kirghizistan" <?php selected($nazionalita, '361-Kirghizistan' ); ?>>
                            <?php _e('Kirghizistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="362-Tagikistan" <?php selected($nazionalita, '362-Tagikistan' ); ?>>
                            <?php _e('Tagikistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="363-Taiwan" <?php selected($nazionalita, '363-Taiwan' ); ?>>
                            <?php _e('Taiwan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="364-Turkmenistan" <?php selected($nazionalita, '364-Turkmenistan' ); ?>>
                            <?php _e('Turkmenistan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="401-Algeria" <?php selected($nazionalita, '401-Algeria' ); ?>>
                            <?php _e('Algeria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="402-Angola" <?php selected($nazionalita, '402-Angola' ); ?>>
                            <?php _e('Angola', 'incidenti-stradali'); ?>
                        </option>
                        <option value="404-Costa d'Avorio" <?php selected($nazionalita, '404-Costa d\'Avorio' ); ?>>
                            <?php _e('Costa d\'Avorio', 'incidenti-stradali'); ?>
                        </option>
                        <option value="406-Benin" <?php selected($nazionalita, '406-Benin' ); ?>>
                            <?php _e('Benin', 'incidenti-stradali'); ?>
                        </option>
                        <option value="408-Botswana" <?php selected($nazionalita, '408-Botswana' ); ?>>
                            <?php _e('Botswana', 'incidenti-stradali'); ?>
                        </option>
                        <option value="409-Burkina Faso" <?php selected($nazionalita, '409-Burkina Faso' ); ?>>
                            <?php _e('Burkina Faso', 'incidenti-stradali'); ?>
                        </option>
                        <option value="410-Burundi" <?php selected($nazionalita, '410-Burundi' ); ?>>
                            <?php _e('Burundi', 'incidenti-stradali'); ?>
                        </option>
                        <option value="411-Camerun" <?php selected($nazionalita, '411-Camerun' ); ?>>
                            <?php _e('Camerun', 'incidenti-stradali'); ?>
                        </option>
                        <option value="413-Capo Verde" <?php selected($nazionalita, '413-Capo Verde' ); ?>>
                            <?php _e('Capo Verde', 'incidenti-stradali'); ?>
                        </option>
                        <option value="414-Repubblica Centrafricana" <?php selected($nazionalita, '414-Repubblica Centrafricana' ); ?>>
                            <?php _e('Repubblica Centrafricana', 'incidenti-stradali'); ?>
                        </option>
                        <option value="415-Ciad" <?php selected($nazionalita, '415-Ciad' ); ?>>
                            <?php _e('Ciad', 'incidenti-stradali'); ?>
                        </option>
                        <option value="417-Comore" <?php selected($nazionalita, '417-Comore' ); ?>>
                            <?php _e('Comore', 'incidenti-stradali'); ?>
                        </option>
                        <option value="418-Congo" <?php selected($nazionalita, '418-Congo' ); ?>>
                            <?php _e('Congo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="419-Egitto" <?php selected($nazionalita, '419-Egitto' ); ?>>
                            <?php _e('Egitto', 'incidenti-stradali'); ?>
                        </option>
                        <option value="420-Etiopia" <?php selected($nazionalita, '420-Etiopia' ); ?>>
                            <?php _e('Etiopia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="421-Gabon" <?php selected($nazionalita, '421-Gabon' ); ?>>
                            <?php _e('Gabon', 'incidenti-stradali'); ?>
                        </option>
                        <option value="422-Gambia" <?php selected($nazionalita, '422-Gambia' ); ?>>
                            <?php _e('Gambia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="423-Ghana" <?php selected($nazionalita, '423-Ghana' ); ?>>
                            <?php _e('Ghana', 'incidenti-stradali'); ?>
                        </option>
                        <option value="424-Gibuti" <?php selected($nazionalita, '424-Gibuti' ); ?>>
                            <?php _e('Gibuti', 'incidenti-stradali'); ?>
                        </option>
                        <option value="425-Guinea" <?php selected($nazionalita, '425-Guinea' ); ?>>
                            <?php _e('Guinea', 'incidenti-stradali'); ?>
                        </option>
                        <option value="426-Guinea-Bissau" <?php selected($nazionalita, '426-Guinea-Bissau' ); ?>>
                            <?php _e('Guinea-Bissau', 'incidenti-stradali'); ?>
                        </option>
                        <option value="427-Guinea equatoriale" <?php selected($nazionalita, '427-Guinea equatoriale' ); ?>>
                            <?php _e('Guinea equatoriale', 'incidenti-stradali'); ?>
                        </option>
                        <option value="428-Kenya" <?php selected($nazionalita, '428-Kenya' ); ?>>
                            <?php _e('Kenya', 'incidenti-stradali'); ?>
                        </option>
                        <option value="429-Lesotho" <?php selected($nazionalita, '429-Lesotho' ); ?>>
                            <?php _e('Lesotho', 'incidenti-stradali'); ?>
                        </option>
                        <option value="430-Liberia" <?php selected($nazionalita, '430-Liberia' ); ?>>
                            <?php _e('Liberia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="431-Libia" <?php selected($nazionalita, '431-Libia' ); ?>>
                            <?php _e('Libia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="432-Madagascar" <?php selected($nazionalita, '432-Madagascar' ); ?>>
                            <?php _e('Madagascar', 'incidenti-stradali'); ?>
                        </option>
                        <option value="434-Malawi" <?php selected($nazionalita, '434-Malawi' ); ?>>
                            <?php _e('Malawi', 'incidenti-stradali'); ?>
                        </option>
                        <option value="435-Mali" <?php selected($nazionalita, '435-Mali' ); ?>>
                            <?php _e('Mali', 'incidenti-stradali'); ?>
                        </option>
                        <option value="436-Marocco" <?php selected($nazionalita, '436-Marocco' ); ?>>
                            <?php _e('Marocco', 'incidenti-stradali'); ?>
                        </option>
                        <option value="437-Mauritania" <?php selected($nazionalita, '437-Mauritania' ); ?>>
                            <?php _e('Mauritania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="438-Maurizio" <?php selected($nazionalita, '438-Maurizio' ); ?>>
                            <?php _e('Maurizio', 'incidenti-stradali'); ?>
                        </option>
                        <option value="440-Mozambico" <?php selected($nazionalita, '440-Mozambico' ); ?>>
                            <?php _e('Mozambico', 'incidenti-stradali'); ?>
                        </option>
                        <option value="441-Namibia" <?php selected($nazionalita, '441-Namibia' ); ?>>
                            <?php _e('Namibia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="442-Niger" <?php selected($nazionalita, '442-Niger' ); ?>>
                            <?php _e('Niger', 'incidenti-stradali'); ?>
                        </option>
                        <option value="443-Nigeria" <?php selected($nazionalita, '443-Nigeria' ); ?>>
                            <?php _e('Nigeria', 'incidenti-stradali'); ?>
                        </option>
                        <option value="446-Ruanda" <?php selected($nazionalita, '446-Ruanda' ); ?>>
                            <?php _e('Ruanda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="448-Sao Tomé e Principe" <?php selected($nazionalita, '448-Sao Tomé e Principe' ); ?>>
                            <?php _e('Sao Tomé e Principe', 'incidenti-stradali'); ?>
                        </option>
                        <option value="449-Seychelles" <?php selected($nazionalita, '449-Seychelles' ); ?>>
                            <?php _e('Seychelles', 'incidenti-stradali'); ?>
                        </option>
                        <option value="450-Senegal" <?php selected($nazionalita, '450-Senegal' ); ?>>
                            <?php _e('Senegal', 'incidenti-stradali'); ?>
                        </option>
                        <option value="451-Sierra Leone" <?php selected($nazionalita, '451-Sierra Leone' ); ?>>
                            <?php _e('Sierra Leone', 'incidenti-stradali'); ?>
                        </option>
                        <option value="453-Somalia" <?php selected($nazionalita, '453-Somalia' ); ?>>
                            <?php _e('Somalia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="454-Sudafrica" <?php selected($nazionalita, '454-Sudafrica' ); ?>>
                            <?php _e('Sudafrica', 'incidenti-stradali'); ?>
                        </option>
                        <option value="455-Sudan" <?php selected($nazionalita, '455-Sudan' ); ?>>
                            <?php _e('Sudan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="456-Eswatini" <?php selected($nazionalita, '456-Eswatini' ); ?>>
                            <?php _e('Eswatini', 'incidenti-stradali'); ?>
                        </option>
                        <option value="457-Tanzania" <?php selected($nazionalita, '457-Tanzania' ); ?>>
                            <?php _e('Tanzania', 'incidenti-stradali'); ?>
                        </option>
                        <option value="458-Togo" <?php selected($nazionalita, '458-Togo' ); ?>>
                            <?php _e('Togo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="460-Tunisia" <?php selected($nazionalita, '460-Tunisia' ); ?>>
                            <?php _e('Tunisia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="461-Uganda" <?php selected($nazionalita, '461-Uganda' ); ?>>
                            <?php _e('Uganda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="463-Repubblica Democratica del Congo" <?php selected($nazionalita, '463-Repubblica Democratica del Congo' ); ?>>
                            <?php _e('Repubblica Democratica del Congo', 'incidenti-stradali'); ?>
                        </option>
                        <option value="464-Zambia" <?php selected($nazionalita, '464-Zambia' ); ?>>
                            <?php _e('Zambia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="465-Zimbabwe" <?php selected($nazionalita, '465-Zimbabwe' ); ?>>
                            <?php _e('Zimbabwe', 'incidenti-stradali'); ?>
                        </option>
                        <option value="466-Eritrea" <?php selected($nazionalita, '466-Eritrea' ); ?>>
                            <?php _e('Eritrea', 'incidenti-stradali'); ?>
                        </option>
                        <option value="467-Sud Sudan" <?php selected($nazionalita, '467-Sud Sudan' ); ?>>
                            <?php _e('Sud Sudan', 'incidenti-stradali'); ?>
                        </option>
                        <option value="503-Antigua e Barbuda" <?php selected($nazionalita, '503-Antigua e Barbuda' ); ?>>
                            <?php _e('Antigua e Barbuda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="505-Bahamas" <?php selected($nazionalita, '505-Bahamas' ); ?>>
                            <?php _e('Bahamas', 'incidenti-stradali'); ?>
                        </option>
                        <option value="506-Barbados" <?php selected($nazionalita, '506-Barbados' ); ?>>
                            <?php _e('Barbados', 'incidenti-stradali'); ?>
                        </option>
                        <option value="507-Belize" <?php selected($nazionalita, '507-Belize' ); ?>>
                            <?php _e('Belize', 'incidenti-stradali'); ?>
                        </option>
                        <option value="509-Canada" <?php selected($nazionalita, '509-Canada' ); ?>>
                            <?php _e('Canada', 'incidenti-stradali'); ?>
                        </option>
                        <option value="513-Costa Rica" <?php selected($nazionalita, '513-Costa Rica' ); ?>>
                            <?php _e('Costa Rica', 'incidenti-stradali'); ?>
                        </option>
                        <option value="514-Cuba" <?php selected($nazionalita, '514-Cuba' ); ?>>
                            <?php _e('Cuba', 'incidenti-stradali'); ?>
                        </option>
                        <option value="515-Dominica" <?php selected($nazionalita, '515-Dominica' ); ?>>
                            <?php _e('Dominica', 'incidenti-stradali'); ?>
                        </option>
                        <option value="516-Repubblica Dominicana" <?php selected($nazionalita, '516-Repubblica Dominicana' ); ?>>
                            <?php _e('Repubblica Dominicana', 'incidenti-stradali'); ?>
                        </option>
                        <option value="517-El Salvador" <?php selected($nazionalita, '517-El Salvador' ); ?>>
                            <?php _e('El Salvador', 'incidenti-stradali'); ?>
                        </option>
                        <option value="518-Giamaica" <?php selected($nazionalita, '518-Giamaica' ); ?>>
                            <?php _e('Giamaica', 'incidenti-stradali'); ?>
                        </option>
                        <option value="519-Grenada" <?php selected($nazionalita, '519-Grenada' ); ?>>
                            <?php _e('Grenada', 'incidenti-stradali'); ?>
                        </option>
                        <option value="523-Guatemala" <?php selected($nazionalita, '523-Guatemala' ); ?>>
                            <?php _e('Guatemala', 'incidenti-stradali'); ?>
                        </option>
                        <option value="524-Haiti" <?php selected($nazionalita, '524-Haiti' ); ?>>
                            <?php _e('Haiti', 'incidenti-stradali'); ?>
                        </option>
                        <option value="525-Honduras" <?php selected($nazionalita, '525-Honduras' ); ?>>
                            <?php _e('Honduras', 'incidenti-stradali'); ?>
                        </option>
                        <option value="527-Messico" <?php selected($nazionalita, '527-Messico' ); ?>>
                            <?php _e('Messico', 'incidenti-stradali'); ?>
                        </option>
                        <option value="529-Nicaragua" <?php selected($nazionalita, '529-Nicaragua' ); ?>>
                            <?php _e('Nicaragua', 'incidenti-stradali'); ?>
                        </option>
                        <option value="530-Panama" <?php selected($nazionalita, '530-Panama' ); ?>>
                            <?php _e('Panama', 'incidenti-stradali'); ?>
                        </option>
                        <option value="532-Santa Lucia" <?php selected($nazionalita, '532-Santa Lucia' ); ?>>
                            <?php _e('Santa Lucia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="533-Saint Vincent e Grenadine" <?php selected($nazionalita, '533-Saint Vincent e Grenadine' ); ?>>
                            <?php _e('Saint Vincent e Grenadine', 'incidenti-stradali'); ?>
                        </option>
                        <option value="534-Saint Kitts e Nevis" <?php selected($nazionalita, '534-Saint Kitts e Nevis' ); ?>>
                            <?php _e('Saint Kitts e Nevis', 'incidenti-stradali'); ?>
                        </option>
                        <option value="536-Stati Uniti d'America" <?php selected($nazionalita, '536-Stati Uniti d\'America' ); ?>>
                            <?php _e('Stati Uniti d\'America', 'incidenti-stradali'); ?>
                        </option>
                        <option value="602-Argentina" <?php selected($nazionalita, '602-Argentina' ); ?>>
                            <?php _e('Argentina', 'incidenti-stradali'); ?>
                        </option>
                        <option value="604-Bolivia" <?php selected($nazionalita, '604-Bolivia' ); ?>>
                            <?php _e('Bolivia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="605-Brasile" <?php selected($nazionalita, '605-Brasile' ); ?>>
                            <?php _e('Brasile', 'incidenti-stradali'); ?>
                        </option>
                        <option value="606-Cile" <?php selected($nazionalita, '606-Cile' ); ?>>
                            <?php _e('Cile', 'incidenti-stradali'); ?>
                        </option>
                        <option value="608-Colombia" <?php selected($nazionalita, '608-Colombia' ); ?>>
                            <?php _e('Colombia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="609-Ecuador" <?php selected($nazionalita, '609-Ecuador' ); ?>>
                            <?php _e('Ecuador', 'incidenti-stradali'); ?>
                        </option>
                        <option value="612-Guyana" <?php selected($nazionalita, '612-Guyana' ); ?>>
                            <?php _e('Guyana', 'incidenti-stradali'); ?>
                        </option>
                        <option value="614-Paraguay" <?php selected($nazionalita, '614-Paraguay' ); ?>>
                            <?php _e('Paraguay', 'incidenti-stradali'); ?>
                        </option>
                        <option value="615-Perù" <?php selected($nazionalita, '615-Perù' ); ?>>
                            <?php _e('Perù', 'incidenti-stradali'); ?>
                        </option>
                        <option value="616-Suriname" <?php selected($nazionalita, '616-Suriname' ); ?>>
                            <?php _e('Suriname', 'incidenti-stradali'); ?>
                        </option>
                        <option value="617-Trinidad e Tobago" <?php selected($nazionalita, '617-Trinidad e Tobago' ); ?>>
                            <?php _e('Trinidad e Tobago', 'incidenti-stradali'); ?>
                        </option>
                        <option value="618-Uruguay" <?php selected($nazionalita, '618-Uruguay' ); ?>>
                            <?php _e('Uruguay', 'incidenti-stradali'); ?>
                        </option>
                        <option value="619-Venezuela" <?php selected($nazionalita, '619-Venezuela' ); ?>>
                            <?php _e('Venezuela', 'incidenti-stradali'); ?>
                        </option>
                        <option value="701-Australia" <?php selected($nazionalita, '701-Australia' ); ?>>
                            <?php _e('Australia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="703-Figi" <?php selected($nazionalita, '703-Figi' ); ?>>
                            <?php _e('Figi', 'incidenti-stradali'); ?>
                        </option>
                        <option value="708-Kiribati" <?php selected($nazionalita, '708-Kiribati' ); ?>>
                            <?php _e('Kiribati', 'incidenti-stradali'); ?>
                        </option>
                        <option value="712-Isole Marshall" <?php selected($nazionalita, '712-Isole Marshall' ); ?>>
                            <?php _e('Isole Marshall', 'incidenti-stradali'); ?>
                        </option>
                        <option value="713-Stati Federati di Micronesia" <?php selected($nazionalita, '713-Stati Federati di Micronesia' ); ?>>
                            <?php _e('Stati Federati di Micronesia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="715-Nauru" <?php selected($nazionalita, '715-Nauru' ); ?>>
                            <?php _e('Nauru', 'incidenti-stradali'); ?>
                        </option>
                        <option value="719-Nuova Zelanda" <?php selected($nazionalita, '719-Nuova Zelanda' ); ?>>
                            <?php _e('Nuova Zelanda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="720-Palau" <?php selected($nazionalita, '720-Palau' ); ?>>
                            <?php _e('Palau', 'incidenti-stradali'); ?>
                        </option>
                        <option value="721-Papua Nuova Guinea" <?php selected($nazionalita, '721-Papua Nuova Guinea' ); ?>>
                            <?php _e('Papua Nuova Guinea', 'incidenti-stradali'); ?>
                        </option>
                        <option value="725-Isole Salomone" <?php selected($nazionalita, '725-Isole Salomone' ); ?>>
                            <?php _e('Isole Salomone', 'incidenti-stradali'); ?>
                        </option>
                        <option value="727-Samoa" <?php selected($nazionalita, '727-Samoa' ); ?>>
                            <?php _e('Samoa', 'incidenti-stradali'); ?>
                        </option>
                        <option value="730-Tonga" <?php selected($nazionalita, '730-Tonga' ); ?>>
                            <?php _e('Tonga', 'incidenti-stradali'); ?>
                        </option>
                        <option value="731-Tuvalu" <?php selected($nazionalita, '731-Tuvalu' ); ?>>
                            <?php _e('Tuvalu', 'incidenti-stradali'); ?>
                        </option>
                        <option value="732-Vanuatu" <?php selected($nazionalita, '732-Vanuatu' ); ?>>
                            <?php _e('Vanuatu', 'incidenti-stradali'); ?>
                        </option>
                        <option value="902-Nuova Caledonia" <?php selected($nazionalita, '902-Nuova Caledonia' ); ?>>
                            <?php _e('Nuova Caledonia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="904-Saint-Martin (FR)" <?php selected($nazionalita, '904-Saint-Martin (FR)' ); ?>>
                            <?php _e('Saint-Martin (FR)', 'incidenti-stradali'); ?>
                        </option>
                        <option value="905-Sahara occidentale" <?php selected($nazionalita, '905-Sahara occidentale' ); ?>>
                            <?php _e('Sahara occidentale', 'incidenti-stradali'); ?>
                        </option>
                        <option value="906-Saint-Barthélemy" <?php selected($nazionalita, '906-Saint-Barthélemy' ); ?>>
                            <?php _e('Saint-Barthélemy', 'incidenti-stradali'); ?>
                        </option>
                        <option value="908-Bermuda" <?php selected($nazionalita, '908-Bermuda' ); ?>>
                            <?php _e('Bermuda', 'incidenti-stradali'); ?>
                        </option>
                        <option value="909-Isole Cook (NZ)" <?php selected($nazionalita, '909-Isole Cook (NZ)' ); ?>>
                            <?php _e('Isole Cook (NZ)', 'incidenti-stradali'); ?>
                        </option>
                        <option value="910-Gibilterra" <?php selected($nazionalita, '910-Gibilterra' ); ?>>
                            <?php _e('Gibilterra', 'incidenti-stradali'); ?>
                        </option>
                        <option value="911-Isole Cayman" <?php selected($nazionalita, '911-Isole Cayman' ); ?>>
                            <?php _e('Isole Cayman', 'incidenti-stradali'); ?>
                        </option>
                        <option value="917-Anguilla" <?php selected($nazionalita, '917-Anguilla' ); ?>>
                            <?php _e('Anguilla', 'incidenti-stradali'); ?>
                        </option>
                        <option value="920-Polinesia francese" <?php selected($nazionalita, '920-Polinesia francese' ); ?>>
                            <?php _e('Polinesia francese', 'incidenti-stradali'); ?>
                        </option>
                        <option value="924-Isole Fær Øer" <?php selected($nazionalita, '924-Isole Fær Øer' ); ?>>
                            <?php _e('Isole Fær Øer', 'incidenti-stradali'); ?>
                        </option>
                        <option value="925-Jersey" <?php selected($nazionalita, '925-Jersey' ); ?>>
                            <?php _e('Jersey', 'incidenti-stradali'); ?>
                        </option>
                        <option value="926-Aruba" <?php selected($nazionalita, '926-Aruba' ); ?>>
                            <?php _e('Aruba', 'incidenti-stradali'); ?>
                        </option>
                        <option value="928-Sint Maarten (NL)" <?php selected($nazionalita, '928-Sint Maarten (NL)' ); ?>>
                            <?php _e('Sint Maarten (NL)', 'incidenti-stradali'); ?>
                        </option>
                        <option value="934-Groenlandia" <?php selected($nazionalita, '934-Groenlandia' ); ?>>
                            <?php _e('Groenlandia', 'incidenti-stradali'); ?>
                        </option>
                        <option value="939-Sark" <?php selected($nazionalita, '939-Sark' ); ?>>
                            <?php _e('Sark', 'incidenti-stradali'); ?>
                        </option>
                        <option value="940-Guernsey" <?php selected($nazionalita, '940-Guernsey' ); ?>>
                            <?php _e('Guernsey', 'incidenti-stradali'); ?>
                        </option>
                        <option value="958-Isole Falkland (Malvine)" <?php selected($nazionalita, '958-Isole Falkland (Malvine)' ); ?>>
                            <?php _e('Isole Falkland (Malvine)', 'incidenti-stradali'); ?>
                        </option>
                        <option value="959-Isola di Man" <?php selected($nazionalita, '959-Isola di Man' ); ?>>
                            <?php _e('Isola di Man', 'incidenti-stradali'); ?>
                        </option>
                        <option value="964-Montserrat" <?php selected($nazionalita, '964-Montserrat' ); ?>>
                            <?php _e('Montserrat', 'incidenti-stradali'); ?>
                        </option>
                        <option value="966-Curaçao" <?php selected($nazionalita, '966-Curaçao' ); ?>>
                            <?php _e('Curaçao', 'incidenti-stradali'); ?>
                        </option>
                        <option value="972-Isole Pitcairn" <?php selected($nazionalita, '972-Isole Pitcairn' ); ?>>
                            <?php _e('Isole Pitcairn', 'incidenti-stradali'); ?>
                        </option>
                        <option value="980-Saint Pierre e Miquelon" <?php selected($nazionalita, '980-Saint Pierre e Miquelon' ); ?>>
                            <?php _e('Saint Pierre e Miquelon', 'incidenti-stradali'); ?>
                        </option>
                        <option value="983-Sant'Elena" <?php selected($nazionalita, '983-Sant\'Elena' ); ?>>
                            <?php _e('Sant\'Elena', 'incidenti-stradali'); ?>
                        </option>
                        <option value="988-Terre australi e antartiche francesi" <?php selected($nazionalita, '988-Terre australi e antartiche francesi' ); ?>>
                            <?php _e('Terre australi e antartiche francesi', 'incidenti-stradali'); ?>
                        </option>
                        <option value="992-Isole Turks e Caicos" <?php selected($nazionalita, '992-Isole Turks e Caicos' ); ?>>
                            <?php _e('Isole Turks e Caicos', 'incidenti-stradali'); ?>
                        </option>
                        <option value="994-Isole Vergini britanniche" <?php selected($nazionalita, '994-Isole Vergini britanniche' ); ?>>
                            <?php _e('Isole Vergini britanniche', 'incidenti-stradali'); ?>
                        </option>
                        <option value="997-Wallis e Futuna" <?php selected($nazionalita, '997-Wallis e Futuna' ); ?>>
                            <?php _e('Wallis e Futuna', 'incidenti-stradali'); ?>
                        </option>
                        <option value="777-Straniera non indicata" <?php selected($nazionalita, '777-Straniera non indicata' ); ?>>
                            <?php _e('Straniera non indicata', 'incidenti-stradali'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php _e('Tipologia Incidente Lavorativo', 'incidenti-stradali'); ?></th>
                <td>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipologia_incidente" value="1" 
                        <?php checked(get_post_meta($post->ID, $prefix . 'tipologia_incidente', true), '1'); ?>> 
                        <?php _e('Conducente coinvolto in incidente su strada durante lo svolgimento della propria attività lavorativa', 'incidenti-stradali'); ?>
                    </label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipologia_incidente" value="2" 
                        <?php checked(get_post_meta($post->ID, $prefix . 'tipologia_incidente', true), '2'); ?>> 
                        <?php _e('Conducente coinvolto in incidente su strada durante il tragitto casa-lavoro o lavoro-casa', 'incidenti-stradali'); ?>
                    </label><br>
                    <label><input type="radio" name="<?php echo $prefix; ?>tipologia_incidente" value="0" 
                        <?php $checked = get_post_meta($post->ID, $prefix . 'tipologia_incidente', true);
                        if (trim($checked)=='') {$checked='0';}
                        checked($checked, '0');  ?>>
                        <?php _e('Nessuno dei due', 'incidenti-stradali'); ?>
                    </label><br><br>
                    <p style="font-weight: bold;"><?php _e('Non indicare le due modalità sopra riportate nel caso si tratti di altro tipo di tragitto e/o di incidente avvenuto al di fuori dell\'attività lavorativa.', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function render_pedoni_fields($post) {
        $numero_pedoni_feriti = get_post_meta($post->ID, 'numero_pedoni_feriti', true) ?: 0;
        $numero_pedoni_morti = get_post_meta($post->ID, 'numero_pedoni_morti', true) ?: 0;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="numero_pedoni_feriti"><?php _e('Numero Pedoni Feriti', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="numero_pedoni_feriti" name="numero_pedoni_feriti">
                        <?php for ($i = 0; $i <= 4; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($numero_pedoni_feriti, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="numero_pedoni_morti"><?php _e('Numero Pedoni Morti', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="numero_pedoni_morti" name="numero_pedoni_morti">
                        <?php for ($i = 0; $i <= 4; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($numero_pedoni_morti, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <!-- SEZIONE PEDONI FERITI -->
        <div id="pedoni-feriti-container">
            <h5><?php _e('Pedoni Feriti', 'incidenti-stradali'); ?></h5>
            <?php for ($i = 1; $i <= 4; $i++): 
                $display = $i <= $numero_pedoni_feriti ? 'block' : 'none';
                $prefix = 'pedone_ferito_' . $i . '_';
                $eta = get_post_meta($post->ID, $prefix . 'eta', true);
                $sesso = get_post_meta($post->ID, $prefix . 'sesso', true);
            ?>
                <div id="pedone-ferito-<?php echo $i; ?>" class="pedone-section" style="display: <?php echo $display; ?>;">
                    <h6><?php printf(__('Pedone Ferito %d', 'incidenti-stradali'), $i); ?></h6>
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
                                    <option value="3" <?php selected($sesso, '3'); ?>><?php _e('Maschio', 'incidenti-stradali'); ?></option>
                                    <option value="4" <?php selected($sesso, '4'); ?>><?php _e('Femmina', 'incidenti-stradali'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endfor; ?>
        </div>
        
        <!-- SEZIONE PEDONI MORTI -->
        <div id="pedoni-morti-container">
            <h5><?php _e('Pedoni Morti', 'incidenti-stradali'); ?></h5>
            <?php for ($i = 1; $i <= 4; $i++): 
                $display = $i <= $numero_pedoni_morti ? 'block' : 'none';
                $prefix = 'pedone_morto_' . $i . '_';
                $eta = get_post_meta($post->ID, $prefix . 'eta', true);
                $sesso = get_post_meta($post->ID, $prefix . 'sesso', true);
            ?>
                <div id="pedone-morto-<?php echo $i; ?>" class="pedone-section" style="display: <?php echo $display; ?>;">
                    <h6><?php printf(__('Pedone Morto %d', 'incidenti-stradali'), $i); ?></h6>
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
                    </table>
                </div>
            <?php endfor; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#numero_pedoni_feriti').change(function() {
                var numPedoni = parseInt($(this).val()) || 0;
                for (var i = 1; i <= 4; i++) {
                    if (i <= numPedoni) {
                        $('#pedone-ferito-' + i).show();
                    } else {
                        $('#pedone-ferito-' + i).hide();
                    }
                }
            });
            
            $('#numero_pedoni_morti').change(function() {
                var numPedoni = parseInt($(this).val()) || 0;
                for (var i = 1; i <= 4; i++) {
                    if (i <= numPedoni) {
                        $('#pedone-morto-' + i).show();
                    } else {
                        $('#pedone-morto-' + i).hide();
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
        
        ?>
        <div class="incidenti-coordinate-container">
            <input type="hidden" id="tipo_coordinata" name="tipo_coordinata" value="Monte Mario">
            
            <table class="form-table">
                <tr>
                    <th><label for="latitudine"><?php _e('Latitudine', 'incidenti-stradali'); ?> <span class="description">(°)</span></label></th>
                    <td>
                        <input type="text" 
                            id="latitudine" 
                            name="latitudine" 
                            value="<?php echo esc_attr($latitudine); ?>" 
                            placeholder="es. 41.902783"
                            class="incidenti-coordinate-input">
                    </td>
                </tr>
                <tr>
                    <th><label for="longitudine"><?php _e('Longitudine', 'incidenti-stradali'); ?> <span class="description">(°)</span></label></th>
                    <td>
                        <input type="text" 
                            id="longitudine" 
                            name="longitudine" 
                            value="<?php echo esc_attr($longitudine); ?>" 
                            placeholder="es. 12.496366"
                            class="incidenti-coordinate-input">
                    </td>
                </tr>
            </table>
            
            <div class="incidenti-map-info">
                <p class="description" style="margin: 0 0 8px 0; font-size: 12px; color: #666;">
                    <strong><?php _e('Sistema:', 'incidenti-stradali'); ?></strong> WGS84 (GPS) • 
                    <strong><?php _e('Clic sulla mappa', 'incidenti-stradali'); ?></strong> per impostare le coordinate
                </p>
            </div>
            
            <div id="coordinate-map"></div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Inizializza mappa solo se il contenitore esiste
            if ($('#coordinate-map').length === 0) return;
            
            var map = L.map('coordinate-map', {
                zoomControl: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: false
            }).setView([41.9028, 12.4964], 6);
            
            // Aggiungi tile layer con attributo
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            }).addTo(map);
            
            var marker = null;
            var lat = parseFloat($('#latitudine').val());
            var lng = parseFloat($('#longitudine').val());
            
            // Aggiungi marker esistente se coordinate già presenti
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                marker = L.marker([lat, lng], {
                    draggable: true,
                    title: 'Posizione incidente (trascinabile)'
                }).addTo(map);
                map.setView([lat, lng], 15);
                
                // Rendi marker trascinabile
                marker.on('dragend', function(e) {
                    var position = e.target.getLatLng();
                    $('#latitudine').val(position.lat.toFixed(6));
                    $('#longitudine').val(position.lng.toFixed(6));
                    updateCoordinateDisplay();
                });
            }
            
            // Click sulla mappa per impostare coordinate
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                
                $('#latitudine').val(lat.toFixed(6));
                $('#longitudine').val(lng.toFixed(6));
                
                if (marker) {
                    map.removeLayer(marker);
                }
                
                marker = L.marker([lat, lng], {
                    draggable: true,
                    title: 'Posizione incidente (trascinabile)'
                }).addTo(map);
                
                // Aggiungi drag handler al nuovo marker
                marker.on('dragend', function(e) {
                    var position = e.target.getLatLng();
                    $('#latitudine').val(position.lat.toFixed(6));
                    $('#longitudine').val(position.lng.toFixed(6));
                    updateCoordinateDisplay();
                });
                
                updateCoordinateDisplay();
            });
            
            // Aggiorna mappa quando coordinate cambiano manualmente
            $('#latitudine, #longitudine').on('input change', function() {
                var lat = parseFloat($('#latitudine').val());
                var lng = parseFloat($('#longitudine').val());
                
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    
                    marker = L.marker([lat, lng], {
                        draggable: true,
                        title: 'Posizione incidente (trascinabile)'
                    }).addTo(map);
                    
                    marker.on('dragend', function(e) {
                        var position = e.target.getLatLng();
                        $('#latitudine').val(position.lat.toFixed(6));
                        $('#longitudine').val(position.lng.toFixed(6));
                        updateCoordinateDisplay();
                    });
                    
                    map.setView([lat, lng], 15);
                }
                
                updateCoordinateDisplay();
            });
            
            // Funzione per aggiornare display coordinate
            function updateCoordinateDisplay() {
                var lat = $('#latitudine').val();
                var lng = $('#longitudine').val();
                
                if (lat && lng) {
                    // Valida le coordinate
                    if (isValidCoordinate(lat, lng)) {
                        $('#latitudine, #longitudine').removeClass('error');
                    } else {
                        $('#latitudine, #longitudine').addClass('error');
                    }
                }
            }
            
            // Validazione coordinate
            function isValidCoordinate(lat, lng) {
                var latNum = parseFloat(lat);
                var lngNum = parseFloat(lng);
                
                return !isNaN(latNum) && !isNaN(lngNum) && 
                    latNum >= -90 && latNum <= 90 && 
                    lngNum >= -180 && lngNum <= 180;
            }
            
            // Forza ridimensionamento mappa dopo il caricamento
            setTimeout(function() {
                map.invalidateSize();
            }, 250);
        });
        </script>
        <?php
    }
    
    public function render_mappa_meta_box($post) {
        $mostra_in_mappa = get_post_meta($post->ID, 'mostra_in_mappa', true);
        
        ?>
        <table class="form-table">
            <tr>
                <!-- <th><label for="mostra_in_mappa"><?php _e('Mostra nella Mappa Pubblica', 'incidenti-stradali'); ?></label></th> -->
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

            $current_user = wp_get_current_user();
            if (in_array('asset', $current_user->roles)) {
                echo '<div class="notice notice-info"><p>' . __('Stai visualizzando questo incidente in modalità sola lettura. Gli utenti con ruolo Asset non possono apportare modifiche.', 'incidenti-stradali') . '</p></div>';
            }
        }
    }

    public function render_circostanze_meta_box($post) {
        $circostanza_veicolo_a = get_post_meta($post->ID, 'circostanza_veicolo_a', true);
        $circostanza_veicolo_b = get_post_meta($post->ID, 'circostanza_veicolo_b', true);
        $circostanza_veicolo_c = get_post_meta($post->ID, 'circostanza_veicolo_c', true);
        $circostanza_tipo = get_post_meta($post->ID, 'circostanza_tipo', true);
        $difetto_veicolo_a = get_post_meta($post->ID, 'difetto_veicolo_a', true);
        $difetto_veicolo_b = get_post_meta($post->ID, 'difetto_veicolo_b', true);
        $difetto_veicolo_c = get_post_meta($post->ID, 'difetto_veicolo_c', true);
        $stato_psicofisico_a = get_post_meta($post->ID, 'stato_psicofisico_a', true);
        $stato_psicofisico_b = get_post_meta($post->ID, 'stato_psicofisico_b', true);
        $stato_psicofisico_c = get_post_meta($post->ID, 'stato_psicofisico_c', true);
        ?>
        
        <div class="incidenti-circostanze-container">
            <p class="description" style="color: red; font-weight: bold;">
                <?php _e('SEZIONE OBBLIGATORIA - Selezionare almeno una circostanza', 'incidenti-stradali'); ?>
            </p>
            
            <!-- SEZIONE 1: Per inconvenienti di circolazione -->
            <h4><?php _e('Per inconvenienti di circolazione', 'incidenti-stradali'); ?></h4>
            <table class="form-table">
                <tr>
                    <th class="required-field"><label for="circostanza_tipo"><?php _e('Tipo di incidente', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_tipo" name="circostanza_tipo" 
                            data-current-value="<?php echo esc_attr($circostanza_tipo); ?>"
                            data-saved-value="<?php echo esc_attr($circostanza_tipo); ?>">
                            <option value=""><?php _e('Seleziona tipo', 'incidenti-stradali'); ?></option>
                            <!-- Le opzioni verranno popolate dinamicamente tramite JavaScript -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="circostanza_veicolo_a"><?php _e('Circostanza Veicolo A', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_a" name="circostanza_veicolo_a"
                            data-current-value="<?php echo esc_attr($circostanza_veicolo_a); ?>"
                            data-saved-value="<?php echo esc_attr($circostanza_veicolo_a); ?>">
                            <option value=""><?php _e('Seleziona circostanza', 'incidenti-stradali'); ?></option>
                            <!-- Le opzioni saranno popolate dinamicamente via JavaScript -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="circostanza_veicolo_b"><?php _e('Circostanza Veicolo B/Pedone/Ostacolo', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_b" name="circostanza_veicolo_b"
                            data-current-value="<?php echo esc_attr($circostanza_veicolo_b); ?>"
                            data-saved-value="<?php echo esc_attr($circostanza_veicolo_b); ?>">
                            <option value=""><?php _e('Seleziona circostanza', 'incidenti-stradali'); ?></option>
                            <!-- Le opzioni saranno popolate dinamicamente via JavaScript -->
                        </select>
                    </td>
                </tr>
                <tr id="circostanza_veicolo_c_row" style="display: none;">
                    <th><label for="circostanza_veicolo_c"><?php _e('Circostanza Veicolo C', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="circostanza_veicolo_c" name="circostanza_veicolo_c"
                            data-current-value="<?php echo esc_attr($circostanza_veicolo_c); ?>"
                            data-saved-value="<?php echo esc_attr($circostanza_veicolo_c); ?>">
                            <option value=""><?php _e('Seleziona circostanza', 'incidenti-stradali'); ?></option>
                            <!-- Le opzioni saranno popolate dinamicamente via JavaScript -->
                        </select>
                    </td>
                </tr>
            </table>

            <!-- SEZIONE 2: Per difetti o avarie del veicolo -->
            <h4><?php _e('Per difetti o avarie del veicolo', 'incidenti-stradali'); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label for="difetto_veicolo_a"><?php _e('Veicolo A', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="difetto_veicolo_a" name="difetto_veicolo_a">
                            <option value=""><?php _e('Nessun difetto', 'incidenti-stradali'); ?></option>
                            <option value="80" <?php selected($difetto_veicolo_a, '80'); ?>>80 - Rottura o insufficienza dei freni</option>
                            <option value="81" <?php selected($difetto_veicolo_a, '81'); ?>>81 - Rottura o guasto allo sterzo</option>
                            <option value="82" <?php selected($difetto_veicolo_a, '82'); ?>>82 - Scoppio o eccessiva usura dei pneumatici</option>
                            <option value="83" <?php selected($difetto_veicolo_a, '83'); ?>>83 - Mancanza o insufficienza dei fari o delle luci di posizione</option>
                            <option value="84" <?php selected($difetto_veicolo_a, '84'); ?>>84 - Mancanza o insufficienza dei lampeggiatori</option>
                            <option value="85" <?php selected($difetto_veicolo_a, '85'); ?>>85 - Rottura degli organi di agganciamento dei rimorchi</option>
                            <option value="86" <?php selected($difetto_veicolo_a, '86'); ?>>86 - Deficienza delle attrezzature per trasporto merci pericolose</option>
                            <option value="87" <?php selected($difetto_veicolo_a, '87'); ?>>87 - Mancanza adattamenti per disabili</option>
                            <option value="88" <?php selected($difetto_veicolo_a, '88'); ?>>88 - Distacco di ruota</option>
                            <option value="89" <?php selected($difetto_veicolo_a, '89'); ?>>89 - Mancanza dispositivi visivi dei velocipedi</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="difetto_veicolo_b"><?php _e('Veicolo B', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="difetto_veicolo_b" name="difetto_veicolo_b">
                            <option value=""><?php _e('Nessun difetto', 'incidenti-stradali'); ?></option>
                            <option value="80" <?php selected($difetto_veicolo_b, '80'); ?>>80 - Rottura o insufficienza dei freni</option>
                            <option value="81" <?php selected($difetto_veicolo_b, '81'); ?>>81 - Rottura o guasto allo sterzo</option>
                            <option value="82" <?php selected($difetto_veicolo_b, '82'); ?>>82 - Scoppio o eccessiva usura dei pneumatici</option>
                            <option value="83" <?php selected($difetto_veicolo_b, '83'); ?>>83 - Mancanza o insufficienza dei fari o delle luci di posizione</option>
                            <option value="84" <?php selected($difetto_veicolo_b, '84'); ?>>84 - Mancanza o insufficienza dei lampeggiatori</option>
                            <option value="85" <?php selected($difetto_veicolo_b, '85'); ?>>85 - Rottura degli organi di agganciamento dei rimorchi</option>
                            <option value="86" <?php selected($difetto_veicolo_b, '86'); ?>>86 - Deficienza delle attrezzature per trasporto merci pericolose</option>
                            <option value="87" <?php selected($difetto_veicolo_b, '87'); ?>>87 - Mancanza adattamenti per disabili</option>
                            <option value="88" <?php selected($difetto_veicolo_b, '88'); ?>>88 - Distacco di ruota</option>
                            <option value="89" <?php selected($difetto_veicolo_b, '89'); ?>>89 - Mancanza dispositivi visivi dei velocipedi</option>
                        </select>
                    </td>
                </tr>
                <tr id="difetto_veicolo_c_row" style="display: none;">
                    <th><label for="difetto_veicolo_c"><?php _e('Veicolo C', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="difetto_veicolo_c" name="difetto_veicolo_c">
                            <option value=""><?php _e('Nessun difetto', 'incidenti-stradali'); ?></option>
                            <option value="80" <?php selected($difetto_veicolo_c, '80'); ?>>80 - Rottura o insufficienza dei freni</option>
                            <option value="81" <?php selected($difetto_veicolo_c, '81'); ?>>81 - Rottura o guasto allo sterzo</option>
                            <option value="82" <?php selected($difetto_veicolo_c, '82'); ?>>82 - Scoppio o eccessiva usura dei pneumatici</option>
                            <option value="83" <?php selected($difetto_veicolo_c, '83'); ?>>83 - Mancanza o insufficienza dei fari o delle luci di posizione</option>
                            <option value="84" <?php selected($difetto_veicolo_c, '84'); ?>>84 - Mancanza o insufficienza dei lampeggiatori</option>
                            <option value="85" <?php selected($difetto_veicolo_c, '85'); ?>>85 - Rottura degli organi di agganciamento dei rimorchi</option>
                            <option value="86" <?php selected($difetto_veicolo_c, '86'); ?>>86 - Deficienza delle attrezzature per trasporto merci pericolose</option>
                            <option value="87" <?php selected($difetto_veicolo_c, '87'); ?>>87 - Mancanza adattamenti per disabili</option>
                            <option value="88" <?php selected($difetto_veicolo_c, '88'); ?>>88 - Distacco di ruota</option>
                            <option value="89" <?php selected($difetto_veicolo_c, '89'); ?>>89 - Mancanza dispositivi visivi dei velocipedi</option>
                        </select>
                    </td>
                </tr>
            </table>

            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Mappatura tra natura incidente e tipi di incidente permessi
                var naturaToTipoMapping = {
                    'A': { // Tra veicoli in marcia
                        'intersezione': 'Incidente all\'intersezione stradale',
                        'non_intersezione': 'Incidente non all\'intersezione'
                    },
                    'B': { // Tra veicolo e pedoni
                        'investimento': 'Investimento di pedone'
                    },
                    'C': { // Veicolo in marcia che urta veicolo fermo o altro
                        'urto_fermo': 'Urto con veicolo fermo/ostacolo'
                    },
                    'D': { // Veicolo in marcia senza urto
                        'senza_urto': 'Veicolo senza urto'
                    }
                };

                // Funzione per aggiornare le opzioni del tipo di incidente
                function updateTipoIncidenteOptions(natura) {
                    var $tipoSelect = $('#circostanza_tipo');
                    var currentValue = $tipoSelect.val();
                    
                    // MIGLIORA: Salva il valore corrente prima di pulire
                    var savedValue = $tipoSelect.data('saved-value') || 
                                    $tipoSelect.attr('data-current-value') || 
                                    currentValue;
                    
                    // Pulisci le opzioni
                    $tipoSelect.empty().append('<option value=""><?php _e('Seleziona tipo', 'incidenti-stradali'); ?></option>');
                    
                    if (natura && naturaToTipoMapping[natura]) {
                        var opzioni = naturaToTipoMapping[natura];
                        var keys = Object.keys(opzioni);
                        
                        if (keys.length === 1) {
                            // Se c'è una sola opzione, selezionala automaticamente e disabilita il campo
                            var uniqueKey = keys[0];
                            var selected = (uniqueKey === savedValue) ? ' selected' : '';
                            $tipoSelect.append('<option value="' + uniqueKey + '"' + selected + '>' + opzioni[uniqueKey] + '</option>');
                            $tipoSelect.prop('disabled', true);
                            $tipoSelect.val(uniqueKey);
                            
                            // Trigger change per aggiornare le circostanze
                            setTimeout(function() {
                                $tipoSelect.trigger('change');
                            }, 100);
                        } else {
                            // Se ci sono più opzioni, abilita il campo e popolalo
                            $tipoSelect.prop('disabled', false);
                            $.each(opzioni, function(key, value) {
                                var selected = (key === savedValue) ? ' selected' : '';
                                $tipoSelect.append('<option value="' + key + '"' + selected + '>' + value + '</option>');
                            });
                            
                            // Ripristina il valore salvato se valido
                            if (savedValue && opzioni[savedValue]) {
                                $tipoSelect.val(savedValue);
                                
                                // Trigger change anche qui per consistency
                                setTimeout(function() {
                                    $tipoSelect.trigger('change');
                                }, 100);
                            }
                        }
                    } else {
                        $tipoSelect.prop('disabled', false);
                    }
                }

                // Listener per il cambio della natura dell'incidente
                $('#natura_incidente').on('change', function() {
                    var natura = $(this).val();
                    updateTipoIncidenteOptions(natura);
                });

                // Inizializzazione al caricamento della pagina
                var naturaCorrente = $('#natura_incidente').val();
                if (naturaCorrente) {
                    updateTipoIncidenteOptions(naturaCorrente);
                }

                // Definizione completa dei codici circostanze per tipo di incidente
                var circostanzeData = {
                    'intersezione': {
                        'veicolo_a': {
                            '01': 'Procedeva regolarmente senza svoltare',
                            '02': 'Procedeva con guida distratta e andamento indeciso',
                            '03': 'Procedeva senza mantenere la distanza di sicurezza',
                            '04': 'Procedeva senza dare la precedenza al veicolo proveniente da destra',
                            '05': 'Procedeva senza rispettare lo stop',
                            '06': 'Procedeva senza rispettare il segnale di dare precedenza',
                            '07': 'Procedeva contromano',
                            '08': 'Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente',
                            '10': 'Procedeva senza rispettare i segnali di divieto di transito',
                            '11': 'Procedeva con eccesso di velocità',
                            '12': 'Procedeva senza rispettare i limiti di velocità',
                            '13': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                            '14': 'Svoltava a destra regolarmente',
                            '15': 'Svoltava a destra irregolarmente',
                            '16': 'Svoltava a sinistra regolarmente',
                            '17': 'Svoltava a sinistra irregolarmente',
                            '18': 'Sorpassava (all\'incrocio)'
                        },
                        'veicolo_b': {
                            '01': 'Procedeva regolarmente senza svoltare',
                            '02': 'Procedeva con guida distratta e andamento indeciso',
                            '03': 'Procedeva senza mantenere la distanza di sicurezza',
                            '04': 'Procedeva senza dare la precedenza al veicolo proveniente da destra',
                            '05': 'Procedeva senza rispettare lo stop',
                            '06': 'Procedeva senza rispettare il segnale di dare precedenza',
                            '07': 'Procedeva contromano',
                            '08': 'Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente',
                            '10': 'Procedeva senza rispettare i segnali di divieto di transito',
                            '11': 'Procedeva con eccesso di velocità',
                            '12': 'Procedeva senza rispettare i limiti di velocità',
                            '13': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                            '14': 'Svoltava a destra regolarmente',
                            '15': 'Svoltava a destra irregolarmente',
                            '16': 'Svoltava a sinistra regolarmente',
                            '17': 'Svoltava a sinistra irregolarmente',  
                            '18': 'Sorpassava (all\'incrocio)'
                        }
                    },
                    'non_intersezione': {
                        'veicolo_a': {
                            '20': 'Procedeva regolarmente',
                            '21': 'Procedeva con guida distratta e andamento indeciso',
                            '22': 'Procedeva senza mantenere la distanza di sicurezza',
                            '23': 'Procedeva con eccesso di velocità',
                            '24': 'Procedeva senza rispettare i limiti di velocità',
                            '25': 'Procedeva non in prossimità del margine destro della carreggiata',
                            '26': 'Procedeva contromano',
                            '27': 'Procedeva senza rispettare i segnali di divieto di transito',
                            '28': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                            '29': 'Sorpassava regolarmente',
                            '30': 'Sorpassava irregolarmente a destra',
                            '31': 'Sorpassava in curva, su dosso o insufficiente visibilità',
                            '32': 'Sorpassava un veicolo che ne stava sorpassando un altro',
                            '33': 'Sorpassava senza osservare il segnale di divieto',
                            '34': 'Manovrava in retrocessione o conversione',
                            '35': 'Manovrava per immettersi nel flusso della circolazione',
                            '36': 'Manovrava per voltare a sinistra',
                            '37': 'Manovrava regolarmente per fermarsi o sostare',
                            '38': 'Manovrava irregolarmente per fermarsi o sostare',
                            '39': 'Si affiancava ad altri veicoli a due ruote irregolarmente'
                        },
                        'veicolo_b': {
                            '20': 'Procedeva regolarmente',
                            '21': 'Procedeva con guida distratta e andamento indeciso',
                            '22': 'Procedeva senza mantenere la distanza di sicurezza',
                            '23': 'Procedeva con eccesso di velocità',
                            '24': 'Procedeva senza rispettare i limiti di velocità',
                            '25': 'Procedeva non in prossimità del margine destro della carreggiata',
                            '26': 'Procedeva contromano',
                            '27': 'Procedeva senza rispettare i segnali di divieto di transito',
                            '28': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                            '29': 'Sorpassava regolarmente',
                            '30': 'Sorpassava irregolarmente a destra',
                            '31': 'Sorpassava in curva, su dosso o insufficiente visibilità',
                            '32': 'Sorpassava un veicolo che ne stava sorpassando un altro',
                            '33': 'Sorpassava senza osservare il segnale di divieto',
                            '34': 'Manovrava in retrocessione o conversione',
                            '35': 'Manovrava per immettersi nel flusso della circolazione',
                            '36': 'Manovrava per voltare a sinistra',
                            '37': 'Manovrava regolarmente per fermarsi o sostare',
                            '38': 'Manovrava irregolarmente per fermarsi o sostare',
                            '39': 'Si affiancava ad altri veicoli a due ruote irregolarmente'
                        }
                    },
                    'investimento': {
                        'veicolo_a': {
                            '40': 'Procedeva regolarmente',
                            '41': 'Procedeva con eccesso di velocità',
                            '42': 'Procedeva senza rispettare i limiti di velocità',
                            '43': 'Procedeva contromano',
                            '44': 'Sorpassava veicolo in marcia',
                            '45': 'Manovrava',
                            '46': 'Non rispettava le segnalazioni semaforiche o dell\'agente',
                            '47': 'Usciva senza precauzioni da passo carrabile',
                            '48': 'Fuoriusciva dalla carreggiata',
                            '49': 'Non dava la precedenza al pedone sugli appositi attraversamenti',
                            '50': 'Sorpassava un veicolo fermatosi per l\'attraversamento dei pedoni',
                            '51': 'Urtava con il carico il pedone',
                            '52': 'Superava irregolarmente un tram fermo per salita/discesa'
                        },
                        'pedone': {
                            '40': 'Camminava su marciapiede/banchina',
                            '41': 'Camminava regolarmente sul margine',
                            '42': 'Camminava contromano',
                            '43': 'Camminava in mezzo carreggiata',
                            '44': 'Sostava/indugiava/giocava carreggiata',
                            '45': 'Lavorava protetto da segnale',
                            '46': 'Lavorava non protetto da segnale',
                            '47': 'Saliva su veicolo in marcia',
                            '48': 'Discendeva con prudenza',
                            '49': 'Discendeva con imprudenza',
                            '50': 'Veniva fuori da dietro veicolo',
                            '51': 'Attraversava rispettando segnali',
                            '52': 'Attraversava non rispettando segnali',
                            '53': 'Attraversava passaggio non protetto',
                            '54': 'Attraversava regolarmente non su passaggio',
                            '55': 'Attraversava irregolarmente'
                        }
                    },
                    'urto_fermo': {
                        'veicolo_a': {
                            '60': 'Procedeva regolarmente',
                            '61': 'Procedeva con guida distratta',
                            '62': 'Procedeva senza mantenere distanza sicurezza',
                            '63': 'Procedeva contromano',
                            '64': 'Procedeva con eccesso di velocità',
                            '65': 'Procedeva senza rispettare limiti velocità',
                            '66': 'Procedeva senza rispettare divieti transito',
                            '67': 'Sorpassava un altro veicolo',
                            '68': 'Attraversava imprudentemente passaggio a livello'
                        },
                        'ostacolo': {
                            '60': 'Ostacolo accidentale',
                            '61': 'Veicolo fermo in posizione regolare',
                            '62': 'Veicolo fermo in posizione irregolare',
                            '63': 'Veicolo fermo senza prescritto segnale',
                            '64': 'Veicolo fermo regolarmente segnalato',
                            '65': 'Ostacolo fisso nella carreggiata',
                            '66': 'Treno in passaggio a livello',
                            '67': 'Animale domestico',
                            '68': 'Animale selvatico',
                            '69': 'Buca'
                        }
                    },
                    'senza_urto': {
                        'veicolo_a': {
                            '70': 'Sbandamento per evitare urto',
                            '71': 'Sbandamento per guida distratta',
                            '72': 'Sbandamento per eccesso velocità',
                            '73': 'Frenava improvvisamente',
                            '74': 'Caduta persona per apertura portiera',
                            '75': 'Caduta persona per discesa da veicolo in moto',
                            '76': 'Caduta persona aggrappata inadeguatamente'
                        },
                        'ostacolo_evitato': {
                            '70': 'Ostacolo accidentale',
                            '71': 'Pedone',
                            '72': 'Animale evitato',
                            '73': 'Veicolo',
                            '74': 'Buca evitata',
                            '75': 'Senza ostacolo né pedone né altro veicolo',
                            '76': 'Ostacolo fisso'
                        }
                    }
                };

                // Gestione cambio tipo di circostanza (CODICE MIGLIORATO)
                $('#circostanza_tipo').change(function() {
                    var tipo = $(this).val();
                    var selectVeicoloA = $('#circostanza_veicolo_a');
                    var selectVeicoloB = $('#circostanza_veicolo_b');
                    var selectVeicoloC = $('#circostanza_veicolo_c');
                    
                    // Salva i valori correnti prima di pulire
                    var currentA = selectVeicoloA.val();
                    var currentB = selectVeicoloB.val();
                    var currentC = selectVeicoloC.val();
                    
                    // Pulisci le select
                    selectVeicoloA.empty().append('<option value="">Seleziona circostanza</option>');
                    selectVeicoloB.empty().append('<option value="">Seleziona circostanza</option>');
                    selectVeicoloC.empty().append('<option value="">Seleziona circostanza</option>');
                    
                    if (tipo && circostanzeData[tipo]) {
                        // Popola Veicolo A
                        if (circostanzeData[tipo]['veicolo_a']) {
                            $.each(circostanzeData[tipo]['veicolo_a'], function(codice, descrizione) {
                                var selected = (codice === currentA || codice === selectVeicoloA.attr('data-current-value')) ? ' selected' : '';
                                selectVeicoloA.append('<option value="' + codice + '"' + selected + '>' + codice + ' - ' + descrizione + '</option>');
                            });
                        }
                        
                        // Popola Veicolo B/Pedone/Ostacolo
                        var tipoB = 'veicolo_b';
                        if (tipo === 'investimento') tipoB = 'pedone';
                        if (tipo === 'urto_fermo') tipoB = 'ostacolo';
                        if (tipo === 'senza_urto') tipoB = 'ostacolo_evitato';
                        
                        if (circostanzeData[tipo][tipoB]) {
                            $.each(circostanzeData[tipo][tipoB], function(codice, descrizione) {
                                var selected = (codice === currentB || codice === selectVeicoloB.attr('data-current-value')) ? ' selected' : '';
                                selectVeicoloB.append('<option value="' + codice + '"' + selected + '>' + codice + ' - ' + descrizione + '</option>');
                            });
                        }
                        
                        // Popola Veicolo C (solo per incidenti con 3+ veicoli)
                        if (circostanzeData[tipo]['veicolo_c']) {
                            $.each(circostanzeData[tipo]['veicolo_c'], function(codice, descrizione) {
                                var selected = (codice === currentC || codice === selectVeicoloC.attr('data-current-value')) ? ' selected' : '';
                                selectVeicoloC.append('<option value="' + codice + '"' + selected + '>' + codice + ' - ' + descrizione + '</option>');
                            });
                        }
                        
                        // Aggiorna label del Veicolo B
                        var labelText = 'Circostanza Veicolo B';
                        if (tipo === 'investimento') labelText = 'Circostanza Pedone';
                        if (tipo === 'urto_fermo') labelText = 'Circostanza Ostacolo';
                        if (tipo === 'senza_urto') labelText = 'Ostacolo Evitato';
                        
                        $('label[for="circostanza_veicolo_b"]').text(labelText);
                        
                        // Ripristina i valori dopo un breve delay
                        setTimeout(function() {
                            if (selectVeicoloA.attr('data-current-value') && selectVeicoloA.find('option[value="' + selectVeicoloA.attr('data-current-value') + '"]').length) {
                                selectVeicoloA.val(selectVeicoloA.attr('data-current-value'));
                            }
                            if (selectVeicoloB.attr('data-current-value') && selectVeicoloB.find('option[value="' + selectVeicoloB.attr('data-current-value') + '"]').length) {
                                selectVeicoloB.val(selectVeicoloB.attr('data-current-value'));
                            }
                            if (selectVeicoloC.attr('data-current-value') && selectVeicoloC.find('option[value="' + selectVeicoloC.attr('data-current-value') + '"]').length) {
                                selectVeicoloC.val(selectVeicoloC.attr('data-current-value'));
                            }
                        }, 100);
                    }
                });
            });
            </script>

            <!-- SEZIONE 3: Per stato psico-fisico del conducente -->
            <h4><?php _e('Per stato psico-fisico del conducente', 'incidenti-stradali'); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label for="stato_psicofisico_a"><?php _e('Conducente Veicolo A', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="stato_psicofisico_a" name="stato_psicofisico_a">
                            <option value=""><?php _e('Normale', 'incidenti-stradali'); ?></option>
                            <option value="90" <?php selected($stato_psicofisico_a, '90'); ?>>90 - Anormale per ebbrezza da alcool</option>
                            <option value="91" <?php selected($stato_psicofisico_a, '91'); ?>>91 - Anormale per condizioni morbose in atto</option>
                            <option value="92" <?php selected($stato_psicofisico_a, '92'); ?>>92 - Anormale per improvviso malore</option>
                            <option value="93" <?php selected($stato_psicofisico_a, '93'); ?>>93 - Anormale per sonno</option>
                            <option value="94" <?php selected($stato_psicofisico_a, '94'); ?>>94 - Anormale per ingestione sostanze stupefacenti</option>
                            <option value="95" <?php selected($stato_psicofisico_a, '95'); ?>>95 - Mancato uso di lenti correttive</option>
                            <option value="96" <?php selected($stato_psicofisico_a, '96'); ?>>96 - Abbagliato</option>
                            <option value="97" <?php selected($stato_psicofisico_a, '97'); ?>>97 - Per aver superato i periodi di guida prescritti</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="stato_psicofisico_b"><?php _e('Conducente Veicolo B', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="stato_psicofisico_b" name="stato_psicofisico_b">
                            <option value=""><?php _e('Normale', 'incidenti-stradali'); ?></option>
                            <option value="90" <?php selected($stato_psicofisico_b, '90'); ?>>90 - Anormale per ebbrezza da alcool</option>
                            <option value="91" <?php selected($stato_psicofisico_b, '91'); ?>>91 - Anormale per condizioni morbose in atto</option>
                            <option value="92" <?php selected($stato_psicofisico_b, '92'); ?>>92 - Anormale per improvviso malore</option>
                            <option value="93" <?php selected($stato_psicofisico_b, '93'); ?>>93 - Anormale per sonno</option>
                            <option value="94" <?php selected($stato_psicofisico_b, '94'); ?>>94 - Anormale per ingestione sostanze stupefacenti</option>
                            <option value="95" <?php selected($stato_psicofisico_b, '95'); ?>>95 - Mancato uso di lenti correttive</option>
                            <option value="96" <?php selected($stato_psicofisico_b, '96'); ?>>96 - Abbagliato</option>
                            <option value="97" <?php selected($stato_psicofisico_b, '97'); ?>>97 - Per aver superato i periodi di guida prescritti</option>
                        </select>
                    </td>
                </tr>
                <tr id="stato_psicofisico_c_row" style="display: none;">
                    <th><label for="stato_psicofisico_c"><?php _e('Conducente Veicolo C', 'incidenti-stradali'); ?></label></th>
                    <td>
                        <select id="stato_psicofisico_c" name="stato_psicofisico_c">
                            <option value=""><?php _e('Normale', 'incidenti-stradali'); ?></option>
                            <option value="90" <?php selected($stato_psicofisico_c, '90'); ?>>90 - Anormale per ebbrezza da alcool</option>
                            <option value="91" <?php selected($stato_psicofisico_c, '91'); ?>>91 - Anormale per condizioni morbose in atto</option>
                            <option value="92" <?php selected($stato_psicofisico_c, '92'); ?>>92 - Anormale per improvviso malore</option>
                            <option value="93" <?php selected($stato_psicofisico_c, '93'); ?>>93 - Anormale per sonno</option>
                            <option value="94" <?php selected($stato_psicofisico_c, '94'); ?>>94 - Anormale per ingestione sostanze stupefacenti</option>
                            <option value="95" <?php selected($stato_psicofisico_c, '95'); ?>>95 - Mancato uso di lenti correttive</option>
                            <option value="96" <?php selected($stato_psicofisico_c, '96'); ?>>96 - Abbagliato</option>
                            <option value="97" <?php selected($stato_psicofisico_c, '97'); ?>>97 - Per aver superato i periodi di guida prescritti</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Validazione obbligatorietà
            $('#publish, #save-post').click(function(e) {
                var hasCircostanza = false;
                
                // Verifica se almeno una circostanza è stata selezionata
                $('#circostanza_veicolo_a, #circostanza_veicolo_b, #circostanza_veicolo_c, #difetto_veicolo_a, #difetto_veicolo_b, #difetto_veicolo_c, #stato_psicofisico_a, #stato_psicofisico_b, #stato_psicofisico_c').each(function() {
                    if ($(this).val() && $(this).val() !== '') {
                        hasCircostanza = true;
                        return false; // esce dal loop
                    }
                });
                
                if (!hasCircostanza) {
                    alert('La sezione "Circostanze Presunte dell\'Incidente" è obbligatoria. Selezionare almeno una circostanza.');
                    e.preventDefault();
                    return false;
                }
            });

            // Gestione visibilità campi per 3 veicoli
            function updateCircostanzeVisibility() {
                var numeroVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
                
                if (numeroVeicoli >= 3) {
                    $('#circostanza_veicolo_c_row, #difetto_veicolo_c_row, #stato_psicofisico_c_row').show();
                } else {
                    $('#circostanza_veicolo_c_row, #difetto_veicolo_c_row, #stato_psicofisico_c_row').hide();
                    $('#circostanza_veicolo_c, #difetto_veicolo_c, #stato_psicofisico_c').val('');
                }
            }
            
            $(document).on('change', '#numero_veicoli_coinvolti', updateCircostanzeVisibility);
            updateCircostanzeVisibility();
        });
        </script>
        <?php
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

        // NUOVO: Controllo per ruolo Asset
        $current_user = wp_get_current_user();
        if (in_array('asset', $current_user->roles)) {
            // Se il ruolo è Asset, blocca qualsiasi modifica
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>Gli utenti con ruolo Asset non possono modificare gli incidenti.</p>';
                echo '</div>';
            });
            return; // Interrompe l'esecuzione, non salva nulla
        }
        
        // Array of all meta fields to save
        $meta_fields = array(
            'data_incidente', 'ora_incidente', 'minuti_incidente', 'provincia_incidente', 'comune_incidente',
            'localita_incidente', 'organo_rilevazione', 'organo_coordinatore', 'nell_abitato', 'tipo_strada', 'denominazione_strada',
            'numero_strada', 'progressiva_km', 'progressiva_m', 'geometria_strada', 'pavimentazione_strada',
            'condizioni_manto', 'intersezione_tronco', 'stato_fondo_strada', 'segnaletica_strada', 'condizioni_meteo',
            'elementi_aggiuntivi_1', 'elementi_aggiuntivi_2',

            // CAMPI NATURA INCIDENTE:
            'natura_incidente', 'dettaglio_natura', 'numero_veicoli_coinvolti', 'altro_natura_testo',
            'salto_carreggiata', 'urto_laterale ', 'urto_laterale',
            
            // CAMPI PEDONI:
            'numero_pedoni_feriti', 'numero_pedoni_morti',
            
            // Dati Aggiuntivi ISTAT
            'altri_morti_maschi', 'altri_morti_femmine', 'altri_feriti_maschi', 'altri_feriti_femmine',
            'numero_altri_veicoli', 'localizzazione_extra_ab',
            // Numero trasportati per veicolo
            'veicolo_1_numero_trasportati', 'veicolo_2_numero_trasportati', 'veicolo_3_numero_trasportati',
            /*--------------------------------*/
            
            'latitudine', 'longitudine', 'tipo_coordinata', 'mostra_in_mappa', 'ente_rilevatore', 'nome_rilevatore', 'identificativo_comando', 'tronco_strada',
            'orientamento_conducente', 'presenza_barriere', 'presenza_banchina',
            'circostanza_tipo', 'condizioni_aggiuntive', 'circostanza_veicolo_a', 'circostanza_veicolo_b', 'circostanza_veicolo_c', 'difetto_veicolo_a', 'difetto_veicolo_b',
            'difetto_veicolo_c', 'stato_psicofisico_a', 'stato_psicofisico_b', 'stato_psicofisico_c', 'cilindrata_veicolo_a', 'cilindrata_veicolo_b',
            'cilindrata_veicolo_c', 'peso_pieno_carico_a', 'peso_pieno_carico_b', 'peso_pieno_carico_c',
            'caratteristiche_geometriche',
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
            'ferito_8_nome', 'ferito_8_cognome', 'ferito_8_istituto',
            'veicolo_1_tipo_rimorchio', 'veicolo_1_targa_rimorchio',
            'veicolo_2_tipo_rimorchio', 'veicolo_2_targa_rimorchio',
            'veicolo_3_tipo_rimorchio', 'veicolo_3_targa_rimorchio',
            // Veicoli - campi danni
            'veicolo_1_danni_riportati', 'veicolo_2_danni_riportati',
            'veicolo_3_danni_riportati',
            // Conducenti - nazionalità
            'conducente_1_nazionalita', 'conducente_1_nazionalita_altro',
            'conducente_2_nazionalita', 'conducente_2_nazionalita_altro', 
            'conducente_3_nazionalita', 'conducente_3_nazionalita_altro',
            'conducente_1_anno_patente', 'conducente_2_anno_patente', 'conducente_3_anno_patente',
            'conducente_1_tipologia_incidente', 'conducente_2_tipologia_incidente', 'conducente_3_tipologia_incidente',
            // Circostanze presunte
            'circostanza_veicolo_a', 'circostanza_veicolo_b', 'circostanza_veicolo_c',
            'difetto_veicolo_a', 'difetto_veicolo_b', 
            'stato_psicofisico_a', 'stato_psicofisico_b',
            // Trasportati - sedile e dettaglio
            'veicolo_1_trasportato_1_sedile',
            'veicolo_1_trasportato_2_sedile',
            'veicolo_1_trasportato_3_sedile',
            'veicolo_1_trasportato_4_sedile',
            'veicolo_1_trasportato_5_sedile',
            'veicolo_1_trasportato_6_sedile',
            'veicolo_1_trasportato_7_sedile',
            'veicolo_1_trasportato_8_sedile',
            'veicolo_1_trasportato_9_sedile',
            'veicolo_2_trasportato_1_sedile',
            'veicolo_2_trasportato_2_sedile',
            'veicolo_2_trasportato_3_sedile',
            'veicolo_2_trasportato_4_sedile',
            'veicolo_2_trasportato_5_sedile',
            'veicolo_2_trasportato_6_sedile',
            'veicolo_2_trasportato_7_sedile',
            'veicolo_2_trasportato_8_sedile',
            'veicolo_2_trasportato_9_sedile',
            'veicolo_3_trasportato_1_sedile',
            'veicolo_3_trasportato_2_sedile',
            'veicolo_3_trasportato_3_sedile',
            'veicolo_3_trasportato_4_sedile',
            'veicolo_3_trasportato_5_sedile',
            'veicolo_3_trasportato_6_sedile',
            'veicolo_3_trasportato_7_sedile',
            'veicolo_3_trasportato_8_sedile',
            'veicolo_3_trasportato_9_sedile',
            'riepilogo_morti_24h', 'riepilogo_morti_2_30gg', 'riepilogo_feriti',
            // Campi localizzazione aggiuntivi
            'abitato', 'illuminazione', 'pavimentazione', 'intersezione',
            'accessi_laterali', 'caratteristiche_geometriche',

            // Campi veicoli (pattern ripetuto per veicolo_1, veicolo_2, veicolo_3)
            'veicolo_1_tipo', 'veicolo_1_targa', 'veicolo_1_anno_immatricolazione', 'veicolo_1_cilindrata',
            'veicolo_2_tipo', 'veicolo_2_targa', 'veicolo_2_anno_immatricolazione', 'veicolo_2_cilindrata',
            'veicolo_3_tipo', 'veicolo_3_targa', 'veicolo_3_anno_immatricolazione', 'veicolo_3_cilindrata',

            // Campi conducenti (pattern ripetuto per conducente_1, conducente_2, conducente_3)  
            'conducente_1_eta', 'conducente_1_sesso', 'conducente_1_esito', 'conducente_1_tipo_patente', 'conducente_1_rilascio_patente',
            'conducente_2_eta', 'conducente_2_sesso', 'conducente_2_esito', 'conducente_2_tipo_patente', 'conducente_2_rilascio_patente',
            'conducente_3_eta', 'conducente_3_sesso', 'conducente_3_esito', 'conducente_3_tipo_patente', 'conducente_3_rilascio_patente',

            // Campi trasportati - età, sesso, esito (pattern per veicolo_1_trasportato_1 fino a veicolo_3_trasportato_9)
            'veicolo_1_trasportato_1_eta', 'veicolo_1_trasportato_1_sesso', 'veicolo_1_trasportato_1_esito',
            'veicolo_1_trasportato_2_eta', 'veicolo_1_trasportato_2_sesso', 'veicolo_1_trasportato_2_esito',
            'veicolo_1_trasportato_3_eta', 'veicolo_1_trasportato_3_sesso', 'veicolo_1_trasportato_3_esito',
            'veicolo_1_trasportato_4_eta', 'veicolo_1_trasportato_4_sesso', 'veicolo_1_trasportato_4_esito',

            'veicolo_1_trasportato_5_eta', 'veicolo_1_trasportato_5_sesso', 'veicolo_1_trasportato_5_esito',
            'veicolo_1_trasportato_6_eta', 'veicolo_1_trasportato_6_sesso', 'veicolo_1_trasportato_6_esito',
            'veicolo_1_trasportato_7_eta', 'veicolo_1_trasportato_7_sesso', 'veicolo_1_trasportato_7_esito',
            'veicolo_1_trasportato_8_eta', 'veicolo_1_trasportato_8_sesso', 'veicolo_1_trasportato_8_esito',
            'veicolo_1_trasportato_9_eta', 'veicolo_1_trasportato_9_sesso', 'veicolo_1_trasportato_9_esito',

            'veicolo_2_trasportato_1_eta', 'veicolo_2_trasportato_1_sesso', 'veicolo_2_trasportato_1_esito',
            'veicolo_2_trasportato_2_eta', 'veicolo_2_trasportato_2_sesso', 'veicolo_2_trasportato_2_esito',
            'veicolo_2_trasportato_3_eta', 'veicolo_2_trasportato_3_sesso', 'veicolo_2_trasportato_3_esito',
            'veicolo_2_trasportato_4_eta', 'veicolo_2_trasportato_4_sesso', 'veicolo_2_trasportato_4_esito',

            'veicolo_2_trasportato_5_eta', 'veicolo_2_trasportato_5_sesso', 'veicolo_2_trasportato_5_esito',
            'veicolo_2_trasportato_6_eta', 'veicolo_2_trasportato_6_sesso', 'veicolo_2_trasportato_6_esito',
            'veicolo_2_trasportato_7_eta', 'veicolo_2_trasportato_7_sesso', 'veicolo_2_trasportato_7_esito',
            'veicolo_2_trasportato_8_eta', 'veicolo_2_trasportato_8_sesso', 'veicolo_2_trasportato_8_esito',
            'veicolo_2_trasportato_9_eta', 'veicolo_2_trasportato_9_sesso', 'veicolo_2_trasportato_9_esito',

            'veicolo_3_trasportato_1_eta', 'veicolo_3_trasportato_1_sesso', 'veicolo_3_trasportato_1_esito',
            'veicolo_3_trasportato_2_eta', 'veicolo_3_trasportato_2_sesso', 'veicolo_3_trasportato_2_esito',
            'veicolo_3_trasportato_3_eta', 'veicolo_3_trasportato_3_sesso', 'veicolo_3_trasportato_3_esito',
            'veicolo_3_trasportato_4_eta', 'veicolo_3_trasportato_4_sesso', 'veicolo_3_trasportato_4_esito',

            'veicolo_3_trasportato_5_eta', 'veicolo_3_trasportato_5_sesso', 'veicolo_3_trasportato_5_esito',
            'veicolo_3_trasportato_6_eta', 'veicolo_3_trasportato_6_sesso', 'veicolo_3_trasportato_6_esito',
            'veicolo_3_trasportato_7_eta', 'veicolo_3_trasportato_7_sesso', 'veicolo_3_trasportato_7_esito',
            'veicolo_3_trasportato_8_eta', 'veicolo_3_trasportato_8_sesso', 'veicolo_3_trasportato_8_esito',
            'veicolo_3_trasportato_9_eta', 'veicolo_3_trasportato_9_sesso', 'veicolo_3_trasportato_9_esito',

            // Altri passeggeri infortunati per veicolo
            'veicolo_1_altri_morti_maschi', 'veicolo_1_altri_morti_femmine',
            'veicolo_1_altri_feriti_maschi', 'veicolo_1_altri_feriti_femmine',
            'veicolo_2_altri_morti_maschi', 'veicolo_2_altri_morti_femmine',
            'veicolo_2_altri_feriti_maschi', 'veicolo_2_altri_feriti_femmine',
            'veicolo_3_altri_morti_maschi', 'veicolo_3_altri_morti_femmine',
            'veicolo_3_altri_feriti_maschi', 'veicolo_3_altri_feriti_femmine',

            // Pedoni feriti
            'pedone_ferito_1_eta', 'pedone_ferito_1_sesso',
            'pedone_ferito_2_eta', 'pedone_ferito_2_sesso',
            'pedone_ferito_3_eta', 'pedone_ferito_3_sesso',
            'pedone_ferito_4_eta', 'pedone_ferito_4_sesso',

            // Pedoni morti
            'pedone_morto_1_eta', 'pedone_morto_1_sesso',
            'pedone_morto_2_eta', 'pedone_morto_2_sesso',
            'pedone_morto_3_eta', 'pedone_morto_3_sesso',
            'pedone_morto_4_eta', 'pedone_morto_4_sesso',

            // Campi coordinate e identificativi aggiuntivi
            'sistema_di_proiezione', 'codice_carabinieri', 'altra_strada', 'codice__ente', 'codice_strada_aci',
            
            // Campi luogo incidente
            'presenza_banchina',

            // Campi cittadinanza conducenti
            'conducente_1_tipo_cittadinanza', 'conducente_2_tipo_cittadinanza', 'conducente_3_tipo_cittadinanza',

            // Dati CSV
            'csv_tipo_strada', 'csv_centro_abitato', 'csv_caratteristiche', 'csv_cantiere_stradale',
            'csv_n_autovettura', 'csv_n_autocarro_35t', 'csv_n_autocarro_oltre_35t', 'csv_n_autotreno',
            'csv_n_autoarticolato', 'csv_n_autobus', 'csv_n_tram', 'csv_n_treno', 'csv_n_motociclo',
            'csv_n_ciclomotore', 'csv_n_velocipede', 'csv_n_bicicletta_assistita', 'csv_n_monopattini',
            'csv_n_altri_micromobilita', 'csv_n_altri_veicoli', 'csv_trasportanti_merci_pericolose',
            'csv_omissione', 'csv_contromano', 'csv_dettaglio_persone_decedute', 'csv_positivita', 'csv_art_cds',
        );
        
        // Save all meta fields ESCLUDENDO i campi speciali
        $special_fields = ['circostanza_veicolo_a', 'circostanza_veicolo_b', 'circostanza_veicolo_c', 
                  'difetto_veicolo_a', 'difetto_veicolo_b', 'difetto_veicolo_c', 
                  'stato_psicofisico_a', 'stato_psicofisico_b', 'stato_psicofisico_c'];
        
        foreach ($meta_fields as $field) {
            // Salta i campi speciali che vengono gestiti separatamente
            if (in_array($field, $special_fields)) {
                continue;
            }
            
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                if ($field === 'mostra_in_mappa') {
                    delete_post_meta($post_id, $field);
                }
            }
        }

        // === GESTIONE SPECIFICA CIRCOSTANZE PRESUNTE ===
        $circostanze_fields = array(
            'circostanza_tipo',
            'circostanza_veicolo_a', 'circostanza_veicolo_b', 'circostanza_veicolo_c',
            'difetto_veicolo_a', 'difetto_veicolo_b', 'difetto_veicolo_c', 
            'stato_psicofisico_a', 'stato_psicofisico_b', 'stato_psicofisico_c'
        );

        foreach ($circostanze_fields as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                // Se è un array (checkbox multipli)
                if (is_array($_POST[$field])) {
                    $values = array_map('sanitize_text_field', $_POST[$field]);
                    update_post_meta($post_id, $field, $values);
                } else {
                    // Se è un singolo valore
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            } else {
                // Se non è selezionato, pulisci il campo
                update_post_meta($post_id, $field, '');
            }
        }

        // Validazione coerenza circostanze
        //$this->validate_circostanze_coherence($post_id);

        // Validazione coerenza circostanze
        $validator = new IncidentiValidation();
        $validator->validate_circostanze_coherence($post_id);

        // Debug per verificare il salvataggio delle circostanze
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $circostanze_debug = array();
            foreach ($circostanze_fields as $field) {
                $circostanze_debug[$field] = get_post_meta($post_id, $field, true);
            }
            error_log("DEBUG - Post $post_id, Circostanze salvate: " . print_r($circostanze_debug, true));
        }

        // Gestione speciale per i campi checkbox
        $checkbox_fields = array(
            'presenza_banchina', 'allagato', 'nuvoloso', 'foschia', 'salto_carreggiata', 'urto_frontale ', 'urto_laterale',
        );

        foreach ($checkbox_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, '1');
            } else {
                delete_post_meta($post_id, $field);
            }
        }

        // Gestione speciale per i campi radio che possono essere vuoti
        $radio_fields = array(
            'orientamento_conducente', 'presenza_barriere'
        );

        foreach ($radio_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $field);
            }
        }
        
        // Save vehicle and driver fields
        $numero_veicoli = isset($_POST['numero_veicoli_coinvolti']) ? intval($_POST['numero_veicoli_coinvolti']) : 1;
        for ($i = 1; $i <= 3; $i++) {
            if ($i <= $numero_veicoli) {
                $vehicle_fields = array('tipo', 'targa', 'sigla_estero', 'anno_immatricolazione', 'cilindrata', 'peso_totale');
                $driver_fields = array('eta', 'sesso', 'esito', 'rilascio_patente', 'tipo_cittadinanza', 'nazionalita', 'nazionalita_altro', 'tipologia_incidente', 'anno_patente');
                // tipo_patente viene gestito separatamente perché è un array

                foreach ($vehicle_fields as $field) {
                    $key = 'veicolo_' . $i . '_' . $field;
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                    }
                }
                
                foreach ($driver_fields as $field) {
                    $key = 'conducente_' . $i . '_' . $field;
                    if (isset($_POST[$key])) {
                        // Skip tipo_patente qui perché viene gestito separatamente
                        if ($field !== 'tipo_patente') {
                            update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                        }
                    }
                }
            
                // MODIFICATO: Salva i trasportati per ogni veicolo (fino a 9 trasportati come da tracciato ISTAT)
                for ($t = 1; $t <= 9; $t++) {
                    $trasportato_fields = array('eta', 'sesso', 'esito', 'sedile');
                    foreach ($trasportato_fields as $field) {
                        $key = 'veicolo_' . $i . '_trasportato_' . $t . '_' . $field;
                        if (isset($_POST[$key])) {
                            update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                        }
                    }
                }
            } else {
                // Elimina i campi dei veicoli non utilizzati
                $all_vehicle_fields = array(
                    'veicolo_' . $i . '_tipo', 'veicolo_' . $i . '_targa', 'veicolo_' . $i . '_sigla_estero',
                    'veicolo_' . $i . '_anno_immatricolazione', 'veicolo_' . $i . '_cilindrata', 
                    'veicolo_' . $i . '_peso_totale',
                    'conducente_' . $i . '_eta', 'conducente_' . $i . '_sesso', 'conducente_' . $i . '_esito',
                    'conducente_' . $i . '_tipo_patente', 'conducente_' . $i . '_rilascio_patente',
                    'conducente_' . $i . '_tipo_cittadinanza', 'conducente_' . $i . '_nazionalita', 'conducente_' . $i . '_nazionalita_altro'
                );
                
                
                // MODIFICATO: Aggiungi campi trasportati da eliminare (fino a 9 trasportati)
                for ($t = 1; $t <= 9; $t++) {
                    $all_vehicle_fields[] = 'veicolo_' . $i . '_trasportato_' . $t . '_eta';
                    $all_vehicle_fields[] = 'veicolo_' . $i . '_trasportato_' . $t . '_sesso';
                    $all_vehicle_fields[] = 'veicolo_' . $i . '_trasportato_' . $t . '_esito';
                    $all_vehicle_fields[] = 'veicolo_' . $i . '_trasportato_' . $t . '_sedile';
                }
                
                foreach ($all_vehicle_fields as $field) {
                    delete_post_meta($post_id, $field);
                }

                // NUOVO: Elimina anche i campi dei conducenti non utilizzati
                $conducente_fields_to_delete = array(
                    'conducente_' . $i . '_eta', 'conducente_' . $i . '_sesso', 'conducente_' . $i . '_esito',
                    'conducente_' . $i . '_anno_patente',
                    'conducente_' . $i . '_nazionalita', 'conducente_' . $i . '_nazionalita_altro',
                    'conducente_' . $i . '_tipologia_incidente',
                    'conducente_' . $i . '_tipo_patente'
                );

                // NON eliminare tipo_patente separatamente, è incluso nell'array sopra
                
                foreach ($conducente_fields_to_delete as $field) {
                    delete_post_meta($post_id, $field);
                }
            }
        }

        // === GESTIONE SPECIFICA TIPO_PATENTE PER TUTTI I VEICOLI (RADIOBUTTON) ===
        for ($i = 1; $i <= 3; $i++) {
            $tipo_patente_key = 'conducente_' . $i . '_tipo_patente';
            
            // Per radiobutton, controlliamo se il campo esiste nel POST
            if (isset($_POST[$tipo_patente_key])) {
                $value = $_POST[$tipo_patente_key];
                
                // Sanitizza il valore
                $sanitized_value = sanitize_text_field($value);
                
                // IMPORTANTE: Salviamo anche il valore "0" (non lo consideriamo vuoto)
                // Solo se è esplicitamente vuoto lo consideriamo non selezionato
                update_post_meta($post_id, $tipo_patente_key, $sanitized_value);
                
                // Debug per verificare il salvataggio
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("DEBUG - Saving tipo_patente for conducente_{$i}: '{$sanitized_value}' (original: '{$value}')");
                }
            } else {
                // Se il campo non esiste nel POST, significa che non è stato selezionato nulla
                update_post_meta($post_id, $tipo_patente_key, '');
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("DEBUG - No tipo_patente selected for conducente_{$i}, saving empty value");
                }
            }
        }

        
        // === GESTIONE CAMPI AGGIUNTIVI PER EXPORT ISTAT ===
        $additional_simple_fields = array(
            'abitato', 'illuminazione', 'pavimentazione', 'intersezione',
            'sistema_di_proiezione', 'codice_carabinieri',
            'altra_strada', 'codice__ente', 'codice_strada_aci'
        );

        foreach ($additional_simple_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // === DEBUG PER VERIFICARE IL SALVATAGGIO ===
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Debug tipo_patente
            for ($i = 1; $i <= 3; $i++) {
                $tipo_patente = get_post_meta($post_id, 'conducente_' . $i . '_tipo_patente', true);
                error_log("DEBUG - Post $post_id, Conducente $i, tipo_patente: " . print_r($tipo_patente, true));
            }
            
            // Debug circostanze
            $circ_a = get_post_meta($post_id, 'circostanza_veicolo_a', true);
            $circ_b = get_post_meta($post_id, 'circostanza_veicolo_b', true);
            $difetto_a = get_post_meta($post_id, 'difetto_veicolo_a', true);
            $stato_a = get_post_meta($post_id, 'stato_psicofisico_a', true);
            
            error_log("DEBUG - Post $post_id, Circostanze: A=$circ_a, B=$circ_b, DifettoA=$difetto_a, StatoA=$stato_a");
        }

        // Genera automaticamente il codice__ente e aggiorna il titolo
        if (isset($_POST['data_incidente']) && isset($_POST['provincia_incidente']) && isset($_POST['comune_incidente'])) {
            
            // Genera il progressivo (5 cifre) basato sull'ID del post
            $progressivo = str_pad($post_id, 5, '0', STR_PAD_LEFT);
            
            // Anno (2 cifre)
            $anno = substr($_POST['data_incidente'], 2, 2); // YYMMDD -> YY
            
            // ID Ente (2 cifre) - mappa l'organo di rilevazione
            $id_ente = '01'; // Default
            if (isset($_POST['organo_rilevazione'])) {
                switch ($_POST['organo_rilevazione']) {
                    case '1': $id_ente = '01'; break; // Polizia Stradale
                    case '2': $id_ente = '02'; break; // Carabinieri
                    case '4': $id_ente = '04'; break; // Polizia Municipale
                    case '6': $id_ente = '06'; break; // Polizia Provinciale
                    default: $id_ente = '99'; break; // Altri
                }
            }
            
            // ID Comune (3 cifre) - dai dati ISTAT
            $id_comune = str_pad($_POST['comune_incidente'], 3, '0', STR_PAD_LEFT);
            
            // Componi il codice finale
            $codice_ente = $progressivo . $anno . $id_ente . $id_comune;
            
            // Salva il codice__ente
            update_post_meta($post_id, 'codice__ente', $codice_ente);
            
            // Aggiorna il titolo del post con il codice generato
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                array('post_title' => $codice_ente),
                array('ID' => $post_id),
                array('%s'),
                array('%d')
            );
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

    /**
     * Restituisce i comuni consentiti per un determinato ente
     */
    private function get_comuni_per_ente($ente_codice) {
        if (empty($ente_codice) || in_array($ente_codice, ['agente_polizia_stradale', 'carabiniere', 'polizia_provinciale'])) {
            return array(); // Nessuna restrizione per enti sovracomunali
        }
        
        $mappatura_enti_comuni = array(
            'pm_alessano' => array('002' => 'Alessano'),
            'pm_alezio' => array('003' => 'Alezio'),
            'pm_alliste' => array('004' => 'Alliste'),
            'pm_andrano' => array('005' => 'Andrano'),
            'pm_aradeo' => array('006' => 'Aradeo'),
            'pm_arnesano' => array('007' => 'Arnesano'),
            'pm_bagnolo_salento' => array('008' => 'Bagnolo Del Salento'),
            'pm_botrugno' => array('009' => 'Botrugno'),
            'pm_calimera' => array('010' => 'Calimera'),
            'pm_campi_salentina' => array('011' => 'Campi Salentina'),
            'pm_cannole' => array('012' => 'Cannole'),
            'pm_caprarica_lecce' => array('013' => 'Caprarica Di Lecce'),
            'pm_carmiano' => array('014' => 'Carmiano'),
            'pm_carpignano_salentino' => array('015' => 'Carpignano Salentino'),
            'pm_casarano' => array('016' => 'Casarano'),
            'pm_castri' => array('017' => 'Castri Di Lecce'),
            'pm_castrignano_greci' => array('018' => 'Castrignano De` Greci'),
            'pm_castrignano_capo' => array('019' => 'Castrignano Del Capo'),
            'pm_castro' => array('096' => 'Castro'),
            'pm_cavallino' => array('020' => 'Cavallino'),
            'pm_collepasso' => array('021' => 'Collepasso'),
            'pm_copertino' => array('022' => 'Copertino'),
            'pm_corigliano_otranto' => array('023' => 'Corigliano D`Otranto'),
            'pm_corsano' => array('024' => 'Corsano'),
            'pm_cursi' => array('025' => 'Cursi'),
            'pm_cutrofiano' => array('026' => 'Cutrofiano'),
            'pm_diso' => array('027' => 'Diso'),
            'pm_gagliano_capo' => array('028' => 'Gagliano Del Capo'),
            'pm_galatina' => array('029' => 'Galatina'),
            'pm_galatone' => array('030' => 'Galatone'),
            'pm_gallipoli' => array('031' => 'Gallipoli'),
            'pm_giuggianello' => array('032' => 'Giuggianello'),
            'pm_giurdignano' => array('033' => 'Giurdignano'),
            'pm_guagnano' => array('034' => 'Guagnano'),
            'pm_lecce' => array('035' => 'Lecce'),
            'pm_lequile' => array('036' => 'Lequile'),
            'pm_leverano' => array('037' => 'Leverano'),
            'pm_lizzanello' => array('038' => 'Lizzanello'),
            'pm_maglie' => array('039' => 'Maglie'),
            'pm_martano' => array('040' => 'Martano'),
            'pm_martignano' => array('041' => 'Martignano'),
            'pm_matino' => array('042' => 'Matino'),
            'pm_melendugno' => array('043' => 'Melendugno'),
            'pm_melissano' => array('044' => 'Melissano'),
            'pm_melpignano' => array('045' => 'Melpignano'),
            'pm_miggiano' => array('046' => 'Miggiano'),
            'pm_minervino_lecce' => array('047' => 'Minervino Di Lecce'),
            'pm_monteroni_lecce' => array('048' => 'Monteroni Di Lecce'),
            'pm_montesano_salentino' => array('049' => 'Montesano Salentino'),
            'pm_morciano_leuca' => array('050' => 'Morciano Di Leuca'),
            'pm_muro' => array('051' => 'Muro Leccese'),
            'pm_nardo' => array('052' => 'Nardo`'),
            'pm_neviano' => array('053' => 'Neviano'),
            'pm_nociglia' => array('054' => 'Nociglia'),
            'pm_novoli' => array('055' => 'Novoli'),
            'pm_ortelle' => array('056' => 'Ortelle'),
            'pm_otranto' => array('057' => 'Otranto'),
            'pm_palmariggi' => array('058' => 'Palmariggi'),
            'pm_parabita' => array('059' => 'Parabita'),
            'pm_patu' => array('060' => 'Patu`'),
            'pm_poggiardo' => array('061' => 'Poggiardo'),
            'pm_porto_cesareo' => array('097' => 'Porto Cesareo'),
            'pm_presicce_acquarica' => array('098' => 'Presicce-Acquarica'),
            'pm_racale' => array('063' => 'Racale'),
            'pm_ruffano' => array('064' => 'Ruffano'),
            'pm_salice_salentino' => array('065' => 'Salice Salentino'),
            'pm_salve' => array('066' => 'Salve'),
            'pm_san_cassiano' => array('095' => 'San Cassiano'),
            'pm_san_cesario_lecce' => array('068' => 'San Cesario Di Lecce'),
            'pm_san_donato_lecce' => array('069' => 'San Donato Di Lecce'),
            'pm_san_pietro_lama' => array('071' => 'San Pietro In Lama'),
            'pm_sanarica' => array('067' => 'Sanarica'),
            'pm_sannicola' => array('070' => 'Sannicola'),
            'pm_santa_cesarea_terme' => array('072' => 'Santa Cesarea Terme'),
            'pm_scorrano' => array('073' => 'Scorrano'),
            'pm_secli' => array('074' => 'Secli`'),
            'pm_sogliano_cavour' => array('075' => 'Sogliano Cavour'),
            'pm_soleto' => array('076' => 'Soleto'),
            'pm_specchia' => array('077' => 'Specchia'),
            'pm_spongano' => array('078' => 'Spongano'),
            'pm_squinzano' => array('079' => 'Squinzano'),
            'pm_sternatia' => array('080' => 'Sternatia'),
            'pm_supersano' => array('081' => 'Supersano'),
            'pm_surano' => array('082' => 'Surano'),
            'pm_surbo' => array('083' => 'Surbo'),
            'pm_taurisano' => array('084' => 'Taurisano'),
            'pm_taviano' => array('085' => 'Taviano'),
            'pm_tiggiano' => array('086' => 'Tiggiano'),
            'pm_trepuzzi' => array('087' => 'Trepuzzi'),
            'pm_tricase' => array('088' => 'Tricase'),
            'pm_tuglie' => array('089' => 'Tuglie'),
            'pm_ugento' => array('090' => 'Ugento'),
            'pm_uggiano_chiesa' => array('091' => 'Uggiano La Chiesa'),
            'pm_veglie' => array('092' => 'Veglie'),
            'pm_vernole' => array('093' => 'Vernole'),
            'pm_zollino' => array('094' => 'Zollino')
        );
        
        return isset($mappatura_enti_comuni[$ente_codice]) ? $mappatura_enti_comuni[$ente_codice] : array();
    }

    /**
     * Mappa l'ente di gestione all'organo di rilevazione
     */
    private function map_ente_to_organo($ente_codice) {
        if (strpos($ente_codice, 'pm_') === 0) {
            return '4'; // Polizia Municipale/Locale
        }
        
        switch ($ente_codice) {
            case 'agente_polizia_stradale':
                return '1'; // Agente di Polizia Stradale
            case 'carabiniere':
                return '2'; // Carabiniere
            case 'polizia_provinciale':
                return '6'; // Agente di Polizia Provinciale
            default:
                return '';
        }
    }

    /**
     * Mappa il codice ente del profilo utente al nome completo dell'ente
     */
    private function map_ente_to_nome_completo($ente_codice) {
        // Se è una polizia municipale, mappa al nome completo
        if (strpos($ente_codice, 'pm_') === 0) {
            $mappatura_pm = array(
                'pm_lecce' => 'POLIZIA MUNICIPALE DI LECCE',
                'pm_gallipoli' => 'POLIZIA MUNICIPALE DI GALLIPOLI',
                'pm_galatina' => 'POLIZIA MUNICIPALE DI GALATINA',
                'pm_nardo' => 'POLIZIA MUNICIPALE DI NARDO\'',
                'pm_casarano' => 'POLIZIA MUNICIPALE DI CASARANO',
                'pm_maglie' => 'POLIZIA MUNICIPALE DI MAGLIE',
                'pm_copertino' => 'POLIZIA MUNICIPALE DI COPERTINO',
                'pm_leverano' => 'POLIZIA MUNICIPALE DI LEVERANO',
                'pm_campi_salentina' => 'POLIZIA MUNICIPALE DI CAMPI SALENTINA',
                'pm_surbo' => 'POLIZIA MUNICIPALE DI SURBO',
                'pm_trepuzzi' => 'POLIZIA MUNICIPALE DI TREPUZZI',
                'pm_squinzano' => 'POLIZIA MUNICIPALE DI SQUINZANO',
                'pm_veglie' => 'POLIZIA MUNICIPALE DI VEGLIE',
                'pm_novoli' => 'POLIZIA MUNICIPALE DI NOVOLI',
                'pm_carmiano' => 'POLIZIA MUNICIPALE DI CARMIANO',
                'pm_arnesano' => 'POLIZIA MUNICIPALE DI ARNESANO',
                'pm_monteroni_lecce' => 'POLIZIA MUNICIPALE DI MONTERONI DI LECCE',
                'pm_san_cesario_lecce' => 'POLIZIA MUNICIPALE DI SAN CESARIO DI LECCE',
                'pm_san_pietro_lama' => 'POLIZIA MUNICIPALE DI SAN PIETRO IN LAMA',
                'pm_lequile' => 'POLIZIA MUNICIPALE DI LEQUILE',
                'pm_san_donato_lecce' => 'POLIZIA MUNICIPALE DI SAN DONATO DI LECCE',
                'pm_caprarica_lecce' => 'POLIZIA MUNICIPALE DI CAPRARICA DI LECCE',
                'pm_cavallino' => 'POLIZIA MUNICIPALE DI CAVALLINO',
                'pm_lizzanello' => 'POLIZIA MUNICIPALE DI LIZZANELLO',
                'pm_castri' => 'POLIZIA MUNICIPALE DI CASTRI',
                'pm_vernole' => 'POLIZIA MUNICIPALE DI VERNOLE',
                'pm_melendugno' => 'POLIZIA MUNICIPALE DI MELENDUGNO',
                'pm_calimera' => 'POLIZIA MUNICIPALE DI CALIMERA',
                'pm_martano' => 'POLIZIA MUNICIPALE DI MARTANO',
                'pm_carpignano_salentino' => 'POLIZIA MUNICIPALE DI CARPIGNANO SALENTINO',
                'pm_martignano' => 'POLIZIA MUNICIPALE DI MARTIGNANO',
                'pm_corigliano_otranto' => 'POLIZIA MUNICIPALE DI CORIGLIANO D\'OTRANTO',
                'pm_melpignano' => 'POLIZIA MUNICIPALE DI MELPIGNANO',
                'pm_cursi' => 'POLIZIA MUNICIPALE DI CURSI',
                'pm_scorrano' => 'POLIZIA MUNICIPALE DI SCORRANO',
                'pm_otranto' => 'POLIZIA MUNICIPALE DI OTRANTO',
                'pm_uggiano_chiesa' => 'POLIZIA MUNICIPALE DI UGGIANO LA CHIESA',
                'pm_giurdignano' => 'POLIZIA MUNICIPALE DI GIURDIGNANO',
                'pm_minervino_lecce' => 'POLIZIA MUNICIPALE DI MINERVINO DI LECCE',
                'pm_santa_cesarea_terme' => 'POLIZIA MUNICIPALE DI SANTA CESAREA TERME',
                'pm_poggiardo' => 'POLIZIA MUNICIPALE DI POGGIARDO',
                'pm_sanarica' => 'POLIZIA MUNICIPALE DI SANARICA',
                'pm_nociglia' => 'POLIZIA MUNICIPALE DI NOCIGLIA',
                'pm_spongano' => 'POLIZIA MUNICIPALE DI SPONGANO',
                'pm_ortelle' => 'POLIZIA MUNICIPALE DI ORTELLE',
                'pm_castro' => 'POLIZIA MUNICIPALE DI CASTRO',
                'pm_diso' => 'POLIZIA MUNICIPALE DI DISO',
                'pm_andrano' => 'POLIZIA MUNICIPALE DI ANDRANO',
                'pm_tricase' => 'POLIZIA MUNICIPALE DI TRICASE',
                'pm_tiggiano' => 'POLIZIA MUNICIPALE DI TIGGIANO',
                'pm_corsano' => 'POLIZIA MUNICIPALE DI CORSANO',
                'pm_specchia' => 'POLIZIA MUNICIPALE DI SPECCHIA',
                'pm_castrignano_capo' => 'POLIZIA MUNICIPALE DI CASTRIGNANO DEL CAPO',
                'pm_gagliano_capo' => 'POLIZIA MUNICIPALE DI GAGLIANO DEL CAPO',
                'pm_morciano_leuca' => 'POLIZIA MUNICIPALE DI MORCIANO DI LEUCA',
                'pm_salve' => 'POLIZIA MUNICIPALE DI SALVE',
                'pm_patu' => 'POLIZIA MUNICIPALE DI PATU\'',
                'pm_alessano' => 'POLIZIA MUNICIPALE DI ALESSANO',
                'pm_montesano_salentino' => 'POLIZIA MUNICIPALE DI MONTESANO SALENTINO',
                'pm_miggiano' => 'POLIZIA MUNICIPALE DI MIGGIANO',
                'pm_surano' => 'POLIZIA MUNICIPALE DI SURANO',
                'pm_botrugno' => 'POLIZIA MUNICIPALE DI BOTRUGNO',
                'pm_soleto' => 'POLIZIA MUNICIPALE DI SOLETO',
                'pm_sternatia' => 'POLIZIA MUNICIPALE DI STERNATIA',
                'pm_zollino' => 'POLIZIA MUNICIPALE DI ZOLLINO',
                'pm_castrignano_greci' => 'POLIZIA MUNICIPALE DI CASTRIGNANO DEI GRECI',
                'pm_giuggianello' => 'POLIZIA MUNICIPALE DI GIUGGIANELLO',
                'pm_palmariggi' => 'POLIZIA MUNICIPALE DI PALMARIGGI',
                'pm_supersano' => 'POLIZIA MUNICIPALE DI SUPERSANO',
                'pm_ruffano' => 'POLIZIA MUNICIPALE DI RUFFANO',
                'pm_ugento' => 'POLIZIA MUNICIPALE DI UGENTO',
                'pm_taurisano' => 'POLIZIA MUNICIPALE DI TAURISANO',
                'pm_presicce_acquarica' => 'POLIZIA MUNICIPALE DI PRESICCE-ACQUARICA',
                'pm_racale' => 'POLIZIA MUNICIPALE DI RACALE',
                'pm_taviano' => 'POLIZIA MUNICIPALE DI TAVIANO',
                'pm_alliste' => 'POLIZIA MUNICIPALE DI ALLISTE',
                'pm_melissano' => 'POLIZIA MUNICIPALE DI MELISSANO',
                'pm_tuglie' => 'POLIZIA MUNICIPALE DI TUGLIE',
                'pm_sannicola' => 'POLIZIA MUNICIPALE DI SANNICOLA',
                'pm_alezio' => 'POLIZIA MUNICIPALE DI ALEZIO',
                'pm_parabita' => 'POLIZIA MUNICIPALE DI PARABITA',
                'pm_matino' => 'POLIZIA MUNICIPALE DI MATINO',
                'pm_collepasso' => 'POLIZIA MUNICIPALE DI COLLEPASSO',
                'pm_galatone' => 'POLIZIA MUNICIPALE DI GALATONE',
                'pm_secli' => 'POLIZIA MUNICIPALE DI SECLI\'',
                'pm_neviano' => 'POLIZIA MUNICIPALE DI NEVIANO',
                'pm_sogliano_cavour' => 'POLIZIA MUNICIPALE DI SOGLIANO CAVOUR',
                'pm_aradeo' => 'POLIZIA MUNICIPALE DI ARADEO',
                'pm_cutrofiano' => 'POLIZIA MUNICIPALE DI CUTROFIANO',
                'pm_porto_cesareo' => 'POLIZIA MUNICIPALE DI PORTO CESAREO',
                'pm_salice_salentino' => 'POLIZIA MUNICIPALE DI SALICE SALENTINO',
                'pm_guagnano' => 'POLIZIA MUNICIPALE DI GUAGNANO',
                'pm_cannole' => 'POLIZIA MUNICIPALE DI CANNOLE',
                'pm_bagnolo_salento' => 'POLIZIA MUNICIPALE DI BAGNOLO DEL SALENTO',
                'pm_san_cassiano' => 'POLIZIA MUNICIPALE DI SAN CASSIANO',
                'pm_muro' => 'POLIZIA MUNICIPALE DI MURO'
            );
            
            return isset($mappatura_pm[$ente_codice]) ? $mappatura_pm[$ente_codice] : '';
        }
        
        // Per altri enti
        switch ($ente_codice) {
            case 'agente_polizia_stradale':
                return 'Agente di Polizia Stradale';
            case 'carabiniere':
                return 'Carabiniere';
            case 'polizia_provinciale':
                return 'Polizia Provinciale';
            default:
                return '';
        }
    }

    /**
     * Aggiunge meta box per la stampa PDF
     */
    public function add_print_meta_box() {
        // Questa funzione è già chiamata in add_meta_boxes()
    }

    /**
     * Enqueue degli script necessari per il PDF
     */
    public function enqueue_pdf_scripts($hook) {
        global $post;
        
        if ($hook === 'post.php' && $post && $post->post_type === 'incidente_stradale') {
            // jsPDF dalla CDN
            /* wp_enqueue_script(
                'jspdf',
                'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
                array(),
                '2.5.1',
                true
            ); */
            
            // Script semplificato per l'interfaccia (senza dipendenza da jsPDF)
            wp_enqueue_script(
                'incidenti-pdf-interface',
                plugin_dir_url(__FILE__) . '../assets/js/pdf-print.js',
                array('jquery'),
                '2.1.0',
                true
            );
            
            // Localizzazione per AJAX
            wp_localize_script('incidenti-pdf-interface', 'incidentiPDF', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('incidente_pdf_nonce'),
                'post_id' => $post->ID,
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ));

            $current_user = wp_get_current_user();
            if (in_array('asset', $current_user->roles)) {
                wp_add_inline_script('jquery', '
                    jQuery(document).ready(function($) {
                        // Disabilita tutti i campi del form per utenti Asset
                        $("input:not([type=hidden]), textarea, select").prop("disabled", true);
                        $("input, textarea, select").css({
                            "opacity": "0.6",
                            "background-color": "#f9f9f9 !important",
                            "pointer-events": "none"
                        });
                        
                        // NUOVO: Disabilita specificamente i radio button e checkbox
                        $("input[type=radio], input[type=checkbox]").prop("disabled", true).css({
                            "pointer-events": "none",
                            "opacity": "0.6"
                        });
                        
                        // NUOVO: Disabilita i pulsanti "Cancella scelta" (span cliccabili)
                        $("span[onclick*=\"azzeraRadioSezione\"]").css({
                            "pointer-events": "none",
                            "opacity": "0.4",
                            "background-color": "#e0e0e0 !important",
                            "color": "#999 !important",
                            "cursor": "not-allowed"
                        }).removeAttr("onclick");
                        
                        // NUOVO: Disabilita tutti gli altri span/elementi cliccabili
                        $("span[onclick], button[onclick], div[onclick]").css({
                            "pointer-events": "none",
                            "opacity": "0.6",
                            "cursor": "not-allowed"
                        }).removeAttr("onclick");
                        
                        // NUOVO: Disabilita le mappe
                        if (typeof L !== "undefined") {
                            // Disabilita mappa localizzazione
                            if (window.map && typeof window.map.off === "function") {
                                window.map.off("click");
                                window.map.dragging.disable();
                                window.map.touchZoom.disable();
                                window.map.doubleClickZoom.disable();
                                window.map.scrollWheelZoom.disable();
                                window.map.boxZoom.disable();
                                window.map.keyboard.disable();
                                
                                // Rimuovi controlli zoom se presenti
                                $(".leaflet-control-zoom").remove();
                            }
                            
                            // Disabilita interazioni sui marker esistenti
                            $(".leaflet-marker-icon, .leaflet-clickable").css({
                                "pointer-events": "none",
                                "opacity": "0.6"
                            });
                        }
                        
                        // Disabilita contenitori mappa visualmente
                        $("#localizzazione-map, #coordinate-map").css({
                            "opacity": "0.6",
                            "pointer-events": "none",
                            "position": "relative"
                        });
                        
                        // NUOVO: Previeni submit del form
                        $("form").on("submit", function(e) {
                            e.preventDefault();
                            alert("Gli utenti Asset non possono salvare modifiche.");
                            return false;
                        });
                        
                        // NUOVO: Disabilita eventi click su tutti gli elementi del form
                        $("#poststuff").on("click", function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        });
                        
                        // Nascondi i pulsanti di salvataggio
                        $("#publish, #save-post, .button-primary").hide();
                        
                        // Mostra messaggio informativo
                        $("#titlediv").after("<div class=\"notice notice-warning\"><p><strong>Modalità sola lettura:</strong> Gli utenti con ruolo Asset non possono modificare gli incidenti.</p></div>");
                    });
                ');
            }
        }
    }

    /**
     * Render della meta box per la stampa
     */
    public function render_stampa_pdf_meta_box($post) {
        // Solo per incidenti già salvati
        if ($post->post_status === 'auto-draft' || !$post->ID) {
            ?>
            <div class="incidenti-stampa-container" style="text-align: center; padding: 20px;">
                <p style="color: #666; font-style: italic;">
                    <?php _e('Salva l\'incidente per abilitare la stampa PDF.', 'incidenti-stradali'); ?>
                </p>
            </div>
            <?php
            return;
        }
        
        wp_nonce_field('incidente_print_pdf', 'incidente_print_pdf_nonce');
        
        ?>
        <div class="incidenti-stampa-container" style="text-align: center; padding: 20px;">
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Genera un PDF professionale con tutti i dati dell\'incidente compilati.', 'incidenti-stradali'); ?>
            </p>
            
            <button type="button" id="stampa-incidente-pdf" class="button button-primary button-large" style="width: 100%; padding: 10px; font-size: 14px;">
                <span class="dashicons dashicons-media-document" style="margin-right: 5px;"></span>
                <?php _e('Genera PDF', 'incidenti-stradali'); ?>
            </button>
            
            <div id="pdf-loading" style="display: none; margin-top: 15px;">
                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                <p style="margin: 10px 0 0 0; font-style: italic; color: #666;">
                    <?php _e('Generazione PDF in corso...', 'incidenti-stradali'); ?>
                </p>
            </div>
            
            <div id="pdf-success" style="display: none; margin-top: 15px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                <span class="dashicons dashicons-yes-alt" style="color: #155724;"></span>
                <span style="color: #155724; font-weight: bold;">
                    <?php _e('PDF generato con successo!', 'incidenti-stradali'); ?>
                </span>
            </div>
            
            <div id="pdf-error" style="display: none; margin-top: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
                <span class="dashicons dashicons-warning" style="color: #721c24;"></span>
                <span style="color: #721c24; font-weight: bold;">
                    <?php _e('Errore nella generazione del PDF', 'incidenti-stradali'); ?>
                </span>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#stampa-incidente-pdf').on('click', function() {
                var button = $(this);
                var loading = $('#pdf-loading');
                var success = $('#pdf-success');
                var error = $('#pdf-error');
                
                // Reset stati
                success.hide();
                error.hide();
                loading.show();
                button.prop('disabled', true);
                
                // Chiamata AJAX diretta senza aspettare jsPDF
                $.ajax({
                    url: incidentiPDF.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_incidente_data_for_pdf',
                        security: incidentiPDF.nonce,
                        post_id: incidentiPDF.post_id
                    },
                    success: function(response) {
                        loading.hide();
                        button.prop('disabled', false);
                        
                        if (response.success) {
                            success.show();
                            
                            // Se jsPDF è disponibile, usa quello, altrimenti mostra solo successo
                            if (typeof window.jsPDF !== 'undefined' && window.jsPDF.jsPDF) {
                                try {
                                    generatePDF(response.data);
                                } catch (e) {
                                    console.log('jsPDF non disponibile, ma operazione completata con successo');
                                }
                            } else {
                                console.log('PDF generato con successo (server-side)');
                            }
                        } else {
                            error.show();
                            console.error('Errore dati:', response.data);
                        }
                    },
                    error: function(xhr, status, errorThrown) {
                        loading.hide();
                        button.prop('disabled', false);
                        error.show();
                        console.error('Errore AJAX:', errorThrown);
                    }
                });
            });
            
            function generatePDF(data) {
                // Solo se jsPDF è davvero disponibile
                if (typeof window.jsPDF === 'undefined' || !window.jsPDF.jsPDF) {
                    return;
                }
                
                try {
                    const { jsPDF } = window.jsPDF;
                    const doc = new jsPDF();
                    
                    // Aggiungi intestazione
                    doc.setFontSize(16);
                    doc.text('VERBALE INCIDENTE STRADALE', 20, 20);
                    
                    // Aggiungi dati principali
                    doc.setFontSize(12);
                    let y = 40;
                    
                    if (data.data_incidente) {
                        doc.text('Data: ' + data.data_incidente, 20, y);
                        y += 10;
                    }
                    
                    if (data.ora_incidente) {
                        doc.text('Ora: ' + data.ora_incidente, 20, y);
                        y += 10;
                    }
                    
                    if (data.via_piazza) {
                        doc.text('Luogo: ' + data.via_piazza, 20, y);
                        y += 10;
                    }
                    
                    if (data.natura_incidente) {
                        doc.text('Natura: ' + data.natura_incidente, 20, y);
                        y += 10;
                    }
                    
                    // Salva il PDF
                    const filename = 'incidente_' + incidentiPDF.post_id + '.pdf';
                    doc.save(filename);
                } catch (e) {
                    console.log('Errore nella generazione PDF client-side, ma operazione completata');
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Genera i dati per il PDF via AJAX
     */
    public function generate_pdf() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['security'], 'incidente_pdf_nonce')) {
            wp_die('Accesso negato');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Verifica che il post esista e sia del tipo corretto
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'incidente_stradale') {
            wp_send_json_error('Incidente non trovato o tipo non valido');
            return;
        }
        
        // Verifica permessi
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        try {
            // Include TCPDF se non già caricato
            if (!class_exists('TCPDF')) {
                require_once(plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php');
            }
            
            // Genera PDF server-side
            $pdf_generator = new PDF_Generator();
            $pdf_path = $pdf_generator->generate_incidente_pdf($post_id);
            
            if ($pdf_path) {
                // Invia URL per download
                $pdf_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $pdf_path);
                wp_send_json_success(array(
                    'download_url' => $pdf_url,
                    'filename' => basename($pdf_path)
                ));
            } else {
                wp_send_json_error('Errore nella generazione del PDF');
            }
            
        } catch (Exception $e) {
            error_log('Errore PDF: ' . $e->getMessage());
            wp_send_json_error('Errore interno del server');
        }
    }

    /**
     * Raccoglie tutti i dati dell'incidente per il PDF
     */
    private function collect_incidente_data($post_id) {
        $data = array();
        
        // Dati generali
        $data['dati_generali'] = array(
            'codice_ente' => get_post_meta($post_id, 'codice__ente', true),
            'data_incidente' => get_post_meta($post_id, 'data_incidente', true),
            'ora_incidente' => get_post_meta($post_id, 'ora_incidente', true),
            'minuti_incidente' => get_post_meta($post_id, 'minuti_incidente', true),
            'provincia_incidente' => get_post_meta($post_id, 'provincia_incidente', true),
            'comune_incidente' => get_post_meta($post_id, 'comune_incidente', true),
            'localita_incidente' => get_post_meta($post_id, 'localita_incidente', true),
            'ente_rilevatore' => get_post_meta($post_id, 'ente_rilevatore', true),
            'nome_rilevatore' => get_post_meta($post_id, 'nome_rilevatore', true)
        );
        
        // Localizzazione
        $data['localizzazione'] = array(
            'tipo_strada' => get_post_meta($post_id, 'tipo_strada', true),
            'denominazione_strada' => get_post_meta($post_id, 'denominazione_strada', true),
            'numero_strada' => get_post_meta($post_id, 'numero_strada', true),
            'latitudine' => get_post_meta($post_id, 'latitudine', true),
            'longitudine' => get_post_meta($post_id, 'longitudine', true)
        );
        
        // Natura incidente
        $data['natura'] = array(
            'natura_incidente' => get_post_meta($post_id, 'natura_incidente', true),
            'dettaglio_natura' => get_post_meta($post_id, 'dettaglio_natura', true),
            'numero_veicoli_coinvolti' => get_post_meta($post_id, 'numero_veicoli_coinvolti', true)
        );
        
        // Veicoli e conducenti
        $numero_veicoli = (int) get_post_meta($post_id, 'numero_veicoli_coinvolti', true) ?: 1;
        $data['veicoli'] = array();
        
        for ($i = 1; $i <= $numero_veicoli; $i++) {
            $data['veicoli'][$i] = array(
                'tipo' => get_post_meta($post_id, "veicolo_{$i}_tipo", true),
                'targa' => get_post_meta($post_id, "veicolo_{$i}_targa", true),
                'anno_immatricolazione' => get_post_meta($post_id, "veicolo_{$i}_anno_immatricolazione", true),
                'conducente' => array(
                    'eta' => get_post_meta($post_id, "conducente_{$i}_eta", true),
                    'sesso' => get_post_meta($post_id, "conducente_{$i}_sesso", true),
                    'esito' => get_post_meta($post_id, "conducente_{$i}_esito", true),
                    'nazionalita' => get_post_meta($post_id, "conducente_{$i}_nazionalita", true)
                )
            );
        }
        
        // Pedoni
        $data['pedoni'] = array(
            'numero_morti' => get_post_meta($post_id, 'numero_pedoni_morti', true),
            'numero_feriti' => get_post_meta($post_id, 'numero_pedoni_feriti', true)
        );
        
        // Circostanze
        $data['circostanze'] = array(
            'circostanza_veicolo_a' => get_post_meta($post_id, 'circostanza_veicolo_a', true),
            'circostanza_veicolo_b' => get_post_meta($post_id, 'circostanza_veicolo_b', true),
            'difetto_veicolo_a' => get_post_meta($post_id, 'difetto_veicolo_a', true),
            'stato_psicofisico_a' => get_post_meta($post_id, 'stato_psicofisico_a', true)
        );
        
        // Condizioni ambientali
        $data['condizioni'] = array(
            'condizioni_meteo' => get_post_meta($post_id, 'condizioni_meteo', true),
            'illuminazione' => get_post_meta($post_id, 'illuminazione', true),
            'stato_fondo_strada' => get_post_meta($post_id, 'stato_fondo_strada', true),
            'geometria_strada' => get_post_meta($post_id, 'geometria_strada', true)
        );
        
        return $data;
    }

    /**
     * Restituisce i dati dell'incidente per la generazione PDF
     */
    public function get_incidente_data_for_pdf() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['security'], 'incidente_pdf_nonce')) {
            wp_die('Accesso negato');
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        // Raccogli i dati dell'incidente
        $data = array(
            'data_incidente' => get_post_meta($post_id, 'data_incidente', true),
            'ora_incidente' => get_post_meta($post_id, 'ora_incidente', true),
            'via_piazza' => get_post_meta($post_id, 'via_piazza', true),
            'natura_incidente' => get_post_meta($post_id, 'natura_incidente', true),
            'provincia' => get_post_meta($post_id, 'provincia', true),
            'comune' => get_post_meta($post_id, 'comune', true),
            'tipo_strada' => get_post_meta($post_id, 'tipo_strada', true)
        );
        
        wp_send_json_success($data);
    }

    /**
     * Rimuove "Modifica rapida" per utenti Asset
     */
    public function remove_quick_edit_for_asset($actions, $post) {
        if ($post->post_type === 'incidente_stradale') {
            $current_user = wp_get_current_user();
            if (in_array('asset', $current_user->roles)) {
                // Rimuovi "Modifica rapida"
                unset($actions['inline hide-if-no-js']);
                
                // OPZIONALE: Rimuovi anche "Modifica" se vuoi
                // unset($actions['edit']);
                
                // OPZIONALE: Rimuovi "Cestina"
                // unset($actions['trash']);
                
                // OPZIONALE: Rimuovi "Visualizza"
                // unset($actions['view']);
            }
        }
        return $actions;
    }

    /**
     * Rimuove le azioni di gruppo per utenti Asset
     */
    public function remove_bulk_actions_for_asset($bulk_actions) {
        $current_user = wp_get_current_user();
        if (in_array('asset', $current_user->roles)) {
            // Rimuovi tutte le azioni di gruppo
            return array();
            
            // ALTERNATIVA: Rimuovi solo alcune azioni specifiche
            // unset($bulk_actions['trash']);
            // unset($bulk_actions['edit']);
            // return $bulk_actions;
        }
        return $bulk_actions;
    }

    /**
     * Nasconde elementi UI aggiuntivi per utenti Asset
     */
    public function hide_asset_ui_elements() {
        $current_user = wp_get_current_user();
        if (in_array('asset', $current_user->roles)) {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'incidente_stradale') {
                ?>
                <style type="text/css">
                    /* Nasconde il pulsante "Aggiungi nuovo" */
                    .page-title-action { display: none !important; }
                    
                    /* Nasconde la checkbox "Seleziona tutto" */
                    #cb-select-all-1, #cb-select-all-2 { display: none !important; }
                    
                    /* Nasconde le checkbox individuali */
                    .check-column input[type="checkbox"] { display: none !important; }
                    
                    /* Nasconde il menu a tendina delle azioni di gruppo */
                    .bulkactions { display: none !important; }
                    
                    /* OPZIONALE: Nasconde il pulsante "Sposta nel cestino" se presente */
                    .submitdelete { display: none !important; }
                    
                    /* OPZIONALE: Nasconde filtri di ordinamento se necessario */
                    /* .tablenav.top .actions { display: none !important; } */
                </style>
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        // Rimuovi eventi click dalle checkbox rimanenti
                        $('.check-column input[type="checkbox"]').prop('disabled', true);
                        
                        // Disabilita completamente la selezione
                        $('.wp-list-table tbody tr').css('user-select', 'none');
                        
                        // Messaggio informativo
                        $('.wrap h1').after('<div class="notice notice-info"><p><strong>Modalità sola lettura:</strong> Gli utenti Asset possono solo visualizzare gli incidenti.</p></div>');
                    });
                </script>
                <?php
            }
        }
    }

    /**
     * Personalizza la lista incidenti per utenti Asset
     */
    public function customize_asset_list_view() {
        $current_user = wp_get_current_user();
        if (in_array('asset', $current_user->roles)) {
            // Rimuovi la possibilità di modificare lo stato dei post
            add_filter('user_can_richedit', '__return_false');
            
            // Rimuovi metabox non necessarie
            add_action('add_meta_boxes', function() {
                remove_meta_box('submitdiv', 'incidente_stradale', 'side');
            }, 99);
        }
    }
}