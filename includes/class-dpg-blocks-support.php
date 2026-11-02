<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class DPG_Blocks_Support extends AbstractPaymentMethodType {

	// Must be the same as the gateway id.
	protected $name = 'demo_pay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_demo_pay_settings', array() );
	}

	public function is_active() {
		return isset( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'dpg-blocks',
			plugins_url( 'assets/js/blocks.js', DPG_FILE ),
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
			'0.3.0',
			true
		);
		return array( 'dpg-blocks' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array( 'products' ),
		);
	}
}