/**
 * ywsbs-product-editor.js
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 * @version 1.0.0
 */
jQuery(function ($) {

	var checkSubscriptionEndPeriod = function () {

		$(document).on('change', '.ywsbs_price_time_option, .variable_ywsbs_subscription', function () {
			var timeOption = $(this),
				$main = timeOption.closest('.ywsbs-general-section'),
				selected = $("option:selected", timeOption),
				timeOptionVal = timeOption.val();

			$main.find('.max-length-time-opt').text(selected.data('text'));

			$(document).trigger('ywsbs_price_time_option_changed', [timeOption, timeOptionVal])
		}).change();
	};

	//Open or close the subscription panel for single products.
	var isSubscription = function () {
		if ( $('#_ywsbs_subscription').is(':checked') ) {
			$('.subscription-settings_tab').show();
		} else {
			$('.subscription-settings_tab').hide();
		}
	};

	//check the option dependances.
	$(document).on('change', '.ywsbs-general-section :input', function (ev) {
		var input = $(this),
			inputType = input.attr('type'),
			inputName = input.attr('name');

		// If is radio and input is not checked skip
		if ( 'radio' === inputType && !input.is(':checked') ) {
			return false;
		}

		// Search deps.
		var depFields = $(document).find('.ywsbs-general-section [data-deps-on="' + inputName + '"]');
		if ( !depFields.length ) {
			return false;
		}

		var inputVal = 'checkbox' === inputType ? (input.is(':checked') ? 'yes' : 'no') : input.val();

		$.each(depFields, function () {
			let depValues = $(this).data('deps-val').split('|'),
				depEffect = $(this).data('deps-effect') ?? 'fade';

			if ( -1 !== $.inArray(inputVal, depValues) ) {
				switch (depEffect) {
					case 'fade':
						$(this).fadeIn();
						break;
					case 'slide':
						$(this).slideDown('slow');
						break;
					case 'plain':
						$(this).show();
						break;
				}
			} else {
				switch (depEffect) {
					case 'fade':
						$(this).fadeOut();
						break;
					case 'slide':
						$(this).slideUp('slow');
						break;
					case 'plain':
						$(this).hide();
						break;
				}
			}

			$(this).change();
		});
	});

	isSubscription();
	$(document).on('click', '#_ywsbs_subscription', isSubscription);
	$(document.body).on('woocommerce-product-type-change', isSubscription);
	checkSubscriptionEndPeriod();

	$(document).find('.ywsbs-general-section :input').change();
});