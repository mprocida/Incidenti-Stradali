<div class="wrap incidenti-import-page">
    <h1><?php _e('Importa Incidenti da TXT', 'incidenti-stradali'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?>">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Carica File TXT', 'incidenti-stradali'); ?></h2>
        
        <form id="import-form" method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="import_incidenti_txt">
            <?php wp_nonce_field('import_incidenti_nonce', 'import_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="txt_file"><?php _e('File TXT', 'incidenti-stradali'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="txt_file" name="txt_file" accept=".txt">
                        <p class="description"><?php _e('Seleziona il file TXT da importare (max 10MB).', 'incidenti-stradali'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="import-submit-btn" class="button-primary" disabled>
                    <?php _e('Importa TXT', 'incidenti-stradali'); ?>
                </button>
                <button type="button" id="import-reset-btn" class="button">
                    <?php _e('Reset', 'incidenti-stradali'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<style>
.incidenti-import-page .card {
    margin: 20px 0;
    padding: 20px;
}

.import-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
}

.status-success {
    color: #28a745;
    font-weight: bold;
}

.status-error {
    color: #dc3545;
    font-weight: bold;
}

#txt-preview-table {
    margin-top: 15px;
}

#txt-preview-table td {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
/* Debug styles */
.button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    background-color: #ccc !important;
}

.button:not(:disabled) {
    opacity: 1 !important;
    cursor: pointer !important;
}

#import-preview-btn:not(:disabled) {
    background-color: #0073aa !important;
    color: white !important;
}

#import-submit-btn:not(:disabled) {
    background-color: #007cba !important;
    color: white !important;
}
</style>