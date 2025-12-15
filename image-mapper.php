<?php

/**

 * Plugin Name:       Image Mapper

  * Plugin URI:       https://github.com/maheshkumarmanu-ai/image-mapper

 * Description:       Create responsive interactive image maps with a visual coordinate selector.

 * Version:           2.4.0

 * Author:            Mahesh Kumar M

 * Author URI:        https://maheshkumarm.com

 * License:           GPL v2 or later

 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html

 * Text Domain:       image-mapper

 * Domain Path:       /languages

 */



// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {

    die;

}



/**

 * Define constants for the plugin.

 */

define( 'IMAPPER_VERSION', '2.4.0' );

define( 'IMAPPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'IMAPPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );



/**

 * The core plugin class that is used to define internationalization,

 * admin-specific hooks, and public-facing site hooks.

 */

require IMAPPER_PLUGIN_DIR . 'includes/class-imapper-cpt.php';

require IMAPPER_PLUGIN_DIR . 'includes/class-imapper-shortcode.php';

require IMAPPER_PLUGIN_DIR . 'includes/class-imapper-admin.php';



/**

 * Begins execution of the plugin.

 */

function run_image_mapper() {

    $cpt = new IMAPPER_CPT();

    $cpt->init();



    $shortcode = new IMAPPER_Shortcode();

    $shortcode->init();



    $admin = new IMAPPER_Admin();

    $admin->init();

}



run_image_mapper();



/**

 * Data migration script for updates prior to 2.2.2

 */

function imapper_plugin_activation_migration() {

    if ( get_option( 'imapper_migration_v221_complete' ) ) { return; }

    $args = array( 'post_type' => 'recipe_map', 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids', );

    $map_ids = get_posts( $args );

    if ( empty( $map_ids ) ) { add_option( 'imapper_migration_v221_complete', true ); return; }

    $key_map = array( '_main_image_id' => 'main_image_id', '_map_areas' => 'map_areas', '_tooltip_bg_color' => 'tooltip_bg_color', '_tooltip_border_color' => 'tooltip_border_color', '_tooltip_animation' => 'tooltip_animation', '_interaction_hint' => 'interaction_hint', '_hotspot_size' => 'hotspot_size', '_title_color' => 'title_color', '_title_size' => 'title_size', '_desc_color' => 'desc_color', '_desc_size' => 'desc_size', '_custom_css' => 'custom_css', );

    foreach ( $map_ids as $map_id ) {

        foreach ( $key_map as $old_key => $new_key ) {

            $old_value = get_post_meta( $map_id, $old_key, true );

            if ( ! empty( $old_value ) ) {

                update_post_meta( $map_id, $new_key, $old_value );

                delete_post_meta( $map_id, $old_key );

            }

        }

    }

    add_option( 'imapper_migration_v221_complete', true );

}


register_activation_hook( __FILE__, 'imapper_plugin_activation_migration' );

