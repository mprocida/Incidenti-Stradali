/**
 * Admin JavaScript for Incidenti Stradali Plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    var validationTimeout;
    var coordinateMap;
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        initializeFieldValidation();
        initializeFieldDependencies();
        initializeCoordinateMap();
        initializeDatePickers();
        initializeFormSections();
        initializeBulkActions();
        initializeExportFunctions();
        initializeTooltips();
        initializeTransportatiSections();
        initializeCircostanzeFields();
        initializeConditionalFields();
    }
    
    /**
     * Initialize real-time field validation
     */
    function initializeFieldValidation() {
        // Required fields validation
        $('input[required], select[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Real-time validation for specific fields
        $('#data_incidente').on('change', function() {
            validateDate($(this));
        });
        
        $('#ora_incidente').on('change', function() {
            validateHour($(this));
        });
        
        $('#provincia_incidente, #comune_incidente').on('input', function() {
            clearTimeout(validationTimeout);
            var $field = $(this);
            
            validationTimeout = setTimeout(function() {
                validateIstatCode($field);
            }, 500);
        });
        
        $('#latitudine').on('input', function() {
            validateLatitude($(this));
        });
        
        $('#longitudine').on('input', function() {
            validateLongitude($(this));
        });
        
        // Form submission validation
        $('#post').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showValidationSummary();
            }
        });
    }
    
    /**
     * Validate individual field
     */
    function validateField($field) {
        var fieldName = $field.attr('name');
        var value = $field.val();
        var isValid = true;
        var message = '';
        
        // Remove previous validation styling
        $field.removeClass('incidenti-validation-error');
        $field.siblings('.incidenti-field-error').remove();
        
        // Required field check
        if ($field.prop('required') && !value.trim()) {
            isValid = false;
            message = 'Questo campo è obbligatorio.';
        }
        
        // Field-specific validation
        switch (fieldName) {
            case 'data_incidente':
                if (value && !isValidDate(value)) {
                    isValid = false;
                    message = 'Formato data non valido.';
                }
                break;
                
            case 'ora_incidente':
                var hour = parseInt(value);
                if (value && (hour < 0 || hour > 24)) {
                    isValid = false;
                    message = 'L\'ora deve essere compresa tra 0 e 24.';
                }
                break;
                
            case 'provincia_incidente':
            case 'comune_incidente':
                if (value && !isValidIstatCode(value, 3)) {
                    isValid = false;
                    message = 'Codice ISTAT deve essere di 3 cifre.';
                }
                break;
        }
        
        // Show validation result
        if (!isValid) {
            $field.addClass('incidenti-validation-error');
            $field.after('<span class="incidenti-field-error">' + message + '</span>');
        }
        
        return isValid;
    }
    
    /**
     * Initialize field dependencies
     */
    function initializeFieldDependencies() {
        // Natura incidente -> Dettaglio natura
        $('#natura_incidente').on('change', function() {
            updateDettaglioNatura($(this).val());
        });
        
        // Numero veicoli -> Sezioni veicoli
        $('#numero_veicoli_coinvolti').on('change', function() {
            updateVeicoliSections(parseInt($(this).val()) || 1);
        });
        
        // Numero pedoni -> Sezioni pedoni
        $('#numero_pedoni_coinvolti').on('change', function() {
            updatePedoniSections(parseInt($(this).val()) || 0);
        });
        
        // Periodo filtro -> Date personalizzate
        $('[id$="-periodo-filter"]').on('change', function() {
            var $customDates = $(this).closest('.incidenti-map-filters').find('.custom-dates');
            
            if ($(this).val() === 'custom') {
                $customDates.show();
            } else {
                $customDates.hide();
            }
        });
        
        // Trigger initial state
        $('#natura_incidente').trigger('change');
    }

    function initializeTransportatiSections() {
        // Per ogni veicolo
        for (var v = 1; v <= 3; v++) {
            (function(vehicleNum) {
                $('#veicolo_' + vehicleNum + '_numero_trasportati').on('change', function() {
                    var numTrasportati = parseInt($(this).val()) || 0;
                    
                    for (var t = 1; t <= 4; t++) {
                        if (t <= numTrasportati) {
                            $('#trasportato-' + vehicleNum + '-' + t).show();
                        } else {
                            $('#trasportato-' + vehicleNum + '-' + t).hide();
                            // Clear hidden fields
                            $('#trasportato-' + vehicleNum + '-' + t).find('input, select').val('');
                        }
                    }
                });
                
                // Trigger on load
                $('#veicolo_' + vehicleNum + '_numero_trasportati').trigger('change');
            })(v);
        }
        // Gestione selezione sedile e controllo esclusività sedile anteriore
        $('select[name*="_sedile"]').on('change', function() {
            var $select = $(this);
            var veicolo = $select.data('veicolo');
            var trasportato = $select.data('trasportato');
            var valore = $select.val();
            var prefix = 'veicolo_' + veicolo + '_trasportato_' + trasportato + '_';
            
            // Mostra/nascondi campo dettaglio
            if (valore === 'anteriore' || valore === 'posteriore') {
                $('#' + prefix + 'dettaglio_sedile_row').show();
                
                // Aggiorna label del dettaglio
                var labelText = valore === 'anteriore' ? 
                    'Dettaglio sedile anteriore:' : 
                    'Dettaglio sedile posteriore:';
                $('#' + prefix + 'dettaglio_label').text(labelText);
                
                // Controllo esclusività sedile anteriore
                if (valore === 'anteriore') {
                    controllaEsclusivitaSedileAnteriore(veicolo, trasportato);
                }
            } else {
                $('#' + prefix + 'dettaglio_sedile_row').hide();
                $('#' + prefix + 'dettaglio_sedile').val('');
            }
        });

        // Funzione per controllare l'esclusività del sedile anteriore
        function controllaEsclusivitaSedileAnteriore(veicolo, trasportatoCorrente) {
            var selectsAnteriori = $('select[data-veicolo="' + veicolo + '"][name*="_sedile"] option[value="anteriore"]:selected');
            
            if (selectsAnteriori.length > 1) {
                // Se più di un trasportato ha selezionato il sedile anteriore
                alert('ATTENZIONE: Nel sedile anteriore può esserci solo il passeggero oltre al conducente. ' +
                    'Gli altri trasportati devono essere nei sedili posteriori.');
                
                // Reset delle selezioni precedenti (tranne quella corrente)
                selectsAnteriori.each(function() {
                    var $option = $(this);
                    var $select = $option.parent();
                    var currentTrasportato = $select.data('trasportato');
                    
                    if (currentTrasportato != trasportatoCorrente) {
                        $select.val('').trigger('change');
                    }
                });
            }
        }

        // Trigger per inizializzare lo stato dei campi al caricamento
        $('select[name*="_sedile"]').trigger('change');
    }

    // Funzione per le circostanze
    function initializeCircostanzeFields() {
        // Carica dinamicamente le opzioni delle circostanze basate sulla natura dell'incidente
        $('#natura_incidente').on('change', function() {
            var natura = $(this).val();
            updateCircostanzeOptions(natura);
        });
        
        // Helper per aggiornare le opzioni delle circostanze
        function updateCircostanzeOptions(natura) {
            // Qui potresti caricare via AJAX le circostanze appropriate
            // basate sulla natura dell'incidente selezionata
        }
    }
    
    /**
     * Update dettaglio natura options
     */
    function updateDettaglioNatura(natura) {
        var $dettaglio = $('#dettaglio_natura');
        var options = {
            'A': {
                '1': 'Scontro frontale',
                '2': 'Scontro frontale-laterale',
                '3': 'Scontro laterale',
                '4': 'Tamponamento',
                '5': 'Salto carreggiata'
            },
            'B': {
                '5': 'Investimento di pedoni'
            },
            'C': {
                'frontale': 'Urto frontale',
                'laterale': 'Urto laterale',
                '6': 'Urto con veicolo in fermata o in arresto',
                '7': 'Urto con veicolo in sosta',
                '8': 'Urto con ostacolo',
                '9': 'Urto con treno'
            },
            'D': {
                '10': 'Fuoriuscita (sbandamento, ...)',
                '11': 'Infortunio per frenata improvvisa',
                '12': 'Infortunio per caduta da veicolo'
            }
        };
        
        $dettaglio.empty().append('<option value="">Seleziona dettaglio</option>');
        
        if (natura && options[natura]) {
            $.each(options[natura], function(value, text) {
                $dettaglio.append('<option value="' + value + '">' + text + '</option>');
            });
        }
        
        // Show/hide numero veicoli based on natura
        var $numeroVeicoli = $('#numero_veicoli_row');
        if (natura === 'A' || (natura === 'C')) {
            $numeroVeicoli.show();
        } else {
            $numeroVeicoli.hide();
            $('#numero_veicoli_coinvolti').val('1');
        }
    }
    
    /**
     * Update veicoli sections visibility
     */
    function updateVeicoliSections(numVeicoli) {
        for (var i = 1; i <= 3; i++) {
            var $section = $('#veicolo-' + i + ', #conducente-' + i);
            
            if (i <= numVeicoli) {
                $section.show();
            } else {
                $section.hide();
                // Clear hidden fields
                $section.find('input, select').val('');
            }
        }
    }
    
    /**
     * Update pedoni sections visibility
     */
    function updatePedoniSections(numPedoni) {
        for (var i = 1; i <= 4; i++) {
            var $section = $('#pedone-' + i);
            
            if (i <= numPedoni) {
                $section.show();
            } else {
                $section.hide();
                // Clear hidden fields
                $section.find('input, select').val('');
            }
        }
    }
    
    /**
     * Initialize coordinate map
     */
    function initializeCoordinateMap() {
        var $mapDiv = $('#coordinate-map');
        
        if ($mapDiv.length === 0) {
            return;
        }
        
        // Initialize map
        var lat = parseFloat($('#latitudine').val()) || 41.9028;
        var lng = parseFloat($('#longitudine').val()) || 12.4964;
        
        coordinateMap = L.map('coordinate-map').setView([lat, lng], lat === 41.9028 ? 6 : 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(coordinateMap);
        
        var marker = null;
        
        // Add existing marker if coordinates are set
        if ($('#latitudine').val() && $('#longitudine').val()) {
            marker = L.marker([lat, lng]).addTo(coordinateMap);
        }
        
        // Click handler to set coordinates
        coordinateMap.on('click', function(e) {
            var newLat = e.latlng.lat;
            var newLng = e.latlng.lng;
            
            $('#latitudine').val(newLat.toFixed(6));
            $('#longitudine').val(newLng.toFixed(6));
            
            if (marker) {
                coordinateMap.removeLayer(marker);
            }
            
            marker = L.marker([newLat, newLng]).addTo(coordinateMap);
            
            // Trigger validation
            validateLatitude($('#latitudine'));
            validateLongitude($('#longitudine'));
        });
        
        // Update marker when coordinates change manually
        $('#latitudine, #longitudine').on('change', function() {
            var newLat = parseFloat($('#latitudine').val());
            var newLng = parseFloat($('#longitudine').val());
            
            if (newLat && newLng) {
                if (marker) {
                    coordinateMap.removeLayer(marker);
                }
                
                marker = L.marker([newLat, newLng]).addTo(coordinateMap);
                coordinateMap.setView([newLat, newLng], 15);
            }
        });
    }
    
    /**
     * Initialize date pickers
     */
    function initializeDatePickers() {
        if ($.datepicker) {
            $('.incidenti-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1950:' + new Date().getFullYear()
            });
        }
    }
    
    /**
     * Initialize collapsible form sections
     */
    function initializeFormSections() {
        $('.incidenti-toggle-header').on('click', function() {
            var $header = $(this);
            var $content = $header.next('.incidenti-toggle-content');
            var $arrow = $header.find('.incidenti-toggle-arrow');
            
            $content.slideToggle();
            $arrow.toggleClass('open');
        });
    }
    
    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        // Handle bulk export actions
        $('select[name="action"], select[name="action2"]').on('change', function() {
            var action = $(this).val();
            
            if (action === 'export_istat' || action === 'export_excel') {
                // Show confirmation dialog
                if (!confirm('Sei sicuro di voler esportare gli elementi selezionati?')) {
                    $(this).val('-1');
                }
            }
        });
    }

    /**
     * Initialize conditional field logic for ISTAT 2019 compliance
     */
    function initializeConditionalFields() {
        // Mostra/nascondi identificativo comando per Carabinieri
        $('#organo_rilevazione').on('change', function() {
            var organoValue = $(this).val();
            var $identificativoRow = $('#identificativo_comando_row');
            var $identificativoField = $('#identificativo_comando');
            
            if (organoValue === '2') { // Carabiniere
                $identificativoRow.show();
                $identificativoField.prop('required', true);
            } else {
                $identificativoRow.hide();
                $identificativoField.prop('required', false).val('');
            }
        }).trigger('change'); // Trigger immediato per inizializzazione
        
        // Logica per tipo strada e numero strada
        $('#tipo_strada').on('change', function() {
            var tipoStrada = $(this).val();
            var $numeroStradaRow = $('#numero_strada_row');
            var $numeroStradaField = $('#numero_strada');
            var $progressivaRow = $('#progressiva_row');
            
            // Tipi di strada che richiedono il numero strada
            var tipiConNumero = ['2', '3', '5', '6'];
            
            if (tipiConNumero.includes(tipoStrada)) {
                $numeroStradaRow.show();
                $numeroStradaField.prop('required', true);
            } else {
                $numeroStradaRow.hide();
                $numeroStradaField.prop('required', false).val('');
            }
            
            // RIMOSSO: Progressiva chilometrica ora sempre visibile
            // var tipiExtraurbani = ['5', '6', '7', '9']; // Fuori dall'abitato
            // if (tipiExtraurbani.includes(tipoStrada)) {
            //     $progressivaRow.show();
            //     $('#progressiva_km, #progressiva_m').prop('required', true);
            // } else {
            //     $progressivaRow.hide();
            //     $('#progressiva_km, #progressiva_m').prop('required', false).val('');
            // }

            // La progressiva chilometrica è sempre visibile
            $progressivaRow.show();

        }).trigger('change');
        
        // Validazione progressiva chilometrica
        $('#progressiva_km, #progressiva_m').on('input', function() {
            validateProgressiva();
        });
        
        // Logica per circostanze incidente - mostra campi appropriati
        $('.circostanza-select').on('change', function() {
            updateCircostanzeLogic();
        });
        
        // Logica per tipo veicolo e campi correlati
        $('[id^="tipo_veicolo_"]').on('change', function() {
            var veicoloIndex = $(this).attr('id').split('_').pop();
            updateVehicleFields(veicoloIndex);
        });
    }

    /**
     * Validate progressiva chilometrica fields
     */
    function validateProgressiva() {
        var $kmField = $('#progressiva_km');
        var $mField = $('#progressiva_m');
        var km = parseInt($kmField.val()) || 0;
        var m = parseInt($mField.val()) || 0;
        
        var isValidKm = km >= 0 && km <= 9999;
        var isValidM = m >= 0 && m <= 999;
        
        // Visual feedback
        $kmField.toggleClass('error', !isValidKm);
        $mField.toggleClass('error', !isValidM);
        
        // Show/hide error messages
        var $errorMsg = $('.progressiva-error');
        if (!isValidKm || !isValidM) {
            if ($errorMsg.length === 0) {
                $('#progressiva_row').append('<p class="description error progressiva-error">Valori non validi: Km deve essere compreso tra 0 e 9999, Mt tra 0 e 999</p>');
            }
        } else {
            $errorMsg.remove();
        }
        
        return isValidKm && isValidM;
    }

    /**
     * Update vehicle-specific fields based on vehicle type
     */
    function updateVehicleFields(index) {
        var $tipoVeicolo = $('#tipo_veicolo_' + index);
        var tipoValue = $tipoVeicolo.val();
        var $cilindrata = $('#cilindrata_veicolo_' + index);
        var $peso = $('#peso_pieno_carico_' + index);
        
        // Mostra cilindrata per veicoli a motore
        var veicoliConCilindrata = ['1', '2', '3', '4', '5', '7', '8', '11', '12', '14', '15', '16'];
        if (veicoliConCilindrata.includes(tipoValue)) {
            $cilindrata.closest('tr').show();
            $cilindrata.prop('required', true);
        } else {
            $cilindrata.closest('tr').hide();
            $cilindrata.prop('required', false).val('');
        }
        
        // Mostra peso per veicoli trasporto merci
        var veicoliTrasportoMerci = ['11', '12', '13', '17', '18', '19'];
        if (veicoliTrasportoMerci.includes(tipoValue)) {
            $peso.closest('tr').show();
            $peso.prop('required', true);
        } else {
            $peso.closest('tr').hide();
            $peso.prop('required', false).val('');
        }
    }

    // Validazione cilindrata: solo numeri, massimo 5 cifre per tutti i veicoli
    $('[id^="cilindrata_veicolo_"]').on('input', function() {
        var value = $(this).val();
        // Rimuovi caratteri non numerici
        value = value.replace(/[^0-9]/g, '');
        // Limita a 5 cifre
        if (value.length > 5) {
            value = value.substring(0, 5);
        }
        $(this).val(value);
    });

    /**
     * Update circostanze logic based on incident nature
     */
    function updateCircostanzeLogic() {
        var naturaIncidente = $('#natura_incidente').val();
        
        // Logica specifica per diversi tipi di natura incidente
        // Personalizzabile in base alle esigenze specifiche
        console.log('Natura incidente cambiata:', naturaIncidente);
    }
    
    /**
     * Initialize export functions
     */
    function initializeExportFunctions() {
        // Export form submissions
        $('form[action*="export_incidenti"]').on('submit', function() {
            var $form = $(this);
            var $button = $form.find('input[type="submit"]');
            
            $button.prop('disabled', true).val('Esportazione in corso...');
            
            // Re-enable button after delay (in case of errors)
            setTimeout(function() {
                $button.prop('disabled', false).val('Esporta');
            }, 10000);
        });
        
        // Download progress simulation
        $('a[href*="download_export"]').on('click', function() {
            var $link = $(this);
            $link.text('Download in corso...');
            
            setTimeout(function() {
                $link.text('Scarica Export');
            }, 3000);
        });
    }
    
    /**
     * Initialize tooltips and help text
     */
    function initializeTooltips() {
        // Add help icons with tooltips
        $('[data-help]').each(function() {
            var $element = $(this);
            var helpText = $element.data('help');
            
            $element.after('<span class="incidenti-help-icon" title="' + helpText + '">?</span>');
        });
        
        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('.incidenti-help-icon').tooltip();
        }
    }
    
    /**
     * Validation helper functions
     */
    function isValidDate(dateString) {
        var regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateString)) return false;
        
        var date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }
    
    function isValidIstatCode(code, length) {
        var regex = new RegExp('^\\d{' + length + '}$');
        return regex.test(code);
    }
    
    function validateDate($field) {
        var value = $field.val();
        var isValid = !value || isValidDate(value);
        
        updateFieldValidation($field, isValid, 'Formato data non valido (YYYY-MM-DD)');
        return isValid;
    }
    
    function validateHour($field) {
        var value = parseInt($field.val());
        var isValid = !$field.val() || (value >= 0 && value <= 24);
        
        updateFieldValidation($field, isValid, 'L\'ora deve essere compresa tra 0 e 24');
        return isValid;
    }
    
    function validateIstatCode($field) {
        var value = $field.val();
        var length = $field.attr('name').includes('provincia') ? 3 : 3;
        var isValid = !value || isValidIstatCode(value, length);
        
        updateFieldValidation($field, isValid, 'Codice ISTAT deve essere di ' + length + ' cifre');
        return isValid;
    }
    
    function validateLatitude($field) {
        var value = parseFloat($field.val());
        var isValid = !$field.val() || (value >= -90 && value <= 90);
        
        updateFieldValidation($field, isValid, 'Latitudine deve essere tra -90 e 90');
        return isValid;
    }
    
    function validateLongitude($field) {
        var value = parseFloat($field.val());
        var isValid = !$field.val() || (value >= -180 && value <= 180);
        
        updateFieldValidation($field, isValid, 'Longitudine deve essere tra -180 e 180');
        return isValid;
    }

    function validateRiepilogo() {
        // Conta i reali morti e feriti dai campi esistenti
        var realMorti24h = 0;
        var realMorti2_30gg = 0; 
        var realFeriti = 0;
        
        // Conta conducenti
        for (var i = 1; i <= 3; i++) {
            var esito = $('select[name="conducente_' + i + '_esito"]').val();
            if (esito == '3') realMorti24h++;
            if (esito == '4') realMorti2_30gg++;
            if (esito == '2') realFeriti++;
        }
        
        // Conta pedoni
        var numPedoni = parseInt($('input[name="numero_pedoni_coinvolti"]').val()) || 0;
        for (var i = 1; i <= numPedoni; i++) {
            var esito = $('select[name="pedone_' + i + '_esito"]').val();
            if (esito == '3') realMorti24h++;
            if (esito == '4') realMorti2_30gg++;
            if (esito == '2') realFeriti++;
        }
        
        // Conta passeggeri (se implementato)
        $('.passeggero-esito').each(function() {
            var esito = $(this).val();
            if (esito == '3') realMorti24h++;
            if (esito == '4') realMorti2_30gg++;
            if (esito == '2') realFeriti++;
        });
        
        // Leggi valori riepilogo
        var riepilogoMorti24h = parseInt($('#riepilogo_morti_24h').val()) || 0;
        var riepilogoMorti2_30gg = parseInt($('#riepilogo_morti_2_30gg').val()) || 0;
        var riepilogoFeriti = parseInt($('#riepilogo_feriti').val()) || 0;
        
        // Validazione
        var isValid = true;
        var message = '';
        
        if (realMorti24h !== riepilogoMorti24h) {
            isValid = false;
            message += 'Morti 24h: rilevati ' + realMorti24h + ', inseriti ' + riepilogoMorti24h + '. ';
        }
        
        if (realMorti2_30gg !== riepilogoMorti2_30gg) {
            isValid = false;
            message += 'Morti 2°-30° gg: rilevati ' + realMorti2_30gg + ', inseriti ' + riepilogoMorti2_30gg + '. ';
        }
        
        if (realFeriti !== riepilogoFeriti) {
            isValid = false;
            message += 'Feriti: rilevati ' + realFeriti + ', inseriti ' + riepilogoFeriti + '. ';
        }
        
        // Mostra/nascondi messaggio
        if (isValid) {
            $('#riepilogo-validation-message').hide();
        } else {
            $('#validation-text').text(message);
            $('#riepilogo-validation-message').show();
        }
        
        return isValid;
    }
    
    function updateFieldValidation($field, isValid, message) {
        $field.removeClass('incidenti-validation-error');
        $field.siblings('.incidenti-field-error').remove();
        
        if (!isValid) {
            $field.addClass('incidenti-validation-error');
            $field.after('<span class="incidenti-field-error">' + message + '</span>');
        }
    }
    
    /**
     * Validate entire form
     */
    function validateForm() {
        var isValid = true;
        var $firstError = null;
        
        // Validate required fields
        $('input[required], select[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
                if (!$firstError) {
                    $firstError = $(this);
                }
            }
        });
        
        // Scroll to first error
        if ($firstError) {
            $('html, body').animate({
                scrollTop: $firstError.offset().top - 100
            }, 500);
            $firstError.focus();
        }
        
        return isValid;
    }
    
    /**
     * Show validation summary
     */
    function showValidationSummary() {
        var errors = [];
        
        $('.incidenti-field-error').each(function() {
            errors.push($(this).text());
        });
        
        if (errors.length > 0) {
            var message = 'Correggere i seguenti errori:\n\n' + errors.join('\n');
            alert(message);
        }
    }
    
    /**
     * Auto-save functionality
     */
    function initializeAutoSave() {
        var autoSaveInterval;
        var hasChanges = false;
        
        // Track changes
        $('#post input, #post select, #post textarea').on('change input', function() {
            hasChanges = true;
        });
        
        // Auto-save every 2 minutes if there are changes
        autoSaveInterval = setInterval(function() {
            if (hasChanges && $('#post').length) {
                // Trigger WordPress auto-save
                if (typeof wp !== 'undefined' && wp.autosave) {
                    wp.autosave.server.triggerSave();
                    hasChanges = false;
                }
            }
        }, 120000); // 2 minutes
    }
    
    /**
     * Handle window beforeunload
     */
    function handlePageLeave() {
        var hasUnsavedChanges = false;
        
        $('#post input, #post select, #post textarea').on('change', function() {
            hasUnsavedChanges = true;
        });
        
        $('#post').on('submit', function() {
            hasUnsavedChanges = false;
        });
        
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return 'Ci sono modifiche non salvate. Vuoi davvero uscire?';
            }
        });
    }
    
    /**
     * Initialize dashboard widgets
     */
    function initializeDashboardWidget() {
        // Refresh dashboard stats
        $('.incidenti-dashboard-refresh').on('click', function() {
            var $widget = $(this).closest('.incidenti-dashboard-widget');
            
            $.ajax({
                url: incidenti_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'refresh_incidenti_dashboard',
                    nonce: $(this).data('nonce')
                },
                success: function(response) {
                    if (response.success) {
                        $widget.find('.widget-content').html(response.data);
                    }
                }
            });
        });
    }
    
    // Initialize everything
    initializeAdmin();
    initializeAutoSave();
    handlePageLeave();
    initializeDashboardWidget();
    
    // Expose public functions
    window.IncidentiAdmin = {
        validateForm: validateForm,
        updateVeicoliSections: updateVeicoliSections,
        updatePedoniSections: updatePedoniSections,
        // Add more public methods as needed
    };
});

/**
 * Import functionality - VERSIONE CORRETTA STANDALONE
 */
jQuery(document).ready(function($) {
    'use strict';
    
    // Controlla se siamo nella pagina di import
    if ($('.incidenti-import-page').length) {
        console.log('Pagina import caricata');
        
        // Gestione cambio file
        $('#csv_file').on('change', function() {
            console.log('File cambiato');
            
            var file = this.files[0];
            $('#csv-preview-section').hide();
            
            if (file) {
                console.log('File selezionato:', file.name);
                
                // Abilita il bottone anteprima
                $('#import-preview-btn').prop('disabled', false);
                
                // Disabilita il bottone importa fino alla preview
                $('#import-submit-btn').prop('disabled', true);
            } else {
                console.log('Nessun file selezionato');
                
                // Disabilita entrambi i bottoni
                $('#import-preview-btn').prop('disabled', true);
                $('#import-submit-btn').prop('disabled', true);
            }
        });
        
        // Preview CSV
        $('#import-preview-btn').on('click', function() {
            console.log('Bottone preview cliccato');
            
            var fileInput = $('#csv_file')[0];
            var file = fileInput.files[0];
            var separator = $('#separator').val();
            
            if (!file) {
                alert('Seleziona un file CSV prima di visualizzare l\'anteprima.');
                return;
            }
            
            // Validazione tipo file
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Seleziona un file CSV valido.');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'preview_csv_import');
            formData.append('csv_file', file);
            formData.append('separator', separator);
            formData.append('nonce', $(this).data('nonce'));
            
            // Mostra loading
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Caricamento...');
            
            $.ajax({
                url: incidenti_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Risposta AJAX:', response);
                    
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        displayCSVPreview(response.data);
                        $('#import-submit-btn').prop('disabled', false);
                    } else {
                        alert('Errore durante l\'anteprima: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Errore AJAX:', error);
                    $btn.prop('disabled', false).text(originalText);
                    alert('Errore di connessione durante l\'anteprima.');
                }
            });
        });
        
        // Submit importazione
        $('#import-submit-btn').on('click', function() {
            if (!confirm('Sei sicuro di voler importare questi dati? L\'operazione non può essere annullata.')) {
                return;
            }
            
            $(this).prop('disabled', true).text('Importazione in corso...');
            $('#import-form').submit();
        });
        
        // Reset form
        $('#import-reset-btn').on('click', function() {
            console.log('Reset cliccato');
            
            $('#import-form')[0].reset();
            $('#csv-preview-section').hide();
            $('#import-submit-btn').prop('disabled', true);
            $('#import-preview-btn').prop('disabled', true);
        });
    }
    
    /**
     * Mostra preview del CSV
     */
    function displayCSVPreview(data) {
        console.log('Visualizzando preview:', data);
        
        var $previewSection = $('#csv-preview-section');
        var $previewTable = $('#csv-preview-table tbody');
        
        // Clear previous preview
        $previewTable.empty();
        
        // Show summary
        $('#csv-total-rows').text(data.total_rows || 0);
        $('#csv-valid-rows').text(data.valid_rows || 0);
        $('#csv-error-rows').text(data.error_rows || 0);
        
        // Show first rows
        if (data.preview && data.preview.length > 0) {
            data.preview.forEach(function(row, index) {
                var $row = $('<tr>');
                
                // Add row number
                $row.append('<td>' + (index + 1) + '</td>');
                
                // Add row data
                $row.append('<td>' + (row.data.data_incidente || '') + '</td>');
                $row.append('<td>' + (row.data.ora_incidente || '') + '</td>');
                $row.append('<td>' + (row.data.comune_incidente || '') + '</td>');
                $row.append('<td>' + (row.data.denominazione_strada || '') + '</td>');
                $row.append('<td>' + (row.data.numero_veicoli_coinvolti || '') + '</td>');
                
                // Add status
                var statusClass = row.valid ? 'success' : 'error';
                var statusText = row.valid ? 'Valido' : 'Errore';
                $row.append('<td class="status-' + statusClass + '">' + statusText + '</td>');
                
                // Add errors
                if (row.errors && row.errors.length > 0) {
                    $row.append('<td>' + row.errors.join(', ') + '</td>');
                } else {
                    $row.append('<td>-</td>');
                }
                
                $previewTable.append($row);
            });
        }
        
        // Show errors summary
        if (data.errors && data.errors.length > 0) {
            var $errorsList = $('#csv-errors-list');
            $errorsList.empty();
            
            data.errors.forEach(function(error) {
                $errorsList.append('<li>' + error + '</li>');
            });
            
            $('#csv-errors-section').show();
        } else {
            $('#csv-errors-section').hide();
        }
        
        $previewSection.show();
    }
});

/**
 * Additional admin utilities
 */
(function($) {
    'use strict';
    
    /**
     * Settings page functionality
     */
    if ($('.incidenti-settings-page').length) {
        // Test export path writability
        $('#test-export-path').on('click', function() {
            var path = $('input[name="incidenti_export_path"]').val();
            
            $.ajax({
                url: incidenti_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'test_export_path',
                    path: path,
                    nonce: $(this).data('nonce')
                },
                success: function(response) {
                    var message = response.success ? 
                        'Percorso accessibile in scrittura.' : 
                        'Errore: ' + response.data;
                    
                    alert(message);
                }
            });
        });
    }
    
    function initializeDeleteHandling() {
        // Conferma eliminazione per azioni individuali
        $(document).on('click', 'a.submitdelete', function(e) {
            var href = $(this).attr('href');
            var isDelete = href.includes('action=delete');
            
            var message = isDelete ? 
                'ATTENZIONE: Stai per eliminare DEFINITIVAMENTE questo incidente. Questa azione non può essere annullata. Continuare?' :
                'Sei sicuro di voler spostare questo incidente nel cestino?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Conferma per bulk actions
        $('#doaction, #doaction2').on('click', function(e) {
            var $select = $(this).siblings('select');
            var action = $select.val();
            var selectedItems = $('.wp-list-table input[name="post[]"]:checked').length;
            
            if (selectedItems === 0 && action !== '-1') {
                alert('Seleziona almeno un elemento.');
                e.preventDefault();
                return false;
            }
            
            var confirmMessage = '';
            switch (action) {
                case 'trash':
                    confirmMessage = 'Sei sicuro di voler spostare ' + selectedItems + ' incidenti nel cestino?';
                    break;
                case 'delete':
                    confirmMessage = 'ATTENZIONE: Stai per eliminare DEFINITIVAMENTE ' + selectedItems + ' incidenti. Questa azione non può essere annullata. Continuare?';
                    break;
                case 'untrash':
                    confirmMessage = 'Sei sicuro di voler ripristinare ' + selectedItems + ' incidenti dal cestino?';
                    break;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Debug: Log delle operazioni
        if (typeof console !== 'undefined' && console.log) {
            $('#posts-filter').on('submit', function() {
                var action = $('select[name="action"]').val() || $('select[name="action2"]').val();
                var selectedItems = $('.wp-list-table input[name="post[]"]:checked').length;
                
                if (action && action !== '-1' && selectedItems > 0) {
                    console.log('Bulk action submitted:', {
                        action: action,
                        items: selectedItems,
                        timestamp: new Date().toISOString()
                    });
                }
            });
        }
    }
    
    // Inizializza le funzioni
    initializeDeleteHandling();
    
})(jQuery);