<?php
/**
 * The template for displaying archive of Incidenti Stradali
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
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '';
$comune = isset($_GET['comune']) ? sanitize_text_field($_GET['comune']) : '';
$provincia = isset($_GET['provincia']) ? sanitize_text_field($_GET['provincia']) : '';
$data_inizio = isset($_GET['data_inizio']) ? sanitize_text_field($_GET['data_inizio']) : '';
$data_fine = isset($_GET['data_fine']) ? sanitize_text_field($_GET['data_fine']) : '';
$tipo_strada = isset($_GET['tipo_strada']) ? sanitize_text_field($_GET['tipo_strada']) : '';
$natura_incidente = isset($_GET['natura_incidente']) ? sanitize_text_field($_GET['natura_incidente']) : '';
$gravita = isset($_GET['gravita']) ? sanitize_text_field($_GET['gravita']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';

// Set default dates if custom period is selected but dates are not provided
if ($periodo === 'custom' && (empty($data_inizio) || empty($data_fine))) {
    $data_inizio = date('Y-m-d', strtotime('-1 year'));
    $data_fine = date('Y-m-d');
}

// Build query args based on filters
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = array(
    'post_type' => 'incidente_stradale',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'paged' => $paged,
    'meta_query' => array(
        'relation' => 'AND'
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

// Add gravita filter
if ($gravita === 'morti') {
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array(
            'key' => 'conducente_1_esito',
            'value' => array('3', '4'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'conducente_2_esito',
            'value' => array('3', '4'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'conducente_3_esito',
            'value' => array('3', '4'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'pedone_1_esito',
            'value' => array('3', '4'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'pedone_2_esito',
            'value' => array('3', '4'),
            'compare' => 'IN'
        )
    );
} elseif ($gravita === 'feriti') {
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array(
            'key' => 'conducente_1_esito',
            'value' => '2',
            'compare' => '='
        ),
        array(
            'key' => 'conducente_2_esito',
            'value' => '2',
            'compare' => '='
        ),
        array(
            'key' => 'conducente_3_esito',
            'value' => '2',
            'compare' => '='
        ),
        array(
            'key' => 'pedone_1_esito',
            'value' => '2',
            'compare' => '='
        ),
        array(
            'key' => 'pedone_2_esito',
            'value' => '2',
            'compare' => '='
        )
    );
} elseif ($gravita === 'solo_danni') {
    // This is more complex - we need to ensure NO casualties
    $args['meta_query'][] = array(
        'relation' => 'AND',
        array(
            'relation' => 'OR',
            array(
                'key' => 'conducente_1_esito',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => 'conducente_1_esito',
                'compare' => 'NOT EXISTS'
            )
        ),
        array(
            'relation' => 'OR',
            array(
                'key' => 'conducente_2_esito',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => 'conducente_2_esito',
                'compare' => 'NOT EXISTS'
            )
        ),
        array(
            'relation' => 'OR',
            array(
                'key' => 'conducente_3_esito',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => 'conducente_3_esito',
                'compare' => 'NOT EXISTS'
            )
        ),
        array(
            'relation' => 'OR',
            array(
                'key' => 'pedone_1_esito',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'numero_pedoni_coinvolti',
                'value' => '0',
                'compare' => '='
            ),
            array(
                'key' => 'numero_pedoni_coinvolti',
                'compare' => 'NOT EXISTS'
            )
        )
    );
}

// Add orderby
if ($orderby === 'date') {
    $args['meta_key'] = 'data_incidente';
    $args['orderby'] = 'meta_value';
    $args['order'] = $order;
} elseif ($orderby === 'comune') {
    $args['meta_key'] = 'comune_incidente';
    $args['orderby'] = 'meta_value';
    $args['order'] = $order;
} elseif ($orderby === 'provincia') {
    $args['meta_key'] = 'provincia_incidente';
    $args['orderby'] = 'meta_value';
    $args['order'] = $order;
} elseif ($orderby === 'gravita') {
    // This is complex - we'll sort in PHP after the query
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';
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

// Run the query
$incidenti_query = new WP_Query($args);

// Get map markers for the current page
$markers = array();
if ($incidenti_query->have_posts()) {
    while ($incidenti_query->have_posts()) {
        $incidenti_query->the_post();
        $post_id = get_the_ID();
        
        $latitudine = get_post_meta($post_id, 'latitudine', true);
        $longitudine = get_post_meta($post_id, 'longitudine', true);
        $mostra_in_mappa = get_post_meta($post_id, 'mostra_in_mappa', true);
        
        //if ($mostra_in_mappa && $latitudine && $longitudine) {
        // SEMPRE mostra in mappa se ha coordinate
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
            
            // Determine marker type
            $marker_type = 'solo_danni';
            if ($morti_incidente > 0) {
                $marker_type = 'morti';
            } elseif ($feriti_incidente > 0) {
                $marker_type = 'feriti';
            }
            
            $markers[] = array(
                'id' => $post_id,
                'lat' => (float)$latitudine,
                'lng' => (float)$longitudine,
                'type' => $marker_type,
                'title' => get_the_title()
            );
        }
    }
    // Reset post data
    wp_reset_postdata();
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="incidenti-archive-container">
            <header class="page-header">
                <h1 class="page-title"><?php _e('Archivio Incidenti Stradali', 'incidenti-stradali'); ?></h1>
                
                <div class="archive-actions">
                    <div class="view-toggle">
                        <a href="<?php echo add_query_arg('view', 'list', remove_query_arg('paged')); ?>" class="view-button <?php echo $view === 'list' ? 'active' : ''; ?>" title="<?php _e('Vista Lista', 'incidenti-stradali'); ?>">
                            <span class="dashicons dashicons-list-view"></span>
                        </a>
                        <a href="<?php echo add_query_arg('view', 'grid', remove_query_arg('paged')); ?>" class="view-button <?php echo $view === 'grid' ? 'active' : ''; ?>" title="<?php _e('Vista Griglia', 'incidenti-stradali'); ?>">
                            <span class="dashicons dashicons-grid-view"></span>
                        </a>
                        <a href="<?php echo add_query_arg('view', 'map', remove_query_arg('paged')); ?>" class="view-button <?php echo $view === 'map' ? 'active' : ''; ?>" title="<?php _e('Vista Mappa', 'incidenti-stradali'); ?>">
                            <span class="dashicons dashicons-location"></span>
                        </a>
                    </div>
                    
                    <?php if (current_user_can('edit_incidenti')): ?>
                        <a href="<?php echo admin_url('post-new.php?post_type=incidente_stradale'); ?>" class="button add-new-button">
                            <span class="dashicons dashicons-plus"></span> <?php _e('Aggiungi Nuovo', 'incidenti-stradali'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </header>
            
            <div class="incidenti-filters-panel">
                <button class="toggle-filters-button">
                    <span class="dashicons dashicons-filter"></span> <?php _e('Filtri', 'incidenti-stradali'); ?>
                </button>
                
                <div class="filters-content">
                    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('incidente_stradale')); ?>" class="incidenti-filters-form">
                        <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
                        
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="periodo"><?php _e('Periodo:', 'incidenti-stradali'); ?></label>
                                <select id="periodo" name="periodo">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
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
                                <label for="gravita"><?php _e('Gravità:', 'incidenti-stradali'); ?></label>
                                <select id="gravita" name="gravita">
                                    <option value=""><?php _e('Tutti', 'incidenti-stradali'); ?></option>
                                    <option value="morti" <?php selected($gravita, 'morti'); ?>><?php _e('Con morti', 'incidenti-stradali'); ?></option>
                                    <option value="feriti" <?php selected($gravita, 'feriti'); ?>><?php _e('Con feriti', 'incidenti-stradali'); ?></option>
                                    <option value="solo_danni" <?php selected($gravita, 'solo_danni'); ?>><?php _e('Solo danni', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="orderby"><?php _e('Ordina per:', 'incidenti-stradali'); ?></label>
                                <select id="orderby" name="orderby">
                                    <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Data', 'incidenti-stradali'); ?></option>
                                    <option value="comune" <?php selected($orderby, 'comune'); ?>><?php _e('Comune', 'incidenti-stradali'); ?></option>
                                    <option value="provincia" <?php selected($orderby, 'provincia'); ?>><?php _e('Provincia', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="order"><?php _e('Ordinamento:', 'incidenti-stradali'); ?></label>
                                <select id="order" name="order">
                                    <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Decrescente', 'incidenti-stradali'); ?></option>
                                    <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Crescente', 'incidenti-stradali'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="button filter-button"><?php _e('Applica Filtri', 'incidenti-stradali'); ?></button>
                            <a href="<?php echo esc_url(get_post_type_archive_link('incidente_stradale')); ?>?view=<?php echo esc_attr($view); ?>" class="button reset-button"><?php _e('Reset', 'incidenti-stradali'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($view === 'map' && !empty($markers)): ?>
                <div class="incidenti-map-preview">
                    <div id="archive-map"></div>
                </div>
            <?php endif; ?>
            
            <?php if ($incidenti_query->have_posts()): ?>
                <div class="incidenti-count">
                    <?php 
                    printf(
                        _n(
                            'Trovato %s incidente', 
                            'Trovati %s incidenti', 
                            $incidenti_query->found_posts, 
                            'incidenti-stradali'
                        ), 
                        '<strong>' . number_format_i18n($incidenti_query->found_posts) . '</strong>'
                    ); 
                    ?>
                </div>
                
                <div class="incidenti-list <?php echo 'view-' . esc_attr($view); ?>">
                    <?php while ($incidenti_query->have_posts()): $incidenti_query->the_post(); 
                        $post_id = get_the_ID();
                        
                        // Get incident details
                        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
                        $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
                        $minuti_incidente = get_post_meta($post_id, 'minuti_incidente', true) ?: '00';
                        $comune_incidente = get_post_meta($post_id, 'comune_incidente', true);
                        $provincia_incidente = get_post_meta($post_id, 'provincia_incidente', true);
                        $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
                        $tipo_strada_val = get_post_meta($post_id, 'tipo_strada', true);
                        $natura_incidente_val = get_post_meta($post_id, 'natura_incidente', true);
                        
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
                        
                        // Determine severity class
                        $severity_class = 'solo-danni';
                        if ($morti_incidente > 0) {
                            $severity_class = 'con-morti';
                        } elseif ($feriti_incidente > 0) {
                            $severity_class = 'con-feriti';
                        }
                    ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('incidente-item ' . $severity_class); ?>>
                            <div class="incidente-content">
                                <div class="incidente-header">
                                    <h2 class="incidente-title">
                                        <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <div class="incidente-meta">
                                        <?php if ($data_incidente): ?>
                                            <span class="incidente-date">
                                                <span class="dashicons dashicons-calendar-alt"></span>
                                                <?php echo date_i18n(get_option('date_format'), strtotime($data_incidente)); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($ora_incidente): ?>
                                            <span class="incidente-time">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php echo esc_html($ora_incidente . ':' . $minuti_incidente); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="incidente-details">
                                    <?php if ($comune_incidente || $provincia_incidente): ?>
                                        <div class="incidente-location">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php 
                                            $location_parts = array();
                                            if ($comune_incidente) $location_parts[] = $comune_incidente;
                                            if ($provincia_incidente) $location_parts[] = $provincia_incidente;
                                            echo implode(', ', $location_parts);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($denominazione_strada): ?>
                                        <div class="incidente-strada">
                                            <span class="dashicons dashicons-admin-site-alt3"></span>
                                            <?php echo esc_html($denominazione_strada); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($tipi_strada[$tipo_strada_val])): ?>
                                        <div class="incidente-tipo-strada">
                                            <span class="dashicons dashicons-admin-network"></span>
                                            <?php echo esc_html($tipi_strada[$tipo_strada_val]); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($nature_incidente[$natura_incidente_val])): ?>
                                        <div class="incidente-natura">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php echo esc_html($nature_incidente[$natura_incidente_val]); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="incidente-casualties">
                                    <?php if ($morti_incidente > 0): ?>
                                        <div class="casualty-count morti">
                                            <span class="count"><?php echo $morti_incidente; ?></span>
                                            <span class="label"><?php echo _n('Morto', 'Morti', $morti_incidente, 'incidenti-stradali'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($feriti_incidente > 0): ?>
                                        <div class="casualty-count feriti">
                                            <span class="count"><?php echo $feriti_incidente; ?></span>
                                            <span class="label"><?php echo _n('Ferito', 'Feriti', $feriti_incidente, 'incidenti-stradali'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($morti_incidente === 0 && $feriti_incidente === 0): ?>
                                        <div class="casualty-count solo-danni">
                                            <span class="label"><?php _e('Solo danni', 'incidenti-stradali'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="incidente-actions">
                                    <a href="<?php the_permalink(); ?>" class="button view-button">
                                        <span class="dashicons dashicons-visibility"></span> <?php _e('Visualizza', 'incidenti-stradali'); ?>
                                    </a>
                                    
                                    <?php if (current_user_can('edit_post', $post_id)): ?>
                                        <a href="<?php echo get_edit_post_link(); ?>" class="button edit-button">
                                            <span class="dashicons dashicons-edit"></span> <?php _e('Modifica', 'incidenti-stradali'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <div class="incidenti-pagination">
                    <?php
                    $big = 999999999; // need an unlikely integer
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $incidenti_query->max_num_pages,
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Precedente', 'incidenti-stradali'),
                        'next_text' => __('Successivo', 'incidenti-stradali') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                    ));
                    ?>
                </div>
            <?php else: ?>
                <div class="no-incidenti-message">
                    <p><?php _e('Nessun incidente trovato con i criteri di ricerca specificati.', 'incidenti-stradali'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if ($view === 'map' && !empty($markers)): ?>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filters panel
    document.querySelector('.toggle-filters-button').addEventListener('click', function() {
        var filtersContent = document.querySelector('.filters-content');
        if (filtersContent.style.display === 'block') {
            filtersContent.style.display = 'none';
            this.classList.remove('active');
        } else {
            filtersContent.style.display = 'block';
            this.classList.add('active');
        }
    });
    
    // Toggle custom dates when periodo changes
    document.getElementById('periodo').addEventListener('change', function() {
        var customDates = document.getElementById('custom-dates');
        if (this.value === 'custom') {
            customDates.style.display = 'block';
        } else {
            customDates.style.display = 'none';
        }
    });
    
    // Initialize map
    var map = L.map('archive-map').setView([41.9028, 12.4964], 6);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add markers
    var markers = <?php echo json_encode($markers); ?>;
    var bounds = [];
    
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
        
        L.marker([marker.lat, marker.lng], {
            icon: customIcon
        }).addTo(map).on('click', function() {
            window.location.href = '<?php echo esc_url(home_url('/')); ?>?p=' + marker.id;
        });
        
        bounds.push([marker.lat, marker.lng]);
    });
    
    // Fit map to markers
    if (bounds.length > 0) {
        map.fitBounds(bounds);
    }
});
</script>
<?php else: ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filters panel
    document.querySelector('.toggle-filters-button').addEventListener('click', function() {
        var filtersContent = document.querySelector('.filters-content');
        if (filtersContent.style.display === 'block') {
            filtersContent.style.display = 'none';
            this.classList.remove('active');
        } else {
            filtersContent.style.display = 'block';
            this.classList.add('active');
        }
    });
    
    // Toggle custom dates when periodo changes
    document.getElementById('periodo').addEventListener('change', function() {
        var customDates = document.getElementById('custom-dates');
        if (this.value === 'custom') {
            customDates.style.display = 'block';
        } else {
            customDates.style.display = 'none';
        }
    });
});
</script>
<?php endif; ?>

<style>
/* Styling for the archive page */
.incidenti-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-title {
    margin: 0;
}

.archive-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.view-toggle {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.view-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f9f9f9;
    color: #666;
    text-decoration: none;
    border-right: 1px solid #ddd;
}

.view-button:last-child {
    border-right: none;
}

.view-button.active {
    background: #0073aa;
    color: white;
}

.add-new-button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.add-new-button:hover {
    background: #005a87;
    color: white;
}

.incidenti-filters-panel {
    margin-bottom: 20px;
    background: #f9f9f9;
    border-radius: 4px;
    overflow: hidden;
}

.toggle-filters-button {
    display: flex;
    align-items: center;
    gap: 5px;
    width: 100%;
    padding: 12px 15px;
    background: #f1f1f1;
    border: none;
    text-align: left;
    font-weight: bold;
    cursor: pointer;
}

.toggle-filters-button.active {
    background: #e1e1e1;
}

.filters-content {
    display: none;
    padding: 15px;
    border-top: 1px solid #ddd;
}

.incidenti-filters-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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

.incidenti-map-preview {
    height: 400px;
    margin-bottom: 20px;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#archive-map {
    height: 100%;
    width: 100%;
}

.incidenti-count {
    margin-bottom: 15px;
    color: #666;
}

.incidenti-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 30px;
}

.incidenti-list.view-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.incidente-item {
    background: white;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.incidente-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.incidente-item.con-morti {
    border-left: 4px solid #d63384;
}

.incidente-item.con-feriti {
    border-left: 4px solid #fd7e14;
}

.incidente-item.solo-danni {
    border-left: 4px solid #0073aa;
}

.incidente-content {
    padding: 15px;
}

.incidente-header {
    margin-bottom: 10px;
}

.incidente-title {
    margin: 0 0 5px 0;
    font-size: 1.2em;
}

.incidente-title a {
    color: #333;
    text-decoration: none;
}

.incidente-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 0.9em;
}

.incidente-date, .incidente-time {
    display: flex;
    align-items: center;
    gap: 5px;
}

.incidente-details {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 15px;
    font-size: 0.9em;
}

.incidente-location, .incidente-strada, .incidente-tipo-strada, .incidente-natura {
    display: flex;
    align-items: center;
    gap: 5px;
}

.incidente-casualties {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.casualty-count {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
}

.casualty-count.morti {
    background: #ffebf1;
    color: #d63384;
}

.casualty-count.feriti {
    background: #fff3e6;
    color: #fd7e14;
}

.casualty-count.solo-danni {
    background: #e6f4ff;
    color: #0073aa;
}

.casualty-count .count {
    font-weight: bold;
    font-size: 1.1em;
}

.incidente-actions {
    display: flex;
    gap: 10px;
}

.view-button, .edit-button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    font-size: 0.9em;
    text-decoration: none;
    border-radius: 4px;
}

.view-button {
    background: #f1f1f1;
    color: #333;
}

.edit-button {
    background: #e1e1e1;
    color: #333;
}

.incidenti-pagination {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}

.incidenti-pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    margin: 0 5px;
    padding: 0 10px;
    background: #f1f1f1;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
}

.incidenti-pagination .page-numbers.current {
    background: #0073aa;
    color: white;
}

.incidenti-pagination .prev,
.incidenti-pagination .next {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.no-incidenti-message {
    padding: 30px;
    text-align: center;
    background: #f9f9f9;
    border-radius: 4px;
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

/* Responsive styles */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .archive-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .incidenti-list.view-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();
