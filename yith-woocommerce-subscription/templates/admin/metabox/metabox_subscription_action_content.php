<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Metabox for Subscription Action Content
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var YWSBS_Subscription $subscription Current subscription.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

$dev = isset( $_GET['ywsbs_dev'] ); //phpcs:ignore
?>
<div class="subscription_actions">
	<select name="ywsbs_subscription_actions">
		<option value=""><?php esc_html_e( 'Actions', 'yith-woocommerce-subscription' ); ?></option>
		<?php if ( $subscription->can_be_active() ) : ?>
			<option
				value="active"><?php esc_html_e( 'Activate Subscription', 'yith-woocommerce-subscription' ); ?></option>
		<?php endif ?>
		<?php if ( $subscription->can_be_cancelled() ) : ?>
			<option
				value="cancelled"><?php esc_html_e( 'Cancel Subscription', 'yith-woocommerce-subscription' ); ?></option>
		<?php endif ?>
	</select>
</div>
<div class="subscription_actions_footer">
	<button type="submit" class="button button-primary"
		title="<?php esc_html_e( 'Process', 'yith-woocommerce-subscription' ); ?>" name="ywsbs_subscription_button"
		value="actions"><?php esc_html_e( 'Process', 'yith-woocommerce-subscription' ); ?></button>
</div>
