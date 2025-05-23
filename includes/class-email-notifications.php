<?php
/**
 * Email Notifications System for Incidenti Stradali
 */

class IncidentiEmailNotifications {
    
    public function __construct() {
        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
        add_action('incidenti_after_save', array($this, 'send_new_incident_notification'), 10, 2);
        add_action('incidenti_after_export', array($this, 'send_export_notification'), 10, 4);
        add_action('incidenti_data_integrity_alert', array($this, 'send_integrity_alert'));
        add_action('incidenti_auto_export', array($this, 'handle_auto_export'));
        add_filter('wp_mail_from', array($this, 'custom_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'custom_mail_from_name'));
    }
    
    /**
     * Handle post status changes
     */
    public function handle_post_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'incidente_stradale') {
            return;
        }
        
        // Notify when incident is published
        if ($new_status === 'publish' && $old_status !== 'publish') {
            $this->send_incident_published_notification($post);
        }
        
        // Notify when incident is updated
        if ($new_status === 'publish' && $old_status === 'publish') {
            $this->send_incident_updated_notification($post);
        }
        
        // Notify when incident involves casualties
        if ($new_status === 'publish') {
            $this->check_and_notify_casualties($post);
        }
    }
    
    /**
     * Send notification for new incident
     */
    public function send_new_incident_notification($post_id, $post_data) {
        if (!get_option('incidenti_notify_new_incident', false)) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
        $ora_incidente = get_post_meta($post_id, 'ora_incidente', true);
        $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
        $comune_incidente = get_post_meta($post_id, 'comune_incidente', true);
        
        // Count casualties
        $morti = $this->count_casualties($post_id, 'morti');
        $feriti = $this->count_casualties($post_id, 'feriti');
        
        $subject = sprintf(
            __('[%s] Nuovo Incidente Stradale Registrato', 'incidenti-stradali'),
            get_bloginfo('name')
        );
        
        $message = $this->build_incident_email_template([
            'post_id' => $post_id,
            'data' => $data_incidente,
            'ora' => $ora_incidente,
            'strada' => $denominazione_strada,
            'comune' => $comune_incidente,
            'morti' => $morti,
            'feriti' => $feriti,
            'edit_link' => get_edit_post_link($post_id),
            'template' => 'new_incident'
        ]);
        
        $this->send_notification_emails($subject, $message);
        
        // Send priority alert for fatal accidents
        if ($morti > 0) {
            $this->send_priority_alert($post_id, $morti);
        }
    }
    
    /**
     * Send incident published notification
     */
    private function send_incident_published_notification($post) {
        $author = get_user_by('ID', $post->post_author);
        $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
        
        $subject = sprintf(
            __('[%s] Incidente Pubblicato - %s', 'incidenti-stradali'),
            get_bloginfo('name'),
            date('d/m/Y', strtotime($data_incidente))
        );
        
        $message = sprintf(
            __("L'incidente del %s è stato pubblicato da %s.\n\nVedi dettagli: %s", 'incidenti-stradali'),
            date('d/m/Y', strtotime($data_incidente)),
            $author->display_name,
            get_edit_post_link($post->ID)
        );
        
        $this->send_admin_notification($subject, $message);
    }
    
    /**
     * Send incident updated notification
     */
    private function send_incident_updated_notification($post) {
        // Only send if significant changes were made
        if (!$this->has_significant_changes($post->ID)) {
            return;
        }
        
        $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
        
        $subject = sprintf(
            __('[%s] Incidente Aggiornato - %s', 'incidenti-stradali'),
            get_bloginfo('name'),
            date('d/m/Y', strtotime($data_incidente))
        );
        
        $message = sprintf(
            __("L'incidente del %s è stato aggiornato.\n\nVedi modifiche: %s", 'incidenti-stradali'),
            date('d/m/Y', strtotime($data_incidente)),
            get_edit_post_link($post->ID)
        );
        
        $this->send_admin_notification($subject, $message);
    }
    
    /**
     * Check and notify for casualties
     */
    private function check_and_notify_casualties($post) {
        $morti = $this->count_casualties($post->ID, 'morti');
        $feriti = $this->count_casualties($post->ID, 'feriti');
        
        if ($morti > 0 || $feriti > 2) { // Alert for deaths or multiple injuries
            $this->send_casualty_alert($post, $morti, $feriti);
        }
    }
    
    /**
     * Send priority alert for fatal accidents
     */
    private function send_priority_alert($post_id, $morti) {
        $data_incidente = get_post_meta($post_id, 'data_incidente', true);
        $denominazione_strada = get_post_meta($post_id, 'denominazione_strada', true);
        
        $subject = sprintf(
            __('[URGENTE] Incidente Mortale - %d vittime', 'incidenti-stradali'),
            $morti
        );
        
        $message = $this->build_incident_email_template([
            'post_id' => $post_id,
            'data' => $data_incidente,
            'strada' => $denominazione_strada,
            'morti' => $morti,
            'edit_link' => get_edit_post_link($post_id),
            'template' => 'priority_alert'
        ]);
        
        // Send to priority recipients + standard list
        $priority_emails = get_option('incidenti_priority_notification_emails', '');
        $standard_emails = get_option('incidenti_notification_emails', '');
        
        $all_emails = array_unique(array_merge(
            $this->parse_email_list($priority_emails),
            $this->parse_email_list($standard_emails)
        ));
        
        foreach ($all_emails as $email) {
            wp_mail($email, $subject, $message, $this->get_email_headers('priority'));
        }
    }
    
    /**
     * Send casualty alert
     */
    private function send_casualty_alert($post, $morti, $feriti) {
        $data_incidente = get_post_meta($post->ID, 'data_incidente', true);
        $denominazione_strada = get_post_meta($post->ID, 'denominazione_strada', true);
        
        $subject = sprintf(
            __('[ATTENZIONE] Incidente con vittime - %s', 'incidenti-stradali'),
            date('d/m/Y', strtotime($data_incidente))
        );
        
        $casualty_text = [];
        if ($morti > 0) $casualty_text[] = sprintf(__('%d morti', 'incidenti-stradali'), $morti);
        if ($feriti > 0) $casualty_text[] = sprintf(__('%d feriti', 'incidenti-stradali'), $feriti);
        
        $message = sprintf(
            __("Incidente con vittime registrato:\n\nData: %s\nLuogo: %s\nVittime: %s\n\nDettagli: %s", 'incidenti-stradali'),
            date('d/m/Y', strtotime($data_incidente)),
            $denominazione_strada ?: __('Non specificato', 'incidenti-stradali'),
            implode(', ', $casualty_text),
            get_edit_post_link($post->ID)
        );
        
        $this->send_notification_emails($subject, $message, 'alert');
    }
    
    /**
     * Send export completion notification
     */
    public function send_export_notification($export_type, $file_path, $record_count, $user_id) {
        if (!get_option('incidenti_notify_export_completion', true)) {
            return;
        }
        
        $user = get_user_by('ID', $user_id);
        $export_name = ($export_type === 'ISTAT_TXT') ? 'ISTAT (TXT)' : 'Excel (CSV)';
        
        $subject = sprintf(
            __('[%s] Esportazione Completata - %s', 'incidenti-stradali'),
            get_bloginfo('name'),
            $export_name
        );
        
        $message = sprintf(
            __("Esportazione completata con successo.\n\nTipo: %s\nUtente: %s\nRecord esportati: %d\nFile: %s\nData: %s", 'incidenti-stradali'),
            $export_name,
            $user->display_name,
            $record_count,
            basename($file_path),
            current_time('d/m/Y H:i')
        );
        
        // Send to user who performed export
        wp_mail($user->user_email, $subject, $message, $this->get_email_headers());
        
        // Send to admins if it's a large export
        if ($record_count > 100) {
            $this->send_admin_notification($subject, $message);
        }
    }
    
    /**
     * Send data integrity alert
     */
    public function send_integrity_alert($issues) {
        if (empty($issues)) {
            return;
        }
        
        $subject = sprintf(
            __('[%s] Problemi Integrità Dati Rilevati', 'incidenti-stradali'),
            get_bloginfo('name')
        );
        
        $message = __("Sono stati rilevati i seguenti problemi nei dati degli incidenti:\n\n", 'incidenti-stradali');
        
        foreach ($issues as $issue) {
            $message .= "• " . $issue . "\n";
        }
        
        $message .= sprintf(
            __("\nÈ consigliabile verificare e correggere questi problemi.\n\nPannello Amministrazione: %s", 'incidenti-stradali'),
            admin_url('edit.php?post_type=incidente_stradale&page=incidenti-settings')
        );
        
        $this->send_admin_notification($subject, $message);
    }
    
    /**
     * Handle auto export notifications
     */
    public function handle_auto_export() {
        if (!get_option('incidenti_auto_export_enabled', false)) {
            return;
        }
        
        // Perform export
        $export_result = $this->perform_auto_export();
        
        if ($export_result['success']) {
            $subject = sprintf(
                __('[%s] Esportazione Automatica Completata', 'incidenti-stradali'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __("Esportazione automatica completata:\n\nRecord esportati: %d\nFile generato: %s\nData: %s", 'incidenti-stradali'),
                $export_result['count'],
                $export_result['filename'],
                current_time('d/m/Y H:i')
            );
        } else {
            $subject = sprintf(
                __('[%s] Errore Esportazione Automatica', 'incidenti-stradali'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __("Errore durante l'esportazione automatica:\n\n%s\n\nData: %s", 'incidenti-stradali'),
                $export_result['error'],
                current_time('d/m/Y H:i')
            );
        }
        
        $email = get_option('incidenti_auto_export_email', get_option('admin_email'));
        wp_mail($email, $subject, $message, $this->get_email_headers());
    }
    
    /**
     * Build incident email template
     */
    private function build_incident_email_template($data) {
        $template = $data['template'] ?? 'default';
        
        switch ($template) {
            case 'new_incident':
                return $this->get_new_incident_template($data);
            case 'priority_alert':
                return $this->get_priority_alert_template($data);
            default:
                return $this->get_default_template($data);
        }
    }
    
    /**
     * New incident email template
     */
    private function get_new_incident_template($data) {
        $html = "<html><body style='font-family: Arial, sans-serif;'>";
        $html .= "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
        $html .= "<h2 style='color: #0073aa; margin-top: 0;'>Nuovo Incidente Stradale Registrato</h2>";
        
        $html .= "<div style='background: white; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
        $html .= "<h3>Dettagli Incidente</h3>";
        $html .= "<p><strong>Data:</strong> " . date('d/m/Y', strtotime($data['data'])) . "</p>";
        $html .= "<p><strong>Ora:</strong> " . $data['ora'] . ":00</p>";
        
        if ($data['strada']) {
            $html .= "<p><strong>Luogo:</strong> " . esc_html($data['strada']) . "</p>";
        }
        
        if ($data['comune']) {
            $html .= "<p><strong>Comune:</strong> " . esc_html($data['comune']) . "</p>";
        }
        
        if ($data['morti'] > 0 || $data['feriti'] > 0) {
            $html .= "<div style='background: #fff5f5; border-left: 4px solid #d63384; padding: 10px; margin: 10px 0;'>";
            $html .= "<h4 style='color: #d63384; margin-top: 0;'>Vittime</h4>";
            if ($data['morti'] > 0) {
                $html .= "<p style='color: #d63384;'><strong>Morti: " . $data['morti'] . "</strong></p>";
            }
            if ($data['feriti'] > 0) {
                $html .= "<p style='color: #fd7e14;'><strong>Feriti: " . $data['feriti'] . "</strong></p>";
            }
            $html .= "</div>";
        }
        
        $html .= "</div>";
        
        $html .= "<p><a href='" . $data['edit_link'] . "' style='background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Visualizza Dettagli</a></p>";
        
        $html .= "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
        $html .= "<p style='font-size: 12px; color: #666;'>Questa è una notifica automatica del sistema di gestione incidenti stradali.</p>";
        $html .= "</div></body></html>";
        
        return $html;
    }
    
    /**
     * Priority alert email template
     */
    private function get_priority_alert_template($data) {
        $html = "<html><body style='font-family: Arial, sans-serif;'>";
        $html .= "<div style='background: #fff5f5; border: 2px solid #d63384; padding: 20px; border-radius: 8px;'>";
        $html .= "<h2 style='color: #d63384; margin-top: 0;'>⚠️ ALLERTA PRIORITARIA - INCIDENTE MORTALE</h2>";
        
        $html .= "<div style='background: white; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #d63384;'>";
        $html .= "<h3>Incidente con " . $data['morti'] . " vittime</h3>";
        $html .= "<p><strong>Data:</strong> " . date('d/m/Y', strtotime($data['data'])) . "</p>";
        
        if ($data['strada']) {
            $html .= "<p><strong>Luogo:</strong> " . esc_html($data['strada']) . "</p>";
        }
        
        $html .= "<p style='color: #d63384; font-size: 16px;'><strong>Vittime: " . $data['morti'] . " morti</strong></p>";
        $html .= "</div>";
        
        $html .= "<p><a href='" . $data['edit_link'] . "' style='background: #d63384; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>VISUALIZZA IMMEDIATAMENTE</a></p>";
        
        $html .= "<p style='color: #d63384; font-weight: bold;'>Questa allerta richiede attenzione immediata.</p>";
        $html .= "</div></body></html>";
        
        return $html;
    }
    
    /**
     * Default email template
     */
    private function get_default_template($data) {
        $message = sprintf(
            __("Dettagli incidente:\n\nData: %s\nOra: %s\n", 'incidenti-stradali'),
            date('d/m/Y', strtotime($data['data'])),
            $data['ora'] . ':00'
        );
        
        if ($data['strada']) {
            $message .= sprintf(__("Luogo: %s\n", 'incidenti-stradali'), $data['strada']);
        }
        
        if ($data['morti'] > 0 || $data['feriti'] > 0) {
            $message .= "\n" . __("VITTIME:\n", 'incidenti-stradali');
            if ($data['morti'] > 0) {
                $message .= sprintf(__("Morti: %d\n", 'incidenti-stradali'), $data['morti']);
            }
            if ($data['feriti'] > 0) {
                $message .= sprintf(__("Feriti: %d\n", 'incidenti-stradali'), $data['feriti']);
            }
        }
        
        $message .= sprintf(__("\nVedi dettagli: %s", 'incidenti-stradali'), $data['edit_link']);
        
        return $message;
    }
    
    /**
     * Send notification to configured email list
     */
    private function send_notification_emails($subject, $message, $type = 'standard') {
        $email_list = get_option('incidenti_notification_emails', get_option('admin_email'));
        $emails = $this->parse_email_list($email_list);
        
        $headers = $this->get_email_headers($type);
        
        foreach ($emails as $email) {
            wp_mail($email, $subject, $message, $headers);
        }
    }
    
    /**
     * Send notification to admins only
     */
    private function send_admin_notification($subject, $message) {
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, $subject, $message, $this->get_email_headers());
    }
    
    /**
     * Parse email list from settings
     */
    private function parse_email_list($email_string) {
        if (empty($email_string)) {
            return array(get_option('admin_email'));
        }
        
        $emails = preg_split('/[,;\n\r]+/', $email_string);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, 'is_email');
        
        return array_unique($emails);
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers($type = 'standard') {
        $headers = array();
        
        if ($type === 'priority' || $type === 'alert') {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'X-Priority: 1';
            $headers[] = 'X-MSMail-Priority: High';
            $headers[] = 'Importance: High';
        } else {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        
        return $headers;
    }
    
    /**
     * Custom mail from address
     */
    public function custom_mail_from($email) {
        $custom_email = get_option('incidenti_mail_from_email');
        return $custom_email ?: $email;
    }
    
    /**
     * Custom mail from name
     */
    public function custom_mail_from_name($name) {
        $custom_name = get_option('incidenti_mail_from_name');
        return $custom_name ?: get_bloginfo('name') . ' - Incidenti Stradali';
    }
    
    /**
     * Count casualties for a post
     */
    private function count_casualties($post_id, $type) {
        $count = 0;
        
        // Count drivers
        for ($i = 1; $i <= 3; $i++) {
            $esito = get_post_meta($post_id, 'conducente_' . $i . '_esito', true);
            if ($type === 'morti' && ($esito == '3' || $esito == '4')) {
                $count++;
            } elseif ($type === 'feriti' && $esito == '2') {
                $count++;
            }
        }
        
        // Count pedestrians
        $num_pedoni = get_post_meta($post_id, 'numero_pedoni_coinvolti', true) ?: 0;
        for ($i = 1; $i <= $num_pedoni; $i++) {
            $esito = get_post_meta($post_id, 'pedone_' . $i . '_esito', true);
            if ($type === 'morti' && ($esito == '3' || $esito == '4')) {
                $count++;
            } elseif ($type === 'feriti' && $esito == '2') {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Check if post has significant changes
     */
    private function has_significant_changes($post_id) {
        // Check if specific important fields were changed
        $important_fields = array(
            'data_incidente', 'ora_incidente', 'natura_incidente',
            'numero_veicoli_coinvolti', 'numero_pedoni_coinvolti'
        );
        
        foreach ($important_fields as $field) {
            if (get_post_meta($post_id, '_' . $field . '_modified', true)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Perform auto export
     */
    private function perform_auto_export() {
        // This would integrate with the export functions
        // Return array with success/error status
        return array(
            'success' => true,
            'count' => 0,
            'filename' => '',
            'error' => ''
        );
    }
}

// Initialize email notifications
new IncidentiEmailNotifications();