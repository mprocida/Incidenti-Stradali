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
        add_shortcode('incidenti_form', array($this, 'render_form_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_get_incidenti_markers', array($this, 'ajax_get_markers'));
        add_action('wp_ajax_nopriv_get_incidenti_markers', array($this, 'ajax_get_markers'));
        add_action('wp_ajax_get_incidente_details', array($this, 'ajax_get_incidente_details'));
        add_action('wp_ajax_nopriv_get_incidente_details', array($this, 'ajax_get_incidente_details'));
        add_action('wp_ajax_get_statistiche_data', array($this, 'ajax_get_statistiche_data'));
        add_action('wp_ajax_nopriv_get_statistiche_data', array($this, 'ajax_get_statistiche_data'));
        add_action('wp_ajax_submit_incidente_frontend', array($this, 'ajax_submit_incidente'));
        add_action('wp_ajax_nopriv_submit_incidente_frontend', array($this, 'ajax_submit_incidente'));
        
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
            has_shortcode($post->post_content, 'incidenti_lista') ||
            has_shortcode($post->post_content, 'incidenti_form'))) {
            
            // Enqueue Leaflet per le mappe
            if (has_shortcode($post->post_content, 'incidenti_mappa')) {
                wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
                wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', array(), '1.7.1');
                
                wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js', array('leaflet'), '1.4.1', true);
                wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css', array('leaflet'), '1.4.1');
                wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css', array('leaflet-markercluster'), '1.4.1');
            }

            // NUOVO: Enqueue script specifico per il form
            if (has_shortcode($post->post_content, 'incidenti_form')) {
                wp_enqueue_script('incidenti-frontend-form', 
                    INCIDENTI_PLUGIN_URL . 'assets/js/frontend-form.js', 
                    array('jquery', 'leaflet'), 
                    INCIDENTI_VERSION, 
                    true
                );
            }
            
            // Enqueue Chart.js per le statistiche
            if (has_shortcode($post->post_content, 'incidenti_statistiche')) {
                wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
            }
            
            // Localizzazione AJAX
            wp_localize_script('jquery', 'incidenti_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('incidenti_nonce')
            ));

            // Localizzazione anche per lo script del form se caricato
            if (has_shortcode($post->post_content, 'incidenti_form')) {
                wp_localize_script('incidenti-frontend-form', 'incidenti_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('incidenti_nonce')
                ));
            }
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
                        <select id="<?php echo $map_id; ?>-comune-filter" class="regular-text">
                            <option value=""><?php _e('Tutti i comuni', 'incidenti-stradali'); ?></option>
                            <?php 
                            $comuni_lecce = $this->get_comuni_lecce();
                            $selected_comune = $atts['comune'];
                            foreach($comuni_lecce as $codice => $nome): ?>
                                <option value="<?php echo esc_attr($codice); ?>" <?php selected($selected_comune, $codice); ?>>
                                    <?php echo esc_html($nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
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
     * Shortcode per visualizzare statistiche degli incidenti - VERSIONE CORRETTA
    */
    public function render_statistiche_shortcode($atts) {
        $atts = shortcode_atts(array(
            'periodo' => 'last_year',
            'comune' => '',
            'style' => 'cards',
            'show_charts' => 'true'
        ), $atts, 'incidenti_statistiche');
        
        $stats = $this->get_incidenti_statistics($atts);
        $unique_id = uniqid();
        
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
                <div class="chart-container" style="position: relative; height: 350px; margin-bottom: 30px;">
                    <h4><?php _e('Incidenti per Mese', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-mesi-<?php echo $unique_id; ?>" 
                            data-chart-type="line"
                            data-periodo="<?php echo esc_attr($atts['periodo']); ?>"
                            data-comune="<?php echo esc_attr($atts['comune']); ?>"
                            style="max-height: 250px;"></canvas>
                </div>
                
                <div class="chart-container" style="position: relative; height: 350px;">
                    <h4><?php _e('Incidenti per Tipo di Strada', 'incidenti-stradali'); ?></h4>
                    <canvas id="chart-strade-<?php echo $unique_id; ?>" 
                            data-chart-type="doughnut"
                            data-periodo="<?php echo esc_attr($atts['periodo']); ?>"
                            data-comune="<?php echo esc_attr($atts['comune']); ?>"
                            style="max-height: 250px;"></canvas>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Inizializza i grafici quando Chart.js è caricato
                function initializeCharts() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(initializeCharts, 100);
                        return;
                    }
                    
                    // Grafico per mesi
                    var ctxMesi = document.getElementById('chart-mesi-<?php echo $unique_id; ?>');
                    if (ctxMesi) {
                        loadChartData(ctxMesi, 'mesi');
                    }
                    
                    // Grafico per tipo strada
                    var ctxStrade = document.getElementById('chart-strade-<?php echo $unique_id; ?>');
                    if (ctxStrade) {
                        loadChartData(ctxStrade, 'tipo_strada');
                    }
                }
                
                function loadChartData(canvas, dataType) {
                    var $canvas = $(canvas);
                    
                    $.ajax({
                        url: incidenti_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_statistiche_data',
                            nonce: incidenti_ajax.nonce,
                            data_type: dataType,
                            periodo: $canvas.data('periodo'),
                            comune: $canvas.data('comune')
                        },
                        success: function(response) {
                            if (response.success) {
                                createChart(canvas, response.data, $canvas.data('chart-type'));
                            }
                        }
                    });
                }
                
                function createChart(canvas, data, type) {
                    var config = {
                        type: type,
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    };
                    
                    if (type === 'line') {
                        config.options.scales = {
                            y: {
                                beginAtZero: true
                            }
                        };
                    }
                    
                    new Chart(canvas.getContext('2d'), config);
                }
                
                initializeCharts();
            });
            </script>
            <?php endif; ?>
            
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler per ottenere dati statistiche
     */
    public function ajax_get_statistiche_data() {
        check_ajax_referer('incidenti_nonce', 'nonce');
        
        $data_type = sanitize_text_field($_POST['data_type']);
        $periodo = sanitize_text_field($_POST['periodo']);
        $comune = sanitize_text_field($_POST['comune']);
        
        $data = array();
        
        switch ($data_type) {
            case 'mesi':
                $data = $this->get_monthly_data($periodo, $comune);
                break;
            case 'tipo_strada':
                $data = $this->get_road_type_data($periodo, $comune);
                break;
        }
        
        wp_send_json_success($data);
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

    /**
     * Ottieni dati mensili per grafico
     */
    private function get_monthly_data($periodo, $comune) {
        $date_range = $this->get_date_range($periodo);
        
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($date_range['start'], $date_range['end']),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        if (!empty($comune)) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => $comune,
                'compare' => '='
            );
        }
        
        $incidents = get_posts($args);
        
        // Aggrega per mese
        $monthly_counts = array();
        $labels = array();
        
        // Inizializza array per gli ultimi 12 mesi
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthly_counts[$month] = 0;
            $labels[] = date('M Y', strtotime("-$i months"));
        }
        
        foreach ($incidents as $incident) {
            $date = get_post_meta($incident->ID, 'data_incidente', true);
            if ($date) {
                $month = date('Y-m', strtotime($date));
                if (isset($monthly_counts[$month])) {
                    $monthly_counts[$month]++;
                }
            }
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Incidenti', 'incidenti-stradali'),
                    'data' => array_values($monthly_counts),
                    'borderColor' => '#0073aa',
                    'backgroundColor' => 'rgba(0, 115, 170, 0.1)',
                    'tension' => 0.4
                )
            )
        );
    }

    /**
     * Ottieni dati per tipo di strada
     */
    private function get_road_type_data($periodo, $comune) {
        $date_range = $this->get_date_range($periodo);
        
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($date_range['start'], $date_range['end']),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        if (!empty($comune)) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => $comune,
                'compare' => '='
            );
        }
        
        $incidents = get_posts($args);
        
        $road_types = array(
            '1' => 'Strada urbana',
            '2' => 'Provinciale entro abitato',
            '3' => 'Statale entro abitato',
            '4' => 'Comunale extraurbana',
            '5' => 'Provinciale',
            '6' => 'Statale',
            '7' => 'Autostrada',
            '8' => 'Altra strada'
        );
        
        $type_counts = array();
        $labels = array();
        $data_values = array();
        
        foreach ($incidents as $incident) {
            $tipo = get_post_meta($incident->ID, 'tipo_strada', true);
            if ($tipo && isset($road_types[$tipo])) {
                if (!isset($type_counts[$tipo])) {
                    $type_counts[$tipo] = 0;
                }
                $type_counts[$tipo]++;
            }
        }
        
        foreach ($type_counts as $tipo => $count) {
            $labels[] = $road_types[$tipo];
            $data_values[] = $count;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data_values,
                    'backgroundColor' => array(
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF'
                    )
                )
            )
        );
    }
    
    /**
     * Ottieni range di date basato sul periodo
     */
    private function get_date_range($periodo) {
        switch ($periodo) {
            case 'last_month':
                return array(
                    'start' => date('Y-m-01', strtotime('-1 month')),
                    'end' => date('Y-m-t', strtotime('-1 month'))
                );
            case 'last_3_months':
                return array(
                    'start' => date('Y-m-01', strtotime('-3 months')),
                    'end' => date('Y-m-d')
                );
            case 'last_year':
            default:
                return array(
                    'start' => date('Y-m-d', strtotime('-1 year')),
                    'end' => date('Y-m-d')
                );
        }
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
     * Shortcode per form di inserimento incidenti dal frontend
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_title' => 'true',
            'redirect_url' => '',
            'success_message' => 'Incidente inserito con successo',
            'require_login' => 'false'
        ), $atts, 'incidenti_form');
        
        // Controllo login se richiesto
        if ($atts['require_login'] === 'true' && !is_user_logged_in()) {
            return '<p>Devi essere loggato per inserire un incidente.</p>';
        }
        
        ob_start();
        ?>
        <div id="incidenti-frontend-form">
            <?php if ($atts['show_title'] === 'true'): ?>
                <h3>Segnala Incidente Stradale</h3>
            <?php endif; ?>
            
            <form id="incidente-form" method="post">
                <?php wp_nonce_field('submit_incidente_frontend', 'incidente_nonce'); ?>
                
                <!-- Dati essenziali incidente -->
                <div class="form-section">
                    <h4>Dati Incidente</h4>
                    
                    <div class="form-row">
                        <label for="data_incidente">Data Incidente *</label>
                        <input type="date" name="data_incidente" id="data_incidente" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="ora_incidente">Ora Incidente *</label>
                        <input type="time" name="ora_incidente" id="ora_incidente" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="provincia_incidente">Provincia *</label>
                        <input type="text" name="provincia_incidente" id="provincia_incidente" required maxlength="3">
                    </div>
                    
                    <div class="form-row">
                        <label for="comune_incidente">Comune *</label>
                        <input type="text" name="comune_incidente" id="comune_incidente" required>
                    </div>
                </div>
                
                <!-- Localizzazione -->
                <div class="form-section">
                    <h4>Localizzazione</h4>
                    
                    <div class="form-row">
                        <label for="indirizzo">Indirizzo/Via</label>
                        <input type="text" name="indirizzo" id="indirizzo">
                    </div>
                    
                    <div class="form-row">
                        <label>Clicca sulla mappa per indicare il punto esatto:</label>
                        <div id="frontend-map" style="height: 300px; margin: 10px 0;"></div>
                        <input type="hidden" name="latitudine" id="latitudine">
                        <input type="hidden" name="longitudine" id="longitudine">
                    </div>
                </div>
                
                <!-- Natura incidente -->
                <div class="form-section">
                    <h4>Tipo di Incidente</h4>
                    
                    <div class="form-row">
                        <label for="natura_incidente">Natura Incidente *</label>
                        <select name="natura_incidente" id="natura_incidente" required>
                            <option value="">Seleziona...</option>
                            <option value="A">Scontro frontale</option>
                            <option value="B">Scontro frontale-laterale</option>
                            <option value="C">Scontro laterale</option>
                            <option value="D">Tamponamento</option>
                            <option value="E">Investimento pedone</option>
                            <option value="F">Urto con ostacolo</option>
                            <option value="G">Fuoriuscita</option>
                        </select>
                    </div>
                </div>
                
                <!-- Persone coinvolte -->
                <div class="form-section">
                    <h4>Persone Coinvolte</h4>
                    
                    <div class="form-row">
                        <label for="morti_immediati">Morti immediati</label>
                        <input type="number" name="morti_immediati" id="morti_immediati" min="0" max="99" value="0">
                    </div>
                    
                    <div class="form-row">
                        <label for="feriti">Feriti</label>
                        <input type="number" name="feriti" id="feriti" min="0" max="99" value="0">
                    </div>
                </div>
                
                <!-- Note aggiuntive -->
                <div class="form-section">
                    <h4>Descrizione</h4>
                    <div class="form-row">
                        <label for="descrizione">Descrizione dell'incidente</label>
                        <textarea name="descrizione" id="descrizione" rows="4" placeholder="Descrivi brevemente la dinamica dell'incidente..."></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="submit-incidente">Invia Segnalazione</button>
                    <span id="form-loading" style="display: none;">Invio in corso...</span>
                </div>
            </form>
            
            <div id="form-messages"></div>
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

    /**
     * Gestisce la submission del form frontend
     */
    public function ajax_submit_incidente() {
        // Debug iniziale
        error_log('ajax_submit_incidente chiamato');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verifica nonce
        if (!isset($_POST['incidente_nonce']) || !wp_verify_nonce($_POST['incidente_nonce'], 'submit_incidente_frontend')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Errore di sicurezza: nonce non valido');
            return;
        }
        
        // Verifica campi obbligatori
        $required_fields = ['data_incidente', 'ora_incidente', 'provincia_incidente', 'comune_incidente', 'natura_incidente'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Campo obbligatorio mancante: $field");
                return;
            }
        }
        
        // Sanitize input
        $data = array(
            'post_title' => 'Incidente del ' . sanitize_text_field($_POST['data_incidente']) . ' - ' . sanitize_text_field($_POST['ora_incidente']),
            'post_type' => 'incidente_stradale',
            'post_status' => 'draft', // Salva come bozza per revisione
            'post_author' => get_current_user_id() > 0 ? get_current_user_id() : 1, // Admin se anonimo
            'meta_input' => array(
                'data_incidente' => sanitize_text_field($_POST['data_incidente']),
                'ora_incidente' => sanitize_text_field($_POST['ora_incidente']),
                'provincia_incidente' => sanitize_text_field($_POST['provincia_incidente']),
                'comune_incidente' => sanitize_text_field($_POST['comune_incidente']),
                'natura_incidente' => sanitize_text_field($_POST['natura_incidente']),
                'inserito_da_frontend' => true,
                'inserito_da_utente' => get_current_user_id(),
                'ip_inserimento' => $_SERVER['REMOTE_ADDR'],
                'data_inserimento' => current_time('mysql')
            )
        );
        
        // Campi opzionali
        if (!empty($_POST['indirizzo'])) {
            $data['meta_input']['indirizzo'] = sanitize_text_field($_POST['indirizzo']);
        }
        
        if (!empty($_POST['latitudine']) && !empty($_POST['longitudine'])) {
            $data['meta_input']['latitudine'] = floatval($_POST['latitudine']);
            $data['meta_input']['longitudine'] = floatval($_POST['longitudine']);
        }
        
        if (!empty($_POST['morti_immediati'])) {
            $data['meta_input']['morti_immediati'] = intval($_POST['morti_immediati']);
        }
        
        if (!empty($_POST['feriti'])) {
            $data['meta_input']['feriti'] = intval($_POST['feriti']);
        }
        
        if (!empty($_POST['descrizione'])) {
            $data['post_content'] = sanitize_textarea_field($_POST['descrizione']);
        }
        
        // Inserisci il post
        $post_id = wp_insert_post($data);
        
        if (is_wp_error($post_id)) {
            error_log('Errore wp_insert_post: ' . $post_id->get_error_message());
            wp_send_json_error('Errore nel salvataggio: ' . $post_id->get_error_message());
            return;
        }
        
        error_log('Post creato con ID: ' . $post_id);
        
        // Invia notifica agli amministratori
        $this->send_notification_new_incidente($post_id);
        
        wp_send_json_success(array(
            'message' => 'Segnalazione ricevuta con successo. Verrà esaminata dal nostro staff.',
            'post_id' => $post_id
        ));
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
     * Implementazione corretta di get_incidenti_statistics
     */
    private function get_incidenti_statistics($atts) {
        $date_range = $this->get_date_range($atts['periodo']);
        
        $args = array(
            'post_type' => 'incidente_stradale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'data_incidente',
                    'value' => array($date_range['start'], $date_range['end']),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );
        
        if (!empty($atts['comune'])) {
            $args['meta_query'][] = array(
                'key' => 'comune_incidente',
                'value' => $atts['comune'],
                'compare' => '='
            );
        }
        
        $incidents = get_posts($args);
        
        $stats = array(
            'totale' => count($incidents),
            'morti' => 0,
            'feriti' => 0,
            'solo_danni' => 0
        );
        
        foreach ($incidents as $incident) {
            $morti_incidente = 0;
            $feriti_incidente = 0;
            
            // Conta morti e feriti da conducenti
            for ($i = 1; $i <= 3; $i++) {
                $esito = get_post_meta($incident->ID, 'conducente_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti_incidente++;
                if ($esito == '2') $feriti_incidente++;
            }
            
            // Conta morti e feriti da pedoni
            $num_pedoni = get_post_meta($incident->ID, 'numero_pedoni_coinvolti', true) ?: 0;
            for ($i = 1; $i <= $num_pedoni; $i++) {
                $esito = get_post_meta($incident->ID, 'pedone_' . $i . '_esito', true);
                if ($esito == '3' || $esito == '4') $morti_incidente++;
                if ($esito == '2') $feriti_incidente++;
            }
            
            $stats['morti'] += $morti_incidente;
            $stats['feriti'] += $feriti_incidente;
            
            if ($morti_incidente == 0 && $feriti_incidente == 0) {
                $stats['solo_danni']++;
            }
        }
        
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
            $comune_codice = get_post_meta($incidente->ID, 'comune_incidente', true);
            $nome_comune = isset($comuni_lecce[$comune_codice]) ? $comuni_lecce[$comune_codice] : $comune_codice;
            
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
                /* 'comune' => get_post_meta($post_id, 'comune_incidente', true),
                'comune_nome' => $this->get_comune_name(get_post_meta($post_id, 'comune_incidente', true)), */
                'comune' => $nome_comune, // Ora mostra il nome invece del codice
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

    /**
     * Invia notifica email per nuovo incidente da frontend
     */
    private function send_notification_new_incidente($post_id) {
        $post = get_post($post_id);
        if (!$post) return;
        
        // Ottieni email amministratori
        $admin_email = get_option('admin_email');
        $notification_emails = get_option('incidenti_notification_emails', array($admin_email));
        
        if (empty($notification_emails)) {
            $notification_emails = array($admin_email);
        }
        
        // Prepara i dati
        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
        $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
        $comune = get_post_meta($post_id, 'comune_incidente', true);
        $natura = get_post_meta($post_id, 'natura_incidente', true);
        
        $subject = '[Incidenti Stradali] Nuova segnalazione dal frontend';
        
        $message = "È stata ricevuta una nuova segnalazione di incidente stradale dal frontend.\n\n";
        $message .= "Dettagli:\n";
        $message .= "- Data: " . $data_incidente . "\n";
        $message .= "- Ora: " . $ora_incidente . "\n";
        $message .= "- Comune: " . $comune . "\n";
        $message .= "- Natura: " . $natura . "\n";
        $message .= "- Stato: Bozza (richiede revisione)\n\n";
        $message .= "Link per modificare: " . admin_url('post.php?post=' . $post_id . '&action=edit') . "\n\n";
        $message .= "IP mittente: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $message .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
        
        // Invia email
        foreach ($notification_emails as $email) {
            wp_mail(trim($email), $subject, $message);
        }
    }
}