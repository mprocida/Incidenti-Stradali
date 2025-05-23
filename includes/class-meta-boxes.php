<?php
/**
 * Meta Boxes for Incidenti Stradali
 */

class IncidentiMetaBoxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('edit_form_after_title', array($this, 'move_meta_boxes_after_title'));
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
    }
    
    public function render_dati_generali_meta_box($post) {
        wp_nonce_field('incidente_meta_box', 'incidente_meta_box_nonce');
        
        $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
        $ora_incidente = get_post_meta($post->ID, 'ora_incidente', true);
        $minuti_incidente = get_post_meta($post->ID, 'minuti_incidente', true);
        $provincia = get_post_meta($post->ID, 'provincia_incidente', true);
        $comune = get_post_meta($post->ID, 'comune_incidente', true);
        $organo_rilevazione = get_post_meta($post->ID, 'organo_rilevazione', true);
        $organo_coordinatore = get_post_meta($post->ID, 'organo_coordinatore', true);
        
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
                    <input type="text" id="provincia_incidente" name="provincia_incidente" value="<?php echo esc_attr($provincia); ?>" required>
                    <p class="description"><?php _e('Codice ISTAT provincia (3 cifre)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="comune_incidente"><?php _e('Comune', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <input type="text" id="comune_incidente" name="comune_incidente" value="<?php echo esc_attr($comune); ?>" required>
                    <p class="description"><?php _e('Codice ISTAT comune (3 cifre)', 'incidenti-stradali'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="organo_rilevazione"><?php _e('Organo di Rilevazione', 'incidenti-stradali'); ?></label></th>
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
            <tr>
                <th><label for="organo_coordinatore"><?php _e('Organo Coordinatore', 'incidenti-stradali'); ?></label></th>
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
        <?php
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
                <th><label><?php _e('L\'incidente è avvenuto', 'incidenti-stradali'); ?> *</label></th>
                <td>
                    <label>
                        <input type="radio" name="nell_abitato" value="1" <?php checked($abitato, '1'); ?> required>
                        <?php _e('Nell\'abitato', 'incidenti-stradali'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="nell_abitato" value="0" <?php checked($abitato, '0'); ?> required>
                        <?php _e('Fuori dall\'abitato', 'incidenti-stradali'); ?>
                    </label>
                </td>
            </tr>
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
            <tr>
                <th><label><?php _e('Progressiva Chilometrica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <label for="progressiva_km"><?php _e('Km', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_km" name="progressiva_km" value="<?php echo esc_attr($progressiva_km); ?>" min="0" max="999" style="width: 80px;">
                    
                    <label for="progressiva_m"><?php _e('Mt', 'incidenti-stradali'); ?></label>
                    <input type="number" id="progressiva_m" name="progressiva_m" value="<?php echo esc_attr($progressiva_m); ?>" min="0" max="999" style="width: 80px;">
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_luogo_meta_box($post) {
        $geometria_strada = get_post_meta($post->ID, 'geometria_strada', true);
        $pavimentazione = get_post_meta($post->ID, 'pavimentazione_strada', true);
        $intersezione = get_post_meta($post->ID, 'intersezione_tronco', true);
        $fondo_strada = get_post_meta($post->ID, 'stato_fondo_strada', true);
        $segnaletica = get_post_meta($post->ID, 'segnaletica_strada', true);
        $condizioni_meteo = get_post_meta($post->ID, 'condizioni_meteo', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="geometria_strada"><?php _e('Tipo di Strada', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="geometria_strada" name="geometria_strada">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($geometria_strada, '1'); ?>><?php _e('Una carreggiata senso unico', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($geometria_strada, '2'); ?>><?php _e('Una carreggiata doppio senso', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($geometria_strada, '3'); ?>><?php _e('Due carreggiate', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($geometria_strada, '4'); ?>><?php _e('Più di 2 carreggiate', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="pavimentazione_strada"><?php _e('Pavimentazione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="pavimentazione_strada" name="pavimentazione_strada">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($pavimentazione, '1'); ?>><?php _e('Strada pavimentata', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($pavimentazione, '2'); ?>><?php _e('Strada pavimentata dissestata', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($pavimentazione, '3'); ?>><?php _e('Strada non pavimentata', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="intersezione_tronco"><?php _e('Intersezione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="intersezione_tronco" name="intersezione_tronco">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <optgroup label="<?php _e('Intersezione', 'incidenti-stradali'); ?>">
                            <option value="1" <?php selected($intersezione, '1'); ?>><?php _e('Incrocio', 'incidenti-stradali'); ?></option>
                            <option value="2" <?php selected($intersezione, '2'); ?>><?php _e('Rotatoria', 'incidenti-stradali'); ?></option>
                            <option value="3" <?php selected($intersezione, '3'); ?>><?php _e('Intersezione segnalata', 'incidenti-stradali'); ?></option>
                            <option value="4" <?php selected($intersezione, '4'); ?>><?php _e('Intersezione con semaforo o vigile', 'incidenti-stradali'); ?></option>
                            <option value="5" <?php selected($intersezione, '5'); ?>><?php _e('Intersezione non segnalata', 'incidenti-stradali'); ?></option>
                            <option value="6" <?php selected($intersezione, '6'); ?>><?php _e('Passaggio a livello', 'incidenti-stradali'); ?></option>
                        </optgroup>
                        <optgroup label="<?php _e('Non Intersezione', 'incidenti-stradali'); ?>">
                            <option value="7" <?php selected($intersezione, '7'); ?>><?php _e('Rettilineo', 'incidenti-stradali'); ?></option>
                            <option value="8" <?php selected($intersezione, '8'); ?>><?php _e('Curva', 'incidenti-stradali'); ?></option>
                            <option value="9" <?php selected($intersezione, '9'); ?>><?php _e('Dosso, strettoia', 'incidenti-stradali'); ?></option>
                            <option value="10" <?php selected($intersezione, '10'); ?>><?php _e('Pendenza', 'incidenti-stradali'); ?></option>
                            <option value="11" <?php selected($intersezione, '11'); ?>><?php _e('Galleria illuminata', 'incidenti-stradali'); ?></option>
                            <option value="12" <?php selected($intersezione, '12'); ?>><?php _e('Galleria non illuminata', 'incidenti-stradali'); ?></option>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="stato_fondo_strada"><?php _e('Fondo Stradale', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="stato_fondo_strada" name="stato_fondo_strada">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($fondo_strada, '1'); ?>><?php _e('Asciutto', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($fondo_strada, '2'); ?>><?php _e('Bagnato', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($fondo_strada, '3'); ?>><?php _e('Sdrucciolevole', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($fondo_strada, '4'); ?>><?php _e('Ghiacciato', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($fondo_strada, '5'); ?>><?php _e('Innevato', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="segnaletica_strada"><?php _e('Segnaletica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="segnaletica_strada" name="segnaletica_strada">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($segnaletica, '1'); ?>><?php _e('Assente', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($segnaletica, '2'); ?>><?php _e('Verticale', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($segnaletica, '3'); ?>><?php _e('Orizzontale', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($segnaletica, '4'); ?>><?php _e('Verticale e orizzontale', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($segnaletica, '5'); ?>><?php _e('Temporanea di cantiere', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="condizioni_meteo"><?php _e('Condizioni Meteorologiche', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="condizioni_meteo" name="condizioni_meteo">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($condizioni_meteo, '1'); ?>><?php _e('Sereno', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($condizioni_meteo, '2'); ?>><?php _e('Nebbia', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($condizioni_meteo, '3'); ?>><?php _e('Pioggia', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($condizioni_meteo, '4'); ?>><?php _e('Grandine', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($condizioni_meteo, '5'); ?>><?php _e('Neve', 'incidenti-stradali'); ?></option>
                        <option value="6" <?php selected($condizioni_meteo, '6'); ?>><?php _e('Vento forte', 'incidenti-stradali'); ?></option>
                        <option value="7" <?php selected($condizioni_meteo, '7'); ?>><?php _e('Altro', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
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
        
        echo '<h4>' . __('Pedoni Coinvolti', 'incidenti-stradali') . '</h4>';
        $this->render_pedoni_fields($post);
        
        echo '</div>';
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
    
    public function save_meta_boxes($post_id) {
        // Verify nonce
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
        
        // Check date restrictions
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        if ($data_blocco && isset($_POST['data_incidente'])) {
            if (strtotime($_POST['data_incidente']) < strtotime($data_blocco)) {
                if (!current_user_can('manage_all_incidenti')) {
                    wp_die(__('Non è possibile modificare incidenti avvenuti prima della data di blocco.', 'incidenti-stradali'));
                }
            }
        }
        
        // Array of all meta fields to save
        $meta_fields = array(
            'data_incidente', 'ora_incidente', 'minuti_incidente', 'provincia_incidente', 'comune_incidente',
            'organo_rilevazione', 'organo_coordinatore', 'nell_abitato', 'tipo_strada', 'denominazione_strada',
            'numero_strada', 'progressiva_km', 'progressiva_m', 'geometria_strada', 'pavimentazione_strada',
            'intersezione_tronco', 'stato_fondo_strada', 'segnaletica_strada', 'condizioni_meteo',
            'natura_incidente', 'dettaglio_natura', 'numero_veicoli_coinvolti', 'numero_pedoni_coinvolti',
            'latitudine', 'longitudine', 'tipo_coordinata', 'mostra_in_mappa'
        );
        
        // Save vehicle fields (up to 3 vehicles)
        for ($i = 1; $i <= 3; $i++) {
            $meta_fields[] = 'veicolo_' . $i . '_tipo';
            $meta_fields[] = 'veicolo_' . $i . '_targa';
            $meta_fields[] = 'veicolo_' . $i . '_anno_immatricolazione';
            $meta_fields[] = 'veicolo_' . $i . '_cilindrata';
            $meta_fields[] = 'veicolo_' . $i . '_peso_totale';
        }
        
        // Save driver fields (up to 3 drivers)
        for ($i = 1; $i <= 3; $i++) {
            $meta_fields[] = 'conducente_' . $i . '_eta';
            $meta_fields[] = 'conducente_' . $i . '_sesso';
            $meta_fields[] = 'conducente_' . $i . '_esito';
            $meta_fields[] = 'conducente_' . $i . '_tipo_patente';
            $meta_fields[] = 'conducente_' . $i . '_anno_patente';
        }
        
        // Save pedestrian fields (up to 4 pedestrians)
        for ($i = 1; $i <= 4; $i++) {
            $meta_fields[] = 'pedone_' . $i . '_eta';
            $meta_fields[] = 'pedone_' . $i . '_sesso';
            $meta_fields[] = 'pedone_' . $i . '_esito';
        }
        
        // Save all meta fields
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Update post title based on location and date
        if (isset($_POST['data_incidente']) && isset($_POST['denominazione_strada'])) {
            $title = sprintf(__('Incidente del %s - %s', 'incidenti-stradali'), 
                           $_POST['data_incidente'], 
                           $_POST['denominazione_strada'] ?: __('Strada non specificata', 'incidenti-stradali'));
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $title
            ));
        }
    }
}