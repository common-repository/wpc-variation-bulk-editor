<?php
/*
Plugin Name: WPC Variation Bulk Editor for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Variation Bulk Editor helps you save precious time working on variations.
Version: 1.1.8
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-variation-bulk-editor
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

! defined( 'WPCVB_VERSION' ) && define( 'WPCVB_VERSION', '1.1.8' );
! defined( 'WPCVB_LITE' ) && define( 'WPCVB_LITE', __FILE__ );
! defined( 'WPCVB_FILE' ) && define( 'WPCVB_FILE', __FILE__ );
! defined( 'WPCVB_PATH' ) && define( 'WPCVB_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'WPCVB_URI' ) && define( 'WPCVB_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCVB_REVIEWS' ) && define( 'WPCVB_REVIEWS', 'https://wordpress.org/support/plugin/wpc-variation-bulk-editor/reviews/?filter=5' );
! defined( 'WPCVB_SUPPORT' ) && define( 'WPCVB_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpcpq&utm_campaign=wporg' );
! defined( 'WPCVB_CHANGELOG' ) && define( 'WPCVB_CHANGELOG', 'https://wordpress.org/plugins/wpc-variation-bulk-editor/#developers' );
! defined( 'WPCVB_DISCUSSION' ) && define( 'WPCVB_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-variation-bulk-editor' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCVB_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcvb_init' ) ) {
	add_action( 'plugins_loaded', 'wpcvb_init', 11 );

	function wpcvb_init() {
		load_plugin_textdomain( 'wpc-variation-bulk-editor', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcvb_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpcvb' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWpcvb {
				public function __construct() {
					require_once 'includes/class-backend.php';
				}
			}

			new WPCleverWpcvb();
		}
	}
}

if ( ! function_exists( 'wpcvb_notice_wc' ) ) {
	function wpcvb_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Variation Bulk Editor</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
