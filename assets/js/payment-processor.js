(function ($) {
	const successCallback = function (data) {
		const checkout_form = $("form.woocommerce-checkout");

		// deactivate the validateAccountNumber function event
		checkout_form.off("checkout_place_order", validateAccountNumber);

		// submit the form now
		checkout_form.submit();
	};

	const errorCallback = function (data) {
		console.log(data);
	};

	const validateAccountNumber = function () {
		// here will be a payment gateway function that process all the card data from your form,
		// maybe it will need your Publishable API key which is misha_params.publishableKey
		// and fires successCallback() on success and errorCallback on failure
		return false;
	};

	$(function () {
		const checkout_form = $("form.woocommerce-checkout");
		checkout_form.on("checkout_place_order", validateAccountNumber);
	});
})(jQuery);
