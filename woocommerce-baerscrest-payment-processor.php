<?php
/**
 * Plugin Name: WooCommerce Baerscrest Payment Processor
 * Description: Process payments using Baerscrest as Payment Processor
 * Author:      MartinCV
 * Author URI:  https://martincv.com
 * Version:     1.0
 * Text Domain: woocommerce-baerscrest-payment-processor
 * Domain Path: /languages
 *
 * @package    WooCommerceBaerscrestPaymentProcessor
 * @author     Baerscrest
 * @since      1.0
 * @copyright  Copyright (c) 2024, MartinCV
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Main Plugin Class
 */
final class WooCommerceBaerscrestPaymentProcessor {
	/**
	 * Instance of the plugin
	 *
	 * @var WooCommerceBaerscrestPaymentProcessor
	 */
	private static $instance;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '1.0';

	const DEPENDENCY_PLUGINS = array(
		'WooCommerce' => 'woocommerce/woocommerce.php',
	);

	/**
	 * Instanciate the plugin
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WooCommerceBaerscrestPaymentProcessor ) ) {
			self::$instance = new WooCommerceBaerscrestPaymentProcessor();
			self::$instance->constants();
			self::$instance->includes();
			self::$instance->check_dependencies();

			add_action( 'plugins_loaded', array( self::$instance, 'run' ) );
		}

		return self::$instance;
	}

	/**
	 * Check if plugin dependancies are installed and active
	 *
	 * @return void
	 */
	private function check_dependencies() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		$inactive = array();

		foreach ( self::DEPENDENCY_PLUGINS as $name => $slug ) {
			if ( ! in_array( $slug, $active_plugins, true ) ) {
				$inactive[] = $name;
			}
		}

		if ( ! empty( $inactive ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';

			$inactive = implode( ', ', $inactive );

			add_action(
				'admin_notices',
				function() use ( $inactive ) {
					printf(
						'<div class="error"><p>%s <strong>%s</strong> %s</p></div>',
						esc_html__( 'WooCommerce PaceFlex Payment Processor requires', 'woocommerce-baerscrest-gateway' ),
						esc_html( $inactive ),
						esc_html__( 'to be installed and activated.', 'woocommerce-baerscrest-gateway' )
					);
				}
			);

			deactivate_plugins( __FILE__ );
		}
	}

	/**
	 * 3rd party includes
	 *
	 * @return  void
	 */
	private function includes() {
		require_once WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_DIR . 'inc/core/autoloader.php';
	}

	/**
	 * Define plugin constants
	 *
	 * @return  void
	 */
	private function constants() {
		// Plugin version.
		if ( ! defined( 'WC_BAERSCREST_PAYMENT_PROCESSOR_VERSION' ) ) {
			define( 'WC_BAERSCREST_PAYMENT_PROCESSOR_VERSION', $this->version );
		}

		// Plugin Folder Path.
		if ( ! defined( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_DIR' ) ) {
			define( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_URL' ) ) {
			define( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		}

		// Plugin Root File.
		if ( ! defined( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_FILE' ) ) {
			define( 'WC_BAERSCREST_PAYMENT_PROCESSOR_PLUGIN_FILE', __FILE__ );
		}
	}

	/**
	 * Initialize classes / objects here
	 *
	 * @return  void
	 */
	public function run() {
		$this->load_textdomain();

		// Global objects.
		WooCommerceBaerscrestPaymentProcessor\WooCommerce::get_instance();
	}

	/**
	 * Register textdomain
	 *
	 * @return  void
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-baerscrest-payment-processor', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
}

WooCommerceBaerscrestPaymentProcessor::get_instance();


// add_action( 'woocommerce_new_order', 'add_engraving_notes', 1, 1 );
// function add_engraving_notes( $order_id ) {
// $order = new WC_Order( $order_id );
// $note = $order->getShippingMethod();;
// $order->add_order_note( $note );
// $order->save();
// }

add_filter( 'manage_edit-shop_order_columns', 'add_payment_method_custom_column', 20 );
function add_payment_method_custom_column( $columns ) {
     $new_columns = array();
     foreach ( $columns as $column_name => $column_info ) {
     $new_columns[ $column_name ] = $column_info;
     if ( 'order_total' === $column_name ) {
     $new_columns['order_payment'] = __( 'Payment Method', 'my-textdomain' );
     }
     }
     return $new_columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'add_payment_method_custom_column_content' );
function add_payment_method_custom_column_content( $column ) {
 global $post;
 if ( 'order_payment' === $column ) {
 $order = wc_get_order( $post->ID );
 echo $order->payment_method_title;
 }
}

/*function woocommerce_disable_autosave_for_orders(){
    global $post;

    if ( $post && get_post_type( $post->ID ) === 'shop_order' ) {
        $order = wc_get_order( $post->ID );
 		$method_title = $order->payment_method_title; ?>
 		<script type="text/javascript">
		jQuery(document).ready(function($) {
			setTimeout(function() {
 				$('#order_data .order_data_column_container .order_data_column').append('<div class="payment_method">payment_method121231231312312</div>');
 			 }, 2000);
		});
 		</script>
    <?php }
}

add_action( 'admin_print_scripts', 'woocommerce_disable_autosave_for_orders' ); */
