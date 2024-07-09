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
		var expDate = $("#wcbpp_expiration_date").val();
		var regex = /^(0[1-9]|1[0-2]) \/ [0-9]{2}$/;

		if (!regex.test(expDate)) {
			alert("Please enter a valid expiration date in the format mm / yy.");
			$("#wcbpp_expiration_date").focus();
			errorCallback();
			return false;
		}

		successCallback();
		return true;
	};

	$(function () {
		$(document).on("input", "#wcbpp_expiration_date", function () {
			var value = $(this).val().replace(/\D/g, ""); // Remove non-numeric characters

			if (value.length > 2) {
				value = value.slice(0, 2) + "/" + value.slice(2, 4); // Insert slash after the month part
			}
			console.log(value);
			$(this).val(value);
		});

		$(document).on("focus", "#wcbpp_expiration_date", function () {
			var value = $(this).val();
			if (value === "") {
				$(this).val(""); // Clear the input on focus if empty
			}
		});

		const checkout_form = $("form.woocommerce-checkout");
		// checkout_form.on("checkout_place_order", validateAccountNumber);
	});
})(jQuery);
