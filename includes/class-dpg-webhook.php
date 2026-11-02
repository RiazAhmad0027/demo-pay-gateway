<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DPG_Webhook {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	public static function register_route() {
		register_rest_route(
			'demo-pay/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true', // Security comes from the signature check below.
			)
		);
	}

	public static function handle( WP_REST_Request $request ) {
		$body      = $request->get_body();
		$signature = (string) $request->get_header( 'x-demopay-signature' );

		$gateways = WC()->payment_gateways()->payment_gateways();
		$secret   = isset( $gateways['demo_pay'] ) ? $gateways['demo_pay']->get_option( 'webhook_secret' ) : '';

		// 1. Is this message really from the provider?
		if ( '' === $secret || ! hash_equals( hash_hmac( 'sha256', $body, $secret ), $signature ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
		}

		$data  = json_decode( $body, true );
		$order = wc_get_order( absint( $data['order_id'] ?? 0 ) );

		// 2. Is it one of our Demo Pay orders?
		if ( ! $order || 'demo_pay' !== $order->get_payment_method() ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		// 3. Already paid? Do nothing (webhooks can arrive twice).
		if ( $order->is_paid() ) {
			return new WP_REST_Response( array( 'ok' => true, 'note' => 'Already paid' ), 200 );
		}

		$txn = sanitize_text_field( $data['transaction_id'] ?? '' );

		if ( 'paid' === ( $data['status'] ?? '' ) ) {
			$order->payment_complete( $txn );
			$order->add_order_note( 'Demo Pay: payment received. Transaction ' . $txn );
		} else {
			$order->update_status( 'failed', 'Demo Pay: payment failed or was declined.' );
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}