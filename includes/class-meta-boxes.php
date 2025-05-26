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
                    <!-- Campo nascosto per garantire che venga sempre inviato un valore -->
                    <input type="hidden" name="nell_abitato" value="">
                    
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
        $illuminazione = get_post_meta($post->ID, 'illuminazione', true);
        $visibilita = get_post_meta($post->ID, 'visibilita', true);
        $traffico = get_post_meta($post->ID, 'traffico', true);
        $segnaletica_semaforica = get_post_meta($post->ID, 'segnaletica_semaforica', true);
        
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
            <tr>
                <th><label for="illuminazione"><?php _e('Illuminazione', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="illuminazione" name="illuminazione">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($illuminazione, '1'); ?>><?php _e('Giorno', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($illuminazione, '2'); ?>><?php _e('Alba o crepuscolo', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($illuminazione, '3'); ?>><?php _e('Notte - illuminazione pubblica presente e in funzione', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($illuminazione, '4'); ?>><?php _e('Notte - illuminazione pubblica presente ma spenta', 'incidenti-stradali'); ?></option>
                        <option value="5" <?php selected($illuminazione, '5'); ?>><?php _e('Notte - illuminazione pubblica assente', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="visibilita"><?php _e('Visibilità', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="visibilita" name="visibilita">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($visibilita, '1'); ?>><?php _e('Buona', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($visibilita, '2'); ?>><?php _e('Ridotta per condizioni atmosferiche', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($visibilita, '3'); ?>><?php _e('Ridotta per altre cause', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="traffico"><?php _e('Traffico', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="traffico" name="traffico">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($traffico, '1'); ?>><?php _e('Scarso', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($traffico, '2'); ?>><?php _e('Normale', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($traffico, '3'); ?>><?php _e('Intenso', 'incidenti-stradali'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="segnaletica_semaforica"><?php _e('Segnaletica semaforica', 'incidenti-stradali'); ?></label></th>
                <td>
                    <select id="segnaletica_semaforica" name="segnaletica_semaforica">
                        <option value=""><?php _e('Seleziona', 'incidenti-stradali'); ?></option>
                        <option value="1" <?php selected($segnaletica_semaforica, '1'); ?>><?php _e('Assente', 'incidenti-stradali'); ?></option>
                        <option value="2" <?php selected($segnaletica_semaforica, '2'); ?>><?php _e('In funzione', 'incidenti-stradali'); ?></option>
                        <option value="3" <?php selected($segnaletica_semaforica, '3'); ?>><?php _e('Lampeggiante', 'incidenti-stradali'); ?></option>
                        <option value="4" <?php selected($segnaletica_semaforica, '4'); ?>><?php _e('Spenta', 'incidenti-stradali'); ?></option>
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
        
        // IMPORTANTE: Previeni loop infiniti
        remove_action('save_post', array($this, 'save_meta_boxes'));
        
        // Check date restrictions
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        if ($data_blocco && isset($_POST['data_incidente'])) {
            if (strtotime($_POST['data_incidente']) < strtotime($data_blocco)) {
                if (!current_user_can('manage_all_incidenti')) {
                    wp_die(__('Non è possibile modificare incidenti avvenuti prima della data di blocco.', 'incidenti-stradali'));
                }
            }
        }
        
        // Array of all meta fields to save - ottimizzato per ridurre memoria
        $meta_fields = array(
            'data_incidente', 'ora_incidente', 'minuti_incidente', 'provincia_incidente', 'comune_incidente',
            'organo_rilevazione', 'organo_coordinatore', 'nell_abitato', 'tipo_strada', 'denominazione_strada',
            'numero_strada', 'progressiva_km', 'progressiva_m', 'geometria_strada', 'pavimentazione_strada',
            'intersezione_tronco', 'stato_fondo_strada', 'segnaletica_strada', 'condizioni_meteo',
            'natura_incidente', 'dettaglio_natura', 'numero_veicoli_coinvolti', 'numero_pedoni_coinvolti',
            'latitudine', 'longitudine', 'tipo_coordinata', 'mostra_in_mappa','illuminazione', 'visibilita',
            'traffico', 'segnaletica_semaforica', 'circostanza_presunta_1', 'circostanza_presunta_2', 'circostanza_presunta_3',
            'circostanza_presunta_1', 'circostanza_presunta_2', 'circostanza_presunta_3', 'circostanza_veicolo_a',
            'circostanza_veicolo_b', 'circostanza_veicolo_c'
    );

        // Salva circostanze per ogni veicolo
        for ($i = 1; $i <= 3; $i++) {
            $meta_fields[] = 'circostanza_veicolo_' . $i;
        }
        
        // Salva dati trasportati
        for ($v = 1; $v <= 3; $v++) {
            $meta_fields[] = 'veicolo_' . $v . '_numero_trasportati';
            
            $num_trasportati = isset($_POST['veicolo_' . $v . '_numero_trasportati']) ? intval($_POST['veicolo_' . $v . '_numero_trasportati']) : 0;
            
            for ($t = 1; $t <= 9; $t++) {
                if ($t <= $num_trasportati) {
                    $prefix = 'veicolo_' . $v . '_trasportato_' . $t . '_';
                    $trasportato_fields = array('sesso', 'eta', 'esito', 'posizione', 'uso_dispositivi');
                    
                    foreach ($trasportato_fields as $field) {
                        $key = $prefix . $field;
                        if (isset($_POST[$key])) {
                            update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                        }
                    }
                } else {
                    // Rimuovi dati per trasportati non utilizzati
                    $prefix = 'veicolo_' . $v . '_trasportato_' . $t . '_';
                    $trasportato_fields = array('sesso', 'eta', 'esito', 'posizione', 'uso_dispositivi');
                    
                    foreach ($trasportato_fields as $field) {
                        delete_post_meta($post_id, $prefix . $field);
                    }
                }
            }
        }
        
        // Save all meta fields in batch
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                // Per checkbox non selezionate
                if ($field === 'mostra_in_mappa') {
                    delete_post_meta($post_id, $field);
                }
            }
        }
        
        // Save vehicle and driver fields with optimization
        $numero_veicoli = isset($_POST['numero_veicoli_coinvolti']) ? intval($_POST['numero_veicoli_coinvolti']) : 1;
        for ($i = 1; $i <= 3; $i++) {
            if ($i <= $numero_veicoli) {
                // Salva solo se il veicolo è attivo
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
                // Rimuovi dati per veicoli non utilizzati
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
        
        // Save pedestrian fields with optimization
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
                // Rimuovi dati per pedoni non utilizzati
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
                // Usa direttamente il database per evitare hook ricorsivi
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
}