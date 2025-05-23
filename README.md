# Plugin Incidenti Stradali ISTAT

Un plugin WordPress completo per la gestione degli incidenti stradali secondo le specifiche ISTAT, con funzionalità di raccolta dati, esportazione e visualizzazione cartografica.

## Caratteristiche Principali

### 📝 Gestione Incidenti
- **Custom Post Type** dedicato per gli incidenti stradali
- **Campi strutturati** secondo il tracciato ISTAT ufficiale
- **Validazione in tempo reale** dei dati inseriti
- **Gestione multipla veicoli** (fino a 3 veicoli per incidente)
- **Dati conducenti e pedoni** coinvolti
- **Coordinate geografiche** con mappa interattiva

### 👥 Gestione Utenti e Permessi
- **Ruoli personalizzati**: Amministratore e Operatore Polizia Comunale
- **Restrizioni geografiche**: ogni operatore può gestire solo gli incidenti del proprio comune
- **Controllo temporale**: possibilità di bloccare modifiche dopo una data specifica
- **Audit trail**: log delle modifiche e delle esportazioni

### 📊 Esportazione Dati
- **Formato ISTAT (TXT)**: esportazione secondo il tracciato ufficiale ISTAT
- **Formato Excel (CSV)**: per la Polizia Stradale
- **Esportazione selettiva**: singoli incidenti o in blocco
- **Filtri avanzati**: per periodo, comune, tipo di incidente
- **Log esportazioni**: tracciamento delle esportazioni effettuate

### 🗺️ Visualizzazione Cartografica
- **Mappa interattiva** con marker personalizzati
- **Clustering automatico** per aree ad alta densità
- **Filtri dinamici** per periodo e area geografica
- **Popup informativi** con dettagli incidente
- **Shortcode personalizzabili** per l'integrazione nelle pagine

### ⚙️ Amministrazione Avanzata
- **Pannello impostazioni** completo
- **Validazione integrità dati**
- **Backup e ripristino**
- **Statistiche dashboard**
- **Notifiche email** per nuovi incidenti

## Installazione

1. **Carica il plugin**
   ```
   wp-content/plugins/incidenti-stradali/
   ```

2. **Attiva il plugin** dal pannello WordPress

3. **Configura le impostazioni** in `Incidenti Stradali > Impostazioni`

4. **Assegna i ruoli utente** agli operatori comunali

## Configurazione Iniziale

### Impostazioni Base
1. Vai su `Incidenti Stradali > Impostazioni`
2. Imposta la **data di blocco modifiche** se necessario
3. Configura il **centro mappa predefinito**
4. Imposta la **cartella esportazioni**

### Gestione Utenti
1. Crea utenti con ruolo `Operatore Polizia Comunale`
2. Assegna a ogni operatore il **codice ISTAT del comune**
3. Configura le **notifiche email** se desiderate

### Permessi Directory
Assicurati che la cartella di esportazione sia scrivibile:
```bash
chmod 755 /path/to/export/directory
```

## Utilizzo

### Inserimento Incidenti

1. **Nuovo Incidente**
   - Vai su `Incidenti Stradali > Aggiungi Nuovo`
   - Compila i campi obbligatori (marcati con *)
   - Inserisci le coordinate cliccando sulla mappa
   - Salva in bozza o pubblica

2. **Validazione Automatica**
   - I campi vengono validati in tempo reale
   - Errori evidenziati in rosso con messaggi esplicativi
   - Controllo coerenza tra i dati inseriti

### Esportazione Dati

1. **Esportazione ISTAT**
   ```
   Incidenti Stradali > Esporta Dati > Formato ISTAT (TXT)
   ```
   - Seleziona periodo e comune
   - File generato secondo specifiche ISTAT

2. **Esportazione Excel**
   ```
   Incidenti Stradali > Esporta Dati > Formato Excel (CSV)
   ```
   - Formato compatibile con Polizia Stradale
   - Dati strutturati in colonne

3. **Esportazione Selettiva**
   - Seleziona incidenti dalla lista
   - Usa azioni in blocco "Esporta ISTAT" o "Esporta Excel"

### Visualizzazione Mappe

#### Shortcode Mappa Base
```php
[incidenti_mappa]
```

#### Mappa Personalizzata
```php
[incidenti_mappa 
    width="100%" 
    height="500px" 
    zoom="12" 
    center_lat="41.9028" 
    center_lng="12.4964"
    show_filters="true"
    cluster="true"]
```

#### Mappa con Filtri
```php
[incidenti_mappa 
    comune="001" 
    periodo="last_month"
    data_inizio="2024-01-01" 
    data_fine="2024-12-31"]
```

#### Statistiche
```php
[incidenti_statistiche 
    periodo="last_year" 
    style="cards"
    show_charts="true"]
```

#### Lista Incidenti
```php
[incidenti_lista 
    limite="10"
    comune="001"
    mostra_dettagli="true"
    ordinamento="data_desc"]
```

## Struttura File

```
incidenti-stradali/
├── incidenti-stradali.php          # File principale
├── includes/
│   ├── class-custom-post-type.php  # Gestione CPT
│   ├── class-meta-boxes.php        # Meta boxes
│   ├── class-user-roles.php        # Ruoli utente
│   ├── class-export-functions.php  # Esportazioni
│   ├── class-validation.php        # Validazione
│   ├── class-shortcodes.php        # Shortcodes
│   └── class-admin-settings.php    # Impostazioni
├── assets/
│   ├── css/
│   │   ├── frontend.css            # CSS frontend
│   │   └── admin.css               # CSS admin
│   └── js/
│       ├── frontend.js             # JS frontend
│       └── admin.js                # JS admin
├── templates/                      # Template personalizzabili
├── languages/                      # File traduzioni
└── README.md                       # Documentazione
```

## Tracciato ISTAT

Il plugin segue rigorosamente il **tracciato record ISTAT** per incidenti stradali:

### Campi Principali
- **Identificativi**: Anno, mese, provincia, comune, numero progressivo
- **Temporali**: Giorno, ora dell'incidente
- **Localizzazione**: Tipo strada, denominazione, chilometrica
- **Caratteristiche luogo**: Geometria, pavimentazione, intersezione, fondo, segnaletica, meteo
- **Natura incidente**: Tipologia e dettaglio dell'incidente
- **Veicoli**: Tipo, targa, anno immatricolazione, cilindrata, peso
- **Persone**: Conducenti, passeggeri, pedoni con età, sesso, esito
- **Coordinate**: Latitudine e longitudine (facoltative)

### Codifiche ISTAT
Tutti i campi codificati seguono le tabelle ISTAT ufficiali:
- Organi di rilevazione
- Tipi di strada
- Nature degli incidenti
- Tipi di veicolo
- Circostanze presunte
- E molti altri...

## API e Hook

### Hook Disponibili
```php
// Prima del salvataggio incidente
do_action('incidenti_before_save', $post_id, $post_data);

// Dopo il salvataggio incidente
do_action('incidenti_after_save', $post_id, $post_data);

// Prima dell'esportazione
do_action('incidenti_before_export', $export_type, $filters);

// Dopo l'esportazione
do_action('incidenti_after_export', $export_type, $file_path, $count);
```

### Filtri Disponibili
```php
// Modifica dati esportazione ISTAT
add_filter('incidenti_istat_export_data', $data, $post_id);

// Modifica query mappa
add_filter('incidenti_map_query_args', $args, $filters);

// Personalizza marker mappa
add_filter('incidenti_map_marker_content', $content, $post_id);
```

## Sviluppo e Personalizzazione

### Aggiungere Campi Personalizzati
```php
add_action('incidenti_meta_boxes', function() {
    add_meta_box(
        'custom_fields',
        'Campi Personalizzati',
        'render_custom_fields',
        'incidente_stradale'
    );
});
```

### Personalizzare Esportazione
```php
add_filter('incidenti_export_data', function($data, $post_id) {
    // Aggiungi campi personalizzati
    $data['campo_custom'] = get_post_meta($post_id, 'campo_custom', true);
    return $data;
}, 10, 2);
```

### Stili Personalizzati
```css
/* Personalizza marker della mappa */
.incidente-marker.custom-style {
    border-color: #custom-color;
    background: #custom-bg;
}

/* Personalizza statistiche */
.incidenti-statistics.custom-theme .stat-card {
    background: linear-gradient(45deg, #color1, #color2);
}
```

## Risoluzione Problemi

### Problemi Comuni

1. **Errore permessi esportazione**
   ```bash
   chmod 755 wp-content/uploads/incidenti-exports/
   chown www-data:www-data wp-content/uploads/incidenti-exports/
   ```

2. **Mappa non si carica**
   - Verifica connessione internet
   - Controlla console browser per errori JavaScript
   - Verifica inclusione libreria Leaflet

3. **Validazione fallisce**
   - Controlla formato date (YYYY-MM-DD)
   - Verifica codici ISTAT (3 cifre numeriche)
   - Controllo coordinate geografiche

4. **Esportazione incompleta**
   - Verifica timeout PHP
   - Controlla memoria disponibile
   - Verifica integrità database

### Debug Mode
Attiva il debug nel file wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('INCIDENTI_DEBUG', true);
```

## Sicurezza

### Misure Implementate
- **Nonce verification** per tutte le operazioni
- **Capability checks** per ogni azione
- **Sanitizzazione input** completa
- **Escape output** per prevenire XSS
- **SQL injection protection** con prepared statements

### Raccomandazioni
- Aggiorna regolarmente WordPress e il plugin
- Usa password complesse per gli utenti
- Implementa backup automatici
- Monitora i log di accesso

## Supporto e Contributi

### Segnalazione Bug
Apri una issue su GitHub con:
- Versione WordPress
- Versione plugin
- Descrizione dettagliata del problema
- Steps per riprodurre
- Log errori se disponibili

### Contributi
I contributi sono benvenuti! Per contribuire:
1. Fork del repository
2. Crea un branch per la feature/fix
3. Implementa le modifiche
4. Test approfonditi
5. Pull request con descrizione dettagliata

## License

Questo plugin è rilasciato sotto licenza GPL v2 o successive.

## Changelog

### v1.0.0
- Rilascio iniziale
- Gestione completa incidenti stradali
- Esportazione ISTAT e Excel
- Mappe interattive
- Sistema ruoli e permessi

---

Per supporto tecnico o domande: [email di supporto]