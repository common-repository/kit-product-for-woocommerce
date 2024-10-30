<?php
/**
 * Plugin Name: Kit Product For Woocommerce
 * Plugin URI: https://logicfire.in/
 * Description: Allow to add multiple products in single kit and buy.
 * Author: Logicfire
 * Version: 1.1
 * Author URI: https://logicfire.in/dev/product/kit-product-regular-pricing/
 * License: GPLv2 or later
 * Text Domain: 'kit-product-for-woocommerce'
 */

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}


define( 'WCKP_VERSION', '1.1' );
define( 'WCKP_MINIMUM_WP_VERSION', '4.0' );
define( 'WCKP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCKP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once( WCKP_PLUGIN_DIR . 'inc/admin/admin-functions.php' );
require_once( WCKP_PLUGIN_DIR . 'inc/admin/class-admin.php' );
require_once( WCKP_PLUGIN_DIR . 'inc/class-front.php' );
require_once( WCKP_PLUGIN_DIR . 'inc/class-cart-work.php' );
require_once( WCKP_PLUGIN_DIR . 'inc/functions.php' );


//add_action( 'init', 'wckp_init' );

function lf_wckp( $obj ) {
	echo '<pre>';
	print_r( $obj );
	echo '</pre>';
}
