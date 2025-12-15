<?php

class IMAPPER_Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        add_filter( 'manage_edit-recipe_map_columns', array( $this, 'add_shortcode_column' ) );
        add_action( 'manage_recipe_map_posts_custom_column', array( $this, 'render_shortcode_column' ), 10, 2 );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=recipe_map',
            __( 'Need Help?', 'image-mapper' ),
            __( 'Need Help?', 'image-mapper' ),
            'manage_options',
            'imapper_help',
            array( $this, 'render_help_page' )
        );
    }
    
    public function render_help_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Image Mapper - Help & Support', 'image-mapper' ); ?></h1>
            <div class="imapper-help-section"><h2><?php _e( 'How to Use the Plugin', 'image-mapper' ); ?></h2><p><?php _e( 'Follow these simple steps to create your first interactive image map:', 'image-mapper' ); ?></p><ol><li><strong><?php _e( 'Add a New Map:', 'image-mapper' ); ?></strong> <?php _e( 'Go to Image Mapper > Add New Map. Give your map a title (e.g., "Christmas Feast").', 'image-mapper' ); ?></li><li><strong><?php _e( 'Upload Your Image:', 'image-mapper' ); ?></strong> <?php _e( 'Click "Upload/Change Image" and select the main image you want to make interactive.', 'image-mapper' ); ?></li><li><strong><?php _e( 'Add Map Areas:', 'image-mapper' ); ?></strong> <?php _e( 'Click the "Add Area" button. Fill in the Title, choose a Shape, and enter the URL it should link to.', 'image-mapper' ); ?></li><li><strong><?php _e( 'Define Coordinates:', 'image-mapper' ); ?></strong> <?php _e( 'Click "Select Coords Visually" for an area, then click directly on the image above to draw the shape.', 'image-mapper' ); ?></li><li><strong><?php _e( 'Get the Shortcode:', 'image-mapper' ); ?></strong> <?php _e( 'Save or Publish your map. The shortcode will appear in the "Shortcode" box on the right. You can also find it in the "All Maps" list.', 'image-mapper' ); ?></li><li><strong><?php _e( 'Display the Map:', 'image-mapper' ); ?></strong> <?php _e( 'Copy the shortcode and paste it into any post or page.', 'image-mapper' ); ?></li></ol></div>
            <div class="imapper-help-section"><h2><?php _e( 'About Yem Coders', 'image-mapper' ); ?></h2><p><?php _e( 'Yem Coders is dedicated to creating high-quality, easy-to-use WordPress plugins that empower you to build amazing websites. We focus on clean code, intuitive user interfaces, and excellent support.', 'image-mapper' ); ?></p></div>
            <div class="imapper-help-section"><h2><?php _e( 'Our Other Plugins', 'image-mapper' ); ?></h2><div class="imapper-plugins-grid"><div class="imapper-plugin-card"><a href="https://yemcoders.com/plugins/super-seo-toolkit" target="_blank"><h3>Super SEO Toolkit</h3><p>An all-in-one SEO solution to boost your rankings and drive more traffic to your site.</p></a></div><div class="imapper-plugin-card"><a href="https://yemcoders.com/plugins/advanced-custom-forms" target="_blank"><h3>Advanced Custom Forms</h3><p>Build powerful, beautiful forms with our intuitive drag-and-drop form builder.</p></a></div><div class="imapper-plugin-card"><a href="https://yemcoders.com/plugins/ultimate-gallery-pro" target="_blank"><h3>Ultimate Gallery Pro</h3><p>Create stunning, responsive image and video galleries in minutes. Fully customizable.</p></a></div></div></div>
        </div>
        <?php
    }

    public function enqueue_scripts( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) { return; }

        $is_map_editor = ( 'recipe_map' === $screen->post_type && ( 'post' === $screen->base || 'post-new' === $screen->base ) );
        $is_help_page = ( 'recipe_map_page_imapper_help' === $screen->id );

        if ( $is_map_editor ) {
            wp_enqueue_media();
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wplink' );
            wp_enqueue_style( 'editor-buttons' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_style( 'imapper-admin', IMAPPER_PLUGIN_URL . 'assets/css/imapper-admin.css', array(), IMAPPER_VERSION, 'all' );
            wp_enqueue_script( 'imapper-admin', IMAPPER_PLUGIN_URL . 'assets/js/imapper-admin.js', array( 'jquery', 'wp-color-picker', 'wplink' ), IMAPPER_VERSION, true );
        } elseif ( $is_help_page ) {
            wp_enqueue_style( 'imapper-admin', IMAPPER_PLUGIN_URL . 'assets/css/imapper-admin.css', array(), IMAPPER_VERSION, 'all' );
        }
    }

    public function add_shortcode_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $title ) {
            $new_columns[$key] = $title;
            if ( $key === 'title' ) {
                $new_columns['shortcode'] = __( 'Shortcode', 'image-mapper' );
            }
        }
        return $new_columns;
    }

    public function render_shortcode_column( $column_name, $post_id ) {
        if ( 'shortcode' === $column_name ) {
            $post_slug = get_post_field( 'post_name', $post_id );
            echo '<input type="text" readonly onfocus="this.select();" value="[image_mapper id=&quot;' . esc_attr( $post_slug ) . '&quot;]">';
        }
    }
}