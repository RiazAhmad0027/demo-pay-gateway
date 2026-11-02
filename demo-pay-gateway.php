<?php
/**
 * Plugin Name: Demo Pay Gateway
 * Description: Practice WooCommerce payment gateway: redirect to a hosted payment page, signed webhook, Block checkout support.
 * Version: 0.1.0
 * Author: Riaz Ahmad
 * Requires Plugins: woocommerce
 * Text Domain: demo-pay-gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPG_FILE', __FILE__ );
define( 'DPG_DIR', plugin_dir_path( __FILE__ ) );

// Tell WooCommerce this plugin supports the new order storage (HPOS) and the Block checkout.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DPG_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', DPG_FILE, true );
	}
} );

// Load the gateway after WooCommerce is ready.
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	require_once DPG_DIR . 'includes/class-dpg-gateway.php';
	require_once DPG_DIR . 'includes/class-dpg-webhook.php';
	require_once DPG_DIR . 'includes/class-dpg-fake-provider.php';

	DPG_Webhook::init();
	DPG_Fake_Provider::init();

	add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
		$gateways[] = 'DPG_Gateway';
		return $gateways;
	} );
} );

// Make the gateway visible in the new Block checkout.
add_action( 'woocommerce_blocks_loaded', function () {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}
	require_once DPG_DIR . 'includes/class-dpg-blocks-support.php';

	add_action( 'woocommerce_blocks_payment_method_type_registration', function ( $registry ) {
		$registry->register( new DPG_Blocks_Support() );
	} );
} );