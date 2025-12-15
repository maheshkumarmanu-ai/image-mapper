jQuery(document).ready(function($) {

    $('.imapper-color-picker').wpColorPicker();

    $('.imapper-tabs-wrapper .nav-tab').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var targetContent = $this.attr('href');
        $('.imapper-tabs-wrapper .nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');
        $('.imapper-tabs-wrapper .imapper-tab-content').removeClass('active');
        $(targetContent).addClass('active');
    });

    const canvas = document.getElementById('imapper-coord-canvas');
    const instructions = $('.instructions');
    let ctx, canvasImage;
    if (canvas) {
        ctx = canvas.getContext('2d', { willReadFrequently: true });
    }
    let isSelecting = false;
    let currentCoordsInput = null;
    let currentShape = null;
    let currentAreaItem = null;
    let points = [];
    var mediaUploader;
    var currentUrlInput = null;

    function loadImageOnCanvas(imageUrl) {
        if (!imageUrl || !ctx) return;
        canvasImage = new Image();
        canvasImage.crossOrigin = "anonymous";
        canvasImage.src = imageUrl;
        canvasImage.onload = () => {
            canvas.width = canvasImage.naturalWidth;
            canvas.height = canvasImage.naturalHeight;
            redrawCanvas();
            $('#visual-selector-container').show();
            $('#no-image-notice').hide();
        };
        canvasImage.onerror = () => { console.error("Image Mapper: CRITICAL - Image failed to load onto canvas."); };
    }

    function drawAllSavedShapes() {
        if (!ctx) return;
        ctx.strokeStyle = 'rgba(29, 122, 225, 0.8)';
        ctx.fillStyle = 'rgba(29, 122, 225, 0.2)';
        ctx.lineWidth = 2;
        $('#map-areas-container .map-area-item').each(function() {
            if ($(this).find('.area-shape').length === 0) return;
            const shape = $(this).find('.area-shape').val();
            const coordsStr = $(this).find('.area-coords').val();
            if (!coordsStr) return;
            const coords = coordsStr.split(',').map(Number);
            ctx.beginPath();
            if (shape === 'rect' && coords.length === 4) { ctx.rect(coords[0], coords[1], coords[2] - coords[0], coords[3] - coords[1]); } 
            else if (shape === 'circle' && coords.length === 3) { ctx.arc(coords[0], coords[1], coords[2], 0, 2 * Math.PI); } 
            else if (shape === 'poly' && coords.length >= 6) {
                ctx.moveTo(coords[0], coords[1]);
                for (let i = 2; i < coords.length; i += 2) { ctx.lineTo(coords[i], coords[i+1]); }
                ctx.closePath();
            }
            ctx.stroke();
            ctx.fill();
        });
    }

    function redrawCanvas() {
        if (!ctx || !canvasImage || !canvasImage.complete) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(canvasImage, 0, 0);
        drawAllSavedShapes();
        if (isSelecting) {
            ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.lineWidth = 2;
            points.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 5, 0, 2 * Math.PI);
                ctx.fill();
                ctx.stroke();
            });
        }
    }

    function stopSelection() {
        isSelecting = false;
        $(canvas).removeClass('is-drawing');
        $('#finish-poly-wrapper').hide();
        if(currentAreaItem) {
            currentAreaItem.removeClass('is-selecting');
        }
        instructions.text('Click "Select Coords Visually" on an area below, then click on this image to draw.');
        redrawCanvas();
    }
    
    $('#upload_main_image_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        mediaUploader = wp.media.frames.file_frame = wp.media({ title: 'Choose Image', button: { text: 'Choose Image' }, multiple: false });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#main_image_id').val(attachment.id);
            $('#remove_main_image_button').show();
            loadImageOnCanvas(attachment.url);
        });
        mediaUploader.open();
    });

    $('#remove_main_image_button').on('click', function(e) {
        e.preventDefault();
        $('#main_image_id').val('');
        $(this).hide();
        $('#visual-selector-container').hide();
        $('#no-image-notice').show().text('Please upload an image to begin.');
        if (ctx) { ctx.clearRect(0, 0, canvas.width, canvas.height); }
    });

    $('#add-area-button').on('click', function() {
        // Collapse all existing areas before adding a new one
        $('#map-areas-container .map-area-item').addClass('closed');
        
        let maxIndex = -1;
        $('#map-areas-container .map-area-item').each(function() {
            const currentIndex = $(this).data('index');
            if (currentIndex > maxIndex) { maxIndex = currentIndex; }
        });
        const newIndex = maxIndex >= 0 ? maxIndex + 1 : 0;
        const newAreaTitle = `Area #${newIndex + 1}`;
        
        var newArea = `
            <div class="map-area-item" data-index="${newIndex}">
                <h3 class="map-area-title"><span>${newAreaTitle}</span><span class="toggle-arrow"></span></h3>
                <div class="map-area-inside">
                    <p><label>Title:</label><input type="text" name="map_areas[${newIndex}][title]" value="${newAreaTitle}" class="widefat area-title-input"></p>
                    <p><label>Shape:</label><select class="area-shape" name="map_areas[${newIndex}][shape]"><option value="rect">Rectangle</option><option value="circle">Circle</option><option value="poly">Polygon</option></select></p>
                    <p><label>Coordinates:</label><input type="text" class="area-coords widefat" name="map_areas[${newIndex}][coords]" value=""></p>
                    <p><button type="button" class="button select-coords-button">Select Coords Visually</button> <button type="button" class="button clear-coords-button">Clear Coords</button></p>
                    <p><label>URL:</label><div class="url-input-group"><input type="url" name="map_areas[${newIndex}][url]" class="area-url widefat"><button type="button" class="button search-select-link-button">Search & Select</button></div></p>
                    <div class="fallback-image-uploader"><p><label>Fallback Image:</label></p><div class="fallback-image-preview"></div><input type="hidden" class="fallback-image-id" name="map_areas[${newIndex}][fallback_image_id]" value=""><button type="button" class="button upload-fallback-button">Upload Image</button> <button type="button" class="button remove-fallback-button" style="display:none;">Remove</button></div>
                    <p><label>Image Source:</label><select name="map_areas[${newIndex}][image_source]"><option value="auto" selected>Auto (Featured then Fallback)</option><option value="fallback">Force Fallback Image</option></select></p>
                    <p><label>Short Description (Optional):</label><textarea name="map_areas[${newIndex}][description]" rows="2" class="widefat"></textarea></p>
                    <p><label>Link Target:</label><select name="map_areas[${newIndex}][target]"><option value="_self" selected>Same Window</option><option value="_blank">New Window</option></select></p>
                    <button type="button" class="button remove-area-button">Remove Area</button>
                </div>
            </div>`;
        $('#map-areas-container').prepend(newArea);
    });

    $('#map-areas-container').on('click', '.remove-area-button', function() {
        $(this).closest('.map-area-item').remove();
        redrawCanvas();
    });

    $('#map-areas-container').on('click', '.clear-coords-button', function() {
        const areaItem = $(this).closest('.map-area-item');
        areaItem.find('.area-coords').val('');
        if (areaItem.is(currentAreaItem)) { points = []; }
        redrawCanvas();
    });

    // Toggle for collapsible areas
    $('#map-areas-container').on('click', '.map-area-title', function() {
        $(this).parent('.map-area-item').toggleClass('closed');
    });

    // Update title in header as user types
    $('#map-areas-container').on('keyup', '.area-title-input', function() {
        var newTitle = $(this).val();
        $(this).closest('.map-area-item').find('.map-area-title span:first-child').text(newTitle);
    });

    $('#interaction_hint_selector').on('change', function() {
        if ($(this).val() === 'hotspot') {
            $('.imapper-hotspot-size-wrapper').show();
        } else {
            $('.imapper-hotspot-size-wrapper').hide();
        }
    }).trigger('change');

    $('#map-areas-container').on('click', '.upload-fallback-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var uploader = wp.media({ title: 'Choose Fallback Image', button: { text: 'Choose Image' }, multiple: false }).on('select', function() {
            var attachment = uploader.state().get('selection').first().toJSON();
            var previewContainer = button.siblings('.fallback-image-preview');
            var idInput = button.siblings('.fallback-image-id');
            var removeButton = button.siblings('.remove-fallback-button');
            idInput.val(attachment.id);
            previewContainer.html('<img src="' + attachment.sizes.thumbnail.url + '" />');
            removeButton.show();
        }).open();
    });

    $('#map-areas-container').on('click', '.remove-fallback-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var previewContainer = button.siblings('.fallback-image-preview');
        var idInput = button.siblings('.fallback-image-id');
        idInput.val('');
        previewContainer.html('');
        button.hide();
    });

    $('#map-areas-container').on('click', '.search-select-link-button', function(e) {
        e.preventDefault();
        currentUrlInput = $(this).closest('.url-input-group').find('.area-url');
        wpLink.open();
    });
    var originalWpLinkUpdate = wpLink.update;
    wpLink.update = function() {
        if (currentUrlInput) {
            var attrs = wpLink.getAttrs();
            currentUrlInput.val(attrs.href).trigger('change');
            currentUrlInput = null;
        }
        originalWpLinkUpdate.apply(this, arguments);
    };
    var originalWpLinkClose = wpLink.close;
    wpLink.close = function() {
        currentUrlInput = null;
        originalWpLinkClose.apply(this, arguments);
    };

    $('#map-areas-container').on('click', '.select-coords-button', function() {
        if (!$('#main_image_id').val()) { alert('Please upload a main image first.'); return; }
        currentAreaItem = $(this).closest('.map-area-item');
        currentCoordsInput = currentAreaItem.find('.area-coords');
        currentShape = currentAreaItem.find('.area-shape').val();
        points = [];
        $('.map-area-item').removeClass('is-selecting');
        currentAreaItem.addClass('is-selecting');
        isSelecting = true;
        currentCoordsInput.val('');
        $(canvas).addClass('is-drawing');
        redrawCanvas();
        switch (currentShape) {
            case 'rect': instructions.text('Click for the TOP-LEFT corner, then click again for the BOTTOM-RIGHT corner.'); break;
            case 'circle': instructions.text('Click for the CENTER, then click for a point on the EDGE.'); break;
            case 'poly': instructions.text('Click points to create the polygon. Click "Finish Polygon" when done.'); $('#finish-poly-wrapper').show(); break;
        }
    });
    
    $('#finish-poly-button').on('click', stopSelection);

    $(canvas).on('click', function(e) {
        if (!isSelecting) return;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = Math.round((e.clientX - rect.left) * scaleX);
        const y = Math.round((e.clientY - rect.top) * scaleY);
        points.push({ x, y });
        redrawCanvas();
        switch (currentShape) {
            case 'rect':
                if (points.length >= 2) {
                    const x1 = Math.min(points[0].x, points[1].x);
                    const y1 = Math.min(points[0].y, points[1].y);
                    const x2 = Math.max(points[0].x, points[1].x);
                    const y2 = Math.max(points[0].y, points[1].y);
                    currentCoordsInput.val(`${x1},${y1},${x2},${y2}`);
                    stopSelection();
                }
                break;
            case 'circle':
                if (points.length >= 2) {
                    const radius = Math.round(Math.sqrt(Math.pow(points[1].x - points[0].x, 2) + Math.pow(points[1].y - points[0].y, 2)));
                    currentCoordsInput.val(`${points[0].x},${points[0].y},${radius}`);
                    stopSelection();
                }
                break;
            case 'poly':
                const polyCoords = points.map(p => `${p.x},${p.y}`).join(',');
                currentCoordsInput.val(polyCoords);
                break;
        }
    });
    
    const initialImageUrl = $(canvas).data('image-url');
    if (initialImageUrl) {
        loadImageOnCanvas(initialImageUrl);
    }

    // Initially collapse all areas on page load
    $('#map-areas-container .map-area-item').addClass('closed');
});