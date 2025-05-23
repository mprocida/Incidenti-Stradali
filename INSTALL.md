# Guida di Installazione - Plugin Incidenti Stradali ISTAT

## Panoramica del Plugin

Il **Plugin Incidenti Stradali ISTAT** è una soluzione completa per la gestione degli incidenti stradali in conformità alle specifiche ISTAT italiane. È progettato specificamente per:

- **Polizie Municipali** e Locali
- **Enti Pubblici** che gestiscono la sicurezza stradale
- **Province e Regioni** per il coordinamento dati
- **Organizzazioni** che necessitano di reportistica ISTAT

## Requisiti di Sistema

### Requisiti Minimi WordPress
- **WordPress**: 5.0 o superiore
- **PHP**: 7.4 o superiore (raccomandato 8.0+)
- **MySQL**: 5.6 o superiore / MariaDB 10.0+
- **Apache/Nginx**: con mod_rewrite abilitato

### Requisiti Server
- **Memoria PHP**: 256MB (raccomandato 512MB)
- **Tempo di esecuzione**: 300 secondi per le esportazioni
- **Spazio disco**: 100MB + spazio per i file esportati
- **Estensioni PHP richieste**:
  - `json`
  - `mbstring`
  - `zip`
  - `gd` o `imagick` (opzionale, per grafici)

### Browser Supportati
- **Chrome** 90+
- **Firefox** 88+
- **Safari** 14+
- **Edge** 90+

## Pre-Installazione

### 1. Backup del Sito
```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/wordpress/
```

### 2. Verifica Permessi Directory
```bash
# Verifica permessi wp-content
ls -la wp-content/
# Dovrebbe mostrare: drwxr-xr-x

# Crea directory per esportazioni
mkdir -p wp-content/uploads/incidenti-exports
chmod 755 wp-content/uploads/incidenti-exports
chown www-data:www-data wp-content/uploads/incidenti-exports
```

### 3. Configurazione PHP
Aggiungi/modifica nel file `php.ini` o `.htaccess`:

```ini
# php.ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
max_input_vars = 3000

# .htaccess (se applicabile)
php_value memory_limit 512M
php_value max_execution_time 300
```

## Installazione

### Metodo 1: Upload Manuale (Raccomandato)

1. **Scarica il plugin** e decomprimilo
2. **Carica via FTP/SFTP**:
   ```bash
   # Upload della cartella
   scp -r incidenti-stradali/ user@server:/path/to/wp-content/plugins/
   
   # Imposta permessi corretti
   chmod -R 755 wp-content/plugins/incidenti-stradali/
   ```

3. **Attiva il plugin** dal pannello WordPress:
   - Vai su `Plugin > Plugin Installati`
   - Trova "Incidenti Stradali ISTAT"
   - Clicca "Attiva"

### Metodo 2: Upload via Admin WordPress

1. **Admin WordPress** > `Plugin > Aggiungi Nuovo`
2. **Carica Plugin** > Seleziona il file ZIP
3. **Installa Ora** > **Attiva Plugin**

## Configurazione Iniziale

### 1. Primo Accesso
Dopo l'attivazione, vedrai una notifica di benvenuto:

![Notifica Benvenuto](screenshot-welcome.png)

Clicca **"Configura Ora"** per iniziare la configurazione guidata.

### 2. Impostazioni Base

#### A. Impostazioni Generali
```
Incidenti Stradali > Impostazioni > Generali
```

- **Data Blocco Modifiche**: `2024-01-01` (esempio)
  - Gli incidenti prima di questa data non saranno modificabili dagli operatori
- **Centro Mappa Predefinito**: 
  - Latitudine: `41.9028` (Roma)
  - Longitudine: `12.4964`
  - Personalizza in base alla tua area geografica

#### B. Impostazioni Esportazione
```
Incidenti Stradali > Impostazioni > Esportazione
```

- **Cartella Esportazioni**: 
  ```
  /home/sito/public_html/wp-content/uploads/incidenti-exports
  ```
- **Esportazione Automatica**: 
  - ☑️ Abilita esportazione automatica
  - Frequenza: `Mensile`
  - Email notifica: `admin@tuodominio.it`

#### C. Impostazioni Mappa
```
Incidenti Stradali > Impostazioni > Mappa
```

- **Provider Mappa**: `OpenStreetMap` (gratuito)
- **Clustering Marker**: ☑️ Abilitato
- **Raggio Clustering**: `80px`

### 3. Gestione Utenti e Ruoli

#### A. Crea Ruoli Operatore
Il plugin crea automaticamente il ruolo `Operatore Polizia Comunale`, ma devi assegnarlo manualmente:

1. **Utenti** > **Aggiungi Nuovo**
2. **Informazioni Base**:
   - Username: `op.comune.roma`
   - Email: `operatore@comune.roma.it`
   - Nome: `Mario Rossi`
   - Ruolo: `Operatore Polizia Comunale`

3. **Informazioni Incidenti Stradali**:
   - Comune Assegnato: `058` (codice ISTAT Roma)

#### B. Configurazione Amministratori
Gli amministratori esistenti ottengono automaticamente tutti i permessi. Per nuovi admin:

1. **Utenti** > **Aggiungi Nuovo**
2. **Ruolo**: `Amministratore`
3. Avrà accesso completo a tutti i comuni e funzioni di esportazione

### 4. Test Configurazione

#### A. Test Creazione Incidente
1. **Incidenti Stradali** > **Aggiungi Nuovo**  
2. **Compila campi obbligatori**:
   - Data: `2024-03-15`
   - Ora: `14`
   - Provincia: `058` (Roma)
   - Comune: `091` (Roma Centro)
   - Tipo Strada: `Strada urbana`
   - Natura Incidente: `Tra veicoli in marcia`

3. **Clicca coordinate sulla mappa**
4. **Salva** come bozza prima, poi **Pubblica**

#### B. Test Esportazione
1. **Incidenti Stradali** > **Esporta Dati**
2. **Formato ISTAT (TXT)**:
   - Periodo: Ultimo mese
   - Comune: (lascia vuoto per tutti)
3. **Clicca "Esporta TXT ISTAT"**
4. **Verifica download** del file

#### C. Test Mappa
1. **Crea una pagina** con shortcode:
   ```php
   [incidenti_mappa height="400px" show_filters="true"]
   ```
2. **Visualizza la pagina**
3. **Verifica** che la mappa si carichi e mostri i marker

## Configurazioni Avanzate

### 1. Personalizzazione Tema

#### A. Template Override
Crea file personalizzati in `wp-content/themes/tuo-tema/incidenti-templates/`:

```php
// single-incidente_stradale.php
<?php get_header(); ?>

<div class="incidente-single">
    <?php while (have_posts()): the_post(); ?>
        <h1><?php the_title(); ?></h1>
        
        <!-- Dati incidente personalizzati -->
        <div class="incidente-details">
            <?php 
            $data = get_post_meta(get_the_ID(), 'data_incidente', true);
            $ora = get_post_meta(get_the_ID(), 'ora_incidente', true);
            ?>
            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($data)); ?></p>
            <p><strong>Ora:</strong> <?php echo $ora; ?>:00</p>
        </div>
        
        <!-- Mappa incidente -->
        <?php echo do_shortcode('[incidenti_mappa height="300px" show_filters="false"]'); ?>
        
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
```

#### B. CSS Personalizzato
Aggiungi in `wp-content/themes/tuo-tema/style.css`:

```css
/* Personalizzazione Incidenti */
.incidenti-map-container {
    border: 2px solid #your-brand-color;
    border-radius: 8px;
}

.incidente-marker-morto {
    border-color: #your-danger-color !important;
}

.incidenti-statistics .stat-card {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
```

### 2. Integrazioni

#### A. Google Analytics
Aggiungi tracking eventi in `functions.php`:

```php
add_action('incidenti_after_save', function($post_id, $post_data) {
    // Invia evento a GA
    if (function_exists('gtag')) {
        ?>
        <script>
        gtag('event', 'incidente_saved', {
            'event_category': 'Incidenti',
            'event_label': '<?php echo get_post_meta($post_id, 'comune_incidente', true); ?>'
        });
        </script>
        <?php
    }
}, 10, 2);
```

#### B. Email Notifiche Personalizzate
```php
add_action('incidenti_after_save', function($post_id, $post_data) {
    // Notifica personalizzata
    $data = get_post_meta($post_id, 'data_incidente', true);
    $comune = get_post_meta($post_id, 'comune_incidente', true);
    
    wp_mail(
        'responsabile@ente.gov.it',
        'Nuovo Incidente Registrato',
        "Nuovo incidente del $data nel comune $comune.\n\nDettagli: " . get_edit_post_link($post_id)
    );
}, 10, 2);
```

### 3. Ottimizzazioni Performance

#### A. Caching
Per siti con molti incidenti, configura caching avanzato:

```php
// wp-config.php
define('INCIDENTI_CACHE_ENABLED', true);
define('INCIDENTI_CACHE_DURATION', 3600); // 1 ora
```

#### B. Database Indexing
Esegui query per ottimizzare le performance:

```sql
-- Indices per meta_key frequenti
ALTER TABLE wp_postmeta ADD INDEX idx_incidenti_data (meta_key, meta_value);
ALTER TABLE wp_postmeta ADD INDEX idx_incidenti_comune (meta_key, meta_value);
```

## Risoluzione Problemi

### Problemi Comuni

#### 1. Errore "Plugin non compatibile"
**Causa**: Versione PHP/WordPress non supportata
**Soluzione**:
```bash
# Verifica versioni
php -v
wp-cli core version

# Aggiorna se necessario
wp core update
wp plugin update --all
```

#### 2. Mappa non si carica
**Causa**: Libreria Leaflet non caricata
**Soluzione**:
```php
// Aggiungi in functions.php se necessario
add_action('wp_enqueue_scripts', function() {
    if (!wp_script_is('leaflet', 'enqueued')) {
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js');
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
    }
});
```

#### 3. Esportazione fallisce
**Causa**: Timeout o memoria insufficiente
**Soluzione**:
```php
// wp-config.php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

// O via .htaccess
php_value memory_limit 512M
php_value max_execution_time 600
```

#### 4. Permessi directory
**Causa**: Directory non scrivibile
**Soluzione**:
```bash
# Fix permessi
sudo chown -R www-data:www-data wp-content/uploads/
sudo chmod -R 755 wp-content/uploads/
sudo chmod -R 775 wp-content/uploads/incidenti-exports/
```

### Log e Debug

#### Abilita Debug
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('INCIDENTI_DEBUG', true);
```

#### Verifica Log
```bash
# Controlla log WordPress
tail -f wp-content/debug.log

# Controlla log server
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

## Manutenzione

### Backup Automatico
Script per backup automatico dei dati:

```bash
#!/bin/bash
# backup-incidenti.sh

DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="wordpress_db"
DB_USER="db_user"
DB_PASS="db_pass"

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > /backup/incidenti_db_$DATE.sql

# Backup uploads
tar -czf /backup/incidenti_uploads_$DATE.tar.gz wp-content/uploads/incidenti-exports/

# Pulizia backup vecchi (30 giorni)
find /backup/ -name "incidenti_*" -mtime +30 -delete
```

### Aggiornamenti
1. **Sempre backup** prima degli aggiornamenti
2. **Test su staging** se possibile
3. **Verifica compatibilità** con tema e altri plugin
4. **Monitora log** post-aggiornamento

### Monitoraggio
Dashboard per monitorare l'utilizzo:

```php
// Aggiungi in functions.php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'incidenti_monitor',
        'Monitor Incidenti',
        function() {
            $stats = wp_count_posts('incidente_stradale');
            echo "<p>Incidenti pubblicati: <strong>{$stats->publish}</strong></p>";
            echo "<p>Bozze: <strong>{$stats->draft}</strong></p>";
            
            $export_path = get_option('incidenti_export_path');
            $writable = is_writable($export_path) ? 'Sì' : 'No';
            echo "<p>Directory esportazione scrivibile: <strong>$writable</strong></p>";
        }
    );
});
```

## Supporto

### Documentazione
- **Wiki**: [link-al-wiki]
- **FAQ**: [link-alle-faq]
- **Video Tutorial**: [link-ai-video]

### Community
- **Forum**: [link-al-forum]
- **Discord**: [link-al-discord]
- **GitHub Issues**: [link-alle-issues]

### Supporto Professionale
Per supporto dedicato:
- **Email**: support@plugin-incidenti.it
- **Telefono**: +39 XXX XXX XXXX
- **Orari**: Lunedì-Venerdì 9:00-18:00

---

**🎉 Congratulazioni!** Il tuo plugin Incidenti Stradali ISTAT è ora configurato e pronto all'uso. Per qualsiasi domanda, consulta la documentazione o contatta il supporto.