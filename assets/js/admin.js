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
        initializePublishButtonHandling();
        initializeScrollToTopButton();
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
                if (value && (hour < 0 || hour > 25)) {
                    isValid = false;
                    message = 'L\'ora deve essere compresa tra 0 e 25.';
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
        /* $('select[name*="_sedile"]').on('change', function() {
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
        }); */

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

        initializeNaturaIncidenteLogic();
        
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
                '4': 'Tamponamento'
            },
            'B': {
                '5': 'Investimento di pedoni'
            },
            'C': {
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

        // Gestisci le opzioni del numero veicoli coinvolti per "Tra veicoli in marcia"
        var $selectNumeroVeicoli = $('#numero_veicoli_coinvolti');
        var currentValue = $selectNumeroVeicoli.val();

        if (natura === 'A') { // "Tra veicoli in marcia"
            // Salva le opzioni originali se non già fatto
            if (!$selectNumeroVeicoli.data('original-options')) {
                $selectNumeroVeicoli.data('original-options', $selectNumeroVeicoli.html());
            }
            
            // Ricostruisci le opzioni con solo 2 e 3
            $selectNumeroVeicoli.empty();
            $selectNumeroVeicoli.append('<option value="">Seleziona numero</option>');
            $selectNumeroVeicoli.append('<option value="2"' + (currentValue === '2' ? ' selected' : '') + '>2</option>');
            $selectNumeroVeicoli.append('<option value="3"' + (currentValue === '3' ? ' selected' : '') + '>3</option>');
            
            // Se il valore corrente è 1, resetta la selezione
            if (currentValue === '1') {
                $selectNumeroVeicoli.val('');
            }
        } else {
            // Ripristina le opzioni originali per altre nature
            var originalOptions = $selectNumeroVeicoli.data('original-options');
            if (originalOptions) {
                $selectNumeroVeicoli.html(originalOptions);
                $selectNumeroVeicoli.val(currentValue);
            }
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
     * Initialize publish button handling for validation errors
     */
    function initializePublishButtonHandling() {
        var originalPublishHtml = '<span class="dashicons dashicons-yes-alt"></span>Pubblica Incidente';
        var originalPublishText = 'Pubblica Incidente';
        
        // Salva il contenuto originale all'inizializzazione
        $(document).ready(function() {
            var $publishBtn = $('#publish');
            if ($publishBtn.length) {
                var currentHtml = $publishBtn.html();
                var currentText = $publishBtn.text().trim();
                
                if (currentHtml && !currentText.includes('...')) {
                    originalPublishHtml = currentHtml;
                    originalPublishText = currentText;
                }
            }
        });
        
        // Observer per monitorare i cambiamenti al bottone
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                var $target = $(mutation.target);
                
                if ($target.attr('id') === 'publish' || $target.closest('#publish').length) {
                    setTimeout(function() {
                        restorePublishButtonIfNeeded();
                    }, 100);
                }
            });
        });
        
        // Intercetta l'alert di validazione
        var originalAlert = window.alert;
        window.alert = function(message) {
            var result = originalAlert(message);
            
            if (message && (
                message.indexOf('campi obbligatori') !== -1 || 
                message.indexOf('required') !== -1 ||
                message.indexOf('obbligatorio') !== -1
            )) {
                setTimeout(function() {
                    restorePublishButtonIfNeeded();
                }, 50);
            }
            
            return result;
        };
        
        // Funzione per ripristinare il bottone
        function restorePublishButtonIfNeeded() {
            var $publishBtn = $('#publish');
            
            if (!$publishBtn.length) return;
            
            // Controlla se ci sono errori di validazione
            var hasValidationErrors = $('.incidenti-field-error').length > 0 || 
                                    $('.incidenti-validation-error').length > 0;
            
            // Ottieni il testo corrente
            var currentText = $publishBtn.text().trim();
            var isDisabled = $publishBtn.prop('disabled');
            
            // Controlla se ha testo di caricamento
            var hasLoadingText = currentText && (
                currentText.includes('Pubblicazione') ||
                currentText.includes('...') ||
                currentText === 'Pubblicazione in corso'
            );
            
            // Ripristina se ci sono errori di validazione
            if (hasValidationErrors && (isDisabled || hasLoadingText)) {
                // Riabilita il bottone
                $publishBtn.prop('disabled', false);
                
                // Ripristina il contenuto HTML completo (icona + testo)
                $publishBtn.html(originalPublishHtml);
                
                // Nascondi gli spinner
                $('#ajax-loading').hide();
                $('.spinner').removeClass('is-active');
                $('#publishing-action .spinner').removeClass('is-active');
                
                console.log('Bottone ripristinato con HTML: "' + originalPublishHtml + '"');
            }
        }
        
        // Intercetta anche il click per salvare lo stato
        $(document).on('click', '#publish', function() {
            var $btn = $(this);
            var currentHtml = $btn.html();
            var currentText = $btn.text().trim();
            
            // Salva solo se non ha già testo di loading
            if (!currentText.includes('...') && !currentText.includes('Pubblicazione')) {
                originalPublishHtml = currentHtml;
                originalPublishText = currentText;
            }
            
            // Controlla dopo il submit
            setTimeout(function() {
                restorePublishButtonIfNeeded();
            }, 200);
            
            // Controllo aggiuntivo con delay più lungo
            setTimeout(function() {
                restorePublishButtonIfNeeded();
            }, 500);
        });
        
        // Avvia l'observer
        var publishBtn = document.getElementById('publish');
        if (publishBtn) {
            observer.observe(publishBtn, {
                attributes: true,
                childList: true,
                subtree: true,
                characterData: true
            });
            
            // Observer anche per il container padre
            var publishingAction = document.getElementById('publishing-action');
            if (publishingAction) {
                observer.observe(publishingAction, {
                    childList: true,
                    subtree: true
                });
            }
        }
    }
    
    /**
     * Initialize date pickers
     */
    function initializeDatePickers() {
        // Imposta la data massima selezionabile (oggi)
        var today = new Date().toISOString().split('T')[0];
        
        // Per i campi di tipo date HTML5
        $('#data_incidente').attr('max', today);
        
        // Per i date picker jQuery UI (se presenti)
        if ($.datepicker) {
            $('.incidenti-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1950:' + new Date().getFullYear(),
                maxDate: 0 // 0 = oggi
            });
        }
        
        // Validazione aggiuntiva per il campo data incidente
        $('#data_incidente').on('change input', function() {
            var selectedDate = new Date($(this).val());
            var currentDate = new Date();
            currentDate.setHours(23, 59, 59, 999); // Fine giornata corrente
            
            if ($(this).val() && selectedDate > currentDate) {
                // Rimuovi la data non valida
                $(this).val('');
                $(this).addClass('incidenti-validation-error');
                
                // Mostra messaggio di errore
                $(this).siblings('.incidenti-field-error').remove();
                $(this).after('<span class="incidenti-field-error">Non è possibile selezionare una data futura per l\'incidente.</span>');
                
                // Alert opzionale per l'utente
                alert('Non è possibile selezionare una data futura per l\'incidente.');
            } else {
                $(this).removeClass('incidenti-validation-error');
                $(this).siblings('.incidenti-field-error').remove();
            }
        });
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
    
        // Verifica se siamo nella pagina di editing di un incidente stradale
        var bodyClass = $('body').attr('class');
        var isIncidentePost = bodyClass && (
            bodyClass.indexOf('post-type-incidente_stradale') !== -1 ||
            (bodyClass.indexOf('post-new-php') !== -1 && window.location.href.indexOf('post_type=incidente_stradale') !== -1)
        );
        
        // Applica le modifiche al titolo solo per il post type incidente_stradale
        if (isIncidentePost) {
            // Rendi il campo titolo non editabile e nascosto
            $('#title').prop('readonly', true).css({
                'background-color': '#f9f9f9',
                'color': '#666',
                'border': '1px solid #ddd'
            });
            
            // Nasconde completamente il campo titolo standard
            $('#titlediv').hide();
        }

        // Logica per mostrare identificativo comando Carabinieri
        $('#ente_rilevatore').on('change', function() {
            var enteValue = $(this).val();
            var $identificativoRow = $('#identificativo_comando_row');
            var $identificativoField = $('#identificativo_comando');
            
            console.log('Ente selezionato:', enteValue); // Debug
            
            if (enteValue === 'Carabiniere') { // CORREZIONE: Maiuscolo come nel select
                $identificativoRow.show();
                $identificativoField.prop('required', true);
                console.log('Campo identificativo comando mostrato'); // Debug
            } else {
                $identificativoRow.hide();
                $identificativoField.prop('required', false).val('');
                console.log('Campo identificativo comando nascosto'); // Debug
            }
        }).trigger('change'); // Trigger immediato per inizializzazione
        
        // Logica per tipo strada e numero strada
        $('#tipo_strada').on('change', function() {
            var tipoStrada = $(this).val();
            var $numeroStradaRow = $('#numero_strada_row');
            var $numeroStradaInput = $('#numero_strada_input');
            var $numeroStradaSelect = $('#numero_strada_select');
            var $progressivaRow = $('#progressiva_row');
            
            // Tipi di strada che richiedono il numero strada
            var tipiConNumero = ['2', '3', '5', '6'];

            // Tipi che usano la select (strade provinciali)
            var tipiConSelect = ['2', '5'];

            // Tipi che usano la select per strade statali
            var tipiConSelectStatali = ['3', '6'];
            
            var $numeroStradaSelectStatali = $('#numero_strada_select_statali');

            if (tipiConNumero.includes(tipoStrada)) {
                $numeroStradaRow.show();
                
                if (tipiConSelect.includes(tipoStrada)) {
                    // Mostra select provinciali e nascondi altri campi
                    $numeroStradaInput.hide().prop('name', '');
                    $numeroStradaSelect.show().prop('name', 'numero_strada').prop('required', true);
                    $numeroStradaSelectStatali.hide().prop('name', '');
                    
                    // Popola la select se è vuota
                    if ($numeroStradaSelect.find('option').length <= 1 && typeof populateStradeProvinciali === 'function') {
                        populateStradeProvinciali();
                    }
                } else if (tipiConSelectStatali.includes(tipoStrada)) {
                    // Mostra select statali e nascondi altri campi
                    $numeroStradaInput.hide().prop('name', '');
                    $numeroStradaSelect.hide().prop('name', '');
                    $numeroStradaSelectStatali.show().prop('name', 'numero_strada').prop('required', true);
                    
                    // Popola la select statali se è vuota
                    if ($numeroStradaSelectStatali.find('option').length <= 1 && typeof populateStradeStatali === 'function') {
                        populateStradeStatali();
                    }
                } else {
                    // Per altri tipi di strade che richiedono numero, mostra input text
                    $numeroStradaSelect.hide().prop('name', '');
                    $numeroStradaSelectStatali.hide().prop('name', '');
                    $numeroStradaInput.show().prop('name', 'numero_strada').prop('required', true);
                }
            } else {
                $numeroStradaRow.hide();
                $numeroStradaInput.prop('required', false).val('');
                $numeroStradaSelect.prop('required', false).val('');
                $numeroStradaSelectStatali.prop('required', false).val('');
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
            // Rendi la progressiva sempre obbligatoria quando c'è un tipo strada selezionato
            if (tipoStrada && tipoStrada !== '4') {
                $('#progressiva_km').prop('required', true);
                $('#progressiva_m').prop('required', false); // Solo i km sono obbligatori secondo le specifiche ISTAT
                
                // Aggiorna il messaggio di aiuto se presente
                /* var $helpText = $('#progressiva_km').siblings('.description');
                if ($helpText.length) {
                    $helpText.text('Obbligatorio per tutti i tipi di strada');
                } */
            } else {
                $('#progressiva_km').prop('required', false);
                $('#progressiva_m').prop('required', false);

                var $helpText = $('#progressiva_km').siblings('.description');
                if ($helpText.length && tipoStrada === '4') {
                    $helpText.text('Facoltativo per le strade comunali extraurbane');
                }
            }

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
        
        // Controllo aggiuntivo per date future
        if (isValid && value) {
            var selectedDate = new Date(value);
            var currentDate = new Date();
            currentDate.setHours(23, 59, 59, 999);
            
            if (selectedDate > currentDate) {
                isValid = false;
            }
        }
        
        var message = isValid ? '' : 'Data non valida o futura';
        updateFieldValidation($field, isValid, message);
        return isValid;
    }
    
    function validateHour($field) {
        var value = parseInt($field.val());
        var isValid = !$field.val() || (value >= 0 && value <= 25);
        
        updateFieldValidation($field, isValid, 'L\'ora deve essere compresa tra 0 e 25');
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
        // Validazione rimossa - il riepilogo è calcolato automaticamente
        $('#riepilogo-validation-message').hide();
        return true;
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
    /* function showValidationSummary() {
        var errors = [];
        
        $('.incidenti-field-error').each(function() {
            errors.push($(this).text());
        });
        
        if (errors.length > 0) {
            var message = 'Correggere i seguenti errori:\n\n' + errors.join('\n');
            alert(message);
        }
    } */

    /**
     * Show validation summary
     */
    function showValidationSummary() {
        // Conta solo i campi obbligatori non popolati
        var requiredFieldsError = $('.incidenti-field-error').filter(function() {
            return $(this).text() === 'Questo campo è obbligatorio.';
        }).length;
        
        if (requiredFieldsError > 0) {
            var message = 'Non hai popolato dei campi obbligatori';
            alert(message);
            
            // Evidenzia in rosso solo i campi obbligatori vuoti
            $('input[required], select[required]').each(function() {
                var $field = $(this);
                if (!$field.val() || !$field.val().trim()) {
                    $field.addClass('incidenti-validation-error');
                }
            });
        } else {
            // Se ci sono altri tipi di errori, mostra quelli specifici
            var errors = [];
            $('.incidenti-field-error').each(function() {
                var errorText = $(this).text();
                if (errorText !== 'Questo campo è obbligatorio.') {
                    errors.push(errorText);
                }
            });
            
            if (errors.length > 0) {
                var message = 'Correggere i seguenti errori:\n\n' + errors.join('\n');
                alert(message);
            }
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

    
    /**
     * Initialize scroll to top button for incident forms
     */
    function initializeScrollToTopButton() {
        // Controlla se siamo nelle pagine di creazione/modifica incidenti
        var $body = $('body');
        if (!$body.hasClass('post-type-incidente_stradale') || 
            (!$body.hasClass('post-new-php') && !$body.hasClass('post-php'))) {
            return;
        }
        
        // Crea il pulsante se non esiste già
        if ($('#incidenti-scroll-to-top').length === 0) {
            createScrollToTopButton();
        }
        
        // Gestisce la visibilità
        handleScrollToTopVisibility();
    }

    /**
     * Create scroll to top button with styling
     */
    function createScrollToTopButton() {
        // Aggiungi CSS al head
        var scrollToTopCSS = `
            <style id="incidenti-scroll-to-top-styles">
            #incidenti-scroll-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
                z-index: 9999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease-in-out;
                font-size: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
            }
            
            #incidenti-scroll-to-top.show {
                opacity: 1;
                visibility: visible;
            }
            
            #incidenti-scroll-to-top:hover {
                background: #005a87;
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 115, 170, 0.4);
            }
            
            #incidenti-scroll-to-top:focus {
                outline: 2px solid #005a87;
                outline-offset: 2px;
            }
            
            #incidenti-scroll-to-top:active {
                transform: translateY(0);
            }
            
            /* Icona freccia con dashicons */
            #incidenti-scroll-to-top .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
                line-height: 1;
            }
            
            /* Fallback se dashicons non disponibili */
            #incidenti-scroll-to-top:not(:has(.dashicons))::before {
                content: "↑";
                font-weight: bold;
            }
            
            /* Mobile */
            @media (max-width: 782px) {
                #incidenti-scroll-to-top {
                    bottom: 20px;
                    right: 20px;
                    width: 45px;
                    height: 45px;
                }
                
                #incidenti-scroll-to-top .dashicons {
                    font-size: 18px;
                    width: 18px;
                    height: 18px;
                }
            }
            
            /* Admin bar adjustment */
            body.admin-bar #incidenti-scroll-to-top {
                bottom: 50px;
            }
            
            @media screen and (max-width: 782px) {
                body.admin-bar #incidenti-scroll-to-top {
                    bottom: 66px;
                }
            }
            
            @media screen and (max-width: 600px) {
                body.admin-bar #incidenti-scroll-to-top {
                    bottom: 86px;
                }
            }
            </style>
        `;
        
        // Rimuovi CSS esistente se presente
        $('#incidenti-scroll-to-top-styles').remove();
        
        // Aggiungi nuovo CSS
        $('head').append(scrollToTopCSS);
        
        // Crea il pulsante
        var $button = $('<button/>', {
            id: 'incidenti-scroll-to-top',
            type: 'button',
            'aria-label': 'Torna all\'inizio della pagina',
            title: 'Torna all\'inizio',
            class: 'button-secondary'
        });
        
        // Aggiungi icona (usa dashicons se disponibile)
        if ($('body').hasClass('wp-admin')) {
            $button.html('<span class="dashicons dashicons-arrow-up-alt"></span>');
        } else {
            $button.html('↑');
        }
        
        // Event handler per il click
        $button.on('click', function(e) {
            e.preventDefault();
            scrollToTopAnimated();
        });
        
        // Aggiungi al body
        $('body').append($button);
    }

    /**
     * Handle scroll to top button visibility
     */
    function handleScrollToTopVisibility() {
        var $button = $('#incidenti-scroll-to-top');
        if ($button.length === 0) return;
        
        var scrollThreshold = 300;
        var $window = $(window);
        
        // Funzione throttled per performance
        var throttledScroll = throttle(function() {
            var scrollTop = $window.scrollTop();
            
            if (scrollTop > scrollThreshold) {
                $button.addClass('show');
            } else {
                $button.removeClass('show');
            }
        }, 100);
        
        // Bind scroll event
        $window.on('scroll.scrollToTop', throttledScroll);
        
        // Controllo iniziale
        throttledScroll();
    }

    /**
     * Animated scroll to top
     */
    function scrollToTopAnimated() {
        $('html, body').animate({
            scrollTop: 0
        }, {
            duration: 800,
            easing: 'swing',
            complete: function() {
                // Focus sul primo elemento della pagina per accessibilità
                var $firstFocusable = $('.wrap h1, #title, .page-title-action').first();
                if ($firstFocusable.length) {
                    $firstFocusable.focus();
                }
            }
        });
    }

    /**
     * Utility function per throttling (se non già presente)
     */
    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        }
    }

    // Aggiungi alla chiamata di inizializzazione principale nel file admin.js esistente
    // Sostituire la funzione $(document).ready esistente o aggiungere questa chiamata

    $(document).ready(function() {
        // ... codice esistente ...
        
        // Inizializza scroll to top button
        initializeScrollToTopButton();
        
        // ... resto del codice esistente ...
    });

    // Per cleanup quando si cambia pagina (se necessario)
    $(window).on('beforeunload', function() {
        $(window).off('scroll.scrollToTop');
    });
    
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


    // JavaScript COMPLETO per Circostanze Presunte dell'Incidente
    // Aggiungere questa sezione al file admin.js esistente

    jQuery(document).ready(function($) {
        'use strict';
        
        // Definizione completa dei codici circostanze per tipo di incidente
        var circostanzeData = {
            'intersezione': {
                'veicolo_a': {
                    '01': 'Procedeva regolarmente senza svoltare',
                    '02': 'Procedeva con guida distratta e andamento indeciso',
                    '03': 'Procedeva senza mantenere la distanza di sicurezza',
                    '04': 'Procedeva senza dare la precedenza al veicolo proveniente da destra',
                    '05': 'Procedeva senza rispettare lo stop',
                    '06': 'Procedeva senza rispettare il segnale di dare precedenza',
                    '07': 'Procedeva contromano',
                    '08': 'Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente',
                    '10': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '11': 'Procedeva con eccesso di velocità',
                    '12': 'Procedeva senza rispettare i limiti di velocità',
                    '13': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '14': 'Svoltava a destra regolarmente',
                    '15': 'Svoltava a destra irregolarmente',
                    '16': 'Svoltava a sinistra regolarmente',
                    '17': 'Svoltava a sinistra irregolarmente',
                    '18': 'Sorpassava (all\'incrocio)'
                },
                'veicolo_b': {
                    '01': 'Procedeva regolarmente senza svoltare',
                    '02': 'Procedeva con guida distratta e andamento indeciso',
                    '03': 'Procedeva senza mantenere la distanza di sicurezza',
                    '04': 'Procedeva senza dare la precedenza al veicolo proveniente da destra',
                    '05': 'Procedeva senza rispettare lo stop',
                    '06': 'Procedeva senza rispettare il segnale di dare precedenza',
                    '07': 'Procedeva contromano',
                    '08': 'Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente',
                    '10': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '11': 'Procedeva con eccesso di velocità',
                    '12': 'Procedeva senza rispettare i limiti di velocità',
                    '13': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '14': 'Svoltava a destra regolarmente',
                    '15': 'Svoltava a destra irregolarmente',
                    '16': 'Svoltava a sinistra regolarmente',
                    '17': 'Svoltava a sinistra irregolarmente',
                    '18': 'Sorpassava (all\'incrocio)'
                },
                'veicolo_c': {
                    '01': 'Procedeva regolarmente senza svoltare',
                    '02': 'Procedeva con guida distratta e andamento indeciso',
                    '03': 'Procedeva senza mantenere la distanza di sicurezza',
                    '04': 'Procedeva senza dare la precedenza al veicolo proveniente da destra',
                    '05': 'Procedeva senza rispettare lo stop',
                    '06': 'Procedeva senza rispettare il segnale di dare precedenza',
                    '07': 'Procedeva contromano',
                    '08': 'Procedeva senza rispettare le segnalazioni semaforiche o dell\'agente',
                    '10': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '11': 'Procedeva con eccesso di velocità',
                    '12': 'Procedeva senza rispettare i limiti di velocità',
                    '13': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '14': 'Svoltava a destra regolarmente',
                    '15': 'Svoltava a destra irregolarmente',
                    '16': 'Svoltava a sinistra regolarmente',
                    '17': 'Svoltava a sinistra irregolarmente',
                    '18': 'Sorpassava (all\'incrocio)'
                }
            },
            'non_intersezione': {
                'veicolo_a': {
                    '20': 'Procedeva regolarmente',
                    '21': 'Procedeva con guida distratta e andamento indeciso',
                    '22': 'Procedeva senza mantenere la distanza di sicurezza',
                    '23': 'Procedeva con eccesso di velocità',
                    '24': 'Procedeva senza rispettare i limiti di velocità',
                    '25': 'Procedeva non in prossimità del margine destro della carreggiata',
                    '26': 'Procedeva contromano',
                    '27': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '28': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '29': 'Sorpassava regolarmente',
                    '30': 'Sorpassava irregolarmente a destra',
                    '31': 'Sorpassava in curva, su dosso o insufficiente visibilità',
                    '32': 'Sorpassava un veicolo che ne stava sorpassando un altro',
                    '33': 'Sorpassava senza osservare il segnale di divieto',
                    '34': 'Manovrava in retrocessione o conversione',
                    '35': 'Manovrava per immettersi nel flusso della circolazione',
                    '36': 'Manovrava per voltare a sinistra',
                    '37': 'Manovrava regolarmente per fermarsi o sostare',
                    '38': 'Manovrava irregolarmente per fermarsi o sostare',
                    '39': 'Si affiancava ad altri veicoli a due ruote irregolarmente'
                },
                'veicolo_b': {
                    '20': 'Procedeva regolarmente',
                    '21': 'Procedeva con guida distratta e andamento indeciso',
                    '22': 'Procedeva senza mantenere la distanza di sicurezza',
                    '23': 'Procedeva con eccesso di velocità',
                    '24': 'Procedeva senza rispettare i limiti di velocità',
                    '25': 'Procedeva non in prossimità del margine destro della carreggiata',
                    '26': 'Procedeva contromano',
                    '27': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '28': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '29': 'Sorpassava regolarmente',
                    '30': 'Sorpassava irregolarmente a destra',
                    '31': 'Sorpassava in curva, su dosso o insufficiente visibilità',
                    '32': 'Sorpassava un veicolo che ne stava sorpassando un altro',
                    '33': 'Sorpassava senza osservare il segnale di divieto',
                    '34': 'Manovrava in retrocessione o conversione',
                    '35': 'Manovrava per immettersi nel flusso della circolazione',
                    '36': 'Manovrava per voltare a sinistra',
                    '37': 'Manovrava regolarmente per fermarsi o sostare',
                    '38': 'Manovrava irregolarmente per fermarsi o sostare',
                    '39': 'Si affiancava ad altri veicoli a due ruote irregolarmente'
                },
                'veicolo_c': {
                    '20': 'Procedeva regolarmente',
                    '21': 'Procedeva con guida distratta e andamento indeciso',
                    '22': 'Procedeva senza mantenere la distanza di sicurezza',
                    '23': 'Procedeva con eccesso di velocità',
                    '24': 'Procedeva senza rispettare i limiti di velocità',
                    '25': 'Procedeva non in prossimità del margine destro della carreggiata',
                    '26': 'Procedeva contromano',
                    '27': 'Procedeva senza rispettare i segnali di divieto di transito',
                    '28': 'Procedeva con le luci abbaglianti incrociando altri veicoli',
                    '29': 'Sorpassava regolarmente',
                    '30': 'Sorpassava irregolarmente a destra',
                    '31': 'Sorpassava in curva, su dosso o insufficiente visibilità',
                    '32': 'Sorpassava un veicolo che ne stava sorpassando un altro',
                    '33': 'Sorpassava senza osservare il segnale di divieto',
                    '34': 'Manovrava in retrocessione o conversione',
                    '35': 'Manovrava per immettersi nel flusso della circolazione',
                    '36': 'Manovrava per voltare a sinistra',
                    '37': 'Manovrava regolarmente per fermarsi o sostare',
                    '38': 'Manovrava irregolarmente per fermarsi o sostare',
                    '39': 'Si affiancava ad altri veicoli a due ruote irregolarmente'
                }
            },
            'investimento': {
                'veicolo_a': {
                    '40': 'Procedeva regolarmente',
                    '41': 'Procedeva con eccesso di velocità',
                    '42': 'Procedeva senza rispettare i limiti di velocità',
                    '43': 'Procedeva contromano',
                    '44': 'Sorpassava veicolo in marcia',
                    '45': 'Manovrava',
                    '46': 'Non rispettava le segnalazioni semaforiche o dell\'agente',
                    '47': 'Usciva senza precauzioni da passo carrabile',
                    '48': 'Fuoriusciva dalla carreggiata',
                    '49': 'Non dava la precedenza al pedone sugli appositi attraversamenti',
                    '50': 'Sorpassava un veicolo fermatosi per l\'attraversamento dei pedoni',
                    '51': 'Urtava con il carico il pedone',
                    '52': 'Superava irregolarmente un tram fermo per salita/discesa'
                },
                'pedone': {
                    '40': 'Camminava su marciapiede/banchina',
                    '41': 'Camminava regolarmente sul margine',
                    '42': 'Camminava contromano',
                    '43': 'Camminava in mezzo carreggiata',
                    '44': 'Sostava/indugiava/giocava carreggiata',
                    '45': 'Lavorava protetto da segnale',
                    '46': 'Lavorava non protetto da segnale',
                    '47': 'Saliva su veicolo in marcia',
                    '48': 'Discendeva con prudenza',
                    '49': 'Discendeva con imprudenza',
                    '50': 'Veniva fuori da dietro veicolo',
                    '51': 'Attraversava rispettando segnali',
                    '52': 'Attraversava non rispettando segnali',
                    '53': 'Attraversava passaggio non protetto',
                    '54': 'Attraversava regolarmente non su passaggio',
                    '55': 'Attraversava irregolarmente'
                }
            },
            'urto_fermo': {
                'veicolo_a': {
                    '60': 'Procedeva regolarmente',
                    '61': 'Procedeva con guida distratta',
                    '62': 'Procedeva senza mantenere distanza sicurezza',
                    '63': 'Procedeva contromano',
                    '64': 'Procedeva con eccesso di velocità',
                    '65': 'Procedeva senza rispettare limiti velocità',
                    '66': 'Procedeva senza rispettare divieti transito',
                    '67': 'Sorpassava un altro veicolo',
                    '68': 'Attraversava imprudentemente passaggio a livello'
                },
                'ostacolo': {
                    '60': 'Ostacolo accidentale',
                    '61': 'Veicolo fermo in posizione regolare',
                    '62': 'Veicolo fermo in posizione irregolare',
                    '63': 'Veicolo fermo senza prescritto segnale',
                    '64': 'Veicolo fermo regolarmente segnalato',
                    '65': 'Ostacolo fisso nella carreggiata',
                    '66': 'Treno in passaggio a livello',
                    '67': 'Animale domestico',
                    '68': 'Animale selvatico',
                    '69': 'Buca'
                }
            },
            'senza_urto': {
                'veicolo_a': {
                    '70': 'Sbandamento per evitare urto',
                    '71': 'Sbandamento per guida distratta',
                    '72': 'Sbandamento per eccesso velocità',
                    '73': 'Frenava improvvisamente',
                    '74': 'Caduta persona per apertura portiera',
                    '75': 'Caduta persona per discesa da veicolo in moto',
                    '76': 'Caduta persona aggrappata inadeguatamente'
                },
                'ostacolo_evitato': {
                    '70': 'Ostacolo accidentale',
                    '71': 'Pedone',
                    '72': 'Animale evitato',
                    '73': 'Veicolo',
                    '74': 'Buca evitata',
                    '75': 'Senza ostacolo né pedone né altro veicolo',
                    '76': 'Ostacolo fisso'
                }
            }
        };

        // Gestione cambio tipo di circostanza
        $('#circostanza_tipo').on('change', function() {
            var tipo = $(this).val();
            var selectVeicoloA = $('#circostanza_veicolo_a');
            var selectVeicoloB = $('#circostanza_veicolo_b');
            var selectVeicoloC = $('#circostanza_veicolo_c');
            
            // Pulisci le select
            selectVeicoloA.empty().append('<option value="">Seleziona circostanza</option>');
            selectVeicoloB.empty().append('<option value="">Seleziona circostanza</option>');
            selectVeicoloC.empty().append('<option value="">Seleziona circostanza</option>');
            
            if (tipo && circostanzeData[tipo]) {
                // Popola Veicolo A
                /* if (circostanzeData[tipo]['veicolo_a']) {
                    $.each(circostanzeData[tipo]['veicolo_a'], function(codice, descrizione) {
                        selectVeicoloA.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                } */

                // Popola Veicolo A
                if (circostanzeData[tipo]['veicolo_a']) {
                    // Aggiungi sempre l'opzione "00 - Circostanza imprecisata" per tutti i tipi
                    selectVeicoloA.append('<option value="00">00 - Circostanza imprecisata</option>');
                    
                    $.each(circostanzeData[tipo]['veicolo_a'], function(codice, descrizione) {
                        selectVeicoloA.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                }
                
                // Popola Veicolo B/Pedone/Ostacolo
                var tipoB = 'veicolo_b';
                if (tipo === 'investimento') tipoB = 'pedone';
                if (tipo === 'urto_fermo') tipoB = 'ostacolo';
                if (tipo === 'senza_urto') tipoB = 'ostacolo_evitato';
                
                /* if (circostanzeData[tipo][tipoB]) {
                    $.each(circostanzeData[tipo][tipoB], function(codice, descrizione) {
                        selectVeicoloB.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                } */

                if (circostanzeData[tipo][tipoB]) {
                    // Aggiungi sempre l'opzione "00 - Circostanza imprecisata" per tutti i tipi
                    selectVeicoloB.append('<option value="00">00 - Circostanza imprecisata</option>');
                    
                    $.each(circostanzeData[tipo][tipoB], function(codice, descrizione) {
                        selectVeicoloB.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                }
                
                // Aggiorna label del Veicolo B
                var labelText = 'Circostanza Veicolo B';
                if (tipo === 'investimento') labelText = 'Circostanza Pedone';
                if (tipo === 'urto_fermo') labelText = 'Circostanza Ostacolo';
                if (tipo === 'senza_urto') labelText = 'Ostacolo Evitato';
                
                $('label[for="circostanza_veicolo_b"]').text(labelText);
            }

            /**/
            function sortSelectOptionsByValue(selectId) {
                const select = document.getElementById(selectId);
                if (!select) return;
                const selectedValue = select.value;
                const options = Array.from(select.options);
                const placeholder = options.shift(); // Preserva la prima opzione come placeholder
                options.sort((a, b) => Number(a.value) - Number(b.value));
                select.innerHTML = '';
                select.appendChild(placeholder);
                options.forEach(opt => select.appendChild(opt));
                select.value = selectedValue; // Ripristina la selezione precedentemente salvata
            }

            // Applica l'ordinamento alle due select
            sortSelectOptionsByValue('circostanza_veicolo_a');
            sortSelectOptionsByValue('circostanza_veicolo_b');

            /**/
        });

        /* $('#circostanza_tipo').on('change', function() {
            var tipo = $(this).val();
            var selectVeicoloA = $('#circostanza_veicolo_a');
            var selectVeicoloB = $('#circostanza_veicolo_b');
            var selectVeicoloC = $('#circostanza_veicolo_c');

            // Pulisci le select
            selectVeicoloA.empty().append('<option value="">Seleziona circostanza</option>');
            selectVeicoloB.empty().append('<option value="">Seleziona circostanza</option>');
            selectVeicoloC.empty().append('<option value="">Seleziona circostanza</option>');

            if (tipo && circostanzeData[tipo]) {
                // Popola Veicolo A ordinato per codice numerico
                if (circostanzeData[tipo]['veicolo_a']) {
                    // Ottieni le chiavi e ordinali numericamente
                    var keysA = Object.keys(circostanzeData[tipo]['veicolo_a']);
                    keysA.sort(function(a, b){ return Number(a) - Number(b); });
                    keysA.forEach(function(codice) {
                        var descrizione = circostanzeData[tipo]['veicolo_a'][codice];
                        selectVeicoloA.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                }

                // Popola Veicolo B ordinato per codice numerico
                if (circostanzeData[tipo]['veicolo_b']) {
                    var keysB = Object.keys(circostanzeData[tipo]['veicolo_b']);
                    keysB.sort(function(a, b){ return Number(a) - Number(b); });
                    keysB.forEach(function(codice) {
                        var descrizione = circostanzeData[tipo]['veicolo_b'][codice];
                        selectVeicoloB.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                }

                // Popola Veicolo C ordinato per codice numerico
                if (circostanzeData[tipo]['veicolo_c']) {
                    var keysC = Object.keys(circostanzeData[tipo]['veicolo_c']);
                    keysC.sort(function(a, b){ return Number(a) - Number(b); });
                    keysC.forEach(function(codice) {
                        var descrizione = circostanzeData[tipo]['veicolo_c'][codice];
                        selectVeicoloC.append('<option value="' + codice + '">' + codice + ' - ' + descrizione + '</option>');
                    });
                }

                // Aggiorna label del Veicolo B
                var labelText = 'Circostanza Veicolo B';
                if (tipo === 'investimento') labelText = 'Circostanza Pedone';
                if (tipo === 'urto_fermo') labelText = 'Circostanza Ostacolo';
                if (tipo === 'senza_urto') labelText = 'Ostacolo Evitato';
                
                $('label[for="circostanza_veicolo_b"]').text(labelText);
            }
        }); */


        // Trigger al caricamento pagina se c'è già un valore
        if ($('#circostanza_tipo').val()) {
            $('#circostanza_tipo').trigger('change');
            
            // Ripristina i valori selezionati dopo il caricamento delle opzioni
            setTimeout(function() {
                var savedA = $('#circostanza_veicolo_a').data('saved-value') || '';
                var savedB = $('#circostanza_veicolo_b').data('saved-value') || '';
                
                if (savedA) $('#circostanza_veicolo_a').val(savedA);
                if (savedB) $('#circostanza_veicolo_b').val(savedB);
            }, 100);
        }

        // Helper per gestire la compatibilità con il codice esistente
        function initializeCircostanzeCompatibility() {
            // Salva i valori attuali come data attributes per il ripristino
            $('#circostanza_veicolo_a').attr('data-saved-value', $('#circostanza_veicolo_a').val());
            $('#circostanza_veicolo_b').attr('data-saved-value', $('#circostanza_veicolo_b').val());
            $('#circostanza_veicolo_c').attr('data-saved-value', $('#circostanza_veicolo_c').val());
        }

        // Inizializzazione
        initializeCircostanzeCompatibility();

        // Gestione automatica del tipo di circostanza basata sulla natura dell'incidente
        $('#natura_incidente').on('change', function() {
            var natura = $(this).val();
            var tipoSuggerito = '';
            
            switch(natura) {
                case 'A': // Tra veicoli in marcia
                    // Non possiamo dedurre automaticamente se è intersezione o no
                    break;
                case 'B': // Tra veicolo e pedoni
                    tipoSuggerito = 'investimento';
                    break;
                case 'C': // Veicolo in marcia che urta veicolo fermo o altro
                    tipoSuggerito = 'urto_fermo';
                    break;
                case 'D': // Veicolo in marcia senza urto
                    tipoSuggerito = 'senza_urto';
                    break;
            }
            
            if (tipoSuggerito && !$('#circostanza_tipo').val()) {
                $('#circostanza_tipo').val(tipoSuggerito).trigger('change');
            }
        });

        // AGGIUNTA: Inferenza automatica al caricamento della pagina
        $(document).ready(function() {
            // Esegui inferenza dopo un breve delay per permettere il caricamento completo
            setTimeout(function() {
                inferTipoIncidenteFromCircostanze();
            }, 500);
        });

        // Funzione per inferire il tipo di incidente dalle circostanze esistenti
        function inferTipoIncidenteFromCircostanze() {
            var naturaIncidente = $('#natura_incidente').val();
            var circostanzaA = $('#circostanza_veicolo_a').val() || $('#circostanza_veicolo_a').attr('data-current-value');
            var circostanzaB = $('#circostanza_veicolo_b').val() || $('#circostanza_veicolo_b').attr('data-current-value');
            
            // Se tipo incidente è già impostato, non fare nulla
            if ($('#circostanza_tipo').val()) {
                return;
            }
            
            // Mappa dei codici circostanze per tipo
            var codiciPerTipo = {
                'intersezione': ['01', '02', '03', '04', '05', '06', '07', '08', '10', '11', '12', '13', '14', '15', '16', '17', '18'],
                'non_intersezione': ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39'],
                'investimento': ['40', '41', '42', '43', '44', '45', '46', '47', '48', '49'],
                'urto_fermo': ['60', '61', '62', '63', '64', '65'],
                'senza_urto': ['70', '71', '72', '73', '74', '75']
            };
            
            var tipoInferito = '';
            
            // Cerca il tipo basandosi sui codici presenti
            for (var tipo in codiciPerTipo) {
                if (codiciPerTipo[tipo].includes(circostanzaA) || codiciPerTipo[tipo].includes(circostanzaB)) {
                    tipoInferito = tipo;
                    break;
                }
            }
            
            // Se hai trovato un tipo, impostalo
            if (tipoInferito) {
                console.log('🔍 Tipo incidente inferito:', tipoInferito, 'da circostanze:', circostanzaA, circostanzaB);
                $('#circostanza_tipo').val(tipoInferito).trigger('change');
            }
        }

        // Validazione coerenza circostanze
        function validateCircostanzeCoherence() {
            var tipo = $('#circostanza_tipo').val();
            var circA = $('#circostanza_veicolo_a').val();
            var circB = $('#circostanza_veicolo_b').val();
            
            // Regole di validazione specifiche
            var warnings = [];
            
            if (tipo === 'intersezione') {
                if ((circA === '01' || circA === '20') && (circB === '01' || circB === '20')) {
                    warnings.push('Attenzione: entrambi i veicoli procedevano regolarmente. Verificare.');
                }
            }
            
            if (tipo === 'investimento') {
                if (circA === '40' && circB === '51') {
                    // Situazione normale: veicolo regolare, pedone sulle strisce
                } else if (circA === '49' && circB === '52') {
                    warnings.push('Verificare: il veicolo non ha dato precedenza ma il pedone non ha rispettato i segnali.');
                }
            }
            
            // Mostra warnings
            if (warnings.length > 0) {
                var warningDiv = $('#circostanze-warnings');
                if (warningDiv.length === 0) {
                    $('.incidenti-circostanze-container').prepend('<div id="circostanze-warnings" class="notice notice-warning"><ul></ul></div>');
                    warningDiv = $('#circostanze-warnings');
                }
                
                var list = warningDiv.find('ul').empty();
                warnings.forEach(function(warning) {
                    list.append('<li>' + warning + '</li>');
                });
                warningDiv.show();
            } else {
                $('#circostanze-warnings').hide();
            }
        }
        
        // Applica validazione quando cambiano i valori
        $('#circostanza_veicolo_a, #circostanza_veicolo_b, #circostanza_veicolo_c').on('change', validateCircostanzeCoherence);

        // Gestione visibilità campi per numero veicoli
        function updateCircostanzeFieldsVisibility() {
            var numeroVeicoli = parseInt($('#numero_veicoli_coinvolti').val()) || 1;
            
            if (numeroVeicoli >= 3) {
                $('#circostanza_veicolo_c_row').show();
            } else {
                $('#circostanza_veicolo_c_row').hide();
                $('#circostanza_veicolo_c').val('');
            }
        }
        
        // Ascolta cambiamenti numero veicoli
        $(document).on('change', '#numero_veicoli_coinvolti', updateCircostanzeFieldsVisibility);
        updateCircostanzeFieldsVisibility();

        // Helper per ottenere descrizione circostanza
        window.getCircostanzaDescription = function(tipo, veicolo, codice) {
            if (!circostanzeData[tipo] || !circostanzeData[tipo][veicolo] || !circostanzeData[tipo][veicolo][codice]) {
                return null;
            }
            return circostanzeData[tipo][veicolo][codice];
        };

        // Helper per validare codice circostanza
        window.isValidCircostanzaCode = function(tipo, veicolo, codice) {
            return circostanzeData[tipo] && 
                circostanzeData[tipo][veicolo] && 
                circostanzeData[tipo][veicolo][codice] !== undefined;
        };

        // Funzione per auto-compilare circostanze suggerite
        function suggestCircostanze() {
            var natura = $('#natura_incidente').val();
            var dettaglio = $('#dettaglio_natura').val();
            var tipo = $('#circostanza_tipo').val();
            
            if (!natura || !tipo) return;
            
            var suggerimenti = {};
            
            // Suggerimenti basati su natura e dettaglio
            if (natura === 'A' && dettaglio === '4') { // Tamponamento
                if (tipo === 'non_intersezione') {
                    suggerimenti.veicolo_a = '22'; // Senza mantenere distanza
                    suggerimenti.veicolo_b = '20'; // Procedeva regolarmente
                }
            } else if (natura === 'A' && dettaglio === '1') { // Scontro frontale
                if (tipo === 'non_intersezione') {
                    suggerimenti.veicolo_a = '26'; // Contromano
                    suggerimenti.veicolo_b = '20'; // Procedeva regolarmente
                }
            } else if (natura === 'B') { // Investimento pedone
                suggerimenti.veicolo_a = '49'; // Non dava precedenza
                suggerimenti.pedone = '51'; // Attraversava rispettando segnali
            }
            
            // Applica suggerimenti solo se i campi sono vuoti
            if (suggerimenti.veicolo_a && !$('#circostanza_veicolo_a').val()) {
                $('#circostanza_veicolo_a').val(suggerimenti.veicolo_a);
            }
            if (suggerimenti.veicolo_b && !$('#circostanza_veicolo_b').val()) {
                $('#circostanza_veicolo_b').val(suggerimenti.veicolo_b);
            }
            if (suggerimenti.pedone && !$('#circostanza_veicolo_b').val()) {
                $('#circostanza_veicolo_b').val(suggerimenti.pedone);
            }
        }

        // Trigger suggerimenti quando cambiano natura/dettaglio
        $('#natura_incidente, #dettaglio_natura').on('change', function() {
            setTimeout(suggestCircostanze, 500); // Delay per permettere il caricamento delle opzioni
        });

        // Funzione per esportare dati circostanze (per debugging)
        window.exportCircostanzeData = function() {
            return {
                tipo: $('#circostanza_tipo').val(),
                veicolo_a: $('#circostanza_veicolo_a').val(),
                veicolo_b: $('#circostanza_veicolo_b').val(),
                difetto_a: $('#difetto_veicolo_a').val(),
                difetto_b: $('#difetto_veicolo_b').val(),
                stato_a: $('#stato_psicofisico_a').val(),
                stato_b: $('#stato_psicofisico_b').val()
            };
        };

        // Logging per debugging
        if (typeof console !== 'undefined' && console.log) {
            console.log('Circostanze Incidenti: Sistema inizializzato con', Object.keys(circostanzeData).length, 'tipi di circostanze');
            
            // Log quando vengono selezionate circostanze
            $('#circostanza_veicolo_a, #circostanza_veicolo_b').on('change', function() {
                var field = $(this).attr('id');
                var value = $(this).val();
                var text = $(this).find('option:selected').text();
                
                if (value) {
                    console.log('Circostanza selezionata:', field, '=', value, '-', text);
                }
            });
        }

        // Inizializzazione finale
        console.log('🔄 Circostanze JavaScript: Inizializzazione completata');
    });

    function initializeNaturaIncidenteLogic() {
        // Mappatura tra natura incidente e tipi di incidente permessi
        var naturaToTipoMapping = {
            'A': { // Tra veicoli in marcia
                'intersezione': 'Incidente all\'intersezione stradale',
                'non_intersezione': 'Incidente non all\'intersezione'
            },
            'B': { // Tra veicolo e pedoni
                'investimento': 'Investimento di pedone'
            },
            'C': { // Veicolo in marcia che urta veicolo fermo o altro
                'urto_fermo': 'Urto con veicolo fermo/ostacolo'
            },
            'D': { // Veicolo in marcia senza urto
                'senza_urto': 'Veicolo senza urto'
            }
        };

        // Funzione per aggiornare le opzioni del tipo di incidente
        function updateTipoIncidenteOptions(natura) {
            var $tipoSelect = $('#circostanza_tipo');
            var currentValue = $tipoSelect.val();
            
            // PRESERVA: Salva il valore corrente prima di pulire (funzionalità esistente)
            var savedValue = $tipoSelect.data('saved-value') || 
                            $tipoSelect.attr('data-current-value') || 
                            currentValue;
            
            // PRESERVA: Pulisci le opzioni (funzionalità esistente)
            $tipoSelect.empty().append('<option value="">Seleziona tipo</option>');
            
            if (natura && naturaToTipoMapping[natura]) {
                var opzioni = naturaToTipoMapping[natura];
                var keys = Object.keys(opzioni);
                
                // PRESERVA: Auto-selezione e disabilitazione per opzione singola
                /* if (keys.length === 1) {
                    var uniqueKey = keys[0];
                    $tipoSelect.append('<option value="' + uniqueKey + '" selected>' + opzioni[uniqueKey] + '</option>');
                    $tipoSelect.prop('disabled', true);
                    $tipoSelect.val(uniqueKey); */

                    if (keys.length === 1) {
                    var uniqueKey = keys[0];
                    $tipoSelect.append('<option value="' + uniqueKey + '" selected>' + opzioni[uniqueKey] + '</option>');
                    $tipoSelect.prop('disabled', true);
                    $tipoSelect.val(uniqueKey);
                    
                    // Crea campo hidden per inviare il valore anche se il select è disabilitato
                    var $existingHidden = $('#circostanza_tipo_hidden');
                    if ($existingHidden.length) {
                        $existingHidden.val(uniqueKey);
                    } else {
                        $tipoSelect.after('<input type="hidden" id="circostanza_tipo_hidden" name="circostanza_tipo" value="' + uniqueKey + '">');
                    }
                    
                    // PRESERVA: Trigger del cambio per aggiornare le circostanze
                    setTimeout(function() {
                        $tipoSelect.trigger('change');
                    }, 100);
                    
                /* } else {
                    // PRESERVA: Gestione opzioni multiple
                    $tipoSelect.prop('disabled', false); */
                    } else {
                    // PRESERVA: Gestione opzioni multiple
                    $tipoSelect.prop('disabled', false);
                    
                    // AGGIUNTO: Rimuovi campo hidden se esiste
                    $('#circostanza_tipo_hidden').remove();
                    $.each(opzioni, function(key, value) {
                        // MIGLIORA: Seleziona l'opzione se corrisponde al valore salvato
                        var selected = (key === savedValue) ? ' selected' : '';
                        $tipoSelect.append('<option value="' + key + '"' + selected + '>' + value + '</option>');
                    });
                    
                    // MIGLIORA: Ripristina il valore salvato se valido
                    if (savedValue && opzioni[savedValue]) {
                        $tipoSelect.val(savedValue);
                        
                        // MIGLIORA: Trigger change anche qui per consistency
                        setTimeout(function() {
                            $tipoSelect.trigger('change');
                        }, 100);
                    }
                }
            } else {
                // PRESERVA: Ripristino di tutte le opzioni quando natura non è selezionata
                $tipoSelect.prop('disabled', false);
                var allOptions = {
                    'intersezione': 'Incidente all\'intersezione stradale',
                    'non_intersezione': 'Incidente non all\'intersezione',
                    'investimento': 'Investimento di pedone',
                    'urto_fermo': 'Urto con veicolo fermo/ostacolo',
                    'senza_urto': 'Veicolo senza urto'
                };
                
                $.each(allOptions, function(key, value) {
                    // MIGLIORA: Seleziona l'opzione se corrisponde al valore salvato
                    var selected = (key === savedValue) ? ' selected' : '';
                    $tipoSelect.append('<option value="' + key + '"' + selected + '>' + value + '</option>');
                });
                
                // MIGLIORA: Ripristina il valore anche quando non c'è natura
                if (savedValue) {
                    $tipoSelect.val(savedValue);
                }
            }
        }

        // Listener per il cambio della natura dell'incidente
        $('#natura_incidente').on('change', function() {
            var natura = $(this).val();
            
            // Salva il valore corrente del tipo prima di aggiornare
            var $tipoSelect = $('#circostanza_tipo');
            if ($tipoSelect.val()) {
                $tipoSelect.data('saved-value', $tipoSelect.val());
            }
            
            updateTipoIncidenteOptions(natura);
        });

        // Inizializzazione al caricamento della pagina
        $(document).ready(function() {
            var naturaCorrente = $('#natura_incidente').val();
            if (naturaCorrente) {
                updateTipoIncidenteOptions(naturaCorrente);
            }
        });
    }

    // Aggiungere al codice esistente di admin.js - INTEGRAZIONE COMPLETA
    function initializeCircostanzeFields() {
        console.log('🔧 Inizializzazione campi circostanze avanzata...');
        
        // Se il tipo è già selezionato al caricamento, popola le opzioni
        var currentTipo = jQuery('#circostanza_tipo').val();
        if (currentTipo) {
            jQuery('#circostanza_tipo').trigger('change');
            
            // Ripristina valori salvati dopo un breve delay
            setTimeout(function() {
                var savedA = jQuery('#circostanza_veicolo_a').attr('data-saved-value');
                var savedB = jQuery('#circostanza_veicolo_b').attr('data-saved-value'); 
                
                if (savedA) jQuery('#circostanza_veicolo_a').val(savedA);
                if (savedB) jQuery('#circostanza_veicolo_b').val(savedB);
                
                console.log('✅ Valori circostanze ripristinati:', savedA, savedB);
            }, 200);
        }
    }

    // Hook nella funzione principale di inizializzazione
    jQuery(document).ready(function() {
        // Aggiungi alla funzione initializeAdmin() esistente
        if (typeof initializeAdmin === 'function') {
            var originalInit = initializeAdmin;
            initializeAdmin = function() {
                originalInit();
                initializeCircostanzeFields();
            };
        } else {
            // Se non esiste la funzione, esegui direttamente
            initializeCircostanzeFields();
        }
    });
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
        $('#txt_file').on('change', function() {
            console.log('File cambiato');
            
            var file = this.files[0];
            
            if (file) {
                console.log('File selezionato:', file.name);
                
                // Validazione tipo file
                if (!file.name.toLowerCase().endsWith('.txt')) {
                    alert('Seleziona un file TXT valido.');
                    $('#import-submit-btn').prop('disabled', true);
                    return;
                }
                
                // Abilita direttamente il bottone importa
                $('#import-submit-btn').prop('disabled', false);
            } else {
                console.log('Nessun file selezionato');
                $('#import-submit-btn').prop('disabled', true);
            }
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
            $('#import-submit-btn').prop('disabled', true);
            $('#import-preview-btn').prop('disabled', true);
        });
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
    
    // Gestione filtro autore
    if ($('#filter-by-author').length) {
        $('#filter-by-author').on('change', function() {
            var selectedAuthor = $(this).val();
            if (selectedAuthor !== '') {
                console.log('Filtrando per autore ID:', selectedAuthor);
            } else {
                console.log('Filtro autore rimosso');
            }
        });
    }
    
    /**
     * Calcolo automatico riepilogo infortunati
     */
    /* function calcolaRiepilogoInfortunati() {
        var feriti = 0;
        var morti24h = 0;
        var morti2_30gg = 0;
        
        // Conta feriti
        $('select[name*="esito_persona"]').each(function() {
            var valore = $(this).val();
            if (valore === '2') { // Ferito
                feriti++;
            } else if (valore === '1') { // Morto
                morti24h++;
            } else if (valore === '3') { // Morto dal 2° al 30° giorno
                morti2_30gg++;
            }
        });
        
        // Conta anche i morti nelle sezioni nominativi
        $('input[name*="nominativo_morto"]').each(function() {
            if ($(this).val().trim() !== '') {
                morti24h++;
            }
        });
        
        return {
            feriti: feriti,
            morti24h: morti24h,
            morti2_30gg: morti2_30gg
        };
    } */

    function mostraConfermaPubblicazione() {
        /* var riepilogo = calcolaRiepilogoInfortunati();
        var messaggio = 'Riepilogo calcolato automaticamente:\n\n';
        messaggio += 'Feriti: ' + riepilogo.feriti + '\n';
        messaggio += 'Morti entro 24h: ' + riepilogo.morti24h + '\n';
        messaggio += 'Morti dal 2° al 30° giorno: ' + riepilogo.morti2_30gg + '\n\n';
        messaggio += 'Vuoi procedere con la pubblicazione?'; */
        var messaggio = 'Vuoi procedere con la pubblicazione?';
        
        if (confirm(messaggio)) {
            // Aggiorna i campi nascosti
            /* $('#riepilogo_feriti').val(riepilogo.feriti);
            $('#riepilogo_morti_24h').val(riepilogo.morti24h);
            $('#riepilogo_morti_2_30gg').val(riepilogo.morti2_30gg);
            
            // Salva le variabili globali per uso successivo
            window.riepilogoCalcolato = riepilogo; */
            
            return true;
        }
        return false;
    }

    // Intercetta il click sul pulsante Pubblica
    $(document).ready(function() {
        /* $('#publish').on('click', function(e) {
            e.preventDefault();
            
            if (mostraConfermaPubblicazione()) {
                // Rimuovi l'event listener per evitare loop infiniti
                $(this).off('click');
                $(this).click();
            }
        }); */

        $('#publish').on('click', function(e) {
            e.preventDefault();
            
            // NUOVO: Controlla direttamente i campi obbligatori vuoti
            var campiObbligatoriVuoti = [];
            
            // Trova tutti i campi required che sono vuoti
            $('input[required], select[required]').each(function() {
                var $field = $(this);
                var valore = $field.val();
                
                // Controllo speciale per nell_abitato che può essere 0
                if ($field.attr('name') === 'nell_abitato') {
                    if (valore === '' || valore === null || valore === undefined) {
                        campiObbligatoriVuoti.push($field);
                    }
                } else {
                    // Controllo normale per altri campi
                    if (!valore || valore.trim() === '') {
                        campiObbligatoriVuoti.push($field);
                    }
                }
            });
            
            if (campiObbligatoriVuoti.length > 0) {
                // Se ci sono campi obbligatori vuoti, NON chiedere conferma
                // Evidenzia i campi vuoti per l'utente
                campiObbligatoriVuoti.forEach(function($field) {
                    $field.addClass('incidenti-validation-error');
                });
                
                // Procedi direttamente con l'invio per mostrare gli errori server-side
                $(this).off('click');
                $(this).click();
                return;
            }
            
            // Solo se tutti i campi obbligatori sono compilati, chiedi conferma
            if (mostraConfermaPubblicazione()) {
                $(this).off('click');
                $(this).click();
            }
        });
        
        // Intercetta anche "Salva bozza" se necessario
        /* $('#save-post').on('click', function(e) {
            e.preventDefault();
            
            if (mostraConfermaPubblicazione()) {
                // Rimuovi l'event listener per evitare loop infiniti
                $(this).off('click');
                $(this).click();
            }
        }); */
        $('#save-post').on('click', function(e) {
            e.preventDefault();
            
            // NUOVO: Controlla direttamente i campi obbligatori vuoti
            var campiObbligatoriVuoti = [];
            
            $('input[required], select[required]').each(function() {
                var $field = $(this);
                var valore = $field.val();
                
                if ($field.attr('name') === 'nell_abitato') {
                    if (valore === '' || valore === null || valore === undefined) {
                        campiObbligatoriVuoti.push($field);
                    }
                } else {
                    if (!valore || valore.trim() === '') {
                        campiObbligatoriVuoti.push($field);
                    }
                }
            });
            
            if (campiObbligatoriVuoti.length > 0) {
                campiObbligatoriVuoti.forEach(function($field) {
                    $field.addClass('incidenti-validation-error');
                });
                
                $(this).off('click');
                $(this).click();
                return;
            }
            
            if (mostraConfermaPubblicazione()) {
                $(this).off('click');
                $(this).click();
            }
        });

        // === DISABILITA RIORDINO METABOX ===
        // Disabilita i bottoni handle-order-lower e handle-order-higher
        $('.handle-order-lower, .handle-order-higher').hide().prop('disabled', true);

        // Disabilita drag and drop delle metabox
        if (typeof postboxes !== 'undefined') {
            $('.postbox .hndle, .postbox .handlediv').off('mousedown');
            $('.meta-box-sortables').sortable('destroy');
        }

        // Nasconde gli handle visivi per il drag
        $('.postbox .hndle').css('cursor', 'default');
    });

})(jQuery);