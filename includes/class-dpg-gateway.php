<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DPG_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'demo_pay';
		$this->method_title       = 'Demo Pay';
		$this->method_description = 'Practice gateway: sends the customer to a hosted payment page and waits for a signed webhook.';
		$this->has_fields         = false;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable Demo Pay',
				'default' => 'no',
			),
			'title'          => array(
				'title'   => 'Title',
				'type'    => 'text',
				'default' => 'Pay by card (Demo Pay)',
			),
			'description'    => array(
				'title'   => 'Description',
				'type'    => 'textarea',
				'default' => 'You will be sent to a secure payment page.',
			),
			'merchant_id'    => array(
				'title' => 'Merchant ID',
				'type'  => 'text',
			),
			'api_key'        => array(
				'title' => 'API key',
				'type'  => 'password',
			),
			'webhook_secret' => array(
				'title'       => 'Webhook secret',
				'type'        => 'password',
				'description' => 'Used to check that webhook messages really come from the payment provider.',
			),
			'test_mode'      => array(
				'title'   => 'Test mode',
				'type'    => 'checkbox',
				'label'   => 'Use the test environment',
				'default' => 'yes',
			),
		);
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// A real gateway calls the provider's API here to create a payment
		// and gets back the address of the provider's hosted payment page.
		$order->add_order_note( 'Customer sent to the Demo Pay payment page.' );

		// Our fake hosted payment page (we build it in Lesson 2).
		$pay_url = add_query_arg(
			array(
				'dpg_pay' => $order->get_id(),
				'key'     => $order->get_order_key(),
			),
			home_url( '/' )
		);

		return array(
			'result'   => 'success',
			'redirect' => $pay_url,
		);
	}
}