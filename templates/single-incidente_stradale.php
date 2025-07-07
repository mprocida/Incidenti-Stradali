<?php
/**
 * Template for displaying single Incidente Stradale
 *
 * @package Incidenti_Stradali
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if user has permission to view this incident
$current_user_id = get_current_user_id();
$user_comune = get_user_meta($current_user_id, 'comune_assegnato', true);
$post_comune = get_post_meta(get_the_ID(), 'comune_incidente', true);

// If user is not admin and doesn't have permission for this comune, redirect
if (!current_user_can('manage_all_incidenti') && 
    current_user_can('edit_incidenti') && 
    $user_comune && $post_comune && 
    $user_comune !== $post_comune) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class('incidente-stradale-single'); ?>>
            
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
                
                <div class="incidente-meta">
                    <?php 
                    $data_incidente = get_post_meta(get_the_ID(), 'data_incidente', true);
                    $ora_incidente = get_post_meta(get_the_ID(), 'ora_incidente', true);
                    $minuti_incidente = get_post_meta(get_the_ID(), 'minuti_incidente', true) ?: '00';
                    
                    if ($data_incidente) {
                        echo '<span class="incidente-data">';
                        echo date_i18n(get_option('date_format'), strtotime($data_incidente));
                        echo '</span>';
                    }
                    
                    if ($ora_incidente) {
                        echo '<span class="incidente-ora">';
                        echo esc_html($ora_incidente . ':' . $minuti_incidente);
                        echo '</span>';
                    }
                    ?>
                </div>
            </header>
            
            <div class="entry-content">
                <?php
                // Check if we should show the map
                $mostra_in_mappa = get_post_meta(get_the_ID(), 'mostra_in_mappa', true);
                $latitudine = get_post_meta(get_the_ID(), 'latitudine', true);
                $longitudine = get_post_meta(get_the_ID(), 'longitudine', true);
                
                if ($mostra_in_mappa && $latitudine && $longitudine) {
                    ?>
                    <div class="incidente-mappa-container">
                        <h3><?php _e('Localizzazione', 'incidenti-stradali'); ?></h3>
                        <div id="incidente-mappa" style="height: 400px; width: 100%;"></div>
                    </div>
                    
                    <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        var map = L.map('incidente-mappa').setView([<?php echo esc_js($latitudine); ?>, <?php echo esc_js($longitudine); ?>], 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);
                        
                        <?php
                        // Determine marker icon based on casualties
                        $morti = 0;
                        $feriti = 0;
                        
                        // Count drivers
                        $numero_veicoli_calc = (int) get_post_meta(get_the_ID(), 'numero_veicoli_coinvolti', true) ?: 0;
                        for ($i = 1; $i <= $numero_veicoli_calc; $i++) {
                            $esito = get_post_meta(get_the_ID(), 'conducente_' . $i . '_esito', true);
                            if ($esito == '3' || $esito == '4') $morti++;
                            if ($esito == '2') $feriti++;
                            
                            // Count passengers
                            $num_passeggeri = get_post_meta(get_the_ID(), 'veicolo_' . $i . '_numero_passeggeri', true) ?: 0;
                            for ($j = 1; $j <= $num_passeggeri; $j++) {
                                $esito_pass = get_post_meta(get_the_ID(), 'passeggero_' . $i . '_' . $j . '_esito', true);
                                if ($esito_pass == '3' || $esito_pass == '4') $morti++;
                                if ($esito_pass == '2') $feriti++;
                            }
                        }
                        
                        // Count pedestrians
                        $num_pedoni = get_post_meta(get_the_ID(), 'numero_pedoni_coinvolti', true) ?: 0;
                        for ($i = 1; $i <= $num_pedoni; $i++) {
                            $esito = get_post_meta(get_the_ID(), 'pedone_' . $i . '_esito', true);
                            if ($esito == '3' || $esito == '4') $morti++;
                            if ($esito == '2') $feriti++;
                        }
                        
                        if ($morti > 0) {
                            echo "var iconClass = 'incidente-marker-morto';";
                            echo "var iconHtml = '<div class=\"marker-inner\">💀</div>';";
                        } elseif ($feriti > 0) {
                            echo "var iconClass = 'incidente-marker-ferito';";
                            echo "var iconHtml = '<div class=\"marker-inner\">🚑</div>';";
                        } else {
                            echo "var iconClass = 'incidente-marker-solo-danni';";
                            echo "var iconHtml = '<div class=\"marker-inner\">🚗</div>';";
                        }
                        ?>
                        
                        var customIcon = L.divIcon({
                            className: 'incidente-marker ' + iconClass,
                            html: iconHtml,
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        });
                        
                        L.marker([<?php echo esc_js($latitudine); ?>, <?php echo esc_js($longitudine); ?>], {icon: customIcon}).addTo(map);
                    });
                    </script>
                    <?php
                }
                ?>
                
                <div class="incidente-details">
                    <div class="incidente-section">
                    <h3><?php _e('Informazioni Generali', 'incidenti-stradali'); ?></h3>
                    <table class="incidente-table">
                        <tr>
                            <th><?php _e('Data Incidente', 'incidenti-stradali'); ?></th>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($data_incidente)); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Ora', 'incidenti-stradali'); ?></th>
                            <td><?php echo esc_html($ora_incidente . ':' . $minuti_incidente); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Provincia (Codice ISTAT)', 'incidenti-stradali'); ?></th>
                            <td><?php echo esc_html(get_post_meta(get_the_ID(), 'provincia_incidente', true)); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Comune (Codice ISTAT)', 'incidenti-stradali'); ?></th>
                            <td><?php echo esc_html(get_post_meta(get_the_ID(), 'comune_incidente', true)); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Organo di Rilevazione', 'incidenti-stradali'); ?></th>
                            <td>
                                <?php 
                                $organo = get_post_meta(get_the_ID(), 'organo_rilevazione', true);
                                $organi = array(
                                    '1' => __('Polizia Municipale', 'incidenti-stradali'),
                                    '2' => __('Carabinieri', 'incidenti-stradali'),
                                    '3' => __('Polizia di Stato', 'incidenti-stradali'),
                                    '4' => __('Guardia di Finanza', 'incidenti-stradali'),
                                    '5' => __('Vigili del Fuoco', 'incidenti-stradali'),
                                    '6' => __('Altro', 'incidenti-stradali')
                                );
                                echo isset($organi[$organo]) ? esc_html($organi[$organo]) : '';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Localizzazione', 'incidenti-stradali'); ?></th>
                            <td>
                                <?php 
                                $nell_abitato = get_post_meta(get_the_ID(), 'nell_abitato', true);
                                echo $nell_abitato == '1' ? __('Nell\'abitato', 'incidenti-stradali') : __('Fuori dall\'abitato', 'incidenti-stradali');
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Tipo Strada', 'incidenti-stradali'); ?></th>
                            <td>
                                <?php 
                                $tipo_strada = get_post_meta(get_the_ID(), 'tipo_strada', true);
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
                                echo isset($tipi_strada[$tipo_strada]) ? esc_html($tipi_strada[$tipo_strada]) : '';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Denominazione Strada', 'incidenti-stradali'); ?></th>
                            <td><?php echo esc_html(get_post_meta(get_the_ID(), 'denominazione_strada', true)); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Numero Civico', 'incidenti-stradali'); ?></th>
                            <td><?php echo esc_html(get_post_meta(get_the_ID(), 'numero_civico_incidente', true)); ?></td>
                        </tr>
                        <?php 
                        $progressiva_km = get_post_meta(get_the_ID(), 'progressiva_km', true);
                        $progressiva_m = get_post_meta(get_the_ID(), 'progressiva_m', true);
                        if ($progressiva_km || $progressiva_m) {
                            ?>
                            <tr>
                                <th><?php _e('Progressiva Chilometrica', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    if ($progressiva_km) echo __('Km', 'incidenti-stradali') . ' ' . esc_html($progressiva_km);
                                    if ($progressiva_km && $progressiva_m) echo ' + ';
                                    if ($progressiva_m) echo esc_html($progressiva_m) . ' ' . __('m', 'incidenti-stradali');
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <th><?php _e('Coordinate', 'incidenti-stradali'); ?></th>
                            <td>
                                <?php 
                                if ($latitudine && $longitudine) {
                                    echo sprintf(__('Lat: %s, Lng: %s', 'incidenti-stradali'), 
                                        number_format((float)$latitudine, 6), 
                                        number_format((float)$longitudine, 6)
                                    );
                                } else {
                                    echo __('Non disponibili', 'incidenti-stradali');
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                    
                    <div class="incidente-section">
                        <h3><?php _e('Caratteristiche dell\'Incidente', 'incidenti-stradali'); ?></h3>
                        <table class="incidente-table">
                            <tr>
                                <th><?php _e('Natura Incidente', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $natura_incidente = get_post_meta(get_the_ID(), 'natura_incidente', true);
                                    $dettaglio_natura = get_post_meta(get_the_ID(), 'dettaglio_natura', true);
                                    
                                    $nature = array(
                                        'A' => __('Tra veicoli in marcia', 'incidenti-stradali'),
                                        'B' => __('Tra veicolo e pedoni', 'incidenti-stradali'),
                                        'C' => __('Veicolo in marcia che urta veicolo fermo o altro', 'incidenti-stradali'),
                                        'D' => __('Veicolo in marcia senza urto', 'incidenti-stradali')
                                    );
                                    
                                    $dettagli = array(
                                        '1' => __('Scontro frontale', 'incidenti-stradali'),
                                        '2' => __('Scontro frontale-laterale', 'incidenti-stradali'),
                                        '3' => __('Scontro laterale', 'incidenti-stradali'),
                                        '4' => __('Tamponamento', 'incidenti-stradali'),
                                        '5' => __('Investimento di pedoni', 'incidenti-stradali'),
                                        '6' => __('Urto con veicolo in fermata o in arresto', 'incidenti-stradali'),
                                        '7' => __('Urto con veicolo in sosta', 'incidenti-stradali'),
                                        '8' => __('Urto con ostacolo', 'incidenti-stradali'),
                                        '9' => __('Urto con treno', 'incidenti-stradali'),
                                        '10' => __('Fuoriuscita (sbandamento, ...)', 'incidenti-stradali'),
                                        '11' => __('Infortunio per frenata improvvisa', 'incidenti-stradali'),
                                        '12' => __('Infortunio per caduta da veicolo', 'incidenti-stradali')
                                    );
                                    
                                    if (isset($nature[$natura_incidente])) {
                                        echo esc_html($nature[$natura_incidente]);
                                        if (isset($dettagli[$dettaglio_natura])) {
                                            echo ' - ' . esc_html($dettagli[$dettaglio_natura]);
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Condizioni Meteo', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $condizioni_meteo = get_post_meta(get_the_ID(), 'condizioni_meteo', true);
                                    $meteo = array(
                                        '1' => __('Sereno', 'incidenti-stradali'),
                                        '2' => __('Nebbia', 'incidenti-stradali'),
                                        '3' => __('Pioggia', 'incidenti-stradali'),
                                        '4' => __('Grandine', 'incidenti-stradali'),
                                        '5' => __('Neve', 'incidenti-stradali'),
                                        '6' => __('Vento forte', 'incidenti-stradali'),
                                        '7' => __('Altro', 'incidenti-stradali')
                                    );
                                    echo isset($meteo[$condizioni_meteo]) ? esc_html($meteo[$condizioni_meteo]) : '';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Fondo Stradale', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $fondo_strada = get_post_meta(get_the_ID(), 'stato_fondo_strada', true);
                                    $fondi = array(
                                        '1' => __('Asciutto', 'incidenti-stradali'),
                                        '2' => __('Bagnato', 'incidenti-stradali'),
                                        '3' => __('Sdrucciolevole', 'incidenti-stradali'),
                                        '4' => __('Ghiacciato', 'incidenti-stradali'),
                                        '5' => __('Innevato', 'incidenti-stradali')
                                    );
                                    echo isset($fondi[$fondo_strada]) ? esc_html($fondi[$fondo_strada]) : '';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Segnaletica', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $segnaletica = get_post_meta(get_the_ID(), 'segnaletica_strada', true);
                                    $segnaletica_tipi = array(
                                        '1' => __('Assente', 'incidenti-stradali'),
                                        '2' => __('Verticale', 'incidenti-stradali'),
                                        '3' => __('Orizzontale', 'incidenti-stradali'),
                                        '4' => __('Verticale e orizzontale', 'incidenti-stradali'),
                                        '5' => __('Temporanea di cantiere', 'incidenti-stradali')
                                    );
                                    echo isset($segnaletica_tipi[$segnaletica]) ? esc_html($segnaletica_tipi[$segnaletica]) : '';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php
                    // Veicoli coinvolti
                    $numero_veicoli = (int) get_post_meta(get_the_ID(), 'numero_veicoli_coinvolti', true) ?: 0;
                    if ($numero_veicoli > 0) {
                        ?>
                        <div class="incidente-section">
                            <h3><?php _e('Veicoli Coinvolti', 'incidenti-stradali'); ?></h3>
                            
                            <?php for ($i = 1; $i <= $numero_veicoli; $i++): 
                                $prefix = 'veicolo_' . $i . '_';
                                $tipo_veicolo = get_post_meta(get_the_ID(), $prefix . 'tipo', true);
                                
                                $tipi_veicolo = array(
                                    '1' => __('Autovettura privata', 'incidenti-stradali'),
                                    '2' => __('Autovettura con rimorchio', 'incidenti-stradali'),
                                    '3' => __('Autovettura pubblica', 'incidenti-stradali'),
                                    '4' => __('Autovettura di soccorso o di polizia', 'incidenti-stradali'),
                                    '8' => __('Autocarro', 'incidenti-stradali'),
                                    '14' => __('Velocipede', 'incidenti-stradali'),
                                    '15' => __('Ciclomotore', 'incidenti-stradali'),
                                    '16' => __('Motociclo a solo', 'incidenti-stradali'),
                                    '17' => __('Motociclo con passeggero', 'incidenti-stradali'),
                                    '21' => __('Quadriciclo', 'incidenti-stradali'),
                                    '22' => __('Monopattino elettrico', 'incidenti-stradali'),
                                    '23' => __('Bicicletta elettrica', 'incidenti-stradali')
                                );
                                
                                // Conducente info
                                $conducente_prefix = 'conducente_' . $i . '_';
                                $conducente_eta = get_post_meta(get_the_ID(), $conducente_prefix . 'eta', true);
                                $conducente_sesso = get_post_meta(get_the_ID(), $conducente_prefix . 'sesso', true);
                                $conducente_esito = get_post_meta(get_the_ID(), $conducente_prefix . 'esito', true);
                                
                                $sessi = array(
                                    '1' => __('Maschio', 'incidenti-stradali'),
                                    '2' => __('Femmina', 'incidenti-stradali')
                                );
                                
                                $esiti = array(
                                    '1' => __('Incolume', 'incidenti-stradali'),
                                    '2' => __('Ferito', 'incidenti-stradali'),
                                    '3' => __('Morto entro 24 ore', 'incidenti-stradali'),
                                    '4' => __('Morto dal 2° al 30° giorno', 'incidenti-stradali')
                                );
                            ?>
                                <div class="veicolo-box">
                                    <h4><?php printf(__('Veicolo %s', 'incidenti-stradali'), chr(64 + $i)); ?></h4>
                                    <table class="incidente-table">
                                        <tr>
                                            <th><?php _e('Tipo Veicolo', 'incidenti-stradali'); ?></th>
                                            <td><?php echo isset($tipi_veicolo[$tipo_veicolo]) ? esc_html($tipi_veicolo[$tipo_veicolo]) : ''; ?></td>
                                        </tr>
                                        <?php if ($conducente_eta || $conducente_sesso || $conducente_esito): ?>
                                            <tr>
                                                <th><?php _e('Conducente', 'incidenti-stradali'); ?></th>
                                                <td>
                                                    <?php 
                                                    $conducente_info = array();
                                                    
                                                    if ($conducente_eta) {
                                                        $conducente_info[] = sprintf(__('Età: %s', 'incidenti-stradali'), $conducente_eta);
                                                    }
                                                    
                                                    if ($conducente_sesso && isset($sessi[$conducente_sesso])) {
                                                        $conducente_info[] = $sessi[$conducente_sesso];
                                                    }
                                                    
                                                    if ($conducente_esito && isset($esiti[$conducente_esito])) {
                                                        $esito_class = '';
                                                        if ($conducente_esito == '3' || $conducente_esito == '4') {
                                                            $esito_class = 'esito-morto';
                                                        } elseif ($conducente_esito == '2') {
                                                            $esito_class = 'esito-ferito';
                                                        }
                                                        
                                                        $conducente_info[] = '<span class="' . $esito_class . '">' . $esiti[$conducente_esito] . '</span>';
                                                    }
                                                    
                                                    echo implode(' - ', $conducente_info);
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php 
                                        // Passeggeri del veicolo
                                        $num_passeggeri = get_post_meta(get_the_ID(), $prefix . 'numero_passeggeri', true) ?: 0;
                                        if ($num_passeggeri > 0): 
                                        ?>
                                            <tr>
                                                <th><?php _e('Passeggeri', 'incidenti-stradali'); ?></th>
                                                <td>
                                                    <?php 
                                                    echo sprintf(__('Numero passeggeri: %d', 'incidenti-stradali'), $num_passeggeri);
                                                    
                                                    // Mostra info sui passeggeri
                                                    for ($j = 1; $j <= $num_passeggeri; $j++) {
                                                        $pass_prefix = 'passeggero_' . $i . '_' . $j . '_';
                                                        $pass_eta = get_post_meta(get_the_ID(), $pass_prefix . 'eta', true);
                                                        $pass_sesso = get_post_meta(get_the_ID(), $pass_prefix . 'sesso', true);
                                                        $pass_esito = get_post_meta(get_the_ID(), $pass_prefix . 'esito', true);
                                                        
                                                        if ($pass_eta || $pass_sesso || $pass_esito) {
                                                            echo '<br><strong>' . sprintf(__('Passeggero %d:', 'incidenti-stradali'), $j) . '</strong> ';
                                                            
                                                            $pass_info = array();
                                                            if ($pass_eta) $pass_info[] = sprintf(__('Età: %s', 'incidenti-stradali'), $pass_eta);
                                                            if ($pass_sesso && isset($sessi[$pass_sesso])) $pass_info[] = $sessi[$pass_sesso];
                                                            if ($pass_esito && isset($esiti[$pass_esito])) {
                                                                $esito_class = '';
                                                                if ($pass_esito == '3' || $pass_esito == '4') {
                                                                    $esito_class = 'esito-morto';
                                                                } elseif ($pass_esito == '2') {
                                                                    $esito_class = 'esito-ferito';
                                                                }
                                                                $pass_info[] = '<span class="' . $esito_class . '">' . $esiti[$pass_esito] . '</span>';
                                                            }
                                                            echo implode(' - ', $pass_info);
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <?php
                    }
                    
                    // Pedoni coinvolti
                    $numero_pedoni = (int) get_post_meta(get_the_ID(), 'numero_pedoni_coinvolti', true) ?: 0;
                    if ($numero_pedoni > 0) {
                        ?>
                        <div class="incidente-section">
                            <h3><?php _e('Pedoni Coinvolti', 'incidenti-stradali'); ?></h3>
                            
                            <div class="pedoni-container">
                                <?php for ($i = 1; $i <= $numero_pedoni; $i++): 
                                    $prefix = 'pedone_' . $i . '_';
                                    $eta = get_post_meta(get_the_ID(), $prefix . 'eta', true);
                                    $sesso = get_post_meta(get_the_ID(), $prefix . 'sesso', true);
                                    $esito = get_post_meta(get_the_ID(), $prefix . 'esito', true);
                                    
                                    $sessi = array(
                                        '1' => __('Maschio', 'incidenti-stradali'),
                                        '2' => __('Femmina', 'incidenti-stradali')
                                    );
                                    
                                    $esiti = array(
                                        '2' => __('Ferito', 'incidenti-stradali'),
                                        '3' => __('Morto entro 24 ore', 'incidenti-stradali'),
                                        '4' => __('Morto dal 2° al 30° giorno', 'incidenti-stradali')
                                    );
                                    
                                    $esito_class = '';
                                    if ($esito == '3' || $esito == '4') {
                                        $esito_class = 'esito-morto';
                                    } elseif ($esito == '2') {
                                        $esito_class = 'esito-ferito';
                                    }
                                ?>
                                    <div class="pedone-box">
                                        <h4><?php printf(__('Pedone %d', 'incidenti-stradali'), $i); ?></h4>
                                        <p>
                                            <?php 
                                            $pedone_info = array();
                                            
                                            if ($eta) {
                                                $pedone_info[] = sprintf(__('Età: %s', 'incidenti-stradali'), $eta);
                                            }
                                            
                                            if ($sesso && isset($sessi[$sesso])) {
                                                $pedone_info[] = $sessi[$sesso];
                                            }
                                            
                                            if ($esito && isset($esiti[$esito])) {
                                                $pedone_info[] = '<span class="' . $esito_class . '">' . $esiti[$esito] . '</span>';
                                            }
                                            
                                            echo implode(' - ', $pedone_info);
                                            ?>
                                        </p>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="incidente-section">
                        <h3><?php _e('Dati Tecnici ISTAT', 'incidenti-stradali'); ?></h3>
                        <table class="incidente-table">
                            <tr>
                                <th><?php _e('Codice Incidente', 'incidenti-stradali'); ?></th>
                                <td><?php echo esc_html(get_post_meta(get_the_ID(), 'codice_incidente', true)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Protocollo', 'incidenti-stradali'); ?></th>
                                <td><?php echo esc_html(get_post_meta(get_the_ID(), 'protocollo_incidente', true)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Anno di Riferimento', 'incidenti-stradali'); ?></th>
                                <td><?php echo esc_html(get_post_meta(get_the_ID(), 'anno_riferimento', true)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Mostra in Mappa', 'incidenti-stradali'); ?></th>
                                <td>
                                    <?php 
                                    $mostra_mappa = get_post_meta(get_the_ID(), 'mostra_in_mappa', true);
                                    echo $mostra_mappa ? __('Sì', 'incidenti-stradali') : __('No', 'incidenti-stradali');
                                    ?>
                                </td>
                            </tr>
                            <?php 
                            $note = get_post_meta(get_the_ID(), 'note_incidente', true);
                            if ($note): 
                            ?>
                            <tr>
                                <th><?php _e('Note', 'incidenti-stradali'); ?></th>
                                <td><?php echo wp_kses_post(wpautop($note)); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="incidente-section incidente-summary">
                        <h3><?php _e('Riepilogo Vittime', 'incidenti-stradali'); ?></h3>
                        <div class="vittime-summary">
                            <div class="vittime-box morti">
                                <span class="vittime-count"><?php echo $morti; ?></span>
                                <span class="vittime-label"><?php _e('Morti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="vittime-box feriti">
                                <span class="vittime-count"><?php echo $feriti; ?></span>
                                <span class="vittime-label"><?php _e('Feriti', 'incidenti-stradali'); ?></span>
                            </div>
                            <div class="vittime-box veicoli">
                                <span class="vittime-count"><?php echo $numero_veicoli; ?></span>
                                <span class="vittime-label"><?php _e('Veicoli', 'incidenti-stradali'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <footer class="entry-footer">
                <div class="incidente-footer-meta">
                    <span class="incidente-id">
                        <?php printf(__('ID Incidente: %s', 'incidenti-stradali'), get_the_ID()); ?>
                    </span>
                    
                    <?php if (current_user_can('edit_post', get_the_ID())): ?>
                        <a href="<?php echo get_edit_post_link(); ?>" class="incidente-edit-link">
                            <?php _e('Modifica', 'incidenti-stradali'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </footer>
        </article>
    </main>
</div>

<style>
/* Styling for the single incident template */
.incidente-stradale-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.incidente-meta {
    margin-bottom: 20px;
    color: #666;
}

.incidente-data, .incidente-ora {
    display: inline-block;
    margin-right: 15px;
}

.incidente-section {
    margin-bottom: 30px;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
}

.incidente-section h3 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.incidente-table {
    width: 100%;
    border-collapse: collapse;
}

.incidente-table th, .incidente-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.incidente-table th {
    width: 30%;
    font-weight: bold;
}

.veicolo-box, .pedone-box {
    margin-bottom: 15px;
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.veicolo-box h4, .pedone-box h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.pedoni-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.pedone-box {
    flex: 1;
    min-width: 200px;
}

.esito-morto {
    color: #d63384;
    font-weight: bold;
}

.esito-ferito {
    color: #fd7e14;
    font-weight: bold;
}

.vittime-summary {
    display: flex;
    justify-content: space-between;
    text-align: center;
}

.vittime-box {
    flex: 1;
    padding: 15px;
    border-radius: 4px;
    background: #f1f1f1;
}

.vittime-box.morti {
    background: #ffebf1;
}

.vittime-box.feriti {
    background: #fff3e6;
}

.vittime-count {
    display: block;
    font-size: 2em;
    font-weight: bold;
}

.vittime-label {
    display: block;
    margin-top: 5px;
    color: #666;
}

.incidente-footer-meta {
    margin-top: 30px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

.incidente-id {
    color: #999;
    font-size: 0.9em;
}

.incidente-edit-link {
    display: inline-block;
    padding: 5px 10px;
    background: #0073aa;
    color: #fff;
    text-decoration: none;
    border-radius: 3px;
}

.incidente-edit-link:hover {
    background: #005177;
    color: #fff;
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

@media (max-width: 768px) {
    .vittime-summary {
        flex-direction: column;
        gap: 10px;
    }
    
    .incidente-table th {
        width: 40%;
    }
}
</style>

<?php
get_footer();
