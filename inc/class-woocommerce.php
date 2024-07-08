<?php
/**
 * WooCommerce class
 *
 * @package WooCommerceBaerscrestPaymentProcessor
 */

namespace WooCommerceBaerscrestPaymentProcessor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce class
 */
class WooCommerce {
	use \WooCommerceBaerscrestPaymentProcessor\Traits\Singleton;

	/**
	 * Intialize class
	 *
	 * @return void
	 */
	private function initialize() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_gateway' ) );
	}

	/**
	 * Add new payment gateway
	 *
	 * @param  array $gateways List of gateways.
	 *
	 * @return array
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = \WooCommerceBaerscrestPaymentProcessor\Gateways\Baerscrest_Payment_Gateway::class;

		return $gateways;
	}
}
