<?php
/**
 * Template Name: Statistiche Incidenti Stradali
 * 
 * Template for displaying statistics about road accidents
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
$data_inizio = isset($_GET['data_inizio']) ? sanitize_text_field($_GET['data_inizio']) : '';
$data_fine = isset($_GET['data_fine']) ? sanitize_text_field($_GET['data_fine']) : '';

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
    'meta_query' => array()
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

// Get incidents
$incidenti = get_posts($args);

// Calculate statistics
$stats = array(
    'totale' => count($incidenti),
    'morti' => 0,
    'feriti' => 0,
    'solo_danni' => 0,
    'per_mese' => array(),
    'per_giorno' => array(),
    'per_ora' => array(),
    'per_tipo_strada' => array(),
    'per_natura' => array(),
    'per_meteo' => array(),
    'comuni' => array()
);

// Initialize arrays
for ($i = 1; $i <= 12; $i++) {
    $stats['per_mese'][$i] = 0;
}

for ($i = 1; $i <= 7; $i++) {
    $stats['per_giorno'][$i] = 0;
}

for ($i = 0; $i <= 23; $i++) {
    $stats['per_ora'][$i] = 0;
}

// Process each incident
foreach ($incidenti as $incidente) {
    $post_id = $incidente->ID;
    
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

    // Count passengers
    $num_veicoli = get_post_meta($post_id, 'numero_veicoli_coinvolti', true) ?: 0;
    for ($v = 1; $v <= $num_veicoli; $v++) {
        $num_trasportati = get_post_meta($post_id, 'veicolo_' . $v . '_numero_trasportati', true) ?: 0;
        for ($t = 1; $t <= $num_trasportati && $t <= 4; $t++) {
            $esito = get_post_meta($post_id, 'veicolo_' . $v . '_trasportato_' . $t . '_esito', true);
            if ($esito == '3' || $esito == '4') $morti_incidente++;
            if ($esito == '2') $feriti_incidente++;
        }
    }
    
    // Update totals
    $stats['morti'] += $morti_incidente;
    $stats['feriti'] += $feriti_incidente;
    if ($morti_incidente == 0 && $feriti_incidente == 0) {
        $stats['solo_danni']++;
    }
    
    // Get incident details
    $data_incidente = get_post_meta($post_id, 'data_incidente', true);
    $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
    $tipo_strada = get_post_meta($post_id, 'tipo_strada', true);
    $natura_incidente = get_post_meta($post_id, 'natura_incidente', true);
    $condizioni_meteo = get_post_meta($post_id, 'condizioni_meteo', true);
    $comune_incidente = get_post_meta($post_id, 'comune_incidente', true);
    
    // Group by month
    if ($data_incidente) {
        $mese = (int)date('n', strtotime($data_incidente));
        $stats['per_mese'][$mese]++;
    }
    
    // Group by day of week
    if ($data_incidente) {
        $giorno = (int)date('N', strtotime($data_incidente)); // 1 (Monday) to 7 (Sunday)
        $stats['per_giorno'][$giorno]++;
    }
    
    // Group by hour
    if ($ora_incidente) {
        $ora = (int)$ora_incidente;
        if ($ora >= 0 && $ora <= 23) {
            $stats['per_ora'][$ora]++;
        }
    }
    
    // Group by road type
    if ($tipo_strada) {
        if (!isset($stats['per_tipo_strada'][$tipo_strada])) {
            $stats['per_tipo_strada'][$tipo_strada] = 0;
        }
        $stats['per_tipo_strada'][$tipo_strada]++;
    }
    
    // Group by nature
    if ($natura_incidente) {
        if (!isset($stats['per_natura'][$natura_incidente])) {
            $stats['per_natura'][$natura_incidente] = 0;
        }
        $stats['per_natura'][$natura_incidente]++;
    }
    
    // Group by weather
    if ($condizioni_meteo) {
        if (!isset($stats['per_meteo'][$condizioni_meteo])) {
            $stats['per_meteo'][$condizioni_meteo] = 0;
        }
        $stats['per_meteo'][$condizioni_meteo]++;
    }
    
    // Group by comune
    if ($comune_incidente) {
        if (!isset($stats['comuni'][$comune_incidente])) {
            $stats['comuni'][$comune_incidente] = array(
                'totale' => 0,
                'morti' => 0,
                'feriti' => 0
            );
        }
        $stats['comuni'][$comune_incidente]['totale']++;
        $stats['comuni'][$comune_incidente]['morti'] += $morti_incidente;
        $stats['comuni'][$comune_incidente]['feriti'] += $feriti_incidente;
    }
}

// Sort comuni by total incidents
if (!empty($stats['comuni'])) {
    uasort($stats['comuni'], function($a, $b) {
        return $b['totale'] - $a['totale'];
    });
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="incidenti-statistics-container">
            <header class="page-header">
                <h1 class="page-title"><?php _e('Statistiche Incidenti Stradali', 'incidenti-stradali'); ?></h1>
            </header>
            
            <div class="incidenti-filters">
                <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="incidenti-filters-form">
                    <div class="filter-row">
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
                        
                        <div class="filter-group comune-filter">
                            <label for="comune"><?php _e('Comune:', 'incidenti-stradali'); ?></label>
                            <input type="text" id="comune" name="comune" value="<?php echo esc_attr($comune); ?>" placeholder="<?php _e('Codice ISTAT comune', 'incidenti-stradali'); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="button filter-button"><?php _e('Filtra', 'incidenti-stradali'); ?></button>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="button reset-button"><?php _e('Reset', 'incidenti-stradali'); ?></a>
                        </div>
                    </div>
                    
                    <div class="filter-row custom-dates" id="custom-dates" style="<?php echo $periodo === 'custom' ? 'display: flex;' : 'display: none;'; ?>">
                        <div class="filter-group">
                            <label for="data_inizio"><?php _e('Da:', 'incidenti-stradali'); ?></label>
                            <input type="date" id="data_inizio" name="data_inizio" value="<?php echo esc_attr($data_inizio); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="data_fine"><?php _e('A:', 'incidenti-stradali'); ?></label>
                            <input type="date" id="data_fine" name="data_fine" value="<?php echo esc_attr($data_fine); ?>">
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if ($stats['totale'] > 0): ?>
                <div class="incidenti-summary">
                    <div class="summary-card totale">
                        <div class="card-value"><?php echo $stats['totale']; ?></div>
                        <div class="card-label"><?php _e('Incidenti Totali', 'incidenti-stradali'); ?></div>
                    </div>
                    
                    <div class="summary-card morti">
                        <div class="card-value"><?php echo $stats['morti']; ?></div>
                        <div class="card-label"><?php _e('Morti', 'incidenti-stradali'); ?></div>
                    </div>
                    
                    <div class="summary-card feriti">
                        <div class="card-value"><?php echo $stats['feriti']; ?></div>
                        <div class="card-label"><?php _e('Feriti', 'incidenti-stradali'); ?></div>
                    </div>
                    
                    <div class="summary-card danni">
                        <div class="card-value"><?php echo $stats['solo_danni']; ?></div>
                        <div class="card-label"><?php _e('Solo Danni', 'incidenti-stradali'); ?></div>
                    </div>
                </div>
                
                <div class="incidenti-charts">
                    <div class="chart-container">
                        <h3><?php _e('Incidenti per Mese', 'incidenti-stradali'); ?></h3>
                        <canvas id="chart-mesi" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3><?php _e('Incidenti per Giorno della Settimana', 'incidenti-stradali'); ?></h3>
                        <canvas id="chart-giorni" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3><?php _e('Incidenti per Ora del Giorno', 'incidenti-stradali'); ?></h3>
                        <canvas id="chart-ore" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3><?php _e('Incidenti per Tipo di Strada', 'incidenti-stradali'); ?></h3>
                        <canvas id="chart-strade" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <?php if (!empty($stats['comuni'])): ?>
                    <div class="incidenti-comuni">
                        <h3><?php _e('Incidenti per Comune', 'incidenti-stradali'); ?></h3>
                        <table class="incidenti-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Comune', 'incidenti-stradali'); ?></th>
                                    <th><?php _e('Incidenti', 'incidenti-stradali'); ?></th>
                                    <th><?php _e('Morti', 'incidenti-stradali'); ?></th>
                                    <th><?php _e('Feriti', 'incidenti-stradali'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach ($stats['comuni'] as $comune_code => $comune_stats): 
                                    $count++;
                                    if ($count > 10) break; // Show only top 10
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($comune_code); ?></td>
                                        <td><?php echo $comune_stats['totale']; ?></td>
                                        <td><?php echo $comune_stats['morti']; ?></td>
                                        <td><?php echo $comune_stats['feriti']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="incidenti-map-container">
                    <h3><?php _e('Mappa degli Incidenti', 'incidenti-stradali'); ?></h3>
                    <div id="incidenti-map" style="height: 500px;"></div>
                </div>
                
                <div class="incidenti-export">
                    <h3><?php _e('Esporta Dati', 'incidenti-stradali'); ?></h3>
                    <p><?php _e('Scarica i dati statistici in formato CSV o PDF.', 'incidenti-stradali'); ?></p>
                    
                    <div class="export-buttons">
                        <a href="#" class="button export-csv"><?php _e('Esporta CSV', 'incidenti-stradali'); ?></a>
                        <a href="#" class="button export-pdf"><?php _e('Esporta PDF', 'incidenti-stradali'); ?></a>
                    </div>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Toggle custom dates when periodo changes
                    document.getElementById('periodo').addEventListener('change', function() {
                        var customDates = document.getElementById('custom-dates');
                        if (this.value === 'custom') {
                            customDates.style.display = 'flex';
                        } else {
                            customDates.style.display = 'none';
                        }
                    });
                    
                    // Chart for months
                    var ctxMesi = document.getElementById('chart-mesi').getContext('2d');
                    var chartMesi = new Chart(ctxMesi, {
                        type: 'bar',
                        data: {
                            labels: [
                                '<?php _e('Gen', 'incidenti-stradali'); ?>',
                                '<?php _e('Feb', 'incidenti-stradali'); ?>',
                                '<?php _e('Mar', 'incidenti-stradali'); ?>',
                                '<?php _e('Apr', 'incidenti-stradali'); ?>',
                                '<?php _e('Mag', 'incidenti-stradali'); ?>',
                                '<?php _e('Giu', 'incidenti-stradali'); ?>',
                                '<?php _e('Lug', 'incidenti-stradali'); ?>',
                                '<?php _e('Ago', 'incidenti-stradali'); ?>',
                                '<?php _e('Set', 'incidenti-stradali'); ?>',
                                '<?php _e('Ott', 'incidenti-stradali'); ?>',
                                '<?php _e('Nov', 'incidenti-stradali'); ?>',
                                '<?php _e('Dic', 'incidenti-stradali'); ?>'
                            ],
                            datasets: [{
                                label: '<?php _e('Incidenti', 'incidenti-stradali'); ?>',
                                data: [
                                    <?php echo $stats['per_mese'][1]; ?>,
                                    <?php echo $stats['per_mese'][2]; ?>,
                                    <?php echo $stats['per_mese'][3]; ?>,
                                    <?php echo $stats['per_mese'][4]; ?>,
                                    <?php echo $stats['per_mese'][5]; ?>,
                                    <?php echo $stats['per_mese'][6]; ?>,
                                    <?php echo $stats['per_mese'][7]; ?>,
                                    <?php echo $stats['per_mese'][8]; ?>,
                                    <?php echo $stats['per_mese'][9]; ?>,
                                    <?php echo $stats['per_mese'][10]; ?>,
                                    <?php echo $stats['per_mese'][11]; ?>,
                                    <?php echo $stats['per_mese'][12]; ?>
                                ],
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    
                    // Chart for days of week
                    var ctxGiorni = document.getElementById('chart-giorni').getContext('2d');
                    var chartGiorni = new Chart(ctxGiorni, {
                        type: 'bar',
                        data: {
                            labels: [
                                '<?php _e('Lun', 'incidenti-stradali'); ?>',
                                '<?php _e('Mar', 'incidenti-stradali'); ?>',
                                '<?php _e('Mer', 'incidenti-stradali'); ?>',
                                '<?php _e('Gio', 'incidenti-stradali'); ?>',
                                '<?php _e('Ven', 'incidenti-stradali'); ?>',
                                '<?php _e('Sab', 'incidenti-stradali'); ?>',
                                '<?php _e('Dom', 'incidenti-stradali'); ?>'
                            ],
                            datasets: [{
                                label: '<?php _e('Incidenti', 'incidenti-stradali'); ?>',
                                data: [
                                    <?php echo $stats['per_giorno'][1]; ?>,
                                    <?php echo $stats['per_giorno'][2]; ?>,
                                    <?php echo $stats['per_giorno'][3]; ?>,
                                    <?php echo $stats['per_giorno'][4]; ?>,
                                    <?php echo $stats['per_giorno'][5]; ?>,
                                    <?php echo $stats['per_giorno'][6]; ?>,
                                    <?php echo $stats['per_giorno'][7]; ?>
                                ],
                                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                                borderColor: 'rgba(255, 159, 64, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    
                    // Chart for hours
                    var ctxOre = document.getElementById('chart-ore').getContext('2d');
                    var chartOre = new Chart(ctxOre, {
                        type: 'line',
                        data: {
                            labels: [
                                '00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11',
                                '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'
                            ],
                            datasets: [{
                                label: '<?php _e('Incidenti', 'incidenti-stradali'); ?>',
                                data: [
                                    <?php 
                                    for ($i = 0; $i <= 23; $i++) {
                                        echo $stats['per_ora'][$i];
                                        if ($i < 23) echo ', ';
                                    }
                                    ?>
                                ],
                                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                                borderColor: 'rgba(153, 102, 255, 1)',
                                borderWidth: 1,
                                tension: 0.1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    
                    // Chart for road types
                    var ctxStrade = document.getElementById('chart-strade').getContext('2d');
                    var tipiStrada = {
                        '1': '<?php _e('Strada urbana', 'incidenti-stradali'); ?>',
                        '2': '<?php _e('Provinciale entro l\'abitato', 'incidenti-stradali'); ?>',
                        '3': '<?php _e('Statale entro l\'abitato', 'incidenti-stradali'); ?>',
                        '0': '<?php _e('Regionale entro l\'abitato', 'incidenti-stradali'); ?>',
                        '4': '<?php _e('Comunale extraurbana', 'incidenti-stradali'); ?>',
                        '5': '<?php _e('Provinciale', 'incidenti-stradali'); ?>',
                        '6': '<?php _e('Statale', 'incidenti-stradali'); ?>',
                        '7': '<?php _e('Autostrada', 'incidenti-stradali'); ?>',
                        '8': '<?php _e('Altra strada', 'incidenti-stradali'); ?>',
                        '9': '<?php _e('Regionale', 'incidenti-stradali'); ?>'
                    };
                    
                    var labels = [];
                    var data = [];
                    var backgroundColors = [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)',
                        'rgba(199, 199, 199, 0.5)',
                        'rgba(83, 102, 255, 0.5)',
                        'rgba(40, 159, 64, 0.5)',
                        'rgba(210, 99, 132, 0.5)'
                    ];
                    
                    <?php 
                    $i = 0;
                    foreach ($stats['per_tipo_strada'] as $tipo => $count) {
                        echo "labels.push(tipiStrada['$tipo'] || '$tipo');\n";
                        echo "data.push($count);\n";
                        $i++;
                    }
                    ?>
                    
                    var chartStrade = new Chart(ctxStrade, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: backgroundColors.slice(0, labels.length),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right',
                                }
                            }
                        }
                    });
                    
                    // Initialize map
                    /* var map = L.map('incidenti-map').setView([41.9028, 12.4964], 6); */
                    var map = L.map('incidenti-map').setView([40.3512508652161, 18.173951042516418], 6);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                    
                    // Add markers for incidents with coordinates
                    <?php
                    $markers_added = false;
                    foreach ($incidenti as $incidente) {
                        $post_id = $incidente->ID;
                        $latitudine = get_post_meta($post_id, 'latitudine', true);
                        $longitudine = get_post_meta($post_id, 'longitudine', true);
                        $mostra_in_mappa = get_post_meta($post_id, 'mostra_in_mappa', true);
                        
                        if ($mostra_in_mappa && $latitudine && $longitudine) {
                            $markers_added = true;
                            
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
                            
                            $data_incidente = get_post_meta($post_id, 'data_incidente', true);
                            $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
                            $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
                            
                            if ($morti_incidente > 0) {
                                echo "var iconClass = 'incidente-marker-morto';\n";
                                echo "var iconHtml = '<div class=\"marker-inner\">💀</div>';\n";
                            } elseif ($feriti_incidente > 0) {
                                echo "var iconClass = 'incidente-marker-ferito';\n";
                                echo "var iconHtml = '<div class=\"marker-inner\">🚑</div>';\n";
                            } else {
                                echo "var iconClass = 'incidente-marker-solo-danni';\n";
                                echo "var iconHtml = '<div class=\"marker-inner\">🚗</div>';\n";
                            }
                            
                            echo "var customIcon = L.divIcon({\n";
                            echo "    className: 'incidente-marker ' + iconClass,\n";
                            echo "    html: iconHtml,\n";
                            echo "    iconSize: [30, 30],\n";
                            echo "    iconAnchor: [15, 15]\n";
                            echo "});\n";
                            
                            echo "var marker = L.marker([" . esc_js($latitudine) . ", " . esc_js($longitudine) . "], {icon: customIcon}).addTo(map);\n";
                            
                            $popup_content = '<div class="incidente-popup">';
                            $popup_content .= '<h4>' . date('d/m/Y', strtotime($data_incidente)) . ' - ' . $ora_incidente . ':00</h4>';
                            $popup_content .= '<p><strong>' . esc_html($denominazione_strada ?: __('Strada non specificata', 'incidenti-stradali')) . '</strong></p>';
                            if ($morti_incidente > 0) $popup_content .= '<p class="morti">💀 ' . $morti_incidente . ' ' . __('morti', 'incidenti-stradali') . '</p>';
                            if ($feriti_incidente > 0) $popup_content .= '<p class="feriti">🚑 ' . $feriti_incidente . ' ' . __('feriti', 'incidenti-stradali') . '</p>';
                            if ($morti_incidente == 0 && $feriti_incidente == 0) $popup_content .= '<p class="solo-danni">🚗 ' . __('Solo danni', 'incidenti-stradali') . '</p>';
                            $popup_content .= '<p><a href="' . get_permalink($post_id) . '">' . __('Dettagli', 'incidenti-stradali') . '</a></p>';
                            $popup_content .= '</div>';
                            
                            echo "marker.bindPopup(" . json_encode($popup_content) . ");\n";
                        }
                    }
                    
                    if ($markers_added) {
                        echo "map.fitBounds(map.getBounds().pad(0.1));\n";
                    }
                    ?>
                    
                    // Export buttons functionality
                    document.querySelector('.export-csv').addEventListener('click', function(e) {
                        e.preventDefault();
                        alert('<?php _e('Funzionalità di esportazione CSV non ancora implementata.', 'incidenti-stradali'); ?>');
                    });
                    
                    document.querySelector('.export-pdf').addEventListener('click', function(e) {
                        e.preventDefault();
                        alert('<?php _e('Funzionalità di esportazione PDF non ancora implementata.', 'incidenti-stradali'); ?>');
                    });
                });
                </script>
            <?php else: ?>
                <div class="no-data-message">
                    <p><?php _e('Nessun incidente trovato per i filtri selezionati.', 'incidenti-stradali'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
/* Styling for the statistics page */
.incidenti-statistics-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
}

.incidenti-filters {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.incidenti-filters-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 200px;
}

.filter-group label {
    margin-bottom: 5px;
    font-weight: bold;
}

.filter-button {
    background: #0073aa;
    color: white;
}

.reset-button {
    background: #f1f1f1;
}

.incidenti-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-card.morti {
    background: #ffebf1;
    border-left: 4px solid #d63384;
}

.summary-card.feriti {
    background: #fff3e6;
    border-left: 4px solid #fd7e14;
}

.summary-card.totale {
    background: #e6f4ff;
    border-left: 4px solid #0073aa;
}

.summary-card.danni {
    background: #f1f1f1;
    border-left: 4px solid #6c757d;
}

.card-value {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-card.morti .card-value {
    color: #d63384;
}

.summary-card.feriti .card-value {
    color: #fd7e14;
}

.summary-card.totale .card-value {
    color: #0073aa;
}

.card-label {
    color: #666;
}

.incidenti-charts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2em;
    color: #333;
}

.incidenti-comuni {
    margin-bottom: 30px;
}

.incidenti-comuni h3 {
    margin-bottom: 15px;
}

.incidenti-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.incidenti-table th, .incidenti-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.incidenti-table th {
    background: #f1f1f1;
    font-weight: bold;
}

.incidenti-table tr:hover {
    background: #f9f9f9;
}

.incidenti-map-container {
    margin-bottom: 30px;
}

.incidenti-map-container h3 {
    margin-bottom: 15px;
}

.incidenti-export {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
}

.export-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.export-csv, .export-pdf {
    padding: 8px 16px;
}

.no-data-message {
    background: #f9f9f9;
    padding: 30px;
    text-align: center;
    border-radius: 5px;
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

.incidente-popup h4 {
    margin: 0 0 5px 0;
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

@media (max-width: 768px) {
    .incidenti-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .incidenti-charts {
        grid-template-columns: 1fr;
    }
    
    .filter-group {
        min-width: 100%;
    }
}
</style>

<?php
get_footer();
