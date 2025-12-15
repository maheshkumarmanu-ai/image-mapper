<?php

class IMAPPER_CPT {

    public function init() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_data' ) );
        add_action( 'admin_head', array( $this, 'hide_editor_css' ) );
    }

    public function hide_editor_css() {
        $screen = get_current_screen();
        if ( isset($screen->post_type) && $screen->post_type == 'recipe_map' ) {
            echo '<style>#postdivrich, div#wp-content-wrap { display: none; }</style>';
        }
    }

    public function register_cpt() {
        $labels = array( 'name' => _x( 'Image Maps', 'Post Type General Name', 'image-mapper' ), 'singular_name' => _x( 'Image Map', 'Post Type Singular Name', 'image-mapper' ), 'menu_name' => __( 'Image Mapper', 'image-mapper' ), 'name_admin_bar' => __( 'Image Map', 'image-mapper' ), 'all_items' => __( 'All Maps', 'image-mapper' ), 'add_new_item' => __( 'Add New Map', 'image-mapper' ), 'add_new' => __( 'Add New Map', 'image-mapper' ), 'new_item' => __( 'New Map', 'image-mapper' ), 'edit_item' => __( 'Edit Map', 'image-mapper' ), 'search_items' => __( 'Search Maps', 'image-mapper' ), 'not_found' => __( 'Not found', 'image-mapper' ), 'not_found_in_trash' => __( 'Not found in Trash', 'image-mapper' ),);
        $args = array( 'label' => __( 'Image Maps', 'image-mapper' ), 'description' => __( 'Interactive Image Maps', 'image-mapper' ), 'labels' => $labels, 'supports' => array( 'title', 'editor' ), 'hierarchical' => false, 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_position' => 20, 'menu_icon' => 'dashicons-image-crop', 'show_in_admin_bar' => false, 'show_in_nav_menus' => false, 'can_export' => true, 'has_archive' => false, 'exclude_from_search' => true, 'publicly_queryable' => false, 'capability_type' => 'page',);
        register_post_type( 'recipe_map', $args );
    }

    public function add_meta_boxes() {
        add_meta_box('imapper_map_settings', __( 'Map Settings', 'image-mapper' ), array( $this, 'render_meta_box' ), 'recipe_map', 'normal', 'high');
        add_meta_box('imapper_map_shortcode', __( 'Shortcode', 'image-mapper' ), array( $this, 'render_shortcode_meta_box' ), 'recipe_map', 'side', 'default');
    }

    public function render_shortcode_meta_box( $post ) {
        $post_slug = $post->post_name;
        if ( empty($post_slug) ) { echo '<p>' . __('Save this map to generate the shortcode.', 'image-mapper') . '</p>'; return; }
        echo '<p>' . __('Use this shortcode to display the map:', 'image-mapper') . '</p>';
        echo '<input type="text" readonly onfocus="this.select();" value="[image_mapper id=&quot;' . esc_attr( $post_slug ) . '&quot;]" style="width:100%;">';
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'imapper_meta_box_nonce', 'imapper_meta_box_nonce' );
        $main_image_id = get_post_meta( $post->ID, 'main_image_id', true );
        $map_areas = get_post_meta( $post->ID, 'map_areas', true );
        $main_image_url = $main_image_id ? wp_get_attachment_url($main_image_id) : '';
        
        $tooltip_bg_color = get_post_meta( $post->ID, 'tooltip_bg_color', true ) ?: '#FFFFFF';
        $tooltip_border_color = get_post_meta( $post->ID, 'tooltip_border_color', true ) ?: '#CCCCCC';
        $tooltip_animation = get_post_meta( $post->ID, 'tooltip_animation', true ) ?: 'fade-in';
        $animation_speed = get_post_meta( $post->ID, 'animation_speed', true ) ?: '300';
        $interaction_hint = get_post_meta( $post->ID, 'interaction_hint', true ) ?: 'none';
        $hotspot_size = get_post_meta( $post->ID, 'hotspot_size', true ) ?: '20';
        $title_color = get_post_meta( $post->ID, 'title_color', true ) ?: '#333333';
        $title_size = get_post_meta( $post->ID, 'title_size', true ) ?: '16';
        $desc_color = get_post_meta( $post->ID, 'desc_color', true ) ?: '#666666';
        $desc_size = get_post_meta( $post->ID, 'desc_size', true ) ?: '13';
        $custom_css = get_post_meta( $post->ID, 'custom_css', true );
        ?>
        <div id="imapper-admin-wrapper">
            <input type="hidden" name="main_image_id" id="main_image_id" value="<?php echo esc_attr( $main_image_id ); ?>">
            
            <div class="imapper-tabs-wrapper">
                <h2 class="nav-tab-wrapper"><a href="#map-settings" class="nav-tab nav-tab-active">Map Settings</a><a href="#custom-css" class="nav-tab">Custom CSS</a></h2>
                <div id="map-settings" class="imapper-tab-content active">
                    <h4><?php _e( 'Map Controls', 'image-mapper' ); ?></h4>
                    <div class="imapper-main-controls"><button type="button" class="button" id="upload_main_image_button"><?php _e( 'Upload/Change Image', 'image-mapper' ); ?></button> <button type="button" class="button" id="remove_main_image_button" style="<?php echo $main_image_id ? '' : 'display:none;'; ?>"><?php _e( 'Remove Image', 'image-mapper' ); ?></button></div>
                    <h4 style="margin-top: 20px;"><?php _e( 'Tooltips Settings', 'image-mapper' ); ?></h4>
                    <div class="imapper-style-panel">
                        <div class="imapper-style-item"><label>Background</label><input type="text" name="tooltip_bg_color" class="imapper-color-picker" value="<?php echo esc_attr($tooltip_bg_color); ?>"></div>
                        <div class="imapper-style-item"><label>Border</label><input type="text" name="tooltip_border_color" class="imapper-color-picker" value="<?php echo esc_attr($tooltip_border_color); ?>"></div>
                        <div class="imapper-style-item"><label>Title Color</label><input type="text" name="title_color" class="imapper-color-picker" value="<?php echo esc_attr($title_color); ?>"></div>
                        <div class="imapper-style-item"><label>Title Size (px)</label><input type="number" name="title_size" value="<?php echo esc_attr($title_size); ?>" min="1"></div>
                        <div class="imapper-style-item"><label>Desc Color</label><input type="text" name="desc_color" class="imapper-color-picker" value="<?php echo esc_attr($desc_color); ?>"></div>
                        <div class="imapper-style-item"><label>Desc Size (px)</label><input type="number" name="desc_size" value="<?php echo esc_attr($desc_size); ?>" min="1"></div>
                        <div class="imapper-style-item">
                            <label>Animation</label>
                            <select name="tooltip_animation">
                                <option value="none" <?php selected($tooltip_animation, 'none'); ?>>None</option>
                                <option value="fade-in" <?php selected($tooltip_animation, 'fade-in'); ?>>Fade In</option>
                                <option value="zoom-in" <?php selected($tooltip_animation, 'zoom-in'); ?>>Zoom In</option>
                                <option value="slide-up" <?php selected($tooltip_animation, 'slide-up'); ?>>Slide Up</option>
                                <option value="slide-down" <?php selected($tooltip_animation, 'slide-down'); ?>>Slide Down</option>
                                <option value="fall-in" <?php selected($tooltip_animation, 'fall-in'); ?>>Fall In</option>
                                <option value="flip-in-x" <?php selected($tooltip_animation, 'flip-in-x'); ?>>Flip In (X-axis)</option>
                                <option value="flip-in-y" <?php selected($tooltip_animation, 'flip-in-y'); ?>>Flip In (Y-axis)</option>
                                <option value="bounce-in" <?php selected($tooltip_animation, 'bounce-in'); ?>>Bounce In</option>
                                <option value="fade-in-up" <?php selected($tooltip_animation, 'fade-in-up'); ?>>Fade In Up</option>
                                <option value="fade-in-down" <?php selected($tooltip_animation, 'fade-in-down'); ?>>Fade In Down</option>
                                <option value="fade-in-left" <?php selected($tooltip_animation, 'fade-in-left'); ?>>Fade In Left</option>
                                <option value="fade-in-right" <?php selected($tooltip_animation, 'fade-in-right'); ?>>Fade In Right</option>
                                <option value="bounce-in-up" <?php selected($tooltip_animation, 'bounce-in-up'); ?>>Bounce In Up</option>
                                <option value="bounce-in-down" <?php selected($tooltip_animation, 'bounce-in-down'); ?>>Bounce In Down</option>
                                <option value="rotate-in" <?php selected($tooltip_animation, 'rotate-in'); ?>>Rotate In</option>
                            </select>
                        </div>
                        <div class="imapper-style-item"><label>Animation Speed (ms)</label><input type="number" name="animation_speed" value="<?php echo esc_attr($animation_speed); ?>" min="0" step="50"></div>
                        <div class="imapper-style-item"><label>Interaction Hint</label><select name="interaction_hint" id="interaction_hint_selector"><option value="none" <?php selected($interaction_hint, 'none'); ?>>None</option><option value="pulse-once" <?php selected($interaction_hint, 'pulse-once'); ?>>Pulse Areas on Load</option><option value="hotspot" <?php selected($interaction_hint, 'hotspot'); ?>>Show Pulsing Hotspots</option></select></div>
                        <div class="imapper-style-item imapper-hotspot-size-wrapper" style="<?php echo ($interaction_hint === 'hotspot') ? '' : 'display: none;'; ?>"><label>Hotspot Size (px)</label><input type="number" name="hotspot_size" value="<?php echo esc_attr($hotspot_size); ?>" min="1"></div>
                    </div><hr>
                    <div class="imapper-editor-layout">
                        <div class="imapper-editor-left">
                            <h4><?php _e( 'Visual Editor', 'image-mapper' ); ?></h4>
                            <div id="visual-selector-container" style="<?php echo $main_image_id ? '' : 'display:none;'; ?>"><p class="instructions">Click "Select Coords Visually" on an area, then click on this image to draw.</p><div id="finish-poly-wrapper" style="display:none; padding-bottom: 10px;"><button type="button" class="button button-primary" id="finish-poly-button">Finish Polygon</button></div><canvas id="imapper-coord-canvas" data-image-url="<?php echo esc_url($main_image_url); ?>"></canvas></div>
                            <p id="no-image-notice" style="<?php echo $main_image_id ? 'display:none;' : ''; ?>"><?php _e('Please upload an image to begin.', 'image-mapper'); ?></p>
                        </div>
                        <div class="imapper-editor-right">
                            <h4><?php _e( 'Map Areas', 'image-mapper' ); ?></h4>
                            <div id="map-areas-container">
                                <?php if ( ! empty( $map_areas ) && is_array($map_areas) ) : ?>
                                    <?php foreach ( $map_areas as $index => $area ) : 
                                        $fallback_image_id = isset($area['fallback_image_id']) ? $area['fallback_image_id'] : '';
                                        $fallback_image_url = $fallback_image_id ? wp_get_attachment_image_url($fallback_image_id, 'thumbnail') : '';
                                        $image_source = isset($area['image_source']) ? $area['image_source'] : 'auto';
                                    ?>
                                        <div class="map-area-item" data-index="<?php echo $index; ?>">
                                            <h3 class="map-area-title"><span><?php echo esc_html( !empty($area['title']) ? $area['title'] : 'Area #'.($index+1) ); ?></span><span class="toggle-arrow"></span></h3>
                                            <div class="map-area-inside">
                                                <p><label>Title:</label><input type="text" name="map_areas[<?php echo $index; ?>][title]" value="<?php echo esc_attr( $area['title'] ); ?>" class="widefat area-title-input"></p>
                                                <p><label>Shape:</label><select class="area-shape" name="map_areas[<?php echo $index; ?>][shape]"><option value="rect" <?php selected( $area['shape'], 'rect' ); ?>>Rectangle</option><option value="circle" <?php selected( $area['shape'], 'circle' ); ?>>Circle</option><option value="poly" <?php selected( $area['shape'], 'poly' ); ?>>Polygon</option></select></p>
                                                <p><label>Coordinates:</label><input type="text" class="area-coords widefat" name="map_areas[<?php echo $index; ?>][coords]" value="<?php echo esc_attr( $area['coords'] ); ?>"></p>
                                                <p><button type="button" class="button select-coords-button">Select Coords Visually</button> <button type="button" class="button clear-coords-button">Clear Coords</button></p>
                                                <p><label>URL:</label><div class="url-input-group"><input type="url" name="map_areas[<?php echo $index; ?>][url]" value="<?php echo esc_url( $area['url'] ); ?>" class="area-url widefat"><button type="button" class="button search-select-link-button">Search & Select</button></div></p>
                                                <div class="fallback-image-uploader"><p><label>Fallback Image:</label></p><div class="fallback-image-preview"><?php if ($fallback_image_url) { echo '<img src="'.esc_url($fallback_image_url).'" />'; } ?></div><input type="hidden" class="fallback-image-id" name="map_areas[<?php echo $index; ?>][fallback_image_id]" value="<?php echo esc_attr($fallback_image_id); ?>"><button type="button" class="button upload-fallback-button">Upload Image</button> <button type="button" class="button remove-fallback-button" style="<?php echo $fallback_image_id ? '' : 'display:none;'; ?>">Remove</button></div>
                                                <p><label>Image Source:</label><select name="map_areas[<?php echo $index; ?>][image_source]"><option value="auto" <?php selected($image_source, 'auto'); ?>>Auto (Featured then Fallback)</option><option value="fallback" <?php selected($image_source, 'fallback'); ?>>Force Fallback Image</option></select></p>
                                                <p><label>Short Description (Optional):</label><textarea name="map_areas[<?php echo $index; ?>][description]" rows="2" class="widefat"><?php echo esc_textarea( isset($area['description']) ? $area['description'] : '' ); ?></textarea></p>
                                                <p><label>Link Target:</label><select name="map_areas[<?php echo $index; ?>][target]"><option value="_self" <?php selected( isset($area['target']) ? $area['target'] : '', '_self' ); ?>>Same Window</option><option value="_blank" <?php selected( isset($area['target']) ? $area['target'] : '', '_blank' ); ?>>New Window</option></select></p>
                                                <button type="button" class="button remove-area-button">Remove Area</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button button-primary" id="add-area-button" style="margin-top: 10px;">Add Area</button>
                            <p class="description">Scroll inside the box above to see all areas.</p>
                        </div>
                    </div>
                </div>
                <div id="custom-css" class="imapper-tab-content">
                    <h4><?php _e( 'Custom CSS', 'image-mapper' ); ?></h4>
                    <p class="description"><?php _e( 'Add your own custom CSS here. It will be loaded only when this map is displayed.', 'image-mapper' ); ?></p>
                    <textarea name="custom_css" rows="10" class="widefat" placeholder="e.g., #imapper-map-container-<?php echo $post->ID; ?> .imapper-tooltip-title { font-weight: bold; }"><?php echo esc_textarea( $custom_css ); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_meta_data( $post_id ) {
        if ( ! isset( $_POST['imapper_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['imapper_meta_box_nonce'], 'imapper_meta_box_nonce' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        
        $fields = [ 'main_image_id' => 'sanitize_text_field', 'tooltip_bg_color' => 'sanitize_hex_color', 'tooltip_border_color' => 'sanitize_hex_color', 'tooltip_animation' => 'sanitize_text_field', 'animation_speed' => 'sanitize_text_field', 'interaction_hint' => 'sanitize_text_field', 'hotspot_size' => 'sanitize_text_field', 'title_color' => 'sanitize_hex_color', 'title_size' => 'sanitize_text_field', 'desc_color' => 'sanitize_hex_color', 'desc_size' => 'sanitize_text_field', 'custom_css' => 'wp_strip_all_tags' ];
        foreach($fields as $key => $sanitize_callback) {
            if (isset($_POST[$key])) { update_post_meta($post_id, $key, $sanitize_callback($_POST[$key])); }
        }

        if ( isset( $_POST['map_areas'] ) ) {
            $sanitized_map_areas = array();
            foreach ( $_POST['map_areas'] as $area ) {
                $sanitized_map_areas[] = array( 'title' => sanitize_text_field( $area['title'] ), 'shape' => sanitize_text_field( $area['shape'] ), 'coords' => sanitize_text_field( $area['coords'] ), 'url' => esc_url_raw( $area['url'] ), 'fallback_image_id' => sanitize_text_field( $area['fallback_image_id'] ), 'image_source' => sanitize_text_field($area['image_source']), 'description' => sanitize_textarea_field( $area['description'] ), 'target' => isset( $area['target'] ) && in_array( $area['target'], [ '_self', '_blank' ] ) ? $area['target'] : '_self', );
            }
            update_post_meta( $post_id, 'map_areas', $sanitized_map_areas );
        } else { delete_post_meta( $post_id, 'map_areas' ); }
    }
}