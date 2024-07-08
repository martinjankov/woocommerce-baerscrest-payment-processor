<?php
/**
 * Baerscrest Payment gateway class
 *
 * @package WooCommerceBaerscrestPaymentProcessor
 */

namespace WooCommerceBaerscrestPaymentProcessor\Gateways;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baerscrest Payment gateway class
 */
class Baerscrest_Payment_Gateway extends \WC_Payment_Gateway {
	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->id                 = 'wc_baerscrest_payment_processor';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'Baerscrest Payment Processor', 'woocommerce-baerscrest-payment-processor' );
		$this->method_description = __( 'Allows you to pay using the Baerscrest Payment Processor', 'woocommerce-baerscrest-payment-processor' );

		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
            'add_payment_method',
		);

		$this->init_form_fields();

		$this->init_settings();
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->enabled         = $this->get_option( 'enabled' );
		$this->auth_key        = $this->get_option( 'auth_key' );
		$this->payment_type    = $this->get_option( 'payment_type' );
		$this->sandbox_enabled = $this->get_option( 'sandbox_enabled' );
		$this->sandbox_url     = $this->get_option( 'sandbox_url' );
		$this->live_url        = $this->get_option( 'live_url' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Show the plugin options
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-baerscrest-payment-processor' ),
				'label'       => __( 'Enable Baerscrest Payment Processor', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-baerscrest-payment-processor' ),
				'default'     => 'Baerscrest',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-baerscrest-payment-processor' ),
				'default'     => __( 'Pay with credit card using Baerscrest Payment Processor.', 'woocommerce-baerscrest-payment-processor' ),
			),
			'auth_key'     => array(
				'title' => __( 'Auth Token', 'woocommerce-baerscrest-payment-processor' ),
				'type'  => 'password',
			),
			'payment_type' => array(
				'title'       => __( 'Payment Type', 'woocommerce-baerscrest-payment-processor' ),
				'description' => __( 'The Baerscrest payment processor API enpoint offers option to send creadit card data directly or use token for which an encryption key needs to be added to generate the token. <a href="https://sandbox-app.baerscrest.com/api/#/Payment/PaymentController_create" target="_blank">Payment API</a>', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'select',
				'options'     => array(
					'card'  => __( 'Card', 'woocommerce-baerscrest-payment-processor' ),
					'token' => __( 'Token', 'woocommerce-baerscrest-payment-processor' ),
				),
				'default'     => 'card',
			),
			'sandbox_enabled'     => array(
				'title'       => __( 'Enable/Disable Sandox', 'woocommerce-baerscrest-payment-processor' ),
				'label'       => __( 'Use Sandbox for testing Payment Processor', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'sandbox_url'       => array(
				'title'       => __( 'Sandbox API URL', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'text',
			),
			'live_url'       => array(
				'title'       => __( 'Live API URL', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'text',
			),
		);
	}

	/**
	 * Payment fields
	 *
	 * @return void
	 */
	public function payment_fields() {
		if (is_user_logged_in() && $this->supports('tokenization')) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->save_payment_method_checkbox();
        }

		if ( $this->description ) {
			echo wp_kses_post( wpautop( $this->description ) );
			ob_start();
			require_once WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_DIR . 'views/template-payment-form.php';
			echo ob_get_clean(); //phpcs:ignore
		}
	}

	/**
	 * Validation of the fields
	 *
	 * @return boolean
	 */
	public function validate_fields() {
		if ( empty( $_POST['baerscrest_cc_number'] ) ) {
			wc_add_notice( __( 'Credit Card Number is required!', 'woocommerce-baerscrest-payment-processor' ), 'error' );
			return false;
		}

		if ( empty( $_POST['baerscrest_cc_exp_date'] ) ) {
			wc_add_notice( __( 'Expiration date is required!', 'woocommerce-baerscrest-payment-processor' ), 'error' );
			return false;
		}

		if ( empty( $_POST['baerscrest_cc_cvc'] ) ) {
			wc_add_notice( __( 'CVC is required!', 'woocommerce-baerscrest-payment-processor' ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Processing the payments
	 *
	 * @param  int $order_id The order id.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;

		// we need it to get any order detailes.
		$order = wc_get_order( $order_id );

		$body_args = array(
			'source' => array(
				'type'    => $this->payment_type,
				'address' => array(
					'address1' => '',
					'address2' => '',
					'city'     => '',
					'state'    => '',
					'country'  => '',
					'zip'      => '',
				),
			),
			'currency' => 'USD',
			'amount'   => 12.34,
	 		'sender'   => array(
				'email'     => '',
				'firstName' => '',
				'lastName'   => '',
			),
		);

		if ( 'card' === $this->payment_type ) {
			$body_args['source']['number'] = sanitize_text_field( $_POST['baerscrest_cc_number'] );
			$body_args['source']['expiry'] = array(
				'year' => sanitize_text_field( $_POST['baerscrest_cc_exp_date'] ),
				'month' => sanitize_text_field( $_POST['baerscrest_cc_exp_date'] ),
			);
			$body_args['source']['number'] = sanitize_text_field( $_POST['baerscrest_cc_cvc'] );
		} else {
			$body_args['source']['token'] = 'token here';
		}

		$args = array(
			'timeout' => 45,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => $this->auth_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body_args ),
		);

		$endpoint_url = $this->sandbox_enabled ? $this->sandbox_url : $this->live_url;

		$response = wp_remote_post( sanitize_url( trailingslashit( $endpoint_url . 'payment' ) ), $args );
		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			// echo '<pre>' . print_r(  $body, true ) . '</pre>'; die;
			// it could be different depending on your payment processor.
			if ( 'deliced' !== $body['status'] ) {
				// we received the payment.
				$order->payment_complete();
				$order->reduce_order_stock();

				// some notes to customer (replace true with false to make it private).
				$order->add_order_note( 'Your order is paid! Thank you!', true );

				if ( ! empty( $body['customer_address'] ) ) {
					$order->add_order_note( "Baerscrest payment\n\n" . $body['customer_address'], false );
				}

				$order->update_meta_data( 'baerscrest_payment_id', $body['_id'] );
				$order->save();

				$woocommerce->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$order->add_order_note( "Baerscrest payment\n\n" . $body['message'] );

				$order->set_status( 'failed' );
				$order->save();

				// translators: %s - Error message.
				wc_add_notice( sprintf( __( 'Error: %s', 'woocommerce-baerscrest-payment-processor' ), $body['message'] ), 'error' );
				return;
			}
		} else {
			// translators: %s - Error message.
			wc_add_notice( sprintf( __( 'Gateway error: %s', 'woocommerce-baerscrest-payment-processor' ), $response->get_error_message() ), 'error' );
			return;
		}
	}

	/**
	 * Process refund
	 *
	 * @param  int    $order_id The order id.
	 * @param  float  $amount   The amount to be refundend.
	 * @param  string $reason   The refund reason.
	 *
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( ! $amount ) {
			return new \WP_Error(
				400,
				__( 'Amount is required', 'woocommerce-baerscrest-payment-processor' )
			);
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new \WP_Error(
				400,
				__( 'Order not found', 'woocommerce-baerscrest-payment-processor' )
			);
		}

		$order_total = $order->get_total();

		if ( (float) abs( $amount ) > (float) $order_total ) {
			return new \WP_Error(
				400,
				__( 'Refund amount needs to be equal or less than the order total', 'woocommerce-baerscrest-payment-processor' )
			);
		}

		$baerscrest_order_id = $order->get_meta( 'baerscrest_payment_id', true );

		if ( empty( $baerscrest_order_id ) ) {
			return new \WP_Error(
				404,
				__( 'Unable to process the refund. Unknown payment in Baerscrest', 'woocommerce-baerscrest-payment-processor' )
			);
		}

		$args = array(
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => $this->auth_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'amount' => (float) $amount,
				)
			),
		);

		$endpoint_url = $this->sandbox_enabled ? $this->sandbox_url : $this->live_url;

		$response = wp_remote_post( sanitize_url( trailingslashit( $endpoint_url . 'refund' . $baerscrest_order_id ) ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 201 !== (int) $response['code'] ) {
			return new \WP_Error(
				$response['code'],
				$response['message'],
			);
		}

		return true;
	}
}
