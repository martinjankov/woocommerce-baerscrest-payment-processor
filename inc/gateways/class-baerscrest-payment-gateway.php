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
		$this->public_key_pem  = $this->get_option( 'public_key_pem' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
	}

	/**
	 * Register payment scripts
	 *
	 * @return void
	 */
	public function payment_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			$this->id,
			WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_URL . 'assets/js/payment-processor.js',
			array( 'jquery' ),
			WC_BAERSCREST_PAYMENT_PROCESSOR_VERSION,
			true
		);

		wp_enqueue_style(
			$this->id,
			WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_URL . 'assets/css/payment-processor.css',
			array(),
			WC_BAERSCREST_PAYMENT_PROCESSOR_VERSION
		);
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
			'public_key_pem' => array(
				'title'       => __( 'Public Key PEM', 'woocommerce-baerscrest-payment-processor' ),
				'type'        => 'textarea',
				'description' => __( 'This key is used for credit card encryption that is used to generate a token', 'woocommerce-baerscrest-payment-processor' ),
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

		$order = wc_get_order( $order_id );

		$cc_number   = sanitize_text_field( wp_unslash( $_POST['baerscrest_cc_number'] ?? '' ) );
		$cc_number   = preg_replace( '/[^0-9]/', '', $cc_number );
		$cc_exp_date = sanitize_text_field( wp_unslash( $_POST['baerscrest_cc_exp_date'] ?? '' ) );
		$cc_cvc      = sanitize_text_field( wp_unslash( $_POST['baerscrest_cc_cvc'] ?? '' ) );

		$exp_date_parts = explode( '/', $cc_exp_date );
		$exp_month      = $exp_date_parts[0];
		$exp_year       = '20' . $exp_date_parts[1];

		// Handle payment method saving
		if ( $this->supports('tokenization') && isset( $_POST['wc-' . $this->id . '-payment-token'] ) && 'new' !== $_POST['wc-' . $this->id . '-payment-token'] ) {
			$token_id = wc_clean( $_POST['wc-' . $this->id . '-payment-token'] );
			$token = \WC_Payment_Tokens::get( $token_id );

			if ( $token && $token->get_user_id() === get_current_user_id() ) {
				$order->add_payment_token( $token );
			} else {
				wc_add_notice(__('Invalid payment method.', 'woocommerce'), 'error');
				return;
			}
		} elseif ( isset($_POST['wc-' . $this->id . '-new-payment-method']) && 'true' === $_POST['wc-' . $this->id . '-new-payment-method'] ) {
			$token_value = $this->generate_token( $cc_number, $exp_month, $exp_year, $cc_cvc );

			$last4     = substr( $cc_number, -4 );
			$card_type = $this->get_card_type( $cc_number );

			$token = new \WC_Payment_Token_CC();
			$token->set_token( $token_value );
			$token->set_gateway_id( $this->id );
			$token->set_card_type( $card_type );
			$token->set_last4( $last4 );
			$token->set_expiry_month( $exp_month );
			$token->set_expiry_year( $exp_year );
			$token->set_user_id( get_current_user_id() );
			$token->save();

			$order->add_payment_token( $token );
		}

		$response = $this->get_api_endpoint_response( $order, $cc_number, $exp_month, $exp_year, $cc_cvc );
		// echo '<pre>' . print_r( wp_remote_retrieve_response_code( $response ), true ) . '</pre>'; die;
		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_wp_error( $response ) && 200 <= $response_code && $response_code <= 299 ) {
			// echo '<pre>' . print_r(  $body, true ) . '</pre>'; die;
			// it could be different depending on your payment processor.
			if ( 'declined' !== $body['status'] ) {
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
			wc_add_notice(
				sprintf(
					__( 'Gateway error: %s',
					'woocommerce-baerscrest-payment-processor'
				),
				is_wp_error( $response ) ? $response->get_error_message() : $body['message']
				),
				'error'
			);
			return;
		}
	}

	/**
	 * Get credit card type
	 *
	 * @param  string $number The CC Number.
	 *
	 * @return string
	 */
	function get_card_type( $number ) {
		$number = preg_replace( '/[^0-9]/', '', $number );

		$card_types = array(
			'visa'        => '/^4[0-9]{12}(?:[0-9]{3})?$/',
			'mastercard'  => '/^5[1-5][0-9]{14}$/',
			'amex'        => '/^3[47][0-9]{13}$/',
			'discover'    => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
			'diners_club' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
			'jcb'         => '/^(?:2131|1800|35\d{3})\d{11}$/',
			'maestro'     => '/^(?:5018|5020|5038|6304|6759|676[1-3])\d{12,15}$/',
		);

		foreach ( $card_types as $type => $pattern ) {
			if ( preg_match( $pattern, $number ) ) {
				return ucfirst( $type );
			}
		}

		return 'Unknown';
	}

	/**
	 * Generate token
	 *
	 * @return string
	 */
	public function generate_token( $cc_number, $cc_month, $cc_year, $cc_cvc ) {
		// The card details here
		$payload = array(
			'number' => $cc_number,
			'expiry' => array(
				"year"  => $cc_year,
				"month" => $cc_month,
			),
			'cvc'    => $cc_cvc,
		);

		$payload_json = wp_json_encode( $payload );

		$public_key = openssl_pkey_get_public( $this->public_key_pem );

		if ( ! $public_key ) {
			return new \WP_Error(
				400,
				__( 'Invalid public key' , 'woocommerce-baerscrest-payment-processor' )
			);
		}

		$encrypted = '';
		openssl_public_encrypt( $payload_json, $encrypted, "$public_key", OPENSSL_PKCS1_OAEP_PADDING );
		openssl_free_key( $public_key );

		return base64_encode( $encrypted );
	}

	/**
	 * Get API response for paying with the card
	 *
	 * @param  WC_Order $order The order.
	 *
	 * @return array|WP_Error
	 */
	public function get_api_endpoint_response( $order, $cc_number, $cc_month, $cc_year, $cc_cvc ) {
		$type = 'token';

		$order_tokens = $order->get_payment_tokens();

		if ( empty( $order_tokens ) ) {
			$type = 'card';
		}

		$body_args = array(
			'source' => array(
				'type'    => $type,
				'address' => array(
					'address1' => $order->get_billing_address_1(),
					'address2' => $order->get_billing_address_2(),
					'city'     => $order->get_billing_city(),
					'state'    => $order->get_billing_state(),
					'country'  => $order->get_billing_country(),
					'zip'      => $order->get_billing_postcode(),
				),
			),
			'currency' => $order->get_currency(),
			'amount'   => $order->get_total(),
	 		'sender'   => array(
				'email'     => $order->get_billing_email(),
				'firstName' => $order->get_billing_first_name(),
				'lastName'  => $order->get_billing_last_name(),
			),
		);

		$token = '';

		if ( ! empty( $order_tokens ) ) {
			foreach ( $order_tokens as $order_token_id ) {
				$payment_token = \WC_Payment_Token_CC::get( $order_tokens[0] );

				if ( $payment_token ) {
					$token = $payment_token->get_token();
					break;
				}
			}
		}

		if ( empty( $token ) ) {
			$body_args['source']['type']   = 'card';
			$body_args['source']['number'] = $cc_number;
			$body_args['source']['expiry'] = array(
				'year'  => $cc_year,
				'month' => $cc_month,
			);
			$body_args['source']['cvc']    = $cc_cvc;
		} else {
			$body_args['source']['token'] = $token;
		}

		$args = array(
			'timeout' => 45,
			'headers' => array(
				'Accept'        => 'application/json',
				'api-key'       => $this->auth_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body_args ),
		);
		$endpoint_url = $this->sandbox_enabled ? $this->sandbox_url : $this->live_url;

		$response = wp_remote_post( sanitize_url( trailingslashit( $endpoint_url . 'payment' ) ), $args );

		return $response;
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
				'api-key'       => $this->auth_key,
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
