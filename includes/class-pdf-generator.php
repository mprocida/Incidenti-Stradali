<?php
if (!defined('ABSPATH')) {
    exit;
}

class PDF_Generator {
    
    public function generate_incidente_pdf($post_id) {
        if (!class_exists('TCPDF')) {
            require_once(plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php');
        }
        
        // Crea istanza TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configurazione documento
        $pdf->SetCreator('Plugin Incidenti Stradali');
        $pdf->SetAuthor('Sistema Gestione Incidenti');
        $pdf->SetTitle('Modulo Incidente Stradale');
        
        // Rimuovi header/footer default
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Margini
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);
        
        // Aggiungi pagina
        $pdf->AddPage();
        
        // Genera contenuto
        $html = $this->generate_pdf_content($post_id);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Salva file
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/incidenti-pdf/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $filename = 'incidente_' . $post_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = $pdf_dir . $filename;
        
        $pdf->Output($filepath, 'F');
        
        return file_exists($filepath) ? $filepath : false;
    }
    
    private function generate_pdf_content($post_id) {
        $post = get_post($post_id);
        $meta = get_post_meta($post_id);
        
        ob_start();
        ?>
        <style>
        body { font-family: helvetica, sans-serif; font-size: 10pt; }
        .header { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; font-size: 11pt; background-color: #f0f0f0; padding: 5px; }
        .field { margin: 5px 0; }
        .field-label { font-weight: bold; display: inline-block; width: 150px; }
        .field-value { display: inline-block; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        .table th { background-color: #f0f0f0; font-weight: bold; }
        </style>
        
        <div class="header">
            MODULO INCIDENTE STRADALE<br>
            Rilevazione statistica degli incidenti stradali con lesioni a persone
        </div>
        
        <!-- Dati Generali -->
        <div class="section">
            <div class="section-title">DATI GENERALI</div>
            <div class="field">
                <span class="field-label">Codice Ente:</span>
                <span class="field-value"><?php echo esc_html($meta['codice__ente'][0] ?? ''); ?></span>
            </div>
            <div class="field">
                <span class="field-label">Data Incidente:</span>
                <span class="field-value"><?php echo esc_html($meta['data_incidente'][0] ?? ''); ?></span>
            </div>
            <div class="field">
                <span class="field-label">Ora:</span>
                <span class="field-value"><?php echo esc_html($meta['ora_incidente'][0] ?? ''); ?>:<?php echo esc_html($meta['minuti_incidente'][0] ?? ''); ?></span>
            </div>
        </div>
        
        <!-- Localizzazione -->
        <div class="section">
            <div class="section-title">LOCALIZZAZIONE</div>
            <div class="field">
                <span class="field-label">Provincia:</span>
                <span class="field-value"><?php echo esc_html($meta['provincia_incidente'][0] ?? ''); ?></span>
            </div>
            <div class="field">
                <span class="field-label">Comune:</span>
                <span class="field-value"><?php echo esc_html($meta['comune_incidente'][0] ?? ''); ?></span>
            </div>
            <div class="field">
                <span class="field-label">Via/Località:</span>
                <span class="field-value"><?php echo esc_html($meta['denominazione_strada'][0] ?? ''); ?></span>
            </div>
        </div>
        
        <?php
        // Aggiungi altre sezioni...
        
        return ob_get_clean();
    }
}