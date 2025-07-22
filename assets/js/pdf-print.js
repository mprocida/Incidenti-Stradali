/**
 * Script per la gestione della stampa PDF degli incidenti stradali
 * File: assets/js/pdf-print.js
 */

jQuery(document).ready(function($) {
    
    // Gestione click del pulsante PDF
    $('#stampa-incidente-pdf').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var loadingDiv = $('#pdf-loading');
        var successDiv = $('#pdf-success');
        var errorDiv = $('#pdf-error');
        
        // Reset dello stato
        successDiv.hide();
        errorDiv.hide();
        
        // Disabilita il pulsante e mostra loading
        button.prop('disabled', true).text('Generazione in corso...');
        loadingDiv.show();
        
        // Chiamata AJAX
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
                console.log('Risposta server:', response);
                
                if (response.success && response.data.download_url) {
                    // Successo - mostra messaggio e inizia download
                    successDiv.show();
                    
                    // Crea link temporaneo per il download
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename || 'incidente.pdf';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Nascondi il messaggio di successo dopo 5 secondi
                    setTimeout(function() {
                        successDiv.fadeOut();
                    }, 5000);
                    
                } else {
                    // Errore dal server
                    var errorMessage = response.data || 'Errore sconosciuto nella generazione del PDF';
                    console.error('Errore PDF:', errorMessage);
                    
                    errorDiv.find('span').last().text('Errore: ' + errorMessage);
                    errorDiv.show();
                }
            },
            
            error: function(xhr, textStatus, errorThrown) {
                console.error('Errore AJAX:', textStatus, errorThrown);
                console.error('Risposta server:', xhr.responseText);
                
                var errorMessage = 'Errore di comunicazione con il server';
                
                if (textStatus === 'timeout') {
                    errorMessage = 'La generazione del PDF sta richiedendo troppo tempo. Riprova.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Errore interno del server. Controlla i log.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Non hai i permessi per generare questo PDF.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Problema di connessione. Verifica la tua connessione internet.';
                }
                
                errorDiv.find('span').last().text(errorMessage);
                errorDiv.show();
            },
            
            complete: function() {
                // Ripristina sempre il pulsante
                loadingDiv.hide();
                button.prop('disabled', false);
                button.html('<span class="dashicons dashicons-media-document" style="margin-right: 5px;"></span>Genera PDF');
            }
        });
    });
    
    // Funzione helper per il debug
    function debugPDFGeneration() {
        if (window.console && console.log) {
            console.log('PDF Generator - Post ID:', incidentiPDF.post_id);
            console.log('PDF Generator - AJAX URL:', incidentiPDF.ajax_url);
            console.log('PDF Generator - Nonce:', incidentiPDF.nonce);
        }
    }
    
    // Avvia debug se necessario
    if (typeof incidentiPDF !== 'undefined') {
        debugPDFGeneration();
    }
    
    // Gestione errori globali per debugging
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('pdf-print.js')) {
            console.error('Errore JavaScript in pdf-print.js:', e.message, 'Riga:', e.lineno);
        }
    });
    
    // Aggiunge informazioni di debug al pulsante (solo in modalità debug)
    if (typeof WP_DEBUG !== 'undefined' && WP_DEBUG) {
        $('#stampa-incidente-pdf').attr('title', 'Post ID: ' + incidentiPDF.post_id);
    }
});

/**
 * Funzione di utilità per verificare se il browser supporta il download
 */
function browserSupportsDownload() {
    var a = document.createElement('a');
    return typeof a.download !== 'undefined';
}

/**
 * Fallback per browser che non supportano download automatico
 */
function openPDFInNewTab(url) {
    window.open(url, '_blank');
}