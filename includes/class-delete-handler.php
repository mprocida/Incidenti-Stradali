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
     * Gestisce le bulk actions di eliminazione
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (!in_array($doaction, ['trash', 'delete', 'untrash'])) {
            return $redirect_to;
        }
        
        $processed = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) !== 'incidente_stradale') {
                continue;
            }
            
            // Verifica permessi
            if (!$this->user_can_delete_incident($post_id)) {
                $errors[] = sprintf(__('Non hai i permessi per eliminare l\'incidente #%d', 'incidenti-stradali'), $post_id);
                continue;
            }
            
            // Verifica restrizioni data
            if (!$this->can_delete_by_date($post_id)) {
                $errors[] = sprintf(__('Impossibile eliminare l\'incidente #%d: data bloccata', 'incidenti-stradali'), $post_id);
                continue;
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
            }
            
            if ($success) {
                $processed++;
            } else {
                $errors[] = sprintf(__('Errore nell\'elaborazione dell\'incidente #%d', 'incidenti-stradali'), $post_id);
            }
        }
        
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