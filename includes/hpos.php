<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// HPOS compatibility
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', defined( 'WPCVB_LITE' ) ? WPCVB_LITE : WPCVB_FILE );
	}
} );