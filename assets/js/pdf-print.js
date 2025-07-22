// File semplificato per interfaccia PDF
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
        
        // Chiamata AJAX semplificata
        $.ajax({
            url: incidentiPDF.ajax_url,
            type: 'POST',
            data: {
                action: 'print_incidente_pdf',
                security: incidentiPDF.nonce,
                post_id: incidentiPDF.post_id
            },
            success: function(response) {
                loading.hide();
                button.prop('disabled', false);
                
                if (response.success) {
                    success.show();
                    
                    // Auto-download del PDF
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    error.show();
                    console.error('Errore PDF:', response.data);
                }
            },
            error: function(xhr, status, errorThrown) {
                loading.hide();
                button.prop('disabled', false);
                error.show();
                console.error('Errore AJAX:', errorThrown);
            }
        });
    });
});