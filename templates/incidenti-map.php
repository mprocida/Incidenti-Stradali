<?php
/**
 * Template Name: Mappa Incidenti Stradali
 * 
 * Template for displaying a map of road accidents
 *
 * @package Incidenti_Stradali
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('read_incidenti') && !current_user_can('edit_incidenti') && !current_user_can('manage_all_incidenti')) {
    wp_redirect(home_url());
    exit;
}

// Get filter parameters
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'last_year';
$comune = isset($_GET['comune']) ? sanitize_text_field($_GET['comune']) : '';
$provincia = isset($_GET['provincia']) ? sanitize_text_field($_GET['provincia']) : '';
$data_inizio = isset($_GET['data_inizio']) ? sanitize_text_field($_GET['data_inizio']) : '';
$data_fine = isset($_GET['data_fine']) ? sanitize_text_field($_GET['data_fine']) : '';
$tipo_strada = isset($_GET['tipo_strada']) ? sanitize_text_field($_GET['tipo_strada']) : '';
$natura_incidente = isset($_GET['natura_incidente']) ? sanitize_text_field($_GET['natura_incidente']) : '';
$condizioni_meteo = isset($_GET['condizioni_meteo']) ? sanitize_text_field($_GET['condizioni_meteo']) : '';
$gravita = isset($_GET['gravita']) ? sanitize_text_field($_GET['gravita']) : '';

// Set default dates if custom period is selected but dates are not provided
if ($periodo === 'custom' && (empty($data_inizio) || empty($data_fine))) {
    $data_inizio = date('Y-m-d', strtotime('-1 year'));
    $data_fine = date('Y-m-d');
}

// Build query args based on filters
$args = array(
    'post_type' => 'incidente_stradale',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    /* 'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'mostra_in_mappa',
            'value' => '1',
            'compare' => '='
        ), */
        'meta_query' => array(
        'relation' => 'AND',
        // RIMOSSO: controllo mostra_in_mappa
        array(
            'key' => 'latitudine',
            'value' => '',
            'compare' => '!='
        ),
        array(
            'key' => 'latitudine',
            'value' => '',
            'compare' => '!='
        ),
        array(
            'key' => 'longitudine',
            'value' => '',
            'compare' => '!='
        )
    )
);

// Add date filter
if ($periodo === 'custom' && !empty($data_inizio) && !empty($data_fine)) {
    $args['meta_query'][] = array(
        'key' => 'data_incidente',
        'value' => array($data_inizio, $data_fine),
        'compare' => 'BETWEEN',
        'type' => 'DATE'
    );
} elseif ($periodo === 'last_month') {
    $args['meta_query'][] = array(
        'key' => 'data_incidente',
        'value' => date('Y-m-d', strtotime('-1 month')),
        'compare' => '>=',
        'type' => 'DATE'
    );
} elseif ($periodo === 'last_3_months') {
    $args['meta_query'][] = array(
        'key' => 'data_incidente',
        'value' => date('Y-m-d', strtotime('-3 months')),
        'compare' => '>=',
        'type' => 'DATE'
    );
} elseif ($periodo === 'last_year') {
    $args['meta_query'][] = array(
        'key' => 'data_incidente',
        'value' => date('Y-m-d', strtotime('-1 year')),
        'compare' => '>=',
        'type' => 'DATE'
    );
} elseif ($periodo === 'current_year') {
    $args['meta_query'][] = array(
        'key' => 'data_incidente',
        'value' => date('Y-01-01'),
        'compare' => '>=',
        'type' => 'DATE'
    );
}

// Add comune filter
if (!empty($comune)) {
    $args['meta_query'][] = array(
        'key' => 'comune_incidente',
        'value' => $comune,
        'compare' => '='
    );
} elseif (!current_user_can('manage_all_incidenti') && current_user_can('edit_incidenti')) {
    // If user is not admin, filter by their assigned comune
    $user_comune = get_user_meta(get_current_user_id(), 'comune_assegnato', true);
    if ($user_comune) {
        $args['meta_query'][] = array(
            'key' => 'comune_incidente',
            'value' => $user_comune,
            'compare' => '='
        );
    }
}

// Add provincia filter
if (!empty($provincia)) {
    $args['meta_query'][] = array(
        'key' => 'provincia_incidente',
        'value' => $provincia,
        'compare' => '='
    );
}

// Add tipo_strada filter
if (!empty($tipo_strada)) {
    $args['meta_query'][] = array(
        'key' => 'tipo_strada',
        'value' => $tipo_strada,
        'compare' => '='
    );
}

// Add natura_incidente filter
if (!empty($natura_incidente)) {
    $args['meta_query'][] = array(
        'key' => 'natura_incidente',
        'value' => $natura_incidente,
        'compare' => '='
    );
}

// Add condizioni_meteo filter
if (!empty($condizioni_meteo)) {
    $args['meta_query'][] = array(
        'key' => 'condizioni_meteo',
        'value' => $condizioni_meteo,
        'compare' => '='
    );
}

// Get incidents
$incidenti = get_posts($args);

// Process incidents for map display
$markers = array();
$heatmap_points = array();
$morti_totali = 0;
$feriti_totali = 0;
$incidenti_totali = count($incidenti);

foreach ($incidenti as $incidente) {
    $post_id = $incidente->ID;
    $latitudine = get_post_meta($post_id, 'latitudine', true);
    $longitudine = get_post_meta($post_id, 'longitudine', true);
    
    if ($latitudine && $longitudine) {
        // Count casualties
        $morti_incidente = 0;
        $feriti_incidente = 0;
        
        // Count drivers
        for ($i = 1; $i <= 3; $i++) {
            $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
            if ($esito == '3' || $esito == '4') $morti_incidente++;
            if ($esito == '2') $feriti_incidente++;
        }
        
        // Count pedestrians
        $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
        for ($i = 1; $i <= $num_pedoni; $i++) {
            $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
            if ($esito == '3' || $esito == '4') $morti_incidente++;
            if ($esito == '2') $feriti_incidente++;
        }
        
        $morti_totali += $morti_incidente;
        $feriti_totali += $feriti_incidente;
        
        // Skip if gravity filter is set and doesn't match
        if ($gravita === 'morti' && $morti_incidente === 0) {
            continue;
        } elseif ($gravita === 'feriti' && $feriti_incidente === 0) {
            continue;
        } elseif ($gravita === 'solo_danni' && ($morti_incidente > 0 || $feriti_incidente > 0)) {
            continue;
        }
        
        // Get incident details
        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
        $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
        $minuti_incidente = get_post_meta($post_id, 'minuti_incidente', true) ?: '00';
        $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
        $comune_incidente = get_post_meta($post_id, 'comune_incidente', true);
        $provincia_incidente = get_post_meta($post_id, 'provincia_incidente', true);
        $tipo_strada_val = get_post_meta($post_id, 'tipo_strada', true);
        $natura_incidente_val = get_post_meta($post_id, 'natura_incidente', true);
        $dettaglio_natura = get_post_meta($post_id, 'dettaglio_natura', true);
        
        // Determine marker type
        $marker_type = 'solo_danni';
        if ($morti_incidente > 0) {
            $marker_type = 'morti';
        } elseif ($feriti_incidente > 0) {
            $marker_type = 'feriti';
        }
        
        // Create marker data
        $marker = array(
            'id' => $post_id,
            'lat' => (float)$latitudine,
            'lng' => (float)$longitudine,
            'type' => $marker_type,
            'title' => get_the_title($post_id),
            'data' => $data_incidente ? date_i18n(get_option('date_format'), strtotime($data_incidente)) : '',
            'ora' => $ora_incidente . ':' . $minuti_incidente,
            'strada' => $denominazione_strada,
            'comune' => $comune_incidente,
            'provincia' => $provincia_incidente,
            'morti' => $morti_incidente,
            'feriti' => $feriti_incidente,
            'url' => get_permalink($post_id)
        );
        
        $markers[] = $marker;
        
        // Add point to heatmap data
        $weight = 1;
        if ($morti_incidente > 0) {
            $weight = 3;
        } elseif ($feriti_incidente > 0) {
            $weight = 2;
        }
        
        $heatmap_points[] = array(
            (float)$latitudine,
            (float)$longitudine,
            $weight
        );
    }
}

// Get filter options
$tipi_strada = array(
    '1' => __('Strada urbana', 'incidenti-stradali'),
    '2' => __('Provinciale entro l\'abitato', 'incidenti-stradali'),
    '3' => __('Statale entro l\'abitato', 'incidenti-stradali'),
    '0' => __('Regionale entro l\'abitato', 'incidenti-stradali'),
    '4' => __('Comunale extraurbana', 'incidenti-stradali'),
    '5' => __('Provinciale', 'incidenti-stradali'),
    '6' => __('Statale', 'incidenti-stradali'),
    '7' => __('Autostrada', 'incidenti-stradali'),
    '8' => __('Altra strada', 'incidenti-stradali'),
    '9' => __('Regionale', 'incidenti-stradali')
);

$nature_incidente = array(
    'A' => __('Tra veicoli in marcia', 'incidenti-stradali'),
    'B' => __('Tra veicolo e pedoni', 'incidenti-stradali'),
    'C' => __('Veicolo in marcia che urta veicolo fermo o altro', 'incidenti-stradali'),
    'D' => __('Veicolo in marcia senza urto', 'incidenti-stradali')
);

$condizioni_meteo_options = array(
    '1' => __('Sereno', 'incidenti-stradali'),
    '2' => __('Nebbia', 'incidenti-stradali'),
    '3' => __('Pioggia', 'incidenti-stradali'),
    '4' => __('Grandine', 'incidenti-stradali'),
    '5' => __('Neve', 'incidenti-stradali'),
    '6' => __('Vento forte', 'incidenti-stradali'),
    '7' => __('Altro', 'incidenti-stradali')
);

get_header();
?>

<div id="primary" class="content-area incidenti-map-page">
    <main id="main" class="site-main">
        <div class="incidenti-map-container">
            <div class="incidenti-map-sidebar">
                <div class="sidebar-header">
                    <h1><?php _e('Mappa Incidenti Stradali', 'incidenti-stradali'); ?></h1>
                    <button id="toggle-sidebar" class="toggle-sidebar-button">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                </div>
                
                <div class="sidebar-content">
                    <div class="sidebar-section">
                        <h3><?php _e('Filtri', 'incidenti-stradali'); ?></h3>
                        <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="incidenti-filters-form">
                            <div class="filter-group">
                                <label for="periodo"><?php _e('Periodo:', 'incidenti-stradali'); ?></label>
                                <select id="periodo" name="periodo">
                                    <option value="last_month" <?php selected($periodo, 'last_month'); ?>><?php _e('Ultimo mese', 'incidenti-stradali'); ?></option>
                                    <option value="last_3_months" <?php selected($periodo, 'last_3_months'); ?>><?php _e('Ultimi 3 mesi', 'incidenti-stradali'); ?></option>
                                    <option value="last_year" <?php selected($periodo, 'last_year'); ?>><?php _e('Ultimo anno', 'incidenti-stradali'); ?></option>
                                    <option value="current_year" <?php selected($periodo, 'current_year'); ?>><?php _e('Anno corrente', 'incidenti-stradali'); ?></option>
                                    <option value="custom" <?php selected($periodo, 'custom'); ?>><?php _e('Personalizzato', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-group custom-dates" id="custom-dates" style="<?php echo $periodo === 'custom' ? 'display: block;' : 'display: none;'; ?>">
                                <label for="data_inizio"><?php _e('Da:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_inizio" name="data_inizio" value="<?php echo esc_attr($data_inizio); ?>">
                                
                                <label for="data_fine"><?php _e('A:', 'incidenti-stradali'); ?></label>
                                <input type="date" id="data_fine" name="data_fine" value="<?php echo esc_attr($data_fine); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="provincia"><?php _e('Provincia:', 'incidenti-stradali'); ?></label>
                                <input type="text" id="provincia" name="provincia" value="<?php echo esc_attr($provincia); ?>" placeholder="<?php _e('Codice provincia', 'incidenti-stradali'); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="comune"><?php _e('Comune:', 'incidenti-stradali'); ?></label>
                                <input type="text" id="comune" name="comune" value="<?php echo esc_attr($comune); ?>" placeholder="<?php _e('Codice ISTAT comune', 'incidenti-stradali'); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="tipo_strada"><?php _e('Tipo Strada:', 'incidenti-stradali'); ?></label>
                                <select id="tipo_strada" name="tipo_strada">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                                    <?php foreach ($tipi_strada as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($tipo_strada, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="natura_incidente"><?php _e('Natura Incidente:', 'incidenti-stradali'); ?></label>
                                <select id="natura_incidente" name="natura_incidente">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                                    <?php foreach ($nature_incidente as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($natura_incidente, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="condizioni_meteo"><?php _e('Condizioni Meteo:', 'incidenti-stradali'); ?></label>
                                <select id="condizioni_meteo" name="condizioni_meteo">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                                    <?php foreach ($condizioni_meteo_options as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($condizioni_meteo, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="gravita"><?php _e('Gravità:', 'incidenti-stradali'); ?></label>
                                <select id="gravita" name="gravita">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                                    <option value="morti" <?php selected($gravita, 'morti'); ?>><?php _e('Con morti', 'incidenti-stradali'); ?></option>
                                    <option value="feriti" <?php selected($gravita, 'feriti'); ?>><?php _e('Con feriti', 'incidenti-stradali'); ?></option>
                                    <option value="solo_danni" <?php selected($gravita, 'solo_danni'); ?>><?php _e('Solo danni', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="button filter-button"><?php _e('Applica Filtri', 'incidenti-stradali'); ?></button>
                                <a href="<?php echo esc_url(get_permalink()); ?>" class="button reset-button"><?php _e('Reset', 'incidenti-stradali'); ?></a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Riepilogo', 'incidenti-stradali'); ?></h3>
                        <div class="summary-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $incidenti_totali; ?></span>
                                <span class="stat-label"><?php _e('Incidenti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $morti_totali; ?></span>
                                <span class="stat-label"><?php _e('Morti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $feriti_totali; ?></span>
                                <span class="stat-label"><?php _e('Feriti', 'incidenti-stradali'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Visualizzazione', 'incidenti-stradali'); ?></h3>
                        <div class="map-controls">
                            <div class="control-group">
                                <label for="map-view"><?php _e('Tipo di Mappa:', 'incidenti-stradali'); ?></label>
                                <select id="map-view">
                                    <option value="markers"><?php _e('Marcatori', 'incidenti-stradali'); ?></option>
                                    <option value="heatmap"><?php _e('Mappa di Calore', 'incidenti-stradali'); ?></option>
                                    <option value="cluster"><?php _e('Cluster', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                            
                            <div class="control-group">
                                <label for="base-layer"><?php _e('Sfondo Mappa:', 'incidenti-stradali'); ?></label>
                                <select id="base-layer">
                                    <option value="streets"><?php _e('Stradale', 'incidenti-stradali'); ?></option>
                                    <option value="satellite"><?php _e('Satellite', 'incidenti-stradali'); ?></option>
                                    <option value="hybrid"><?php _e('Ibrido', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Legenda', 'incidenti-stradali'); ?></h3>
                        <div class="map-legend">
                            <div class="legend-item">
                                <div class="legend-marker morti"></div>
                                <span><?php _e('Incidenti con morti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-marker feriti"></div>
                                <span><?php _e('Incidenti con feriti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-marker solo_danni"></div>
                                <span><?php _e('Incidenti con soli danni', 'incidenti-stradali'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Azioni', 'incidenti-stradali'); ?></h3>
                        <div class="map-actions">
                            <button id="print-map" class="button action-button">
                                <span class="dashicons dashicons-printer"></span> <?php _e('Stampa Mappa', 'incidenti-stradali'); ?>
                            </button>
                            <button id="export-data" class="button action-button">
                                <span class="dashicons dashicons-download"></span> <?php _e('Esporta Dati', 'incidenti-stradali'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="incidenti-map-wrapper">
                <div id="incidenti-map"></div>
                <div class="map-overlay">
                    <button id="show-sidebar" class="show-sidebar-button" style="display: none;">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                    <div class="map-zoom-controls">
                        <button id="zoom-in" class="zoom-button">+</button>
                        <button id="zoom-out" class="zoom-button">−</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://unpkg.com/esri-leaflet@3.0.8/dist/esri-leaflet.js"></script>
<script src="https://unpkg.com/esri-leaflet-vector@3.1.1/dist/esri-leaflet-vector.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle custom dates when periodo changes
    document.getElementById('periodo').addEventListener('change', function() {
        var customDates = document.getElementById('custom-dates');
        if (this.value === 'custom') {
            customDates.style.display = 'block';
        } else {
            customDates.style.display = 'none';
        }
    });
    
    // Toggle sidebar
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        document.querySelector('.incidenti-map-container').classList.add('sidebar-hidden');
        document.getElementById('show-sidebar').style.display = 'block';
    });
    
    document.getElementById('show-sidebar').addEventListener('click', function() {
        document.querySelector('.incidenti-map-container').classList.remove('sidebar-hidden');
        this.style.display = 'none';
    });
    
    // Initialize map
    var map = L.map('incidenti-map').setView([41.9028, 12.4964], 6);
    
    // Base layers
    var streetsLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });
    
    var hybridLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });
    
    // Switch base layer
    document.getElementById('base-layer').addEventListener('change', function() {
        var value = this.value;
        
        map.removeLayer(streetsLayer);
        map.removeLayer(satelliteLayer);
        map.removeLayer(hybridLayer);
        
        if (value === 'streets') {
            streetsLayer.addTo(map);
        } else if (value === 'satellite') {
            satelliteLayer.addTo(map);
        } else if (value === 'hybrid') {
            hybridLayer.addTo(map);
            // Add labels layer for hybrid view
            L.esri.Vector.vectorBasemapLayer('ArcGIS:Navigation', {
                apiKey: null // Replace with your ArcGIS API key if available
            }).addTo(map);
        }
    });
    
    // Marker layers
    var markers = <?php echo json_encode($markers); ?>;
    var markersLayer = L.layerGroup();
    var clusterLayer = L.markerClusterGroup({
        maxClusterRadius: 50,
        iconCreateFunction: function(cluster) {
            var childCount = cluster.getChildCount();
            var hasMorti = false;
            var hasFeriti = false;
            
            cluster.getAllChildMarkers().forEach(function(marker) {
                if (marker.options.incidentType === 'morti') {
                    hasMorti = true;
                } else if (marker.options.incidentType === 'feriti') {
                    hasFeriti = true;
                }
            });
            
            var className = 'marker-cluster';
            if (hasMorti) {
                className += ' marker-cluster-morti';
            } else if (hasFeriti) {
                className += ' marker-cluster-feriti';
            } else {
                className += ' marker-cluster-danni';
            }
            
            return L.divIcon({ 
                html: '<div><span>' + childCount + '</span></div>', 
                className: className, 
                iconSize: new L.Point(40, 40) 
            });
        }
    });
    
    // Create heatmap layer
    var heatmapData = <?php echo json_encode($heatmap_points); ?>;
    var heatmapLayer = L.heatLayer(heatmapData, {
        radius: 25,
        blur: 15,
        maxZoom: 17,
        gradient: {
            0.4: 'blue',
            0.6: 'lime',
            0.8: 'yellow',
            1.0: 'red'
        }
    });
    
    // Add markers to layers
    markers.forEach(function(marker) {
        var iconClass = 'incidente-marker-solo-danni';
        var iconHtml = '<div class="marker-inner">🚗</div>';
        
        if (marker.type === 'morti') {
            iconClass = 'incidente-marker-morto';
            iconHtml = '<div class="marker-inner">💀</div>';
        } else if (marker.type === 'feriti') {
            iconClass = 'incidente-marker-ferito';
            iconHtml = '<div class="marker-inner">🚑</div>';
        }
        
        var customIcon = L.divIcon({
            className: 'incidente-marker ' + iconClass,
            html: iconHtml,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        
        var popupContent = '<div class="incidente-popup">' +
            '<h4>' + marker.data + ' - ' + marker.ora + '</h4>' +
            '<p><strong>' + (marker.strada || '<?php _e('Strada non specificata', 'incidenti-stradali'); ?>') + '</strong></p>' +
            '<p>' + marker.comune + ' (' + marker.provincia + ')</p>';
            
        if (marker.morti > 0) {
            popupContent += '<p class="morti">💀 ' + marker.morti + ' <?php _e('morti', 'incidenti-stradali'); ?></p>';
        }
        if (marker.feriti > 0) {
            popupContent += '<p class="feriti">🚑 ' + marker.feriti + ' <?php _e('feriti', 'incidenti-stradali'); ?></p>';
        }
        if (marker.morti === 0 && marker.feriti === 0) {
            popupContent += '<p class="solo-danni">🚗 <?php _e('Solo danni', 'incidenti-stradali'); ?></p>';
        }
        
        popupContent += '<p><a href="' + marker.url + '" class="view-details"><?php _e('Visualizza Dettagli', 'incidenti-stradali'); ?></a></p>' +
            '</div>';
        
        var markerObj = L.marker([marker.lat, marker.lng], {
            icon: customIcon,
            incidentType: marker.type,
            incidentId: marker.id
        }).bindPopup(popupContent);
        
        markersLayer.addLayer(markerObj);
        clusterLayer.addLayer(markerObj);
    });
    
    // Add markers layer to map
    markersLayer.addTo(map);
    
    // Switch visualization
    document.getElementById('map-view').addEventListener('change', function() {
        var value = this.value;
        
        map.removeLayer(markersLayer);
        map.removeLayer(clusterLayer);
        map.removeLayer(heatmapLayer);
        
        if (value === 'markers') {
            markersLayer.addTo(map);
        } else if (value === 'cluster') {
            clusterLayer.addTo(map);
        } else if (value === 'heatmap') {
            heatmapLayer.addTo(map);
        }
    });
    
    // Zoom controls
    document.getElementById('zoom-in').addEventListener('click', function() {
        map.zoomIn();
    });
    
    document.getElementById('zoom-out').addEventListener('click', function() {
        map.zoomOut();
    });
    
    // Print map
    document.getElementById('print-map').addEventListener('click', function() {
        window.print();
    });
    
    // Export data
    document.getElementById('export-data').addEventListener('click', function() {
        // Create CSV content
        var csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "ID,Data,Ora,Strada,Comune,Provincia,Latitudine,Longitudine,Morti,Feriti,URL\n";
        
        markers.forEach(function(marker) {
            csvContent += [
                marker.id,
                marker.data,
                marker.ora,
                '"' + (marker.strada || '').replace(/"/g, '""') + '"',
                marker.comune,
                marker.provincia,
                marker.lat,
                marker.lng,
                marker.morti,
                marker.feriti,
                marker.url
            ].join(',') + "\n";
        });
        
        // Create download link
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "incidenti_stradali.csv");
        document.body.appendChild(link);
        
        // Trigger download
        link.click();
    });
    
    // Fit map to markers if there are any
    if (markers.length > 0) {
        var bounds = [];
        markers.forEach(function(marker) {
            bounds.push([marker.lat, marker.lng]);
        });
        map.fitBounds(bounds);
    }
});
</script>

<style>
/* Styling for the map page */
.incidenti-map-page {
    margin: 0;
    padding: 0;
}

.incidenti-map-container {
    display: flex;
    height: calc(100vh - 60px); /* Adjust based on your theme's header height */
    position: relative;
    transition: all 0.3s ease;
}

.incidenti-map-sidebar {
    width: 350px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h1 {
    margin: 0;
    font-size: 1.5em;
}

.toggle-sidebar-button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2em;
    color: #666;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.sidebar-section {
    margin-bottom: 20px;
}

.sidebar-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2em;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.incidenti-filters-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 0.9em;
}

.filter-group select,
.filter-group input {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.filter-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.reset-button {
    background: #f1f1f1;
    color: #333;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    text-align: center;
}

.summary-stats {
    display: flex;
    justify-content: space-between;
    text-align: center;
}

.stat-item {
    flex: 1;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-value {
    display: block;
    font-size: 1.8em;
    font-weight: bold;
    color: #0073aa;
}

.stat-item:nth-child(2) .stat-value {
    color: #d63384;
}

.stat-item:nth-child(3) .stat-value {
    color: #fd7e14;
}

.stat-label {
    display: block;
    font-size: 0.9em;
    color: #666;
}

.map-controls {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.control-group {
    display: flex;
    flex-direction: column;
}

.map-legend {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.legend-marker {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.legend-marker.morti {
    background-color: rgba(214, 51, 132, 0.8);
}

.legend-marker.feriti {
    background-color: rgba(253, 126, 20, 0.8);
}

.legend-marker.solo_danni {
    background-color: rgba(0, 115, 170, 0.8);
}

.map-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 8px 16px;
    background: #f1f1f1;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.action-button:hover {
    background: #e1e1e1;
}

.incidenti-map-wrapper {
    flex: 1;
    position: relative;
}

#incidenti-map {
    height: 100%;
    width: 100%;
}

.map-overlay {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 1000;
}

.show-sidebar-button {
    background: white;
    border: none;
    border-radius: 4px;
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
    padding: 8px;
    cursor: pointer;
}

.map-zoom-controls {
    display: flex;
    flex-direction: column;
    margin-top: 10px;
}

.zoom-button {
    background: white;
    border: none;
    border-radius: 4px;
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
    padding: 8px 12px;
    font-size: 1.2em;
    cursor: pointer;
    margin-bottom: 5px;
}

.sidebar-hidden .incidenti-map-sidebar {
    margin-left: -350px;
}

/* Map marker styles */
.incidente-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.incidente-marker-morto {
    background-color: rgba(214, 51, 132, 0.8);
}

.incidente-marker-ferito {
    background-color: rgba(253, 126, 20, 0.8);
}

.incidente-marker-solo-danni {
    background-color: rgba(0, 115, 170, 0.8);
}

.marker-inner {
    font-size: 16px;
    line-height: 1;
}

/* Cluster styles */
.marker-cluster {
    background-clip: padding-box;
    border-radius: 20px;
}

.marker-cluster div {
    width: 30px;
    height: 30px;
    margin-left: 5px;
    margin-top: 5px;
    text-align: center;
    border-radius: 15px;
    font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
}

.marker-cluster span {
    color: white;
}

.marker-cluster-morti {
    background-color: rgba(214, 51, 132, 0.6);
}

.marker-cluster-morti div {
    background-color: rgba(214, 51, 132, 0.8);
}

.marker-cluster-feriti {
    background-color: rgba(253, 126, 20, 0.6);
}

.marker-cluster-feriti div {
    background-color: rgba(253, 126, 20, 0.8);
}

.marker-cluster-danni {
    background-color: rgba(0, 115, 170, 0.6);
}

.marker-cluster-danni div {
    background-color: rgba(0, 115, 170, 0.8);
}

/* Popup styles */
.incidente-popup {
    min-width: 200px;
}

.incidente-popup h4 {
    margin: 0 0 5px 0;
    font-size: 1.1em;
}

.incidente-popup p {
    margin: 5px 0;
}

.incidente-popup .morti {
    color: #d63384;
    font-weight: bold;
}

.incidente-popup .feriti {
    color: #fd7e14;
    font-weight: bold;
}

.incidente-popup .view-details {
    display: inline-block;
    margin-top: 5px;
    padding: 5px 10px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 3px;
    font-size: 0.9em;
}

/* Print styles */
@media print {
    .incidenti-map-sidebar,
    .map-overlay {
        display: none !important;
    }
    
    .incidenti-map-container {
        height: 100vh !important;
    }
    
    .incidenti-map-wrapper {
        width: 100% !important;
    }
}

/* Responsive styles */
@media (max-width: 768px) {
    .incidenti-map-container {
        flex-direction: column;
        height: auto;
    }
    
    .incidenti-map-sidebar {
        width: 100%;
        height: auto;
        max-height: 50vh;
    }
    
    .incidenti-map-wrapper {
        height: 50vh;
    }
    
    .sidebar-hidden .incidenti-map-sidebar {
        margin-left: 0;
        margin-top: -50vh;
    }
}
</style>

<?php
get_footer();
