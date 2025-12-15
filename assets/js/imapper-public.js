jQuery(document).ready(function($) {

    // Activate the responsive image map library on our images.
    $('img[usemap]').rwdImageMaps();

    $('.imapper-container').each(function() {
        var container = $(this);
        var tooltip = container.find('.imapper-tooltip');
        var tooltipTitle = tooltip.find('.imapper-tooltip-title');
        var tooltipImage = tooltip.find('.imapper-tooltip-image');
        var tooltipDescription = tooltip.find('.imapper-tooltip-description');
        var fallbackNotice = container.find('.imapper-fallback-notice');
        var imageMapAreas = container.find('area');
        var imageCache = {}; 
        var hoverTimeout;
        
        const hintType = container.data('interaction-hint');

        if (hintType === 'pulse-once') {
            const hotspots = container.find('.imapper-hotspot');
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        hotspots.css('animation-play-state', 'running');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            observer.observe(container[0]);
        }

        imageMapAreas.on('mouseenter', function() {
            var area = $(this);
            var title = area.data('title');
            var url = area.data('url');
            var description = area.data('description');
            var fallbackImg = area.data('fallback-img');
            var imageSource = area.data('image-source');

            clearTimeout(hoverTimeout);
            
            tooltipTitle.text(title);
            fallbackNotice.hide(); // Hide notice by default
            
            if (description) {
                tooltipDescription.text(description).show();
            } else {
                tooltipDescription.hide();
            }

            var displayFallback = function(showNotice) {
                if (fallbackImg) {
                    tooltipImage.html('<img src="' + fallbackImg + '">');
                    if (showNotice) {
                        fallbackNotice.show();
                    }
                } else {
                    tooltipImage.html('');
                }
            };

            // --- NEW IMAGE SOURCE LOGIC ---
            if (imageSource === 'fallback') {
                // User wants to force the fallback image, so we display it immediately.
                displayFallback(false); // No notice needed when it's forced.
            } else { // 'auto' mode
                if (imageCache[url]) {
                    tooltipImage.html('<img src="' + imageCache[url] + '">');
                } else if (url) {
                    tooltipImage.html('<span class="loading-spinner">Loading...</span>');
                    $.ajax({
                        url: imapper_ajax.ajax_url,
                        type: 'POST',
                        data: { action: 'get_featured_image', url: url },
                        success: function(response) {
                            if (response.success && response.data.image_url) {
                                var imageUrl = response.data.image_url;
                                imageCache[url] = imageUrl; 
                                tooltipImage.html('<img src="' + imageUrl + '">');
                            } else {
                                displayFallback(true); // Show notice because auto failed
                            }
                        },
                        error: function() {
                             displayFallback(true); // Show notice because auto failed
                        }
                    });
                } else {
                    // No URL, so just use fallback
                    displayFallback(false);
                }
            }
            
            tooltip.addClass('visible');

        }).on('mouseleave', function() {
            hoverTimeout = setTimeout(function() {
                tooltip.removeClass('visible');
            }, 100);
        }).on('mousemove', function(e) {
            var containerRect = container[0].getBoundingClientRect();
            var x = e.clientX - containerRect.left + 20;
            var y = e.clientY - containerRect.top + 20;
            var tooltipWidth = tooltip.outerWidth();
            var tooltipHeight = tooltip.outerHeight();
            var viewportWidth = $(window).width();
            var viewportHeight = $(window).height();
            if (e.clientX + tooltipWidth + 20 > viewportWidth) {
                x = e.clientX - containerRect.left - tooltipWidth - 20;
            }
            if (e.clientY + tooltipHeight + 20 > viewportHeight) {
                y = e.clientY - containerRect.top - tooltipHeight - 20;
            }
            tooltip.css({ left: x, top: y });
        });

        tooltip.on('mouseenter', function() {
            clearTimeout(hoverTimeout);
        }).on('mouseleave', function() {
            tooltip.removeClass('visible');
        });
    });
});