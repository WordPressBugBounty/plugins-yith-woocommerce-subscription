<?php
/**
 * Traits for handling singleton classes.
 *
 * @since 3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * YITH_Vendors_Singleton_Trait class.
 *
 * @internal
 */
trait YITH_WC_Subscription_Singleton_Trait {

	/**
	 * Main instance
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Clone.
	 * Disable class cloning and throw an error on object clone.
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since  3.0.0
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, 'Something went wrong.', '1.0.0' );
	}

	/**
	 * Wakeup.
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since  3.0.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, 'Something went wrong.', '1.0.0' );
	}

	/**
	 * Get class single instance.
	 *
	 * @static
	 * @since  3.0.0
	 * @return static
	 */
	public static function get_instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}
