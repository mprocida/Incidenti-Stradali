<?php
/**
 * Shortcodes Class for Incidenti Stradali
 */

class IncidentiShortcodes {
    
    public function __construct() {
        // Registra shortcodes
        add_shortcode('incidenti_mappa', array($this, 'render_mappa_shortcode'));
        add_shortcode('incidenti_statistiche', array($this, 'render_statistiche_shortcode'));
        add_shortcode('incidenti_lista', array($this, 'render_lista_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_get_incidenti_markers', array($this, 'ajax_get_markers'));
        add_action('wp_ajax_nopriv_get_incidenti_markers', array($this, 'ajax_get_markers'));
        add_action('wp_ajax_get_incidente_details', array($this, 'ajax_get_incidente_details'));
        add_action('wp_ajax_nopriv_get_incidente_details', array($this, 'ajax_get_incidente_details'));
        
        // Enqueue scripts for shortcodes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_scripts'));
    }

    // Aggiungi questo nuovo metodo
    public function enqueue_shortcode_scripts() {
        global $post;
        
        // Controlla se la pagina contiene uno shortcode del plugin
        if (is_a($post, 'WP_Post') && 
            (has_shortcode($post->post_content, 'incidenti_mappa') || 
            has_shortcode($post->post_content, 'incidenti_statistiche') || 
            has_shortcode($post->post_content, 'incidenti_lista'))) {
            
            // Enqueue Leaflet
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', array(), '1.7.1');
            
            // Enqueue marker cluster if needed
            wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', array('leaflet'), '1.4.1', true);
            wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css', array('leaflet'), '1.4.1');
            wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css', array('leaflet-markercluster'), '1.4.1');
        }
    }
    
    /**
     * Shortcode per visualizzare la mappa degli incidenti
     * 
     * Attributi:
     * - width: larghezza della mappa (default: 100%)
     * - height: altezza della mappa (default: 400px)
     * - zoom: livello di zoom iniziale (default: 10)
     * - center_lat: latitudine del centro (default: 41.9028)
     * - center_lng: longitudine del centro (default: 12.4964)
     * - comune: filtra per codice ISTAT comune
     * - periodo: filtra per periodo (last_month, last_year, custom)
     * - data_inizio: data inizio filtro (YYYY-MM-DD)
     * - data_fine: data fine filtro (YYYY-MM-DD)
     * - show_filters: mostra i filtri (true/false)
     * - cluster: raggruppa i marker vicini (true/false)
     */
    public function render_mappa_shortcode($atts) {
        $atts = shortcode_atts(array(
            'width' => '100%',
            'height' => '400px',
            'zoom' => '10',
            'center_lat' => '41.9028',
            'center_lng' => '12.4964',
            'comune' => '',
            'periodo' => '',
            'data_inizio' => '',
            'data_fine' => '',
            'show_filters' => 'true',
            'cluster' => 'true',
            'style' => 'default'
        ), $atts, 'incidenti_mappa');
        
        // Generate unique ID for this map
        $map_id = 'incidenti-map-' . uniqid();
        
        ob_start();
        ?>
        
        <div class="incidenti-map-container" style="width: <?php echo esc_attr($atts['width']); ?>;">
            
            <?php if ($atts['show_filters'] === 'true'): ?>
            <div class="incidenti-map-filters" style="margin-bottom: 15px;">
                <div class="filter-row">
                    <label for="<?php echo $map_id; ?>-comune-filter">
                        <?php _e('Comune:', 'incidenti-stradali'); ?>
                    </label>
                    <input type="text" 
                           id="<?php echo $map_id; ?>-comune-filter" 
                           placeholder="<?php _e('Codice ISTAT comune', 'incidenti-stradali'); ?>"
                           value="<?php echo esc_attr($atts['comune']); ?>">
                    
                    <label for="<?php echo $map_id; ?>-periodo-filter">
                        <?php _e('Periodo:', 'incidenti-stradali'); ?>
                    </label>
                    <select id="<?php echo $map_id; ?>-periodo-filter">
                        <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                        <option value="last_month" <?php selected($atts['periodo'], 'last_month'); ?>><?php _e('Ultimo mese', 'incidenti-stradali'); ?></option>
                        <option value="last_3_months" <?php selected($atts['periodo'], 'last_3_months'); ?>><?php _e('Ultimi 3 mesi', 'incidenti-stradali'); ?></option>
                        <option value="last_year" <?php selected($atts['periodo'], 'last_year'); ?>><?php _e('Ultimo anno', 'incidenti-stradali'); ?></option>
                        <option value="custom"><?php _e('Personalizzato', 'incidenti-stradali'); ?></option>
                    </select>
                    
                    <button type="button" id="<?php echo $map_id; ?>-filter-btn" class="button">
                        <?php _e('Filtra', 'incidenti-stradali'); ?>
                    </button>
                </div>
                
                <div class="filter-row custom-dates" id="<?php echo $map_id; ?>-custom-dates" style="display: none; margin-top: 10px;">
                    <label for="<?php echo $map_id; ?>-data-inizio">
                        <?php _e('Da:', 'incidenti-stradali'); ?>
                    </label>
                    <input type="date" 
                           id="<?php echo $map_id; ?>-data-inizio" 
                           value="<?php echo esc_attr($atts['data_inizio']); ?>">
                    
                    <label for="<?php echo $map_id; ?>-data-fine">
                        <?php _e('A:', 'incidenti-stradali'); ?>
                    </label>
                    <input type="date" 
                           id="<?php echo $map_id; ?>-data-fine" 
                           value="<?php echo esc_attr($atts['data_fine']); ?>">
                </div>
            </div>
            <?php endif; ?>
            
            <div id="<?php echo $map_id; ?>" 
                 class="incidenti-map <?php echo esc_attr($atts['style']); ?>" 
                 style="height: <?php echo esc_attr($atts['height']); ?>; width: 100%;">
            </div>
            
            <div class="incidenti-map-legend" style="margin-top: 10px;">
                <div class="legend-item">
                    <span class="legend-marker legend-marker-ferito"></span>
                    <?php _e('Incidenti con feriti', 'incidenti-stradali'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-marker legend-marker-morto"></span>
                    <?php _e('Incidenti mortali', 'incidenti-stradali'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-marker legend-marker-solo-danni"></span>
                    <?php _e('Incidenti con soli danni', 'incidenti-stradali'); ?>
                </div>
            </div>
            
            <div class="incidenti-map-stats" id="<?php echo $map_id; ?>-stats" style="margin-top: 15px;">
                <!-- Statistics will be loaded via AJAX -->
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mapId = '<?php echo $map_id; ?>';
            var map = L.map(mapId).setView([<?php echo floatval($atts['center_lat']); ?>, <?php echo floatval($atts['center_lng']); ?>], <?php echo intval($atts['zoom']); ?>);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            var markersLayer = <?php echo $atts['cluster'] === 'true' ? 'L.markerClusterGroup()' : 'L.layerGroup()'; ?>;
            map.addLayer(markersLayer);
            
            // Custom marker icons
            var iconMorto = L.divIcon({
                className: 'incidente-marker incidente-marker-morto',
                html: '<div class="marker-inner">💀</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var iconFerito = L.divIcon({
                className: 'incidente-marker incidente-marker-ferito',
                html: '<div class="marker-inner">🚑</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var iconSoloDanni = L.divIcon({
                className: 'incidente-marker incidente-marker-solo-danni',
                html: '<div class="marker-inner">🚗</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            function loadMarkers() {
                var filters = {
                    comune: $('#' + mapId + '-comune-filter').val(),
                    periodo: $('#' + mapId + '-periodo-filter').val(),
                    data_inizio: $('#' + mapId + '-data-inizio').val(),
                    data_fine: $('#' + mapId + '-data-fine').val()
                };
                
                $.ajax({
                    url: incidenti_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_incidenti_markers',
                        nonce: incidenti_ajax.nonce,
                        filters: filters
                    },
                    success: function(response) {
                        if (response.success) {
                            markersLayer.clearLayers();
                            
                            $.each(response.data.markers, function(i, marker) {
                                var icon = iconSoloDanni;
                                if (marker.morti > 0) {
                                    icon = iconMorto;
                                } else if (marker.feriti > 0) {
                                    icon = iconFerito;
                                }
                                
                                var leafletMarker = L.marker([marker.lat, marker.lng], {icon: icon})
                                    .bindPopup(marker.popup);
                                
                                markersLayer.addLayer(leafletMarker);
                            });
                            
                            // Update statistics
                            $('#' + mapId + '-stats').html(response.data.stats_html);
                            
                            // Fit map to markers if available
                            if (response.data.markers.length > 0) {
                                map.fitBounds(markersLayer.getBounds(), {padding: [20, 20]});
                            }
                        } else {
                            console.error('Error loading markers:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });
            }
            
            // Event handlers
            $('#' + mapId + '-filter-btn').on('click', loadMarkers);
            
            $('#' + mapId + '-periodo-filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#' + mapId + '-custom-dates').show();
                } else {
                    $('#' + mapId + '-custom-dates').hide();
                }
            });
            
            // Load initial markers
            loadMarkers();
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode per visualizzare statistiche degli incidenti
     */
    public function render_statistiche_shortcode($atts) {
        $atts = shortcode_atts(array(
            'periodo' => 'last_year',
            'comune' => '',
            'style' => 'cards',
            'show_charts' => 'true'
        ), $atts, 'incidenti_statistiche');
        
        $stats = $this->get_incidenti_statistics($atts);
        
        ob_start();
        ?>
        
        <div class="incidenti-statistics <?php echo esc_attr($atts['style']); ?>">
            
            <?php if ($atts['style'] === 'cards'): ?>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['totale']; ?></div>
                    <div class="stat-label"><?php _e('Incidenti Totali', 'incidenti-stradali'); ?></div>
                </div>
                <div class="stat-card morti">
                    <div class="stat-number"><?php echo $stats['morti']; ?></div>
                    <div class="stat-label"><?php _e('Morti', 'incidenti-stradali'); ?></div>
                </div>
                <div class="stat-card feriti">
                    <div class="stat-number"><?php echo $stats['feriti']; ?></div>
                    <div class="stat-label"><?php _e('Feriti', 'incidenti-stradali'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['solo_danni']; ?></div>
                    <div class="stat-label"><?php _e('Solo Danni', 'incidenti-stradali'); ?></div>
                </div>
            </div>
            <?php elseif ($atts['style'] === 'table'): ?>
            <table class="incidenti-stats-table">
                <tr>
                    <th><?php _e('Tipo', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Numero', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Percentuale', 'incidenti-stradali'); ?></th>
                </tr>
                <tr>
                    <td><?php _e('Totali', 'incidenti-stradali'); ?></td>
                    <td><?php echo $stats['totale']; ?></td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td><?php _e('Con morti', 'incidenti-stradali'); ?></td>
                    <td><?php echo $stats['morti']; ?></td>
                    <td><?php echo $stats['totale'] > 0 ? round(($stats['morti'] / $stats['totale']) * 100, 1) : 0; ?>%</td>
                </tr>
                <tr>
                    <td><?php _e('Con feriti', 'incidenti-stradali'); ?></td>
                    <td><?php echo $stats['feriti']; ?></td>
                    <td><?php echo $stats['totale'] > 0 ? round(($stats['feriti'] / $stats['totale']) * 100, 1) : 0; ?>%</td>
                </tr>
                <tr>
                    <td><?php _e('Solo danni', 'incidenti-stradali'); ?></td>
                    <td><?php echo $stats['solo_danni']; ?></td>
                    <td><?php echo $stats['totale'] > 0 ? round(($stats['solo_danni'] / $stats['totale']) * 100, 1) : 0; ?>%</td>
                </tr>
            </table>
            <?php endif; ?>
            
            <?php if ($atts['show_charts'] === 'true' && !empty($stats['chart_data'])): ?>
            <div class="stats-charts" style="margin-top: 30px;">
                <div class="chart-container">
                    <h4><?php _e('Incidenti per Mese', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-mesi-<?php echo uniqid(); ?>" width="400" height="200" data-chart="<?php echo esc_attr(json_encode($stats['chart_data']['monthly'])); ?>"></canvas>
                </div>
                
                <div class="chart-container">
                    <h4><?php _e('Incidenti per Giorno della Settimana', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-giorni-<?php echo uniqid(); ?>" width="400" height="200" data-chart="<?php echo esc_attr(json_encode($stats['chart_data']['weekly'])); ?>"></canvas>
                </div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Carica Chart.js se disponibile
                if (typeof Chart !== 'undefined') {
                    $('.stats-charts canvas').each(function() {
                        var ctx = this.getContext('2d');
                        var chartData = $(this).data('chart');
                        
                        new Chart(ctx, {
                            type: 'bar',
                            data: chartData,
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
                }
            });
            </script>
            <?php endif; ?>
            
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode per visualizzare lista degli incidenti
     */
    public function render_lista_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limite' => '10',
            'comune' => '',
            'periodo' => '',
            'mostra_dettagli' => 'false',
            'ordinamento' => 'data_desc'
        ), $atts, 'incidenti_lista');
        
        $incidenti = $this->get_incidenti_list($atts);
        
        ob_start();
        ?>
        
        <div class="incidenti-lista">
            <?php if (empty($incidenti)): ?>
                <p class="no-incidents"><?php _e('Nessun incidente trovato.', 'incidenti-stradali'); ?></p>
            <?php else: ?>
                <div class="incidenti-items">
                    <?php foreach ($incidenti as $incidente): ?>
                        <div class="incidente-item" data-id="<?php echo $incidente['id']; ?>">
                            <div class="incidente-data">
                                <strong><?php echo date('d/m/Y', strtotime($incidente['data'])); ?></strong>
                                <span class="incidente-ora"><?php echo $incidente['ora']; ?>:<?php echo $incidente['minuti'] ?: '00'; ?></span>
                            </div>
                            
                            <div class="incidente-location">
                                <span class="location-icon">📍</span>
                                <?php echo esc_html($incidente['denominazione_strada'] ?: __('Strada non specificata', 'incidenti-stradali')); ?>
                                <?php if ($incidente['comune_nome']): ?>
                                    <small>(<?php echo esc_html($incidente['comune_nome']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="incidente-gravita">
                                <?php if ($incidente['morti'] > 0): ?>
                                    <span class="badge badge-morti"><?php echo $incidente['morti']; ?> <?php _e('morti', 'incidenti-stradali'); ?></span>
                                <?php endif; ?>
                                <?php if ($incidente['feriti'] > 0): ?>
                                    <span class="badge badge-feriti"><?php echo $incidente['feriti']; ?> <?php _e('feriti', 'incidenti-stradali'); ?></span>
                                <?php endif; ?>
                                <?php if ($incidente['morti'] == 0 && $incidente['feriti'] == 0): ?>
                                    <span class="badge badge-danni"><?php _e('Solo danni', 'incidenti-stradali'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($atts['mostra_dettagli'] === 'true'): ?>
                                <div class="incidente-dettagli">
                                    <?php if ($incidente['natura']): ?>
                                    <p><strong><?php _e('Natura:', 'incidenti-stradali'); ?></strong> <?php echo esc_html($this->get_natura_label($incidente['natura'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($incidente['num_veicoli']): ?>
                                    <p><strong><?php _e('Veicoli coinvolti:', 'incidenti-stradali'); ?></strong> <?php echo $incidente['num_veicoli']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($incidente['condizioni_meteo']): ?>
                                    <p><strong><?php _e('Meteo:', 'incidenti-stradali'); ?></strong> <?php echo esc_html($this->get_meteo_label($incidente['condizioni_meteo'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($incidente['tipo_strada']): ?>
                                    <p><strong><?php _e('Tipo strada:', 'incidenti-stradali'); ?></strong> <?php echo esc_html($this->get_strada_label($incidente['tipo_strada'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($incidenti) >= intval($atts['limite'])): ?>
                <div class="incidenti-pagination">
                    <p><em><?php printf(__('Mostrati %d incidenti. Potrebbero essercene altri.', 'incidenti-stradali'), count($incidenti)); ?></em></p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Metodi helper per le etichette
     */
    private function get_natura_label($natura) {
        $labels = array(
            'A' => __('Tra veicoli in marcia', 'incidenti-stradali'),
            'B' => __('Tra veicolo e pedoni', 'incidenti-stradali'),
            'C' => __('Veicolo in marcia che urta veicolo fermo o altro', 'incidenti-stradali'),
            'D' => __('Veicolo in marcia senza urto', 'incidenti-stradali')
        );
        
        return $labels[$natura] ?? $natura;
    }

    private function get_meteo_label($meteo) {
        $labels = array(
            '1' => __('Sereno', 'incidenti-stradali'),
            '2' => __('Nebbia', 'incidenti-stradali'),
            '3' => __('Pioggia', 'incidenti-stradali'),
            '4' => __('Grandine', 'incidenti-stradali'),
            '5' => __('Neve', 'incidenti-stradali'),
            '6' => __('Vento forte', 'incidenti-stradali'),
            '7' => __('Altro', 'incidenti-stradali')
        );
        
        return $labels[$meteo] ?? $meteo;
    }

    private function get_strada_label($tipo) {
        $labels = array(
            '1' => __('Strada urbana', 'incidenti-stradali'),
            '2' => __('Provinciale entro l\'abitato', 'incidenti-stradali'),
            '3' => __('Statale entro l\'abitato', 'incidenti-stradali'),
            '4' => __('Comunale extraurbana', 'incidenti-stradali'),
            '5' => __('Provinciale', 'incidenti-stradali'),
            '6' => __('Statale', 'incidenti-stradali'),
            '7' => __('Autostrada', 'incidenti-stradali'),
            '8' => __('Altra strada', 'incidenti-stradali'),
            '9' => __('Regionale', 'incidenti-stradali')
        );
        
        return $labels[$tipo] ?? $tipo;
    }

    private function get_comune_name($codice) {
        // Qui potresti implementare una lookup dei nomi comuni
        // Per ora restituisce solo il codice
        return $codice;
    }
    
    public function ajax_get_markers() {
        check_ajax_referer('incidenti_nonce', 'nonce');
        
        $filters = $_POST['filters'];
        
        // Build query
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'mostra_in_mappa',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'latitudine',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'longitudine',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        // Apply filters
        if (!empty($filters['comune'])) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => sanitize_text_field($filters['comune']),
                'compare' => '='
            );
        }
        
        if (!empty($filters['periodo'])) {
            $date_query = $this->get_date_query_for_period($filters['periodo']);
            if ($date_query) {
                $args['meta_query'][] = $date_query;
            }
        }
        
        if (!empty($filters['data_inizio']) && !empty($filters['data_fine'])) {
            $args['meta_query'][] = array(
                'key' => 'data_incidente',
                'value' => array($filters['data_inizio'], $filters['data_fine']),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            );
        }
        
        $incidenti = get_posts($args);
        
        $markers = array();
        $stats = array('totale' => 0, 'morti' => 0, 'feriti' => 0, 'solo_danni' => 0);
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            $lat = get_post_meta($post_id, 'latitudine', true);
            $lng = get_post_meta($post_id, 'longitudine', true);
            
            if (empty($lat) || empty($lng)) continue;
            
            // Count casualties
            $morti = 0;
            $feriti = 0;
            
            // Count drivers
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti++;
                if ($esito == '2') $feriti++;
            }
            
            // Count pedestrians
            $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true);
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti++;
                if ($esito == '2') $feriti++;
            }
            
            $data = get_post_meta($post_id, 'data_incidente', true);
            $ora = get_post_meta($post_id, 'ora_incidente', true);
            $minuti = get_post_meta($post_id, 'minuti_incidente', true) ?: '00';
            $comune_codice = get_post_meta($post_id, 'comune_incidente', true);
            $organo_rilevazione = get_post_meta($post_id, 'organo_rilevazione', true);
            
            // Get nome comune from codice ISTAT
            $nome_comune = $this->get_nome_comune_from_codice($comune_codice);
            
            // Get organo rilevazione description
            $organo_desc = $this->get_organo_rilevazione_description($organo_rilevazione);
            
            // Format data italiana
            $data_italiana = '';
            if ($data) {
                $data_italiana = date('d/m/Y', strtotime($data));
            }
            
            // Format ora
            $ora_formattata = $ora . ':' . $minuti;
            
            // Generate popup content with new format
            $popup_content = '<div class="incidente-popup">';
            
            // Codice (titolo con link)
            $edit_link = get_edit_post_link($post_id);
            $popup_content .= '<h4><a href="' . esc_url($edit_link) . '" target="_blank" style="color: #0073aa; text-decoration: none;">';
            $popup_content .= '#' . str_pad($post_id, 4, '0', STR_PAD_LEFT) . ': ' . esc_html($incidente->post_title);
            $popup_content .= '</a></h4>';
            
            // Data e ora
            $popup_content .= '<p><strong>📅 Data/Ora:</strong> ' . $data_italiana . ' ' . $ora_formattata . '</p>';
            
            // Comune
            $popup_content .= '<p><strong>🏛️ Comune:</strong> ' . esc_html($nome_comune) . '</p>';
            
            // Ente/Organo rilevazione
            $popup_content .= '<p><strong>👮 Ente:</strong> ' . esc_html($organo_desc) . '</p>';
            
            // Coordinate
            $popup_content .= '<p><strong>📍 Coordinate:</strong> ' . number_format(floatval($lat), 6) . ', ' . number_format(floatval($lng), 6) . '</p>';
            
            // Vittime (se presenti)
            if ($morti > 0 || $feriti > 0) {
                $popup_content .= '<hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">';
                if ($morti > 0) {
                    $popup_content .= '<p class="morti" style="color: #d63384; font-weight: bold;">💀 ' . $morti . ' ' . _n('morto', 'morti', $morti, 'incidenti-stradali') . '</p>';
                }
                if ($feriti > 0) {
                    $popup_content .= '<p class="feriti" style="color: #fd7e14; font-weight: bold;">🚑 ' . $feriti . ' ' . _n('ferito', 'feriti', $feriti, 'incidenti-stradali') . '</p>';
                }
            }
            
            $popup_content .= '</div>';
            
            $markers[] = array(
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'morti' => $morti,
                'feriti' => $feriti,
                'popup' => $popup_content,
                'id' => $post_id
            );
            
            // Update statistics (resto del codice rimane uguale...)
            $stats['totale']++;
            $stats['morti'] += $morti;
            $stats['feriti'] += $feriti;
            if ($morti == 0 && $feriti == 0) $stats['solo_danni']++;
        }
        
        // Generate stats HTML (resto rimane uguale...)
        $stats_html = '<div class="map-stats-summary">';
        $stats_html .= '<span>' . sprintf(__('Totale: %d incidenti', 'incidenti-stradali'), $stats['totale']) . '</span>';
        $stats_html .= '<span class="morti">' . sprintf(__('Morti: %d', 'incidenti-stradali'), $stats['morti']) . '</span>';
        $stats_html .= '<span class="feriti">' . sprintf(__('Feriti: %d', 'incidenti-stradali'), $stats['feriti']) . '</span>';
        $stats_html .= '<span>' . sprintf(__('Solo danni: %d', 'incidenti-stradali'), $stats['solo_danni']) . '</span>';
        $stats_html .= '</div>';
        
        wp_send_json_success(array(
            'markers' => $markers,
            'stats' => $stats,
            'stats_html' => $stats_html
        ));
    }
    
    public function ajax_get_incidente_details() {
        check_ajax_referer('incidenti_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'incidente_stradale') {
            wp_send_json_error('Incidente not found');
        }
        
        // Get incident details
        $details = array(
            'data' => get_post_meta($post_id, 'data_incidente', true),
            'ora' => get_post_meta($post_id, 'ora_incidente', true),
            'denominazione_strada' => get_post_meta($post_id, 'denominazione_strada', true),
            'natura_incidente' => get_post_meta($post_id, 'natura_incidente', true),
            'numero_veicoli' => get_post_meta($post_id, 'numero_veicoli_coinvolti', true),
            'condizioni_meteo' => get_post_meta($post_id, 'condizioni_meteo', true)
        );
        
        wp_send_json_success($details);
    }
    
    private function get_date_query_for_period($periodo) {
        switch ($periodo) {
            case 'last_month':
                return array(
                    'key' => 'data_incidente',
                    'value' => date('Y-m-d', strtotime('-1 month')),
                    'compare' => '>=',
                    'type' => 'DATE'
                );
                
            case 'last_3_months':
                return array(
                    'key' => 'data_incidente',
                    'value' => date('Y-m-d', strtotime('-3 months')),
                    'compare' => '>=',
                    'type' => 'DATE'
                );
                
            case 'last_year':
                return array(
                    'key' => 'data_incidente',
                    'value' => date('Y-m-d', strtotime('-1 year')),
                    'compare' => '>=',
                    'type' => 'DATE'
                );
                
            default:
                return null;
        }
    }
    
    /**
     * Implementazione completa di get_incidenti_statistics
    */
    private function get_incidenti_statistics($atts) {
        // Costruisci query base
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        // Applica filtro comune
        if (!empty($atts['comune'])) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => sanitize_text_field($atts['comune']),
                'compare' => '='
            );
        }
        
        // Applica filtro periodo
        if (!empty($atts['periodo'])) {
            $date_query = $this->get_date_query_for_period($atts['periodo']);
            if ($date_query) {
                $args['meta_query'][] = $date_query;
            }
        }
        
        $incidenti = get_posts($args);
        
        $stats = array(
            'totale' => count($incidenti),
            'morti' => 0,
            'feriti' => 0,
            'solo_danni' => 0,
            'chart_data' => array(
                'monthly' => array(),
                'weekly' => array()
            )
        );
        
        $monthly_data = array();
        $weekly_data = array_fill(0, 7, 0); // 0=Domenica, 1=Lunedì, etc.
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            
            // Conta vittime
            $morti_incidente = 0;
            $feriti_incidente = 0;
            
            // Conta conducenti
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti_incidente++;
                if ($esito == '2') $feriti_incidente++;
            }
            
            // Conta pedoni
            $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti_incidente++;
                if ($esito == '2') $feriti_incidente++;
            }
            
            $stats['morti'] += $morti_incidente;
            $stats['feriti'] += $feriti_incidente;
            
            if ($morti_incidente == 0 && $feriti_incidente == 0) {
                $stats['solo_danni']++;
            }
            
            // Dati per grafici
            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
            if ($data_incidente) {
                // Dati mensili
                $month_key = date('Y-m', strtotime($data_incidente));
                $monthly_data[$month_key] = ($monthly_data[$month_key] ?? 0) + 1;
                
                // Dati settimanali
                $day_of_week = date('w', strtotime($data_incidente));
                $weekly_data[$day_of_week]++;
            }
        }
        
        // Prepara dati per grafici
        if (!empty($monthly_data)) {
            ksort($monthly_data);
            $stats['chart_data']['monthly'] = array(
                'labels' => array_keys($monthly_data),
                'datasets' => array(array(
                    'label' => __('Incidenti per Mese', 'incidenti-stradali'),
                    'data' => array_values($monthly_data),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ))
            );
        }
        
        $days_labels = array(
            __('Domenica', 'incidenti-stradali'),
            __('Lunedì', 'incidenti-stradali'),
            __('Martedì', 'incidenti-stradali'),
            __('Mercoledì', 'incidenti-stradali'),
            __('Giovedì', 'incidenti-stradali'),
            __('Venerdì', 'incidenti-stradali'),
            __('Sabato', 'incidenti-stradali')
        );
        
        $stats['chart_data']['weekly'] = array(
            'labels' => $days_labels,
            'datasets' => array(array(
                'label' => __('Incidenti per Giorno', 'incidenti-stradali'),
                'data' => $weekly_data,
                'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1
            ))
        );
        
        return $stats;
    }
    
    /**
     * Implementazione completa di get_incidenti_list
     */
    private function get_incidenti_list($atts) {
        // Costruisci query
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'meta_query' => array()
        );
        
        // Applica ordinamento
        switch ($atts['ordinamento']) {
            case 'data_asc':
                $args['meta_key'] = 'data_incidente';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'ASC';
                $args['meta_type'] = 'DATE';
                break;
            case 'data_desc':
            default:
                $args['meta_key'] = 'data_incidente';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'DESC';
                $args['meta_type'] = 'DATE';
                break;
        }
        
        // Applica filtro comune
        if (!empty($atts['comune'])) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => sanitize_text_field($atts['comune']),
                'compare' => '='
            );
        }
        
        // Applica filtro periodo
        if (!empty($atts['periodo'])) {
            $date_query = $this->get_date_query_for_period($atts['periodo']);
            if ($date_query) {
                $args['meta_query'][] = $date_query;
            }
        }
        
        $incidenti = get_posts($args);
        
        $result = array();
        
        foreach ($incidenti as $incidente) {
            $post_id = $incidente->ID;
            
            // Conta vittime
            $morti = 0;
            $feriti = 0;
            
            // Conta conducenti
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti++;
                if ($esito == '2') $feriti++;
            }
            
            // Conta pedoni
            $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti++;
                if ($esito == '2') $feriti++;
            }
            
            $result[] = array(
                'id' => $post_id,
                'data' => get_post_meta($post_id, 'data_incidente', true),
                'ora' => get_post_meta($post_id, 'ora_incidente', true),
                'minuti' => get_post_meta($post_id, 'minuti_incidente', true),
                'denominazione_strada' => get_post_meta($post_id, 'denominazione_strada', true),
                'comune' => get_post_meta($post_id, 'comune_incidente', true),
                'comune_nome' => $this->get_comune_name(get_post_meta($post_id, 'comune_incidente', true)),
                'natura' => get_post_meta($post_id, 'natura_incidente', true),
                'num_veicoli' => get_post_meta($post_id, 'numero_veicoli_coinvolti', true),
                'condizioni_meteo' => get_post_meta($post_id, 'condizioni_meteo', true),
                'tipo_strada' => get_post_meta($post_id, 'tipo_strada', true),
                'morti' => $morti,
                'feriti' => $feriti
            );
        }
        
        return $result;
    }

    /**
     * Get nome comune from codice ISTAT
     */
    private function get_nome_comune_from_codice($codice_comune) {
        if (empty($codice_comune)) {
            return __('Non specificato', 'incidenti-stradali');
        }
        
        // Carica il file JSON dei comuni
        $comuni_file = INCIDENTI_PLUGIN_PATH . 'data/codici-istat-comuni.json';
        
        if (!file_exists($comuni_file)) {
            return $codice_comune; // Fallback al codice se il file non esiste
        }
        
        $comuni_data = json_decode(file_get_contents($comuni_file), true);
        
        if (!$comuni_data || !isset($comuni_data['comuni'])) {
            return $codice_comune;
        }
        
        // Cerca il comune nelle varie province
        foreach ($comuni_data['comuni'] as $provincia_codice => $comuni_provincia) {
            if (isset($comuni_provincia[$codice_comune])) {
                return $comuni_provincia[$codice_comune];
            }
        }
        
        return $codice_comune; // Fallback se non trovato
    }

    /**
     * Get organo rilevazione description
     */
    private function get_organo_rilevazione_description($codice_organo) {
        $organi = array(
            '1' => __('Polizia Stradale', 'incidenti-stradali'),
            '2' => __('Carabinieri', 'incidenti-stradali'),
            '3' => __('Polizia di Stato', 'incidenti-stradali'),
            '4' => __('Polizia Municipale/Locale', 'incidenti-stradali'),
            '5' => __('Altri', 'incidenti-stradali'),
            '6' => __('Polizia Provinciale', 'incidenti-stradali')
        );
        
        return isset($organi[$codice_organo]) ? $organi[$codice_organo] : __('Non specificato', 'incidenti-stradali');
    }
}