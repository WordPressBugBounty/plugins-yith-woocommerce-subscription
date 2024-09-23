<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements admin features of YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Admin
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Admin' ) ) {
	/**
	 * Class YITH_WC_Subscription_Admin
	 */
	class YITH_WC_Subscription_Admin {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Panel page
		 *
		 * @var string Panel page
		 */
		protected $panel_page = 'yith_woocommerce_subscription';

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		protected function __construct() {

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWSBS_DIR . '/' . basename( YITH_YWSBS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
			// custom styles and javascripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 11 );
			// product editor.
			add_filter( 'product_type_options', array( $this, 'add_type_options' ) );
			// Sanitize the options before that are saved.
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_value_option' ), 20, 3 );
			add_filter( 'update_option_ywsbs_enable_shop_manager', array( $this, 'maybe_regenerate_shop_manager_capabilities' ), 10, 3 );
		}

		/**
		 * Add a product type option in single product editor
		 *
		 * @access public
		 *
		 * @param array $types Types.
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public function add_type_options( $types ) {
			$types['ywsbs_subscription'] = array(
				'id'            => '_ywsbs_subscription',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Subscription', 'yith-woocommerce-subscription' ),
				'description'   => __( 'Create a subscription for this product', 'yith-woocommerce-subscription' ),
				'default'       => 'no',
			);
			return $types;
		}


		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue_styles_scripts() {

			wp_register_style( 'yith_ywsbs_backend', YITH_YWSBS_ASSETS_URL . '/css/backend.css', array( 'woocommerce_admin_styles', 'jquery-ui-style' ), YITH_YWSBS_VERSION );
			wp_register_script( 'yith_ywsbs_admin', YITH_YWSBS_ASSETS_URL . '/js/ywsbs-admin' . YITH_YWSBS_SUFFIX . '.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );
			wp_register_script( 'jquery-blockui', YITH_YWSBS_ASSETS_URL . '/js/jquery.blockUI.min.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );
			wp_register_style( 'yith-ywsbs-product', YITH_YWSBS_ASSETS_URL . '/css/ywsbs-product-editor.css', array( 'yith-plugin-fw-fields' ), YITH_YWSBS_VERSION );

			wp_register_script( 'yith-ywsbs-product', YITH_YWSBS_ASSETS_URL . '/js/ywsbs-product-editor' . YITH_YWSBS_SUFFIX . '.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'edit-' . YITH_YWSBS_POST_TYPE === $screen_id || ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE ) || ( isset( $_REQUEST['page'] ) && 'yith_woocommerce_subscription' === $_REQUEST['page'] ) ) { //phpcs:ignore
				wp_enqueue_style( 'yith_ywsbs_backend' );
				wp_enqueue_script( 'yith_ywsbs_admin' );
				wp_enqueue_script( 'selectWoo' );
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( ywsbs_check_valid_admin_page( 'product' ) ) {
				wp_enqueue_style( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			wp_localize_script(
				'yith_ywsbs_admin',
				'yith_ywsbs_admin',
				array(
					'ajaxurl'                      => admin_url( 'admin-ajax.php' ),
					'back_to_all_subscription'     => esc_html__( 'back to all subscriptions', 'yith-woocommerce-subscription' ),
					'url_back_to_all_subscription' => add_query_arg( array( 'post_type' => YITH_YWSBS_POST_TYPE ), admin_url( 'edit.php' ) ),
					'block_loader'                 => apply_filters( 'yith_ywsbs_block_loader_admin', YITH_YWSBS_ASSETS_URL . '/images/block-loader.gif' ),
				)
			);
		}

		/**
		 * Get an array of panel tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_panel_tabs() {
			return apply_filters(
				'ywsbs_register_panel_tabs',
				array(
					'subscriptions' => array(
						'title' => __( 'Subscriptions', 'yith-woocommerce-subscription' ),
						'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>',
					),
					'general'       => array(
						'title'       => __( 'General settings', 'yith-woocommerce-subscription' ),
						'description' => __( 'Set the general behaviour of the plugin.', 'yith-woocommerce-subscription' ),
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
					),
					'customization' => array(
						'title'       => __( 'Customization', 'yith-woocommerce-subscription' ),
						'description' => __( 'Set custom labels to create your own style.', 'yith-woocommerce-subscription' ),
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" /></svg>',
					),
				)
			);
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @use      /YIT_Plugin_Panel_WooCommerce class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH WooCommerce Subscriptions',
				'menu_title'       => 'Subscriptions',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->panel_page,
				'plugin_slug'      => YITH_YWSBS_SLUG,
				'plugin-url'       => YITH_YWSBS_URL,
				'admin-tabs'       => $this->get_panel_tabs(),
				'class'            => yith_set_wrapper_class(),
				'ui_version'       => 2,
				'options-path'     => YITH_YWSBS_DIR . '/plugin-options',
				'is_free'          => true,
				'premium_tab'      => array(
					'features' => $this->get_premium_features(),
				),
			);

			// enable shop manager to set Manage subscriptions.
			if ( 'yes' === get_option( 'ywsbs_enable_shop_manager' ) ) {
				add_filter( 'option_page_capability_yit_' . $args['parent'] . '_options', array( $this, 'change_capability' ) );
				$args['capability'] = 'manage_woocommerce';
			}

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel' ) ) {
				require_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel.php';
			}
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Get premium tab features array
		 *
		 * @since 3.0.0
		 * @return array
		 */
		protected function get_premium_features() {
			return array(
				array(
					'title'       => __( 'Extra options to set up subscription plans', 'yith-woocommerce-subscription' ),
					'description' => __( 'In the premium version, you can set up weekly and annual subscriptions and use product variations to set up different subscription plans for the same product. ', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Offer a free trial period and push users to subscribe', 'yith-woocommerce-subscription' ),
					'description' => __( 'A free trial period can be the most effective tool to push users to sign up and try your products or services for free and without a commitment: when the trial period expires, it will be easier to push them to subscribe and increase conversions.', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Set a subscription fee for subscriptions   ', 'yith-woocommerce-subscription' ),
					'description' => __( 'Choose whether to charge an extra fee (subscription fee, insurance, etc.) to your customers when they subscribe, define the amount, and choose the label to display it in your shop.', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Create advanced discount coupons to push users to subscribe', 'yith-woocommerce-subscription' ),
					'description' => __( 'In the premium version, you can generate coupons to offer discounts on the first payment (when users sign up for the subscription) or on subsequent recurring payments (e.g. a 50% discount on the first three months, a strategy also implemented by Amazon!)', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Synchronize all recurring payments to a specific day (of the week, month, etc.)', 'yith-woocommerce-subscription' ),
					'description' => __( 'Simplify subscription management by synchronizing all recurring payments to a specific day (every Monday, the 1st of each month, etc.) and choose how to handle the first payment at the time of subscription: you can ask users to pay a prorated amount or postpone the payment of the total amount to the day set in the synchronization.', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Schedule the shipping of the subscription products', 'yith-woocommerce-subscription' ),
					'description' => __( 'If subscriptions include shipping a product (e.g. a box of products, a print magazine, etc.) use the dedicated option to schedule the shipping and decide whether or not to synchronize all the shipments to the usual day (e.g. the box will be sent every 5th day of the month, the magazine every Monday, etc.).', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Create and manage subscriptions manually', 'yith-woocommerce-subscription' ),
					'description' => __( 'Has one of your customers decided to subscribe and you want to manage the subscription manually? In the premium version, you can create subscriptions manually to manage cash payments and not lose customers who would find it difficult to subscribe on your site (due to age, lack of time, unfamiliarity with using an e-commerce, etc.).', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Extra options to pay for the subscriptions', 'yith-woocommerce-subscription' ),
					'description' => __( 'In the free version, your customers have to pay for the subscription with PayPal. With the premium version, you can offer additional payment methods and use one of our plugins (e.g. Stripe) to charge their credit cards automatically.', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Sell Subscription Boxes', 'yith-woocommerce-subscription' ),
					'description' => __( 'Our plugin is the only one out there that lets your customers subscribe to a box and customize it by picking exactly what and how many products they want. You can easily create a step-by-step process to guide them through their choices, set a fixed price or base it on the value of what they select, and even set limits on the minimum value or the number of products in the box.', 'yith-woocommerce-subscription' ),
				),
				array(
					'title'       => __( 'Use Subscription in combination with "Membership" to manage access to digital content and products', 'yith-woocommerce-subscription' ),
					'description' => __( 'With the premium version of Subscription and Membership, you can create subscription plans and allow subscribers to access exclusive content (video courses, digital resources, e-books and audiobooks, podcasts, etc.) on restricted sections of your site. Users who have not subscribed will not have access to such content.', 'yith-woocommerce-subscription' ),
				),
			);
		}

		/**
		 * Action Links
		 *
		 * @param array $links Links plugin array.
		 * @return mixed
		 */
		public function action_links( $links ) {
			if ( function_exists( 'yith_add_action_links' ) ) {
				$links = yith_add_action_links( $links, $this->panel_page, false );
			}
			return $links;
		}


		/**
		 * Add the action links to plugin admin page.
		 *
		 * @param array  $new_row_meta_args Plugin Meta New args.
		 * @param string $plugin_meta Plugin Meta.
		 * @param string $plugin_file Plugin file.
		 * @param array  $plugin_data Plugin data.
		 * @param string $status Status.
		 * @param string $init_file Init file.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWSBS_FREE_INIT' ) {
			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_YWSBS_SLUG;
			}

			return $new_row_meta_args;
		}

		/**
		 * Sanitize the option of type 'relative_date_selector' before that are saved.
		 *
		 * @param mixed $value Value.
		 * @param array $option Option.
		 * @param mixed $raw_value Raw value.
		 *
		 * @return array
		 * @since 1.4
		 */
		public function sanitize_value_option( $value, $option, $raw_value ) {

			if ( isset( $option['id'] ) && in_array( $option['id'], array( 'ywsbs_trash_pending_subscriptions', 'ywsbs_trash_cancelled_subscriptions' ), true ) ) { //phpcs:ignore
				$raw_value = maybe_unserialize( $raw_value );
				$value     = wc_parse_relative_date_option( $raw_value );
			}

			return $value;
		}

		/**
		 * Maybe regenerate the capabilities for shop manager.
		 *
		 * @since  3.0.0
		 * @param mixed  $old_value The old option value.
		 * @param mixed  $value     The new option value.
		 * @param string $option    Option name.
		 * @return void
		 */
		public function maybe_regenerate_shop_manager_capabilities( $old_value, $value, $option ) {
			if ( $old_value !== $value ) {
				$method = 'no' === $value ? 'remove_capabilities' : 'add_capabilities';
				YWSBS_Subscription_Capabilities::$method( 'shop_manager' );
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Subscription_Admin class
 *
 * @return YITH_WC_Subscription_Admin
 */
function YITH_WC_Subscription_Admin() { //phpcs:ignore
	return YITH_WC_Subscription_Admin::get_instance();
}
