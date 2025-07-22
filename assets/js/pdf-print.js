// File finale per interfaccia PDF - UNICO HANDLER
jQuery(document).ready(function($) {
    // Assicurati che ci sia un solo event listener
    $('#stampa-incidente-pdf').off('click').on('click', function() {
        var button = $(this);
        var loading = $('#pdf-loading');
        var success = $('#pdf-success');
        var error = $('#pdf-error');
        
        // Previeni multiple chiamate
        if (button.prop('disabled')) {
            console.log('Bottone già disabilitato, ignoro il click');
            return false;
        }
        
        // Reset stati
        success.hide();
        error.hide();
        loading.show();
        button.prop('disabled', true);
        
        // Debug
        console.log('Avvio generazione PDF...', {
            ajax_url: incidentiPDF.ajax_url,
            post_id: incidentiPDF.post_id
        });
        
        // Chiamata AJAX al server per generazione PDF
        $.ajax({
            url: incidentiPDF.ajax_url,
            type: 'POST',
            data: {
                action: 'print_incidente_pdf',
                security: incidentiPDF.nonce,
                post_id: incidentiPDF.post_id
            },
            timeout: 30000, // 30 secondi di timeout
            success: function(response) {
                console.log('Risposta ricevuta:', response);
                
                loading.hide();
                button.prop('disabled', false);
                
                if (response.success) {
                    success.show();
                    
                    // Auto-download del PDF se disponibile
                    if (response.data && response.data.download_url) {
                        console.log('Avvio download:', response.data.download_url);
                        
                        // Attendi un momento prima del download per mostrare il messaggio di successo
                        setTimeout(function() {
                            var link = document.createElement('a');
                            link.href = response.data.download_url;
                            link.download = response.data.filename || 'incidente.pdf';
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            console.log('Download completato');
                        }, 500);
                    } else {
                        console.warn('URL download non trovato nella risposta');
                    }
                } else {
                    error.show();
                    console.error('Errore PDF:', response.data || response);
                    
                    // Mostra errore più dettagliato se disponibile
                    if (response.data && typeof response.data === 'string') {
                        var errorSpan = error.find('span:last');
                        if (errorSpan.length) {
                            errorSpan.text('Errore: ' + response.data);
                        }
                    }
                }
            },
            error: function(xhr, status, errorThrown) {
                console.error('Errore AJAX:', {
                    status: status,
                    error: errorThrown,
                    response: xhr.responseText
                });
                
                loading.hide();
                button.prop('disabled', false);
                error.show();
                
                // Mostra errore più specifico
                var errorSpan = error.find('span:last');
                if (errorSpan.length) {
                    errorSpan.text('Errore di comunicazione: ' + (errorThrown || status));
                }
            }
        });
        
        return false; // Previeni comportamenti predefiniti
    });
});