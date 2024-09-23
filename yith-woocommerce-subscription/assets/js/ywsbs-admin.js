/**
 * admin.js
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 * @version 1.0.0
 */

jQuery(function ($) {
	"use strict";

	function toggle_product_editor_single_product() {
		if ( $('#_ywsbs_subscription').prop('checked') ) {
			$('.ywsbs_price_is_per, .ywsbs_max_length').show();
		} else {
			$('.ywsbs_price_is_per, .ywsbs_max_length').hide();
		}
	}

	$('#_ywsbs_subscription').on('change', function () {
		toggle_product_editor_single_product();
	});
	toggle_product_editor_single_product();

	$('#_ywsbs_price_time_option').on('change', function () {
		$('.ywsbs_max_length .description span').text($(this).val());
		var selected = $(this).find(':selected'),
			max_value = selected.data('max');
		$('.ywsbs_max_length .description .max-l').text(max_value);
	});

	$(document).on('click', '#cbs input', function () {
		var $t = $(this);
		if ( $t.is(':checked') ) {
			$('.check_subscription').attr('checked', true);
			$('.column-cbs input').attr('checked', true);
		} else {
			$('.check_subscription').attr('checked', false);
			$('.column-cbs input').attr('checked', false);
		}
	});

	/**
	 * SUBSCRIPTION EDITOR TITLE
	 */
	if ( $(document).find('.wp-heading-inline').length > 0 ) {
		$('<div class="view-all-subs"><a href="' + yith_ywsbs_admin.url_back_to_all_subscription + '"> < ' + yith_ywsbs_admin.back_to_all_subscription + '</a></div>').insertBefore('.wp-heading-inline');
	}
});