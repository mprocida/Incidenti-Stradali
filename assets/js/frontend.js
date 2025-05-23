/**
 * Frontend JavaScript for Incidenti Stradali Plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize maps when they become visible
    var mapObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                initializeMap(entry.target);
                mapObserver.unobserve(entry.target);
            }
        });
    });
    
    $('.incidenti-map').each(function() {
        mapObserver.observe(this);
    });
    
    /**
     * Initialize a single map
     */
    function initializeMap(mapElement) {
        var $map = $(mapElement);
        var mapId = $map.attr('id');
        
        if (!mapId || window[mapId + '_initialized']) {
            return;
        }
        
        // Mark as initialized
        window[mapId + '_initialized'] = true;
        
        // Get map configuration from data attributes or defaults
        var config = {
            center: [$map.data('center-lat') || 41.9028, $map.data('center-lng') || 12.4964],
            zoom: $map.data('zoom') || 10,
            cluster: $map.data('cluster') !== false,
            style: $map.data('style') || 'default'
        };
        
        // Initialize Leaflet map
        var map = L.map(mapId).setView(config.center, config.zoom);
        
        // Add tile layer
        var tileLayer = getTileLayer(config.style);
        tileLayer.addTo(map);
        
        // Initialize marker layer
        var markersLayer = config.cluster ? 
            L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 60
            }) : 
            L.layerGroup();
        
        map.addLayer(markersLayer);
        
        // Store references
        window[mapId + '_map'] = map;
        window[mapId + '_markers'] = markersLayer;
        
        // Load initial markers
        loadMapMarkers(mapId);
        
        // Setup event handlers for this map
        setupMapEventHandlers(mapId);
    }
    
    /**
     * Get tile layer based on style
     */
    function getTileLayer(style) {
        var layers = {
            'default': 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'satellite': 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'terrain': 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png'
        };
        
        var attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
        
        return L.tileLayer(layers[style] || layers['default'], {
            attribution: attribution,
            maxZoom: 18
        });
    }
    
    /**
     * Load markers for a specific map
     */
    function loadMapMarkers(mapId) {
        var $container = $('#' + mapId).closest('.incidenti-map-container');
        var map = window[mapId + '_map'];
        var markersLayer = window[mapId + '_markers'];
        
        if (!map || !markersLayer) {
            return;
        }
        
        // Show loading state
        $container.addClass('loading');
        
        // Get filter values
        var filters = getMapFilters(mapId);
        
        // Make AJAX request
        $.ajax({
            url: incidenti_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_incidenti_markers',
                nonce: incidenti_ajax.nonce,
                filters: filters
            },
            success: function(response) {
                $container.removeClass('loading');
                
                if (response.success) {
                    updateMapMarkers(mapId, response.data);
                    updateMapStats(mapId, response.data);
                } else {
                    showMapError(mapId, response.data || 'Errore nel caricamento dei dati');
                }
            },
            error: function(xhr, status, error) {
                $container.removeClass('loading');
                showMapError(mapId, 'Errore di connessione: ' + error);
            }
        });
    }
    
    /**
     * Get filter values for a map
     */
    function getMapFilters(mapId) {
        var $container = $('#' + mapId).closest('.incidenti-map-container');
        
        return {
            comune: $container.find('[id$="-comune-filter"]').val() || '',
            periodo: $container.find('[id$="-periodo-filter"]').val() || '',
            data_inizio: $container.find('[id$="-data-inizio"]').val() || '',
            data_fine: $container.find('[id$="-data-fine"]').val() || ''
        };
    }
    
    /**
     * Update markers on map
     */
    function updateMapMarkers(mapId, data) {
        var map = window[mapId + '_map'];
        var markersLayer = window[mapId + '_markers'];
        
        if (!map || !markersLayer || !data.markers) {
            return;
        }
        
        // Clear existing markers
        markersLayer.clearLayers();
        
        // Custom icons
        var icons = getCustomIcons();
        
        // Add new markers
        data.markers.forEach(function(markerData) {
            var icon = getMarkerIcon(markerData, icons);
            
            var marker = L.marker([markerData.lat, markerData.lng], {
                icon: icon
            });
            
            // Add popup
            if (markerData.popup) {
                marker.bindPopup(markerData.popup, {
                    maxWidth: 300,
                    className: 'incidente-popup'
                });
            }
            
            // Add click handler for detailed view
            marker.on('click', function() {
                if (markerData.id) {
                    loadIncidentDetails(markerData.id, marker);
                }
            });
            
            markersLayer.addLayer(marker);
        });
        
        // Fit map to markers if available
        if (data.markers.length > 0) {
            var group = new L.featureGroup(markersLayer.getLayers());
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }
    
    /**
     * Get custom marker icons
     */
    function getCustomIcons() {
        return {
            morto: L.divIcon({
                className: 'incidente-marker incidente-marker-morto',
                html: '<div class="marker-inner">💀</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            }),
            ferito: L.divIcon({
                className: 'incidente-marker incidente-marker-ferito',
                html: '<div class="marker-inner">🚑</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            }),
            soloDanni: L.divIcon({
                className: 'incidente-marker incidente-marker-solo-danni',
                html: '<div class="marker-inner">🚗</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            })
        };
    }
    
    /**
     * Get appropriate icon for marker
     */
    function getMarkerIcon(markerData, icons) {
        if (markerData.morti > 0) {
            return icons.morto;
        } else if (markerData.feriti > 0) {
            return icons.ferito;
        } else {
            return icons.soloDanni;
        }
    }
    
    /**
     * Update map statistics
     */
    function updateMapStats(mapId, data) {
        var $container = $('#' + mapId).closest('.incidenti-map-container');
        var $stats = $container.find('.incidenti-map-stats');
        
        if ($stats.length && data.stats_html) {
            $stats.html(data.stats_html);
        }
    }
    
    /**
     * Show map error
     */
    function showMapError(mapId, message) {
        var $container = $('#' + mapId).closest('.incidenti-map-container');
        var $stats = $container.find('.incidenti-map-stats');
        
        $stats.html('<div class="incidenti-error">' + message + '</div>');
    }
    
    /**
     * Setup event handlers for map
     */
    function setupMapEventHandlers(mapId) {
        var $container = $('#' + mapId).closest('.incidenti-map-container');
        
        // Filter button
        $container.find('[id$="-filter-btn"]').on('click', function() {
            loadMapMarkers(mapId);
        });
        
        // Period filter change
        $container.find('[id$="-periodo-filter"]').on('change', function() {
            var $customDates = $container.find('[id$="-custom-dates"]');
            
            if ($(this).val() === 'custom') {
                $customDates.show();
            } else {
                $customDates.hide();
                // Auto-reload when period changes (except custom)
                loadMapMarkers(mapId);
            }
        });
        
        // Date inputs change (for custom period)
        $container.find('[id$="-data-inizio"], [id$="-data-fine"]').on('change', function() {
            var periodo = $container.find('[id$="-periodo-filter"]').val();
            if (periodo === 'custom') {
                loadMapMarkers(mapId);
            }
        });
        
        // Enter key on inputs
        $container.find('input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                loadMapMarkers(mapId);
            }
        });
    }
    
    /**
     * Load detailed incident information
     */
    function loadIncidentDetails(incidentId, marker) {
        $.ajax({
            url: incidenti_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_incidente_details',
                nonce: incidenti_ajax.nonce,
                post_id: incidentId
            },
            success: function(response) {
                if (response.success) {
                    var details = response.data;
                    var content = buildDetailedPopupContent(details);
                    
                    marker.setPopupContent(content);
                    marker.openPopup();
                }
            }
        });
    }
    
    /**
     * Build detailed popup content
     */
    function buildDetailedPopupContent(details) {
        var html = '<div class="incidente-popup-detailed">';
        html += '<h4>' + formatDate(details.data) + ' - ' + details.ora + ':00</h4>';
        
        if (details.denominazione_strada) {
            html += '<p><strong>📍 ' + details.denominazione_strada + '</strong></p>';
        }
        
        if (details.natura_incidente) {
            html += '<p><strong>Natura:</strong> ' + getNaturaLabel(details.natura_incidente) + '</p>';
        }
        
        if (details.numero_veicoli) {
            html += '<p><strong>Veicoli coinvolti:</strong> ' + details.numero_veicoli + '</p>';
        }
        
        if (details.condizioni_meteo) {
            html += '<p><strong>Meteo:</strong> ' + getMeteoLabel(details.condizioni_meteo) + '</p>';
        }
        
        html += '</div>';
        
        return html;
    }
    
    /**
     * Format date for display
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        
        var date = new Date(dateString);
        return date.toLocaleDateString('it-IT');
    }
    
    /**
     * Get natura incidente label
     */
    function getNaturaLabel(natura) {
        var labels = {
            'A': 'Tra veicoli in marcia',
            'B': 'Tra veicolo e pedoni',
            'C': 'Veicolo in marcia che urta veicolo fermo o altro',
            'D': 'Veicolo in marcia senza urto'
        };
        
        return labels[natura] || natura;
    }
    
    /**
     * Get meteo label
     */
    function getMeteoLabel(meteo) {
        var labels = {
            '1': 'Sereno',
            '2': 'Nebbia',
            '3': 'Pioggia',
            '4': 'Grandine',
            '5': 'Neve',
            '6': 'Vento forte',
            '7': 'Altro'
        };
        
        return labels[meteo] || meteo;
    }
    
    /**
     * Initialize statistics charts
     */
    function initializeCharts() {
        $('.stats-charts canvas').each(function() {
            var $canvas = $(this);
            var chartType = $canvas.data('chart-type') || 'bar';
            
            // This would require Chart.js library
            // Implementation depends on specific chart requirements
            console.log('Chart initialization would go here for:', $canvas.attr('id'));
        });
    }
    
    /**
     * Initialize incident list functionality
     */
    function initializeIncidentList() {
        $('.incidenti-lista').each(function() {
            var $list = $(this);
            
            // Add click handlers for expandable details
            $list.find('.incidente-item').on('click', function() {
                var $item = $(this);
                var $details = $item.find('.incidente-dettagli');
                
                if ($details.length) {
                    $details.toggle();
                    $item.toggleClass('expanded');
                }
            });
        });
    }
    
    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        var $window = $(window);
        
        $window.on('resize', function() {
            // Resize maps
            $('.incidenti-map').each(function() {
                var mapId = $(this).attr('id');
                var map = window[mapId + '_map'];
                
                if (map) {
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 100);
                }
            });
        });
    }
    
    /**
     * Initialize all components
     */
    function initialize() {
        initializeCharts();
        initializeIncidentList();
        handleResponsive();
    }
    
    // Initialize when DOM is ready
    initialize();
    
    /**
     * Public API for external use
     */
    window.IncidentiStradali = {
        loadMapMarkers: loadMapMarkers,
        getMapFilters: getMapFilters,
        refreshMap: function(mapId) {
            loadMapMarkers(mapId);
        },
        // Add more public methods as needed
    };
    
    /**
     * Handle print functionality
     */
    window.addEventListener('beforeprint', function() {
        // Expand all collapsed sections for printing
        $('.incidente-dettagli').show();
        $('.incidenti-toggle-content').show();
    });
    
    window.addEventListener('afterprint', function() {
        // Restore original state after printing
        $('.incidente-dettagli').hide();
        $('.incidenti-toggle-content').hide();
    });
});

/**
 * Utility functions available globally
 */
window.IncidentiUtils = {
    /**
     * Format number with thousands separator
     */
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    
    /**
     * Debounce function calls
     */
    debounce: function(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Check if element is in viewport
     */
    isInViewport: function(element) {
        var rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
};