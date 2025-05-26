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
    /**
     * Initialize shortcode functionality
     */
    function initializeShortcodes() {
        initializeStatisticsCharts();
        initializeIncidentList();
        initializeStatisticsCards();
    }
    
    /**
     * Initialize statistics charts
     */
    function initializeStatisticsCharts() {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            // Try to load Chart.js dynamically
            loadChartJS().then(function() {
                renderCharts();
            }).catch(function() {
                console.warn('Chart.js not available. Charts will not be displayed.');
            });
        } else {
            renderCharts();
        }
    }
    
    /**
     * Load Chart.js dynamically
     */
    function loadChartJS() {
        return new Promise(function(resolve, reject) {
            if (typeof Chart !== 'undefined') {
                resolve();
                return;
            }
            
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Render all charts
     */
    function renderCharts() {
        $('.stats-charts canvas[data-chart]').each(function() {
            var $canvas = $(this);
            var chartData = $canvas.data('chart');
            
            if (!chartData) return;
            
            var ctx = this.getContext('2d');
            var chartType = $canvas.data('chart-type') || 'bar';
            
            // Default chart configuration
            var config = {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y;
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            };
            
            // Create chart
            new Chart(ctx, config);
        });
    }
    
    /**
     * Initialize incident list functionality
     */
    function initializeIncidentList() {
        // Expandable details
        $('.incidenti-lista .incidente-item').on('click', function(e) {
            // Don't expand if clicking on a link or button
            if ($(e.target).is('a, button, input, select')) {
                return;
            }
            
            var $item = $(this);
            var $details = $item.find('.incidente-dettagli');
            
            if ($details.length) {
                $details.slideToggle(300);
                $item.toggleClass('expanded');
            }
        });
        
        // Add severity classes based on casualties
        $('.incidenti-lista .incidente-item').each(function() {
            var $item = $(this);
            var $morti = $item.find('.badge-morti');
            var $feriti = $item.find('.badge-feriti');
            
            if ($morti.length) {
                $item.attr('data-severity', 'fatal');
            } else if ($feriti.length) {
                $item.attr('data-severity', 'injury');
            } else {
                $item.attr('data-severity', 'damage');
            }
        });
        
        // Lazy loading for long lists
        initializeLazyLoading();
    }
    
    /**
     * Initialize statistics cards
     */
    function initializeStatisticsCards() {
        // Add counter animation
        $('.stat-number').each(function() {
            var $counter = $(this);
            var target = parseInt($counter.text());
            var current = 0;
            var increment = target / 50;
            var duration = 1000; // 1 second
            var stepTime = duration / 50;
            
            // Only animate if element is in viewport
            if (isElementInViewport(this)) {
                animateCounter($counter, current, target, increment, stepTime);
            } else {
                // Animate when scrolled into view
                $(window).on('scroll', function() {
                    if (isElementInViewport($counter[0]) && !$counter.hasClass('animated')) {
                        $counter.addClass('animated');
                        animateCounter($counter, current, target, increment, stepTime);
                    }
                });
            }
        });
        
        // Add hover effects
        $('.stat-card').hover(
            function() {
                $(this).css('transform', 'scale(1.05)');
            },
            function() {
                $(this).css('transform', 'scale(1)');
            }
        );
    }
    
    /**
     * Animate counter
     */
    function animateCounter($element, current, target, increment, stepTime) {
        var timer = setInterval(function() {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            $element.text(Math.floor(current));
        }, stepTime);
    }
    
    /**
     * Check if element is in viewport
     */
    function isElementInViewport(element) {
        var rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    /**
     * Initialize lazy loading for incident lists
     */
    function initializeLazyLoading() {
        var $lists = $('.incidenti-lista');
        
        $lists.each(function() {
            var $list = $(this);
            var $items = $list.find('.incidente-item');
            var itemsPerPage = 10;
            var currentPage = 1;
            
            if ($items.length <= itemsPerPage) {
                return; // No need for lazy loading
            }
            
            // Hide items beyond first page
            $items.slice(itemsPerPage).hide();
            
            // Add load more button
            var $loadMoreBtn = $('<button class="load-more-btn">Carica altri incidenti</button>');
            $list.append($loadMoreBtn);
            
            $loadMoreBtn.on('click', function() {
                var start = currentPage * itemsPerPage;
                var end = start + itemsPerPage;
                var $nextItems = $items.slice(start, end);
                
                $nextItems.fadeIn(300);
                currentPage++;
                
                // Hide button if no more items
                if (end >= $items.length) {
                    $loadMoreBtn.hide();
                }
            });
        });
    }
    
    /**
     * Add filtering functionality to lists
     */
    function addListFilters() {
        $('.incidenti-lista').each(function() {
            var $list = $(this);
            var $items = $list.find('.incidente-item');
            
            // Add filter controls
            var $filterContainer = $('<div class="incidenti-filter-controls"></div>');
            var $severityFilter = $('<select class="severity-filter"><option value="">Tutti i tipi</option><option value="fatal">Solo mortali</option><option value="injury">Con feriti</option><option value="damage">Solo danni</option></select>');
            var $searchInput = $('<input type="text" class="search-input" placeholder="Cerca per strada...">');
            
            $filterContainer.append($searchInput, $severityFilter);
            $list.prepend($filterContainer);
            
            // Search functionality
            $searchInput.on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                filterItems(searchTerm, $severityFilter.val());
            });
            
            // Severity filter
            $severityFilter.on('change', function() {
                var severity = $(this).val();
                filterItems($searchInput.val().toLowerCase(), severity);
            });
            
            function filterItems(searchTerm, severity) {
                $items.each(function() {
                    var $item = $(this);
                    var text = $item.text().toLowerCase();
                    var itemSeverity = $item.attr('data-severity');
                    
                    var matchesSearch = !searchTerm || text.includes(searchTerm);
                    var matchesSeverity = !severity || itemSeverity === severity;
                    
                    if (matchesSearch && matchesSeverity) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
            }
        });
    }
    
    /**
     * Export functionality for statistics
     */
    function addExportFunctionality() {
        $('.incidenti-statistics').each(function() {
            var $stats = $(this);
            
            // Add export button
            var $exportBtn = $('<button class="export-stats-btn">Esporta Statistiche</button>');
            $stats.append($exportBtn);
            
            $exportBtn.on('click', function() {
                exportStatistics($stats);
            });
        });
    }
    
    /**
     * Export statistics to CSV
     */
    function exportStatistics($statsContainer) {
        var data = [];
        
        // Extract data from cards or table
        if ($statsContainer.find('.stats-cards').length) {
            $statsContainer.find('.stat-card').each(function() {
                var $card = $(this);
                var label = $card.find('.stat-label').text();
                var value = $card.find('.stat-number').text();
                data.push([label, value]);
            });
        } else if ($statsContainer.find('.incidenti-stats-table').length) {
            $statsContainer.find('.incidenti-stats-table tr').each(function() {
                var row = [];
                $(this).find('td, th').each(function() {
                    row.push($(this).text());
                });
                data.push(row);
            });
        }
        
        // Convert to CSV
        var csv = data.map(function(row) {
            return row.join(',');
        }).join('\n');
        
        // Download
        downloadCSV(csv, 'statistiche_incidenti.csv');
    }
    
    /**
     * Download CSV file
     */
    function downloadCSV(csv, filename) {
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }
    
    /**
     * Add print functionality
     */
    function addPrintFunctionality() {
        $('.incidenti-statistics, .incidenti-lista').each(function() {
            var $container = $(this);
            
            // Add print button
            var $printBtn = $('<button class="print-btn">Stampa</button>');
            $container.append($printBtn);
            
            $printBtn.on('click', function() {
                printContainer($container);
            });
        });
    }
    
    /**
     * Print specific container
     */
    function printContainer($container) {
        var originalContents = document.body.innerHTML;
        var printContents = $container[0].outerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // Re-initialize after restoring content
        setTimeout(function() {
            initializeShortcodes();
        }, 100);
    }
    
    /**
     * Add refresh functionality for real-time data
     */
    function addRefreshFunctionality() {
        $('.incidenti-statistics, .incidenti-lista').each(function() {
            var $container = $(this);
            
            // Add refresh button
            var $refreshBtn = $('<button class="refresh-btn">Aggiorna</button>');
            $container.append($refreshBtn);
            
            $refreshBtn.on('click', function() {
                refreshShortcodeData($container);
            });
        });
    }
    
    /**
     * Refresh shortcode data via AJAX
     */
    function refreshShortcodeData($container) {
        var $refreshBtn = $container.find('.refresh-btn');
        var originalText = $refreshBtn.text();
        
        $refreshBtn.text('Aggiornamento...').prop('disabled', true);
        
        // This would need to be implemented with proper AJAX calls
        // For now, just simulate refresh
        setTimeout(function() {
            $refreshBtn.text(originalText).prop('disabled', false);
            
            // Add visual feedback
            $container.addClass('updated');
            setTimeout(function() {
                $container.removeClass('updated');
            }, 1000);
        }, 2000);
    }
    
    // Initialize all functionality
    initializeShortcodes();
    
    // Optional: Add enhanced features
    if (typeof window.IncidentiEnhanced !== 'undefined' && window.IncidentiEnhanced) {
        addListFilters();
        addExportFunctionality();
        addPrintFunctionality();
        addRefreshFunctionality();
    }
    
    // Public API
    window.IncidentiShortcodes = {
        init: initializeShortcodes,
        refreshCharts: renderCharts,
        exportStats: exportStatistics,
        printContainer: printContainer
    };
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

/**
 * CSS aggiuntivo per i controlli JavaScript
 */
var additionalCSS = `
<style>
.incidenti-filter-controls {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.incidenti-filter-controls input,
.incidenti-filter-controls select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.incidenti-filter-controls input {
    flex: 1;
    min-width: 200px;
}

.load-more-btn,
.export-stats-btn,
.print-btn,
.refresh-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin: 10px 5px;
    transition: background-color 0.3s;
}

.load-more-btn:hover,
.export-stats-btn:hover,
.print-btn:hover,
.refresh-btn:hover {
    background: #005a87;
}

.load-more-btn:disabled,
.refresh-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.incidenti-statistics.updated,
.incidenti-lista.updated {
    animation: pulse 0.5s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.stat-card {
    transition: transform 0.3s ease;
}

@media (max-width: 768px) {
    .incidenti-filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .incidenti-filter-controls input {
        min-width: auto;
    }
}
</style>
`;

// Inject additional CSS
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalCSS);
}