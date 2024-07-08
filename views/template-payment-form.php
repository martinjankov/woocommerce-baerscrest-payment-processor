<?php
/**
 * Payment form template
 *
 * @package WooCommerceBaerscrestPaymentProcessor
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-baerscrest-payment-processor-form wc-payment-form" style="background:transparent;">
	<?php do_action( 'woocommerce_baerscrest_payment_processor_form_start', $this->id ); ?>
	<div class="form-row form-row-first">
		<label for="wcbpp_cc_number"><?php echo esc_html_e( 'Credit Card Number', 'woocommerce-baerscrest-payment-processor' ); ?> <span class="required">*</span></label>
		<input id="wcbpp_cc_number" class="input-text" type="text" autocomplete="off" name="baerscrest_cc_number"/>
	</div>
	<div class="form-row form-row-last">
		<div class="form-col-left">
			<label for="wcbpp_expiration_date"><?php echo esc_html_e( 'Expiration Date', 'woocommerce-baerscrest-payment-processor' ); ?> <span class="required">*</span></label>
			<input id="wcbpp_expiration_date" class="input-text" type="text" autocomplete="off" name="baerscrest_cc_exp_date"/>
		</div>
		<div class="form-col-right">
			<label for="wcbpp_cvv"><?php echo esc_html_e( 'CVC', 'woocommerce-baerscrest-payment-processor' ); ?> <span class="required">*</span></label>
			<input id="wcbpp_cvv" class="input-text" type="number" autocomplete="off" name="baerscrest_cc_cvc"/>
		</div>
	</div>
	<div class="clear"></div>
	<?php do_action( 'woocommerce_baerscrest_payment_processor_form_end', $this->id ); ?>
	<div class="clear"></div>
</fieldset>
