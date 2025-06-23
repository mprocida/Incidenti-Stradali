<div class="wrap incidenti-import-page">
    <h1><?php _e('Importa Incidenti da CSV', 'incidenti-stradali'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?>">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Carica File CSV', 'incidenti-stradali'); ?></h2>
        
        <form id="import-form" method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="import_incidenti_csv">
            <?php wp_nonce_field('import_incidenti_nonce', 'import_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_file"><?php _e('File CSV', 'incidenti-stradali'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <p class="description"><?php _e('Seleziona il file CSV da importare (max 10MB).', 'incidenti-stradali'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="separator"><?php _e('Separatore', 'incidenti-stradali'); ?></label>
                    </th>
                    <td>
                        <select id="separator" name="separator">
                            <option value=","><?php _e('Virgola (,)', 'incidenti-stradali'); ?></option>
                            <option value=";"><?php _e('Punto e virgola (;)', 'incidenti-stradali'); ?></option>
                            <option value="	"><?php _e('Tab', 'incidenti-stradali'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="import-preview-btn" class="button" disabled 
                        data-nonce="<?php echo wp_create_nonce('import_incidenti_nonce'); ?>">
                    <?php _e('Anteprima CSV', 'incidenti-stradali'); ?>
                </button>
                <button type="button" id="import-submit-btn" class="button-primary" disabled>
                    <?php _e('Importa CSV', 'incidenti-stradali'); ?>
                </button>
                <button type="button" id="import-reset-btn" class="button">
                    <?php _e('Reset', 'incidenti-stradali'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <!-- Preview Section -->
    <div id="csv-preview-section" class="card" style="display: none;">
        <h3><?php _e('Anteprima Importazione', 'incidenti-stradali'); ?></h3>
        
        <div class="import-summary">
            <p>
                <strong><?php _e('Righe totali:', 'incidenti-stradali'); ?></strong> <span id="csv-total-rows">0</span> |
                <strong><?php _e('Valide:', 'incidenti-stradali'); ?></strong> <span id="csv-valid-rows">0</span> |
                <strong style="color: #d63384;"><?php _e('Errori:', 'incidenti-stradali'); ?></strong> <span id="csv-error-rows">0</span>
            </p>
        </div>
        
        <table id="csv-preview-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('#', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Data', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Ora', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Comune', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Strada', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Veicoli', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Stato', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Errori', 'incidenti-stradali'); ?></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        
        <div id="csv-errors-section" style="display: none;">
            <h4 style="color: #d63384;"><?php _e('Errori Riscontrati:', 'incidenti-stradali'); ?></h4>
            <ul id="csv-errors-list"></ul>
        </div>
    </div>
    
    <div class="card">
        <h3><?php _e('Formato CSV Richiesto', 'incidenti-stradali'); ?></h3>
        <p><?php _e('Il file CSV deve avere le seguenti colonne (con intestazioni esatte):', 'incidenti-stradali'); ?></p>
        
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th><?php _e('Nome Colonna', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Obbligatorio', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Formato/Esempio', 'incidenti-stradali'); ?></th>
                    <th><?php _e('Descrizione', 'incidenti-stradali'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>data_incidente</code></td>
                    <td><span style="color: red;">Sì</span></td>
                    <td>2024-12-31</td>
                    <td><?php _e('Data nel formato YYYY-MM-DD', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>ora_incidente</code></td>
                    <td><span style="color: red;">Sì</span></td>
                    <td>14:30</td>
                    <td><?php _e('Ora nel formato HH:MM', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>comune_incidente</code></td>
                    <td><span style="color: red;">Sì</span></td>
                    <td>001</td>
                    <td><?php _e('Codice ISTAT del comune (3 cifre)', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>denominazione_strada</code></td>
                    <td><span style="color: red;">Sì</span></td>
                    <td>Via Roma</td>
                    <td><?php _e('Nome della strada', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>numero_veicoli_coinvolti</code></td>
                    <td><span style="color: red;">Sì</span></td>
                    <td>2</td>
                    <td><?php _e('Numero veicoli coinvolti (1-3)', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>latitudine</code></td>
                    <td><?php _e('No', 'incidenti-stradali'); ?></td>
                    <td>41.9028</td>
                    <td><?php _e('Coordinate GPS', 'incidenti-stradali'); ?></td>
                </tr>
                <tr>
                    <td><code>longitudine</code></td>
                    <td><?php _e('No', 'incidenti-stradali'); ?></td>
                    <td>12.4964</td>
                    <td><?php _e('Coordinate GPS', 'incidenti-stradali'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php _e('Esempio File CSV:', 'incidenti-stradali'); ?></h4>
        <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto;">data_incidente,ora_incidente,comune_incidente,denominazione_strada,numero_veicoli_coinvolti,latitudine,longitudine
2024-01-15,08:30,001,Via Roma,2,41.9028,12.4964
2024-01-16,14:45,002,Corso Italia,1,41.8919,12.5113</pre>
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

#csv-preview-table {
    margin-top: 15px;
}

#csv-preview-table td {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>