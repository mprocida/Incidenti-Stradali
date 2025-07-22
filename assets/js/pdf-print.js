/**
 * Generazione PDF per incidenti stradali
 * Utilizza jsPDF per la creazione del documento
 */

    window.generateIncidentePDF = function() {
        // Verifica che jsPDF sia caricato - supporta entrambi i formati
        let jsPDF;
        
        if (typeof window.jsPDF !== 'undefined') {
            // Se jsPDF è disponibile come oggetto
            if (typeof window.jsPDF.jsPDF !== 'undefined') {
                jsPDF = window.jsPDF.jsPDF; // Formato UMD
            } else if (typeof window.jsPDF === 'function') {
                jsPDF = window.jsPDF; // Formato standard
            }
        } else if (typeof window.jspdf !== 'undefined') {
            jsPDF = window.jspdf.jsPDF;
        }
        
        if (!jsPDF) {
            console.error('jsPDF non è caricato correttamente');
            console.log('window.jsPDF:', window.jsPDF);
            console.log('Oggetti disponibili:', Object.keys(window).filter(key => key.toLowerCase().includes('pdf')));
            return;
        }

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
        currentY = addSection(pdf, 'DATI GENERALI', formData.dati_generali, currentY, margin, contentWidth);
    }
    
    // Sezione Localizzazione
    if (hasData(formData.localizzazione)) {
        checkPageBreak(30);
        currentY = addSection(pdf, 'LOCALIZZAZIONE', formData.localizzazione, currentY, margin, contentWidth);
    }
    
    // Sezione Veicoli
    if (formData.veicoli && formData.veicoli.length > 0) {
        checkPageBreak(40);
        currentY = addVehiclesSection(pdf, formData.veicoli, currentY, margin, contentWidth);
    }
    
    // Sezione Circostanze
    if (hasData(formData.circostanze)) {
        checkPageBreak(30);
        currentY = addSection(pdf, 'CIRCOSTANZE', formData.circostanze, currentY, margin, contentWidth);
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
            numero_strada: $('#numero_strada').val(),
            latitudine: $('#latitudine, #latitudine_inline').val(),
            longitudine: $('#longitudine, #longitudine_inline').val()
        },
        natura: {
            natura_incidente: $('#natura_incidente option:selected').text(),
            dettaglio_natura: $('#dettaglio_natura option:selected').text(),
            numero_veicoli: $('#numero_veicoli_coinvolti').val()
        },
        veicoli: collectVehiclesData(),
        circostanze: {
            circostanza_veicolo_a: $('#circostanza_veicolo_a option:selected').text(),
            circostanza_veicolo_b: $('#circostanza_veicolo_b option:selected').text(),
            difetto_veicolo_a: $('#difetto_veicolo_a option:selected').text(),
            stato_psicofisico_a: $('#stato_psicofisico_a option:selected').text()
        },
        pedoni: {
            numero_morti: $('#numero_pedoni_morti').val(),
            numero_feriti: $('#numero_pedoni_feriti').val()
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
            tipo: $(`#veicolo_${i}_tipo option:selected`).text(),
            targa: $(`#veicolo_${i}_targa`).val(),
            anno: $(`#veicolo_${i}_anno_immatricolazione`).val(),
            conducente: {
                eta: $(`#conducente_${i}_eta`).val(),
                sesso: $(`#conducente_${i}_sesso option:selected`).text(),
                esito: $(`#conducente_${i}_esito option:selected`).text(),
                nazionalita: $(`#conducente_${i}_nazionalita option:selected`).text()
            }
        };
        
        // Aggiungi solo se ha dati significativi
        if (veicolo.tipo || veicolo.targa || veicolo.conducente.eta) {
            vehicles.push(veicolo);
        }
    }
    
    return vehicles;
}

function hasData(obj) {
    if (!obj) return false;
    return Object.values(obj).some(value => value && value.trim() !== '' && value !== 'Seleziona...' && value !== 'Seleziona');
}

function addSection(pdf, title, data, startY, margin, contentWidth) {
    let currentY = startY;
    
    // Titolo sezione
    pdf.setFontSize(12);
    pdf.setFont(undefined, 'bold');
    pdf.text(title, margin, currentY);
    currentY += 8;
    
    // Contenuto
    pdf.setFontSize(10);
    pdf.setFont(undefined, 'normal');
    
    Object.entries(data).forEach(([key, value]) => {
        if (value && value.trim() !== '' && value !== 'Seleziona...' && value !== 'Seleziona') {
            const label = formatLabel(key);
            const text = `${label}: ${value}`;
            
            // Gestione testo lungo
            const lines = pdf.splitTextToSize(text, contentWidth);
            pdf.text(lines, margin + 5, currentY);
            currentY += lines.length * 5;
        }
    });
    
    return currentY + 5; // Spazio dopo la sezione
}

function addVehiclesSection(pdf, vehicles, startY, margin, contentWidth) {
    let currentY = startY;
    
    pdf.setFontSize(12);
    pdf.setFont(undefined, 'bold');
    pdf.text('VEICOLI E CONDUCENTI', margin, currentY);
    currentY += 8;
    
    vehicles.forEach((veicolo, index) => {
        pdf.setFontSize(11);
        pdf.setFont(undefined, 'bold');
        pdf.text(`Veicolo ${String.fromCharCode(65 + index)}`, margin + 5, currentY);
        currentY += 6;
        
        pdf.setFontSize(10);
        pdf.setFont(undefined, 'normal');
        
        // Dati veicolo
        if (veicolo.tipo) {
            pdf.text(`Tipo: ${veicolo.tipo}`, margin + 10, currentY);
            currentY += 5;
        }
        if (veicolo.targa) {
            pdf.text(`Targa: ${veicolo.targa}`, margin + 10, currentY);
            currentY += 5;
        }
        if (veicolo.anno) {
            pdf.text(`Anno immatricolazione: ${veicolo.anno}`, margin + 10, currentY);
            currentY += 5;
        }
        
        // Dati conducente
        if (hasData(veicolo.conducente)) {
            pdf.setFont(undefined, 'bold');
            pdf.text('Conducente:', margin + 10, currentY);
            currentY += 5;
            
            pdf.setFont(undefined, 'normal');
            Object.entries(veicolo.conducente).forEach(([key, value]) => {
                if (value && value.trim() !== '' && value !== 'Seleziona') {
                    pdf.text(`${formatLabel(key)}: ${value}`, margin + 15, currentY);
                    currentY += 5;
                }
            });
        }
        
        currentY += 3; // Spazio tra veicoli
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
        latitudine: 'Latitudine',
        longitudine: 'Longitudine',
        natura_incidente: 'Natura Incidente',
        dettaglio_natura: 'Dettaglio',
        numero_veicoli: 'Numero Veicoli',
        numero_morti: 'Numero Morti',
        numero_feriti: 'Numero Feriti',
        circostanza_veicolo_a: 'Circostanza Veicolo A',
        circostanza_veicolo_b: 'Circostanza Veicolo B',
        difetto_veicolo_a: 'Difetto Veicolo A',
        stato_psicofisico_a: 'Stato Psicofisico A',
        eta: 'Età',
        sesso: 'Sesso',
        esito: 'Esito',
        nazionalita: 'Nazionalità'
    };
    
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}