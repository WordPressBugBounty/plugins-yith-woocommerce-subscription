<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Capabilities Class.
 *
 * @class   YITH_WC_Subscription
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Capabilities' ) ) {
	/**
	 * Class YWSBS_Subscription_Helper
	 */
	class YWSBS_Subscription_Capabilities {

		/**
		 * Return the list of subscription capabilities
		 *
		 * @return array
		 * @since  2.0.0
		 */
		public static function get_capabilities() {
			$caps = array(
				'read_post'          => 'read_ywsbs_sub',
				'read_others_post'   => 'read_others_ywsbs_subs',
				'edit_post'          => 'edit_ywsbs_sub',
				'edit_posts'         => 'edit_ywsbs_subs',
				'edit_others_post'   => 'edit_others_ywsbs_subs',
				'delete_post'        => 'delete_ywsbs_sub',
				'delete_others_post' => 'delete_others_ywsbs_subs',
			);

			return apply_filters( 'ywsbs_get_subscription_capabilities', $caps );
		}

		/**
		 * Get an array of roles with subscriptions capabilities
		 *
		 * @since 3.0.0
		 * @return array
		 */
		public static function get_roles() {
			$roles = array( 'administrator' );
			if ( 'yes' === get_option( 'ywsbs_enable_shop_manager', 'yes' ) ) {
				$roles[] = 'shop_manager';
			}

			return apply_filters( 'ywsbs_roles_with_subscription_capabilities', $roles );
		}

		/**
		 * Add subscription management capabilities to Admin and Shop Manager
		 *
		 * @since 3.0.0
		 * @param null|string|string[] $roles (Optional) A single role or an array of roles. If null get default roles.
		 */
		public static function add_capabilities( $roles = null ) {
			$roles = ! empty( $roles ) ? (array) $roles : self::get_roles();
			foreach ( $roles as $role_id ) {
				$role = get_role( $role_id );
				if ( empty( $role ) ) {
					continue;
				}

				foreach ( self::get_capabilities() as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}

		/**
		 * Remove subscription management capabilities from roles
		 *
		 * @since 3.0.0
		 * @param null|string|string[] $roles (Optional) A single role or an array of roles. If null get default roles.
		 * @return void
		 */
		public static function remove_capabilities( $roles = null ) {
			$roles = ! empty( $roles ) ? (array) $roles : self::get_roles();
			foreach ( $roles as $role_id ) {
				$role = get_role( $role_id );
				if ( empty( $role ) ) {
					continue;
				}

				foreach ( self::get_capabilities() as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
