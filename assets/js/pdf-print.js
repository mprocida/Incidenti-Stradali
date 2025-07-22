/**
 * Generazione PDF per incidenti stradali
 * Utilizza jsPDF per la creazione del documento
 */

window.generateIncidentePDF = function() {
    // Attendi che jsPDF sia caricato
    function waitForJsPDF(callback, attempts = 0) {
        if (attempts > 50) { // Max 5 secondi di attesa
            console.error('Timeout: jsPDF non caricato');
            return;
        }
        
        if (typeof window.jsPDF !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForJsPDF(callback, attempts + 1), 100);
        }
    }
    
    waitForJsPDF(function() {
        // jsPDF è ora disponibile
        const jsPDF = window.jsPDF;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        // Configurazione documento
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 20;
        const contentWidth = pageWidth - (margin * 2);
        
        let currentY = margin;
        
        // Funzione per aggiungere nuova pagina se necessario
        function checkPageBreak(height) {
            if (currentY + height > pageHeight - margin) {
                pdf.addPage();
                currentY = margin;
                return true;
            }
            return false;
        }
        
        // Header del documento
        pdf.setFontSize(16);
        pdf.setFont(undefined, 'bold');
        pdf.text('MODULO INCIDENTE STRADALE', pageWidth / 2, currentY, { align: 'center' });
        currentY += 10;
        
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'normal');
        pdf.text('Rilevazione statistica degli incidenti stradali con lesioni a persone', pageWidth / 2, currentY, { align: 'center' });
        currentY += 15;
        
        // Linea separatrice
        pdf.setLineWidth(0.5);
        pdf.line(margin, currentY, pageWidth - margin, currentY);
        currentY += 10;
        
        // Raccolta dati dal form
        const formData = collectFormData();
        
        // Sezione Dati Generali
        if (hasData(formData.dati_generali)) {
            currentY = addSection(pdf, 'DATI GENERALI', formData.dati_generali, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Localizzazione
        if (hasData(formData.localizzazione)) {
            checkPageBreak(30);
            currentY = addSection(pdf, 'LOCALIZZAZIONE', formData.localizzazione, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Natura Incidente
        if (hasData(formData.natura)) {
            checkPageBreak(30);
            currentY = addSection(pdf, 'NATURA INCIDENTE', formData.natura, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Veicoli
        if (formData.veicoli && formData.veicoli.length > 0) {
            checkPageBreak(40);
            currentY = addVehiclesSection(pdf, formData.veicoli, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Pedoni
        if (hasData(formData.pedoni)) {
            checkPageBreak(20);
            currentY = addSection(pdf, 'PEDONI COINVOLTI', formData.pedoni, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Circostanze
        if (hasData(formData.circostanze)) {
            checkPageBreak(30);
            currentY = addSection(pdf, 'CIRCOSTANZE', formData.circostanze, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Sezione Condizioni Ambientali
        if (hasData(formData.condizioni)) {
            checkPageBreak(30);
            currentY = addSection(pdf, 'CONDIZIONI AMBIENTALI', formData.condizioni, currentY, margin, contentWidth, checkPageBreak);
        }
        
        // Footer
        const now = new Date();
        const dateString = now.toLocaleDateString('it-IT') + ' ' + now.toLocaleTimeString('it-IT');
        
        pdf.setFontSize(8);
        pdf.setFont(undefined, 'italic');
        pdf.text(`Documento generato il ${dateString}`, margin, pageHeight - 10);
        
        // Download del PDF
        const filename = `Incidente_${formData.dati_generali.codice_ente || 'DRAFT'}_${formData.dati_generali.data_incidente || 'no-date'}.pdf`;
        pdf.save(filename);
        
        // Mostra messaggio di successo
        jQuery('#pdf-success').show();
    });
};

function collectFormData() {
    const $ = jQuery;
    
    return {
        dati_generali: {
            codice_ente: $('#codice__ente').val() || $('input[name="codice__ente"]').val(),
            data_incidente: $('#data_incidente').val(),
            ora_incidente: $('#ora_incidente').val(),
            minuti_incidente: $('#minuti_incidente').val(),
            provincia: $('#provincia_incidente').val(),
            comune: $('#comune_incidente option:selected').text(),
            localita: $('#localita_incidente').val(),
            ente_rilevatore: $('#ente_rilevatore').val(),
            nome_rilevatore: $('#nome_rilevatore').val()
        },
        localizzazione: {
            tipo_strada: $('#tipo_strada option:selected').text(),
            denominazione_strada: $('#denominazione_strada').val(),
            numero_strada: $('#numero_strada').val() || $('#numero_strada_input').val() || $('#numero_strada_select').val() || $('#numero_strada_select_statali').val(),
            progressiva_km: $('#progressiva_km').val(),
            progressiva_m: $('#progressiva_m').val(),
            latitudine: $('#latitudine').val() || $('#latitudine_inline').val(),
            longitudine: $('#longitudine').val() || $('#longitudine_inline').val()
        },
        natura: {
            natura_incidente: $('#natura_incidente option:selected').text(),
            dettaglio_natura: $('#dettaglio_natura option:selected').text(),
            numero_veicoli: $('#numero_veicoli_coinvolti').val(),
            altro_natura_testo: $('#altro_natura_testo').val()
        },
        veicoli: collectVehiclesData(),
        circostanze: {
            tipo_incidente: $('#circostanza_tipo option:selected').text(),
            circostanza_veicolo_a: $('#circostanza_veicolo_a option:selected').text(),
            circostanza_veicolo_b: $('#circostanza_veicolo_b option:selected').text(),
            difetto_veicolo_a: $('#difetto_veicolo_a option:selected').text(),
            difetto_veicolo_b: $('#difetto_veicolo_b option:selected').text(),
            stato_psicofisico_a: $('#stato_psicofisico_a option:selected').text(),
            stato_psicofisico_b: $('#stato_psicofisico_b option:selected').text()
        },
        pedoni: {
            numero_morti: $('#numero_pedoni_morti').val(),
            numero_feriti: $('#numero_pedoni_feriti').val()
        },
        condizioni: {
            condizioni_meteo: $('#condizioni_meteo option:selected').text(),
            illuminazione: $('#illuminazione option:selected').text(),
            stato_fondo_strada: $('input[name="stato_fondo_strada"]:checked').next('label').text() || $('input[name="stato_fondo_strada"]:checked').parent().text(),
            geometria_strada: $('input[name="geometria_strada"]:checked').next('label').text() || $('input[name="geometria_strada"]:checked').parent().text(),
            intersezione_tronco: $('input[name="intersezione_tronco"]:checked').next('label').text() || $('input[name="intersezione_tronco"]:checked').parent().text(),
            pavimentazione_strada: $('input[name="pavimentazione_strada"]:checked').next('label').text() || $('input[name="pavimentazione_strada"]:checked').parent().text(),
            segnaletica_strada: $('input[name="segnaletica_strada"]:checked').next('label').text() || $('input[name="segnaletica_strada"]:checked').parent().text()
        }
    };
}

function collectVehiclesData() {
    const $ = jQuery;
    const vehicles = [];
    const numVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
    
    for (let i = 1; i <= numVeicoli; i++) {
        const veicolo = {
            numero: i,
            lettera: String.fromCharCode(64 + i), // A, B, C
            tipo: $(`#veicolo_${i}_tipo option:selected`).text(),
            targa: $(`#veicolo_${i}_targa`).val(),
            sigla_estero: $(`#veicolo_${i}_sigla_estero`).val(),
            anno: $(`#veicolo_${i}_anno_immatricolazione`).val(),
            cilindrata: $(`#veicolo_${i}_cilindrata`).val(),
            peso_totale: $(`#veicolo_${i}_peso_totale`).val(),
            conducente: {
                eta: $(`#conducente_${i}_eta`).val(),
                sesso: $(`#conducente_${i}_sesso option:selected`).text(),
                esito: $(`#conducente_${i}_esito option:selected`).text(),
                nazionalita: $(`#conducente_${i}_nazionalita option:selected`).text(),
                anno_patente: $(`#conducente_${i}_anno_patente`).val(),
                tipo_patente: collectPatenteTypes(i)
            }
        };
        
        // Aggiungi solo se ha dati significativi
        if (veicolo.tipo || veicolo.targa || veicolo.conducente.eta) {
            vehicles.push(veicolo);
        }
    }
    
    return vehicles;
}

function collectPatenteTypes(conducente_num) {
    const $ = jQuery;
    const tipi = [];
    
    $(`input[name="conducente_${conducente_num}_tipo_patente[]"]:checked`).each(function() {
        const value = $(this).val();
        const labels = {
            '0': 'Patente ciclomotori',
            '1': 'Patente tipo A',
            '2': 'Patente tipo B',
            '3': 'Patente tipo C',
            '4': 'Patente tipo D',
            '5': 'Patente tipo E',
            '6': 'ABC speciale',
            '7': 'Non richiesta',
            '8': 'Foglio rosa',
            '9': 'Sprovvisto'
        };
        
        if (labels[value]) {
            tipi.push(labels[value]);
        }
    });
    
    return tipi.join(', ');
}

function hasData(obj) {
    if (!obj) return false;
    return Object.values(obj).some(value => {
        if (Array.isArray(value)) return value.length > 0;
        return value && value.toString().trim() !== '' && value !== 'Seleziona...' && value !== 'Seleziona' && value !== 'Seleziona tipo' && value !== 'Seleziona comune';
    });
}

function addSection(pdf, title, data, startY, margin, contentWidth, checkPageBreak) {
    let currentY = startY;
    
    // Controlla se serve una nuova pagina per il titolo
    checkPageBreak(15);
    
    // Titolo sezione con sfondo colorato
    pdf.setFillColor(240, 240, 240);
    pdf.rect(margin, currentY - 5, contentWidth, 10, 'F');
    
    pdf.setFontSize(12);
    pdf.setFont(undefined, 'bold');
    pdf.text(title, margin + 2, currentY + 2);
    currentY += 12;
    
    // Contenuto
    pdf.setFontSize(10);
    pdf.setFont(undefined, 'normal');
    
    Object.entries(data).forEach(([key, value]) => {
        if (value && value.toString().trim() !== '' && value !== 'Seleziona...' && value !== 'Seleziona' && value !== 'Seleziona tipo' && value !== 'Seleziona comune') {
            const label = formatLabel(key);
            const text = `${label}: ${value}`;
            
            // Gestione testo lungo
            const lines = pdf.splitTextToSize(text, contentWidth - 10);
            
            // Controlla se serve una nuova pagina
            checkPageBreak(lines.length * 5 + 2);
            
            pdf.text(lines, margin + 5, currentY);
            currentY += lines.length * 5 + 1;
        }
    });
    
    return currentY + 5; // Spazio dopo la sezione
}

function addVehiclesSection(pdf, vehicles, startY, margin, contentWidth, checkPageBreak) {
    let currentY = startY;
    
    // Controlla se serve una nuova pagina per il titolo
    checkPageBreak(15);
    
    // Titolo sezione con sfondo colorato
    pdf.setFillColor(240, 240, 240);
    pdf.rect(margin, currentY - 5, contentWidth, 10, 'F');
    
    pdf.setFontSize(12);
    pdf.setFont(undefined, 'bold');
    pdf.text('VEICOLI E CONDUCENTI', margin + 2, currentY + 2);
    currentY += 15;
    
    vehicles.forEach((veicolo, index) => {
        // Controlla se serve una nuova pagina per questo veicolo
        checkPageBreak(50);
        
        // Sottotitolo veicolo
        pdf.setFillColor(250, 250, 250);
        pdf.rect(margin + 5, currentY - 3, contentWidth - 10, 8, 'F');
        
        pdf.setFontSize(11);
        pdf.setFont(undefined, 'bold');
        pdf.text(`Veicolo ${veicolo.lettera}`, margin + 7, currentY + 2);
        currentY += 10;
        
        pdf.setFontSize(10);
        pdf.setFont(undefined, 'normal');
        
        // Dati veicolo
        const veicoloData = {
            tipo: veicolo.tipo,
            targa: veicolo.targa,
            sigla_estero: veicolo.sigla_estero,
            anno_immatricolazione: veicolo.anno,
            cilindrata: veicolo.cilindrata,
            peso_totale: veicolo.peso_totale
        };
        
        Object.entries(veicoloData).forEach(([key, value]) => {
            if (value && value.toString().trim() !== '' && value !== 'Seleziona tipo') {
                const label = formatLabel(key);
                pdf.text(`${label}: ${value}`, margin + 10, currentY);
                currentY += 5;
            }
        });
        
        // Dati conducente
        if (hasData(veicolo.conducente)) {
            currentY += 2;
            pdf.setFont(undefined, 'bold');
            pdf.text('Conducente:', margin + 10, currentY);
            currentY += 5;
            
            pdf.setFont(undefined, 'normal');
            Object.entries(veicolo.conducente).forEach(([key, value]) => {
                if (value && value.toString().trim() !== '' && value !== 'Seleziona') {
                    const label = formatLabel(key);
                    const text = `${label}: ${value}`;
                    const lines = pdf.splitTextToSize(text, contentWidth - 30);
                    pdf.text(lines, margin + 15, currentY);
                    currentY += lines.length * 5;
                }
            });
        }
        
        currentY += 5; // Spazio tra veicoli
    });
    
    return currentY + 5;
}

function formatLabel(key) {
    const labels = {
        codice_ente: 'Codice Ente',
        data_incidente: 'Data Incidente',
        ora_incidente: 'Ora',
        minuti_incidente: 'Minuti',
        provincia: 'Provincia',
        comune: 'Comune',
        localita: 'Località',
        ente_rilevatore: 'Ente Rilevatore',
        nome_rilevatore: 'Nome Rilevatore',
        tipo_strada: 'Tipo Strada',
        denominazione_strada: 'Denominazione Strada',
        numero_strada: 'Numero Strada',
        progressiva_km: 'Progressiva Km',
        progressiva_m: 'Progressiva Mt',
        latitudine: 'Latitudine',
        longitudine: 'Longitudine',
        natura_incidente: 'Natura Incidente',
        dettaglio_natura: 'Dettaglio',
        numero_veicoli: 'Numero Veicoli Coinvolti',
        altro_natura_testo: 'Altro (specificare)',
        numero_morti: 'Numero Morti',
        numero_feriti: 'Numero Feriti',
        tipo_incidente: 'Tipo Incidente',
        circostanza_veicolo_a: 'Circostanza Veicolo A',
        circostanza_veicolo_b: 'Circostanza Veicolo B',
        difetto_veicolo_a: 'Difetto Veicolo A',
        difetto_veicolo_b: 'Difetto Veicolo B',
        stato_psicofisico_a: 'Stato Psicofisico Conducente A',
        stato_psicofisico_b: 'Stato Psicofisico Conducente B',
        condizioni_meteo: 'Condizioni Meteorologiche',
        illuminazione: 'Illuminazione',
        stato_fondo_strada: 'Stato Fondo Strada',
        geometria_strada: 'Geometria Strada',
        intersezione_tronco: 'Intersezione/Tronco',
        pavimentazione_strada: 'Pavimentazione',
        segnaletica_strada: 'Segnaletica',
        tipo: 'Tipo Veicolo',
        targa: 'Targa',
        sigla_estero: 'Sigla Estero',
        anno: 'Anno Immatricolazione',
        anno_immatricolazione: 'Anno Immatricolazione',
        cilindrata: 'Cilindrata (cc)',
        peso_totale: 'Peso Totale (q)',
        eta: 'Età',
        sesso: 'Sesso',
        esito: 'Esito',
        nazionalita: 'Nazionalità',
        anno_patente: 'Anno Rilascio Patente',
        tipo_patente: 'Tipo Patente'
    };
    
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}