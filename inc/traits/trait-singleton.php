<?php
/**
 * Singleton Trait
 *
 * @package WooCommerceBaerscrestPaymentProcessor
 */

namespace WooCommerceBaerscrestPaymentProcessor\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Singleton {
	/**
	 * Instance of the object
	 *
	 * @var \Object
	 */
	private static $instance = null;

	/**
	 * Setup singleton instance
	 *
	 * @return  \Object
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Private consturct
	 *
	 * @return  void
	 */
	private function __construct() {
		if ( method_exists( $this, 'initialize' ) ) {
			$this->initialize();
		}
	}
}
