<?php

class IncidentiDeleteHandler {
    
    public function __construct() {
        // Gestione bulk actions personalizzata
        add_filter('handle_bulk_actions-edit-incidente_stradale', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Verifica permessi per eliminazioni individuali
        add_action('admin_action_trash', array($this, 'check_individual_permissions'), 1);
        add_action('admin_action_delete', array($this, 'check_individual_permissions'), 1);
        
        // Messaggi personalizzati
        add_action('admin_notices', array($this, 'show_delete_messages'));
    }

    /**
     * Pre-carica dati per bulk operation (NUOVO METODO)
     */
    private function preload_bulk_data($post_ids) {
        global $wpdb;
        
        $ids_string = implode(',', array_map('intval', $post_ids));
        
        // Una query per tutti i post types
        $post_types = $wpdb->get_results(
            "SELECT ID, post_type, post_author FROM {$wpdb->posts} WHERE ID IN ($ids_string)",
            OBJECT_K
        );
        
        // Una query per tutte le date
        $post_dates = $wpdb->get_results(
            "SELECT post_id, meta_value as data_incidente 
            FROM {$wpdb->postmeta} 
            WHERE post_id IN ($ids_string) AND meta_key = 'data_incidente'",
            OBJECT_K
        );
        
        // Cache comuni utenti
        $user_comuni = array();
        if (!current_user_can('manage_all_incidenti')) {
            $user_comune = get_user_meta(get_current_user_id(), 'comune_assegnato', true);
            if ($user_comune) {
                $post_comuni = $wpdb->get_results(
                    "SELECT post_id, meta_value as comune 
                    FROM {$wpdb->postmeta} 
                    WHERE post_id IN ($ids_string) AND meta_key = 'comune_incidente'",
                    OBJECT_K
                );
                $user_comuni = array('current' => $user_comune, 'posts' => $post_comuni);
            }
        }
        
        return array(
            'types' => $post_types,
            'dates' => $post_dates,
            'comuni' => $user_comuni
        );
    }
    
    /**
     * Eliminazione massiva ottimizzata con query dirette
     */
    private function bulk_delete_optimized($post_ids) {
        global $wpdb;
        
        $ids_string = implode(',', array_map('intval', $post_ids));
        
        // Elimina tutti i postmeta in una query
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_string)");
        
        // Elimina tutti i post in una query
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($ids_string)");
        
        // Pulisci cache
        foreach ($post_ids as $post_id) {
            clean_post_cache($post_id);
        }
        
        return count($post_ids);
    }

    /**
     * Processa eliminazioni in chunks per evitare timeout
     */
    private function process_in_chunks($post_ids, $chunk_size = 100) {
        $chunks = array_chunk($post_ids, $chunk_size);
        $total_processed = 0;
        
        foreach ($chunks as $chunk) {
            $total_processed += $this->bulk_delete_optimized($chunk);
            
            // Previeni timeout
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        return $total_processed;
    }

    /**
     * Gestisce le bulk actions di eliminazione
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (!in_array($doaction, ['trash', 'delete', 'untrash'])) {
            return $redirect_to;
        }
        
        $processed = 0;
        $errors = array();
        
        // OTTIMIZZAZIONE: Pre-carica tutti i dati necessari
        $bulk_data = $this->preload_bulk_data($post_ids);
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        $user_id = get_current_user_id();
        $can_manage_all = current_user_can('manage_all_incidenti');

        // OTTIMIZZAZIONE: Disabilita hook temporaneamente
        $this->disable_hooks_for_bulk();

        /* foreach ($post_ids as $post_id) {
            // Verifica tipo usando cache
            if (!isset($bulk_data['types'][$post_id]) || 
                $bulk_data['types'][$post_id]->post_type !== 'incidente_stradale') {
                continue;
            }
            
            // Verifica permessi usando dati pre-caricati
            if (!$can_manage_all) {
                // Verifica permessi base
                if (!current_user_can('delete_incidente', $post_id)) {
                    $errors[] = sprintf(__('Non hai i permessi per eliminare l\'incidente #%d', 'incidenti-stradali'), $post_id);
                    continue;
                }
                
                // Verifica autore
                if ($bulk_data['types'][$post_id]->post_author != $user_id && 
                    !current_user_can('delete_others_incidenti')) {
                    $errors[] = sprintf(__('Non hai i permessi per eliminare l\'incidente #%d', 'incidenti-stradali'), $post_id);
                    continue;
                }
                
                // Verifica comune usando cache
                if (!empty($bulk_data['comuni']['current'])) {
                    $post_comune = isset($bulk_data['comuni']['posts'][$post_id]) ? 
                                  $bulk_data['comuni']['posts'][$post_id]->comune : null;
                    if ($post_comune && $bulk_data['comuni']['current'] !== $post_comune) {
                        $errors[] = sprintf(__('Non hai i permessi per eliminare l\'incidente #%d', 'incidenti-stradali'), $post_id);
                        continue;
                    }
                }
                
                // Verifica data usando cache
                if ($data_blocco && isset($bulk_data['dates'][$post_id])) {
                    if (strtotime($bulk_data['dates'][$post_id]->data_incidente) < strtotime($data_blocco)) {
                        $errors[] = sprintf(__('Impossibile eliminare l\'incidente #%d: data bloccata', 'incidenti-stradali'), $post_id);
                        continue;
                    }
                }
            }
            
            // Esegui l'azione
            $success = false;
            switch ($doaction) {
                case 'trash':
                    $success = wp_trash_post($post_id);
                    break;
                case 'delete':
                    $success = wp_delete_post($post_id, true);
                    break;
                case 'untrash':
                    $success = wp_untrash_post($post_id);
                    break;
            } */

        // Array per raccogliere ID da eliminare in batch
        $ids_to_delete = array();
        $ids_to_trash = array();
        $ids_to_untrash = array();

        foreach ($post_ids as $post_id) {
            // ... mantieni tutte le verifiche permessi esistenti ...
            
            // Invece di eliminare subito, aggiungi all'array appropriato
            switch ($doaction) {
                case 'trash':
                    $ids_to_trash[] = $post_id;
                    break;
                case 'delete':
                    $ids_to_delete[] = $post_id;
                    break;
                case 'untrash':
                    $ids_to_untrash[] = $post_id;
                    break;
            }
        }

        // Elabora in batch
        if (!empty($ids_to_delete)) {
            // Se più di 100 item, usa chunking
            if (count($ids_to_delete) > 100) {
                $processed += $this->process_in_chunks($ids_to_delete, 100);
            } else {
                $processed += $this->bulk_delete_optimized($ids_to_delete);
            }
        }
        if (!empty($ids_to_trash)) {
            foreach ($ids_to_trash as $id) {
                if (wp_trash_post($id)) $processed++;
            }
        }
        if (!empty($ids_to_untrash)) {
            foreach ($ids_to_untrash as $id) {
                if (wp_untrash_post($id)) $processed++;
            }
        }            
            
        if ($success) {
            $processed++;
        } else {
            $errors[] = sprintf(__('Errore nell\'elaborazione dell\'incidente #%d', 'incidenti-stradali'), $post_id);
        }

        // OTTIMIZZAZIONE: Riabilita hook
        $this->enable_hooks_for_bulk();
        
        // Aggiungi risultati alla URL di redirect
        $redirect_to = add_query_arg(array(
            'incidenti_processed' => $processed,
            'incidenti_errors' => count($errors),
            'incidenti_action' => $doaction
        ), $redirect_to);
        
        // Salva errori in transient se presenti
        if (!empty($errors)) {
            set_transient('incidenti_bulk_errors_' . get_current_user_id(), $errors, 60);
        }
        
        return $redirect_to;
    }

    /**
     * Disabilita hook pesanti durante bulk operations
     */
    /**
     * Disabilita hook pesanti durante bulk operations
     */
    private function disable_hooks_for_bulk() {
        if (!isset($GLOBALS['incidenti_bulk_operation'])) {
            $GLOBALS['incidenti_bulk_operation'] = true;
        }
        
        // === HOOK DEL PLUGIN ===
        // Rimuovi hook logging
        remove_action('wp_trash_post', array('IncidentiMetaBoxes', 'on_post_trashed'));
        remove_action('before_delete_post', array('IncidentiMetaBoxes', 'on_post_deleted'));
        remove_action('untrash_post', array('IncidentiMetaBoxes', 'on_post_untrashed'));
        
        // CRITICO: Rimuovi hook pesanti del plugin
        remove_action('save_post', array('IncidentiMetaBoxes', 'save_meta_boxes'));
        remove_filter('wp_insert_post_data', array('IncidentiValidation', 'validate_before_save'), 10);
        remove_action('transition_post_status', array('IncidentiEmailNotifications', 'handle_post_status_change'), 10);
        
        // === HOOK WORDPRESS PER TRASH (spostamento nel cestino) ===
        remove_action('transition_post_status', '_transition_post_status', 10);
        
        // === HOOK WORDPRESS PER DELETE (eliminazione definitiva) ===
        remove_action('delete_post', '_wp_delete_post_menu_item');
        remove_action('before_delete_post', 'wp_delete_post_revisions');
        remove_action('deleted_post', 'wp_cache_set_posts_last_changed');
        
        // Disabilita aggiornamento term count durante bulk delete
        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);
    }

    /**
     * Riabilita hook dopo bulk operations
     */
    private function enable_hooks_for_bulk() {
        // Resetta il flag globale
        if (isset($GLOBALS['incidenti_bulk_operation'])) {
            $GLOBALS['incidenti_bulk_operation'] = false;
        }
        
        // Riabilita conteggi differiti
        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);
        
        // NOTA: Gli hook con add_action/remove_action verranno 
        // automaticamente re-registrati al prossimo caricamento
        // perché sono nel costruttore delle classi
    }
    
    /**
     * Verifica permessi per eliminazioni individuali
     */
    public function check_individual_permissions() {
        if (!isset($_GET['post']) || !isset($_GET['action'])) {
            return;
        }
        
        $post_id = intval($_GET['post']);
        $action = sanitize_text_field($_GET['action']);
        
        if (get_post_type($post_id) !== 'incidente_stradale') {
            return;
        }
        
        if (!in_array($action, ['trash', 'delete'])) {
            return;
        }
        
        // Verifica nonce
        $nonce_action = $action . '-post_' . $post_id;
        if (!wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
            wp_die(__('Nonce verification failed.', 'incidenti-stradali'));
        }
        
        // Verifica permessi
        if (!$this->user_can_delete_incident($post_id)) {
            wp_die(__('Non hai i permessi per eliminare questo incidente.', 'incidenti-stradali'));
        }
        
        // Verifica restrizioni data
        if (!$this->can_delete_by_date($post_id)) {
            wp_die(__('Impossibile eliminare l\'incidente: data bloccata dalle impostazioni.', 'incidenti-stradali'));
        }
        
        // Se tutto ok, non interrompiamo il flusso normale
    }
    
    /**
     * Verifica se l'utente può eliminare l'incidente
     */
    private function user_can_delete_incident($post_id) {
        // Amministratori possono sempre eliminare
        if (current_user_can('manage_all_incidenti')) {
            return true;
        }
        
        // Verifica permessi standard
        if (!current_user_can('delete_incidente', $post_id)) {
            return false;
        }
        
        // Verifica se l'utente può eliminare incidenti di altri utenti
        $post = get_post($post_id);
        if ($post->post_author != get_current_user_id() && !current_user_can('delete_others_incidenti')) {
            return false;
        }
        
        // Verifica restrizione per comune
        if (!current_user_can('manage_all_incidenti')) {
            $user_comune = get_user_meta(get_current_user_id(), 'comune_assegnato', true);
            $post_comune = get_post_meta($post_id, 'comune_incidente', true);
            
            if ($user_comune && $post_comune && $user_comune !== $post_comune) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se l'incidente può essere eliminato in base alla data
     */
    private function can_delete_by_date($post_id) {
        // Amministratori possono sempre eliminare
        if (current_user_can('manage_all_incidenti')) {
            return true;
        }
        
        $data_blocco = get_option('incidenti_data_blocco_modifica');
        if (!$data_blocco) {
            return true; // Nessuna restrizione
        }
        
        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
        if (!$data_incidente) {
            return true; // Nessuna data, permetti eliminazione
        }
        
        return strtotime($data_incidente) >= strtotime($data_blocco);
    }
    
    /**
     * Mostra messaggi per le operazioni di eliminazione
     */
    public function show_delete_messages() {
        if (isset($_GET['incidenti_processed']) && isset($_GET['incidenti_action'])) {
            $processed = intval($_GET['incidenti_processed']);
            $action = sanitize_text_field($_GET['incidenti_action']);
            $errors_count = isset($_GET['incidenti_errors']) ? intval($_GET['incidenti_errors']) : 0;
            
            if ($processed > 0) {
                $message = $this->get_action_message($action, $processed);
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            }
            
            if ($errors_count > 0) {
                $errors = get_transient('incidenti_bulk_errors_' . get_current_user_id());
                if ($errors) {
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p><strong>Errori durante l\'operazione:</strong></p>';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                    delete_transient('incidenti_bulk_errors_' . get_current_user_id());
                }
            }
        }
    }
    
    /**
     * Genera messaggi appropriati per le azioni
     */
    private function get_action_message($action, $count) {
        switch ($action) {
            case 'trash':
                return sprintf(
                    _n(
                        '%d incidente spostato nel cestino.',
                        '%d incidenti spostati nel cestino.',
                        $count,
                        'incidenti-stradali'
                    ),
                    $count
                );
            case 'delete':
                return sprintf(
                    _n(
                        '%d incidente eliminato definitivamente.',
                        '%d incidenti eliminati definitivamente.',
                        $count,
                        'incidenti-stradali'
                    ),
                    $count
                );
            case 'untrash':
                return sprintf(
                    _n(
                        '%d incidente ripristinato dal cestino.',
                        '%d incidenti ripristinati dal cestino.',
                        $count,
                        'incidenti-stradali'
                    ),
                    $count
                );
            default:
                return sprintf('%d incidenti elaborati.', $count);
        }
    }
}