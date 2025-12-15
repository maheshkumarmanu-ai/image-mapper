<?php

class IMAPPER_Shortcode {

    public function init() {
        add_shortcode( 'image_mapper', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_get_featured_image', array( $this, 'get_featured_image_ajax' ) );
        add_action( 'wp_ajax_nopriv_get_featured_image', array( $this, 'get_featured_image_ajax' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'imapper-public', IMAPPER_PLUGIN_URL . 'assets/css/imapper-public.css', array(), IMAPPER_VERSION, 'all' );
        wp_enqueue_script( 'rwd-image-maps', IMAPPER_PLUGIN_URL . 'assets/js/jquery.rwdImageMaps.min.js', array( 'jquery' ), '1.6', true );
        wp_enqueue_script( 'imapper-public', IMAPPER_PLUGIN_URL . 'assets/js/imapper-public.js', array( 'jquery', 'rwd-image-maps' ), IMAPPER_VERSION, true );
        wp_localize_script( 'imapper-public', 'imapper_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    private function calculate_area_center($shape, $coords) {
        $points = explode(',', $coords);
        if (empty($points[0])) return null;
        if ($shape === 'circle' && count($points) >= 2) { return ['x' => (float)$points[0], 'y' => (float)$points[1]]; }
        if ($shape === 'rect' && count($points) >= 4) { $x1 = (float)$points[0]; $y1 = (float)$points[1]; $x2 = (float)$points[2]; $y2 = (float)$points[3]; return ['x' => $x1 + (($x2 - $x1) / 2), 'y' => $y1 + (($y2 - $y1) / 2)]; }
        if ($shape === 'poly' && count($points) >= 2) { $total_points = count($points) / 2; $sum_x = 0; $sum_y = 0; for ($i = 0; $i < count($points); $i += 2) { $sum_x += (float)$points[$i]; $sum_y += (float)$points[$i + 1]; } return ['x' => $sum_x / $total_points, 'y' => $sum_y / $total_points]; }
        return null;
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array('id' => '',), $atts, 'image_mapper' );
        if ( empty( $atts['id'] ) ) { return '<!-- Image Mapper: Shortcode ID is missing. -->'; }
        $args = array( 'name' => sanitize_title( $atts['id'] ), 'post_type' => 'recipe_map', 'post_status' => 'publish', 'posts_per_page' => 1, );
        $query = new WP_Query( $args );
        if ( ! $query->have_posts() ) { return '<!-- Image Mapper: Map with ID "' . esc_attr($atts['id']) . '" not found. -->'; }
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $main_image_id = get_post_meta( $post_id, 'main_image_id', true );
            $map_areas = get_post_meta( $post_id, 'map_areas', true );
            
            $tooltip_bg_color = get_post_meta( $post_id, 'tooltip_bg_color', true ) ?: '#FFFFFF';
            $tooltip_border_color = get_post_meta( $post_id, 'tooltip_border_color', true ) ?: '#CCCCCC';
            $tooltip_animation = get_post_meta( $post_id, 'tooltip_animation', true ) ?: 'fade-in';
            $animation_speed = get_post_meta( $post_id, 'animation_speed', true ) ?: '300';
            $interaction_hint = get_post_meta( $post_id, 'interaction_hint', true ) ?: 'none';
            $hotspot_size = get_post_meta( $post_id, 'hotspot_size', true ) ?: '20';
            $title_color = get_post_meta( $post_id, 'title_color', true ) ?: '#333333';
            $title_size = get_post_meta( $post_id, 'title_size', true ) ?: '16';
            $desc_color = get_post_meta( $post_id, 'desc_color', true ) ?: '#666666';
            $desc_size = get_post_meta( $post_id, 'desc_size', true ) ?: '13';
            $custom_css = get_post_meta( $post_id, 'custom_css', true );
            $container_id = 'imapper-map-container-' . $post_id;

            if ( ! $main_image_id || empty( $map_areas ) ) { continue; }
            $image_url_data = wp_get_attachment_image_src( $main_image_id, 'full' );
            if ( ! $image_url_data ) { continue; }
            $image_url = $image_url_data[0];
            $image_width = $image_url_data[1];
            $image_height = $image_url_data[2];
            
            echo "<style>";
            echo "#" . esc_attr($container_id) . " .imapper-tooltip { background-color: " . esc_attr($tooltip_bg_color) . "; border-color: " . esc_attr($tooltip_border_color) . "; }";
            echo "#" . esc_attr($container_id) . " .imapper-tooltip .imapper-tooltip-title { color: " . esc_attr($title_color) . "; font-size: " . esc_attr($title_size) . "px; }";
            echo "#" . esc_attr($container_id) . " .imapper-tooltip .imapper-tooltip-description { color: " . esc_attr($desc_color) . "; font-size: " . esc_attr($desc_size) . "px; }";
            if ($interaction_hint === 'hotspot') {
                echo "#" . esc_attr($container_id) . " .imapper-hotspot { width: " . esc_attr($hotspot_size) . "px; height: " . esc_attr($hotspot_size) . "px; }";
            }
            if (!empty($custom_css)) { echo $custom_css; }
            echo "</style>";
            ?>
            <div id="<?php echo esc_attr($container_id); ?>" class="imapper-container" data-interaction-hint="<?php echo esc_attr($interaction_hint); ?>">
                <img src="<?php echo esc_url( $image_url ); ?>" usemap="#<?php echo esc_attr( $atts['id'] ); ?>" class="imapper-image" width="<?php echo esc_attr($image_width); ?>" height="<?php echo esc_attr($image_height); ?>">
                <map name="<?php echo esc_attr( $atts['id'] ); ?>">
                    <?php foreach ( $map_areas as $index => $area ) : ?>
                        <?php 
                        $target = ! empty( $area['target'] ) ? $area['target'] : '_self'; 
                        $description = ! empty( $area['description'] ) ? $area['description'] : '';
                        $fallback_image_id = ! empty( $area['fallback_image_id'] ) ? $area['fallback_image_id'] : '';
                        $fallback_image_url = $fallback_image_id ? wp_get_attachment_image_url($fallback_image_id, 'medium') : '';
                        $image_source = ! empty( $area['image_source'] ) ? $area['image_source'] : 'auto';
                        ?>
                        <area id="imapper-area-<?php echo $post_id . '-' . $index; ?>" shape="<?php echo esc_attr( $area['shape'] ); ?>" coords="<?php echo esc_attr( $area['coords'] ); ?>" href="<?php echo esc_url( $area['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>" alt="<?php echo esc_attr( $area['title'] ); ?>" data-url="<?php echo esc_url( $area['url'] ); ?>" data-title="<?php echo esc_attr( $area['title'] ); ?>" data-description="<?php echo esc_attr( $description ); ?>" data-fallback-img="<?php echo esc_url($fallback_image_url); ?>" data-image-source="<?php echo esc_attr($image_source); ?>">
                    <?php endforeach; ?>
                </map>
                
                <?php if ($interaction_hint === 'hotspot' || $interaction_hint === 'pulse-once') : ?>
                    <?php foreach ($map_areas as $index => $area) : ?>
                        <?php
                        $center = $this->calculate_area_center($area['shape'], $area['coords']);
                        if ($center) :
                            $left_percent = ($center['x'] / $image_width) * 100;
                            $top_percent = ($center['y'] / $image_height) * 100;
                        ?>
                            <div class="imapper-hotspot" data-area-id="imapper-area-<?php echo $post_id . '-' . $index; ?>" style="left: <?php echo esc_attr($left_percent); ?>%; top: <?php echo esc_attr($top_percent); ?>%;"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="imapper-tooltip" data-animation="<?php echo esc_attr($tooltip_animation); ?>" style="animation-duration: <?php echo esc_attr($animation_speed); ?>ms;">
                    <h5 class="imapper-tooltip-title"></h5>
                    <div class="imapper-tooltip-image"></div>
                    <div class="imapper-tooltip-description"></div>
                    <div class="imapper-fallback-notice"><?php _e('Featured image not found. Showing fallback.', 'image-mapper'); ?></div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function get_featured_image_ajax() {
        if ( ! isset( $_POST['url'] ) || empty( $_POST['url'] ) ) { wp_send_json_error( 'Missing or empty URL' ); }
        $url = esc_url_raw( $_POST['url'] );
        $post_id = url_to_postid( $url );
        if ( $post_id && has_post_thumbnail( $post_id ) ) {
            $image_id = get_post_thumbnail_id( $post_id );
            $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            wp_send_json_success( array( 'image_url' => $image_url ) );
        }
        wp_send_json_error( 'Featured image not found' );
    }
}