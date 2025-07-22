// File corretto per interfaccia PDF - solo server-side
jQuery(document).ready(function($) {
    $('#stampa-incidente-pdf').on('click', function() {
        var button = $(this);
        var loading = $('#pdf-loading');
        var success = $('#pdf-success');
        var error = $('#pdf-error');
        
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
            success: function(response) {
                console.log('Risposta ricevuta:', response);
                
                loading.hide();
                button.prop('disabled', false);
                
                if (response.success) {
                    success.show();
                    
                    // Auto-download del PDF se disponibile
                    if (response.data && response.data.download_url) {
                        console.log('Avvio download:', response.data.download_url);
                        
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename || 'incidente.pdf';
                        link.target = '_blank'; // Apri in nuova finestra come fallback
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        console.warn('URL download non trovato nella risposta');
                    }
                } else {
                    error.show();
                    console.error('Errore PDF:', response.data || response);
                    
                    // Mostra errore più dettagliato se disponibile
                    if (response.data && typeof response.data === 'string') {
                        error.find('span:last').text('Errore: ' + response.data);
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
                error.find('span:last').text('Errore di comunicazione: ' + (errorThrown || status));
            }
        });
    });
});