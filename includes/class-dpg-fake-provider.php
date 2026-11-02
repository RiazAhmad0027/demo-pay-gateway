<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAKE payment provider, for practice only.
 * In a real project this page is on the provider's website,
 * and the provider's server sends the webhook. We simulate both here.
 */
class DPG_Fake_Provider {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_show_page' ) );
	}

	public static function maybe_show_page() {
		if ( empty( $_GET['dpg_pay'] ) ) {
			return;
		}
				// The fake provider only works in test mode. Never on a live store.
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways['demo_pay'] ) || 'yes' !== $gateways['demo_pay']->get_option( 'test_mode' ) ) {
			return;
		}

		$order_id = absint( $_GET['dpg_pay'] );
		$key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$order    = wc_get_order( $order_id );

		// Only someone with the secret order key may open this page.
		if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || 'demo_pay' !== $order->get_payment_method() ) {
			wp_die( 'This payment link is not valid.', 'Demo Pay', array( 'response' => 403 ) );
		}

		// Already paid? Go to the thank-you page.
		if ( $order->is_paid() ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		// The customer clicked "Pay" or "Decline".
		if ( isset( $_POST['dpg_result'] ) ) {
			check_admin_referer( 'dpg_pay_' . $order_id );

			$result = ( 'paid' === $_POST['dpg_result'] ) ? 'paid' : 'failed';
			$sent   = self::send_webhook( $order_id, $result );

			if ( is_wp_error( $sent ) ) {
				wp_die( 'Webhook could not be sent: ' . esc_html( $sent->get_error_message() ) );
			}

			$redirect = ( 'paid' === $result ) ? $order->get_checkout_order_received_url() : $order->get_checkout_payment_url();
			wp_safe_redirect( $redirect );
			exit;
		}

		self::render_page( $order );
		exit;
	}

	private static function send_webhook( $order_id, $result ) {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$secret   = isset( $gateways['demo_pay'] ) ? $gateways['demo_pay']->get_option( 'webhook_secret' ) : '';

		$body = wp_json_encode(
			array(
				'order_id'       => $order_id,
				'status'         => $result,
				'transaction_id' => 'DP-' . wp_generate_password( 10, false ),
			)
		);

		$response = wp_remote_post(
			rest_url( 'demo-pay/v1/webhook' ),
			array(
				'headers' => array(
					'Content-Type'        => 'application/json',
					'X-DemoPay-Signature' => hash_hmac( 'sha256', $body, $secret ),
				),
				'body'    => $body,
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'dpg_webhook', 'The webhook answered with HTTP ' . $code );
		}

		return true;
	}

	private static function render_page( $order ) {
		$action = add_query_arg(
			array(
				'dpg_pay' => $order->get_id(),
				'key'     => $order->get_order_key(),
			),
			home_url( '/' )
		);
		?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo Pay</title>
<style>
body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
.box{background:#fff;padding:32px;border-radius:16px;max-width:380px;width:100%;box-shadow:0 10px 30px rgba(0,0,0,.08)}
h1{margin:0 0 4px;font-size:22px}
p{color:#475569}
.amount{font-size:32px;font-weight:800;margin:16px 0}
button{width:100%;padding:14px;border:0;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;margin-top:10px}
.pay{background:#0f766e;color:#fff}
.fail{background:#fee2e2;color:#991b1b}
.note{font-size:13px;color:#64748b;margin-top:16px}
</style>
</head>
<body>
<div class="box">
	<h1>Demo Pay</h1>
	<p>Order #<?php echo esc_html( $order->get_order_number() ); ?></p>
	<div class="amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></div>
	<form method="post" action="<?php echo esc_url( $action ); ?>">
		<?php wp_nonce_field( 'dpg_pay_' . $order->get_id() ); ?>
		<button class="pay" name="dpg_result" value="paid">Pay now (test success)</button>
		<button class="fail" name="dpg_result" value="failed">Decline (test failure)</button>
	</form>
	<p class="note">Practice payment page. No real money is used.</p>
</div>
</body>
</html>
		<?php
	}
}