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
            <?php endif; ?>
            
            <?php if ($atts['show_charts'] === 'true'): ?>
            <div class="stats-charts" style="margin-top: 30px;">
                <div class="chart-container">
                    <h4><?php _e('Incidenti per Mese', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-mesi-<?php echo uniqid(); ?>" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-container">
                    <h4><?php _e('Incidenti per Tipo di Strada', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-strade-<?php echo uniqid(); ?>" width="400" height="200"></canvas>
                </div>
            </div>
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
                <p><?php _e('Nessun incidente trovato.', 'incidenti-stradali'); ?></p>
            <?php else: ?>
                <div class="incidenti-items">
                    <?php foreach ($incidenti as $incidente): ?>
                        <div class="incidente-item">
                            <div class="incidente-data">
                                <strong><?php echo date('d/m/Y', strtotime($incidente['data'])); ?></strong>
                                <span class="incidente-ora"><?php echo $incidente['ora']; ?></span>
                            </div>
                            
                            <div class="incidente-location">
                                <span class="location-icon">📍</span>
                                <?php echo esc_html($incidente['denominazione_strada'] ?: __('Strada non specificata', 'incidenti-stradali')); ?>
                                <?php if ($incidente['comune']): ?>
                                    <small>(Comune: <?php echo esc_html($incidente['comune']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="incidente-gravita">
                                <?php if ($incidente['morti'] > 0): ?>
                                    <span class="badge badge-morti"><?php echo $incidente['morti']; ?> morti</span>
                                <?php endif; ?>
                                <?php if ($incidente['feriti'] > 0): ?>
                                    <span class="badge badge-feriti"><?php echo $incidente['feriti']; ?> feriti</span>
                                <?php endif; ?>
                                <?php if ($incidente['morti'] == 0 && $incidente['feriti'] == 0): ?>
                                    <span class="badge badge-danni"><?php _e('Solo danni', 'incidenti-stradali'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($atts['mostra_dettagli'] === 'true'): ?>
                                <div class="incidente-dettagli">
                                    <p><strong><?php _e('Natura:', 'incidenti-stradali'); ?></strong> <?php echo esc_html($incidente['natura']); ?></p>
                                    <p><strong><?php _e('Veicoli coinvolti:', 'incidenti-stradali'); ?></strong> <?php echo $incidente['num_veicoli']; ?></p>
                                    <?php if ($incidente['condizioni_meteo']): ?>
                                        <p><strong><?php _e('Meteo:', 'incidenti-stradali'); ?></strong> <?php echo esc_html($incidente['condizioni_meteo']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        return ob_get_clean();
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
            $denominazione = get_post_meta($post_id, 'denominazione_strada', true);
            
            $popup_content = '<div class="incidente-popup">';
            $popup_content .= '<h4>' . date('d/m/Y', strtotime($data)) . ' - ' . $ora . ':00</h4>';
            $popup_content .= '<p><strong>' . esc_html($denominazione ?: __('Strada non specificata', 'incidenti-stradali')) . '</strong></p>';
            if ($morti > 0) $popup_content .= '<p class="morti">💀 ' . $morti . ' ' . __('morti', 'incidenti-stradali') . '</p>';
            if ($feriti > 0) $popup_content .= '<p class="feriti">🚑 ' . $feriti . ' ' . __('feriti', 'incidenti-stradali') . '</p>';
            if ($morti == 0 && $feriti == 0) $popup_content .= '<p class="solo-danni">🚗 ' . __('Solo danni', 'incidenti-stradali') . '</p>';
            $popup_content .= '</div>';
            
            $markers[] = array(
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'morti' => $morti,
                'feriti' => $feriti,
                'popup' => $popup_content
            );
            
            // Update statistics
            $stats['totale']++;
            $stats['morti'] += $morti;
            $stats['feriti'] += $feriti;
            if ($morti == 0 && $feriti == 0) $stats['solo_danni']++;
        }
        
        // Generate stats HTML
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
    
    private function get_incidenti_statistics($atts) {
        // Implement statistics calculation
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        
        // Apply filters based on attributes
        // ... (implementation similar to ajax_get_markers)
        
        return array(
            'totale' => 0,
            'morti' => 0,
            'feriti' => 0,
            'solo_danni' => 0
        );
    }
    
    private function get_incidenti_list($atts) {
        // Implement list retrieval
        // ... (implementation based on attributes)
        
        return array();
    }
}