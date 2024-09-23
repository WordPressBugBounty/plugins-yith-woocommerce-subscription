<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Autoloader class. This is used to decrease memory consumption
 *
 * @since   2.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Autoloader' ) ) {
	/**
	 * Class YITH_WC_Subscription_Autoloader
	 *
	 * @since 2.0.0
	 */
	class YITH_WC_Subscription_Autoloader {


		/**
		 * Constructor
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
		}

		/**
		 * Get an array of registered paths
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_registered_paths() {
			return apply_filters( 'yith_ywsbs_autoload_registered_path', array() );
		}

		/**
		 * Autoload callback
		 *
		 * @since  2.0.0
		 * @param string $class Load the class.
		 */
		public function autoload( $class ) {

			$path            = YITH_YWSBS_INC;
			$class           = str_replace( '_', '-', strtolower( $class ) );
			$file            = "class-{$class}.php";
			$admin_files     = array(
				'class-ywsbs-product-post-type-admin.php',
				'class-ywsbs-subscription-post-type-admin.php',
				'class-ywsbs-subscription-list-table.php',
			);
			$registered_path = $this->get_registered_paths();

			// Search first for registered path.
			if ( in_array( $file, $admin_files, true ) ) {
				$path .= 'admin/';
			} elseif ( array_key_exists( $file, $registered_path ) ) {
				$path = $registered_path[ $file ];
			} else {
				// Search now dynamically.
				if ( false !== strpos( $class, 'module' ) ) {
					$path .= 'modules/';
				} elseif ( false !== strpos( $class, 'trait' ) ) {
					$file  = 'trait-' . str_replace( '-trait', '', $class ) . '.php';
					$path .= 'traits/';
				} elseif ( false !== strpos( $class, 'legacy' ) ) {
					$file  = 'abstract-' . str_replace( '_', '-', $class ) . '.php';
					$path .= 'legacy/';
				}
			}

			if ( file_exists( $path . $file ) && is_readable( $path . $file ) ) {
				include_once $path . $file;
			}
		}
	}
}

new YITH_WC_Subscription_Autoloader();
