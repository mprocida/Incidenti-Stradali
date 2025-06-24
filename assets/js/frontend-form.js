(function($) {
    'use strict';
    
    // Inizializza il form quando il documento è pronto
    $(document).ready(function() {
        initFrontendForm();
    });
    
    function initFrontendForm() {
        // Inizializza mappa se presente
        if ($('#frontend-map').length) {
            initFrontendMap();
        }
        
        // Gestione submit form
        $('#incidente-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateFrontendForm()) {
                return false;
            }
            
            submitIncidenteForm();
        });
        
        // Validazione in tempo reale
        setupRealTimeValidation();
    }
    
    function initFrontendMap() {
        var map = L.map('frontend-map').setView([41.9028, 12.4964], 10);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        var marker = null;
        
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            
            marker = L.marker(e.latlng).addTo(map);
            $('#latitudine').val(e.latlng.lat);
            $('#longitudine').val(e.latlng.lng);
            
            // Feedback visivo
            showFormMessage('Posizione selezionata: ' + e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6), 'info');
        });
        
        // Salva riferimento globale per reset
        window.frontendMap = map;
        window.frontendMapMarker = null;
        
        // Try to get user location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 13);
            });
        }
    }
    
    function setupRealTimeValidation() {
        // Validazione in tempo reale per campi obbligatori
        $('input[required], select[required]').on('blur', function() {
            validateSingleField($(this));
        });
        
        // Controllo data non futura
        $('#data_incidente').on('change', function() {
            var selectedDate = new Date($(this).val());
            var today = new Date();
            today.setHours(23, 59, 59, 999); // Fine giornata
            
            if (selectedDate > today) {
                $(this).addClass('error');
                showFormMessage('La data dell\'incidente non può essere futura', 'error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Controllo numeri non negativi
        $('input[type="number"]').on('input', function() {
            var value = parseInt($(this).val());
            if (value < 0) {
                $(this).val(0);
            }
        });
    }
    
    function validateSingleField($field) {
        var isValid = true;
        var fieldName = $field.attr('name');
        
        if ($field.prop('required') && !$field.val().trim()) {
            isValid = false;
            $field.addClass('error');
        } else {
            $field.removeClass('error');
        }
        
        return isValid;
    }
    
    function validateFrontendForm() {
        var isValid = true;
        var errorMessages = [];
        
        // Reset errori precedenti
        $('.form-row input, .form-row select').removeClass('error');
        
        // Campi obbligatori
        var requiredFields = [
            { id: 'data_incidente', label: 'Data incidente' },
            { id: 'ora_incidente', label: 'Ora incidente' },
            { id: 'provincia_incidente', label: 'Provincia' },
            { id: 'comune_incidente', label: 'Comune' },
            { id: 'natura_incidente', label: 'Natura incidente' }
        ];
        
        requiredFields.forEach(function(field) {
            var $field = $('#' + field.id);
            if (!$field.val().trim()) {
                isValid = false;
                $field.addClass('error');
                errorMessages.push('Il campo "' + field.label + '" è obbligatorio');
            }
        });
        
        // Verifica coordinate
        if (!$('#latitudine').val() || !$('#longitudine').val()) {
            isValid = false;
            $('#frontend-map').css('border-color', '#dc3232');
            errorMessages.push('Seleziona un punto sulla mappa cliccando su di essa');
        } else {
            $('#frontend-map').css('border-color', '#ddd');
        }
        
        // Verifica data non futura
        var dataIncidente = new Date($('#data_incidente').val());
        var oggi = new Date();
        if (dataIncidente > oggi) {
            isValid = false;
            $('#data_incidente').addClass('error');
            errorMessages.push('La data dell\'incidente non può essere futura');
        }
        
        // Verifica provincia (formato ISTAT)
        var provincia = $('#provincia_incidente').val().trim();
        if (provincia && (provincia.length !== 3 || !/^\d{3}$/.test(provincia))) {
            isValid = false;
            $('#provincia_incidente').addClass('error');
            errorMessages.push('La provincia deve essere un codice ISTAT di 3 cifre (es. 058 per Roma)');
        }
        
        if (!isValid) {
            showFormMessage(errorMessages.join('<br>'), 'error');
            // Scroll al primo errore
            var $firstError = $('.error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
        
        return isValid;
    }
    
    function submitIncidenteForm() {
        var $form = $('#incidente-form');
        var $submitBtn = $('#submit-incidente');
        var $loading = $('#form-loading');
        
        // Disabilita form durante invio
        $submitBtn.prop('disabled', true);
        $loading.show();
        $('#form-messages').empty();
        
        // Prepara dati
        var formData = $form.serialize() + '&action=submit_incidente_frontend';
        
        $.ajax({
            url: incidenti_ajax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000, // 30 secondi timeout
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    
                    if (data.success) {
                        showFormMessage(data.message, 'success');
                        resetForm();
                        
                        // Scroll to success message
                        $('html, body').animate({
                            scrollTop: $('#form-messages').offset().top - 50
                        }, 500);
                        
                    } else {
                        showFormMessage(data.message || 'Errore durante l\'invio', 'error');
                    }
                } catch (e) {
                    console.error('Errore parsing JSON:', e);
                    showFormMessage('Errore nella risposta del server', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore AJAX:', status, error);
                
                if (status === 'timeout') {
                    showFormMessage('Timeout: il server sta impiegando troppo tempo a rispondere. Riprova più tardi.', 'error');
                } else {
                    showFormMessage('Errore di connessione. Verifica la tua connessione internet e riprova.', 'error');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $loading.hide();
            }
        });
    }
    
    function resetForm() {
        var $form = $('#incidente-form');
        
        // Reset form fields
        $form[0].reset();
        $('#latitudine, #longitudine').val('');
        
        // Reset validation styles
        $('.form-row input, .form-row select').removeClass('error');
        $('#frontend-map').css('border-color', '#ddd');
        
        // Reset mappa
        if (window.frontendMap && window.frontendMapMarker) {
            window.frontendMap.removeLayer(window.frontendMapMarker);
            window.frontendMapMarker = null;
        }
        
        // Reset alla posizione iniziale
        if (window.frontendMap) {
            window.frontendMap.setView([41.9028, 12.4964], 10);
        }
    }
    
    function showFormMessage(message, type) {
        var $messages = $('#form-messages');
        var cssClass = 'success-message';
        var icon = '✓';
        
        switch(type) {
            case 'error':
                cssClass = 'error-message';
                icon = '✗';
                break;
            case 'info':
                cssClass = 'info-message';
                icon = 'ℹ';
                break;
            case 'warning':
                cssClass = 'warning-message';
                icon = '⚠';
                break;
        }
        
        var html = '<div class="form-message ' + cssClass + '">' +
                   '<span class="message-icon">' + icon + '</span>' +
                   '<span class="message-text">' + message + '</span>' +
                   '</div>';
        
        $messages.html(html);
        
        // Auto-hide success and info messages
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $messages.fadeOut(500, function() {
                    $messages.empty().show();
                });
            }, type === 'success' ? 5000 : 3000);
        }
    }
    
    // Esporta funzioni per uso esterno se necessario
    window.IncidentiFrontendForm = {
        reset: resetForm,
        validate: validateFrontendForm,
        showMessage: showFormMessage
    };
    
})(jQuery);