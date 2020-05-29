<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Lifecycle;
use SkyVerge\WooCommerce\Facebook\Utilities\Background_Disable_Virtual_Products_Sync;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

if ( ! class_exists( 'WC_Facebookcommerce' ) ) :

	include_once 'includes/fbutils.php';

	class WC_Facebookcommerce extends Framework\SV_WC_Plugin {


		/** @var string the plugin version */
		const VERSION = '2.0.0-dev.1';

		/** @var string for backwards compatibility TODO: remove this in v2.0.0 {CW 2020-02-06} */
		const PLUGIN_VERSION = self::VERSION;

		/** @var string the plugin ID */
		const PLUGIN_ID = 'facebook_for_woocommerce';

		/** @var string the integration ID */
		const INTEGRATION_ID = 'facebookcommerce';

		/** @var string the integration class name (including namespaces) */
		const INTEGRATION_CLASS = '\\WC_Facebookcommerce_Integration';


		/** @var \WC_Facebookcommerce singleton instance */
		protected static $instance;

		/** @var SkyVerge\WooCommerce\Facebook\API instance */
		private $api;

		/** @var \WC_Facebookcommerce_Integration instance */
		private $integration;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin admin handler instance */
		private $admin;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin\Settings */
		private $admin_settings;

		/** @var \SkyVerge\WooCommerce\Facebook\AJAX Ajax handler instance */
		private $ajax;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Feed product feed handler */
		private $product_feed;

		/** @var Background_Disable_Virtual_Products_Sync instance */
		protected $background_disable_virtual_products_sync;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Sync products sync handler */
		private $products_sync_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Sync\Background background sync handler */
		private $sync_background_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Handlers\Connection connection handler */
		private $connection_handler;


		/**
		 * Constructs the plugin.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			parent::__construct(
				self::PLUGIN_ID,
				self::VERSION,
				[
					'text_domain' => 'facebook-for-woocommerce',
				]
			);

			$this->init();
		}


		/**
		 * Initializes the plugin.
		 *
		 * @internal
		 */
		public function init() {

			if ( \WC_Facebookcommerce_Utils::isWoocommerceIntegration() ) {

				include_once 'facebook-commerce.php';

				require_once $this->get_framework_path() . '/utilities/class-sv-wp-async-request.php';
				require_once $this->get_framework_path() . '/utilities/class-sv-wp-background-job-handler.php';

				require_once __DIR__ . '/includes/Handlers/Connection.php';
				require_once __DIR__ . '/includes/Integrations/Integrations.php';
				require_once __DIR__ . '/includes/Products.php';
				require_once __DIR__ . '/includes/Products/Feed.php';
				require_once __DIR__ . '/includes/Products/Sync.php';
				require_once __DIR__ . '/includes/Products/Sync/Background.php';
				require_once __DIR__ . '/includes/fbproductfeed.php';
				require_once __DIR__ . '/facebook-commerce-messenger-chat.php';
				require_once __DIR__ . '/includes/Events/Event.php';

				$this->product_feed            = new \SkyVerge\WooCommerce\Facebook\Products\Feed();
				$this->products_sync_handler   = new \SkyVerge\WooCommerce\Facebook\Products\Sync();
				$this->sync_background_handler = new \SkyVerge\WooCommerce\Facebook\Products\Sync\Background();

				if ( is_ajax() ) {

					require_once __DIR__ . '/includes/AJAX.php';

					$this->ajax = new \SkyVerge\WooCommerce\Facebook\AJAX();
				}

				// register the WooCommerce integration
				add_filter( 'woocommerce_integrations', [ $this, 'add_woocommerce_integration' ] );

				$this->integrations = new \SkyVerge\WooCommerce\Facebook\Integrations\Integrations( $this );

				if ( 'yes' !== get_option( 'wc_facebook_sync_virtual_products_disabled', 'no' ) ) {

					require_once __DIR__ . '/includes/Utilities/Background_Disable_Virtual_Products_Sync.php';

					$this->background_disable_virtual_products_sync = new Background_Disable_Virtual_Products_Sync();
				}

				$this->connection_handler = new \SkyVerge\WooCommerce\Facebook\Handlers\Connection( $this );

				// load admin handlers, before admin_init
				if ( is_admin() ) {

					require_once __DIR__ . '/includes/Admin/Settings.php';
					require_once __DIR__ . '/includes/Admin/Abstract_Settings_Screen.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Connection.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Product_Sync.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Messenger.php';

					$this->admin_settings = new \SkyVerge\WooCommerce\Facebook\Admin\Settings();
				}
			}
		}


		/**
		 * Initializes the admin handling.
		 *
		 * @internal
		 *
		 * @since 1.10.0
		 */
		public function init_admin() {

			require_once __DIR__ . '/includes/Admin.php';

			$this->admin = new \SkyVerge\WooCommerce\Facebook\Admin();
		}


		/**
		 * Adds the plugin admin notices.
		 *
		 * @since 1.11.0
		 */
		public function add_admin_notices() {

			parent::add_admin_notices();

			// inform users that they need to connect
			if ( ! $this->get_connection_handler()->is_connected() ) {

				$message    = '';
				$message_id = '';

				//  to FBE 2.0 if they've upgraded from FBE 1.x
				if ( 'no' === get_option( 'wc_facebook_has_connected_fbe_2' ) && $this->get_integration()->get_external_merchant_settings_id() ) {

					$message = sprintf(
						/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
						__( '%1$sHeads up!%2$s You\'re ready to migrate to a more secure, reliable Facebook for WooCommerce connection. Please %3$sclick here%4$s to reconnect!', 'facebook-for-woocommerce' ),
						'<strong>', '</strong>',
						'<a href="' . esc_url( $this->get_connection_handler()->get_connect_url() ) . '">', '</a>'
					);

					$message_id = 'migrate_to_v2_0';

				// otherwise, a general getting started message
				} elseif ( ! $this->is_plugin_settings() ) {

					$message = sprintf(
						/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
						esc_html__(
							'%1$sFacebook for WooCommerce is almost ready.%2$s To complete your configuration, %3$scomplete the setup steps%4$s.',
							'facebook-for-woocommerce'
						),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url( facebook_for_woocommerce()->get_settings_url() ) . '">',
						'</a>'
					);

					$message_id = 'get_started';
				}

				if ( $message ) {

					$this->get_admin_notice_handler()->add_admin_notice( $message, self::PLUGIN_ID . '_' . $message_id, [
						'dismissible'  => false,
						'notice_class' => 'notice-info',
					] );
				}
			}

			// if the connection is otherwise invalid, but there is an access token
			if ( get_transient( 'wc_facebook_connection_invalid' ) && $this->get_connection_handler()->is_connected() ) {

				$message = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sHeads up!%2$s Your connection to Facebook is no longer valid. Please %3$sclick here%4$s to securely reconnect your account and continue syncing products.', 'facebook-for-woocommerce' ),
					'<strong>', '</strong>',
					'<a href="' . esc_url( $this->get_connection_handler()->get_connect_url() ) . '">', '</a>'
				);

				$this->get_admin_notice_handler()->add_admin_notice( $message, 'connection_invalid', [
					'notice_class' => 'notice-error',
				] );
			}
		}


		/**
		 * Adds a Facebook integration to WooCommerce.
		 *
		 * @internal
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $integrations class names
		 * @return string[]
		 */
		public function add_woocommerce_integration( $integrations = [] ) {

			if ( ! class_exists( self::INTEGRATION_CLASS ) ) {
				include_once __DIR__ . '/facebook-commerce.php';
			}

			$integrations[ self::INTEGRATION_ID ] = self::INTEGRATION_CLASS;

			return $integrations;
		}


		public function add_wordpress_integration() {
			new WP_Facebook_Integration();
		}


		/**
		 * Logs an API request.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $request request data
		 * @param array $response response data
		 * @param null $log_id log ID
		 */
		public function log_api_request( $request, $response, $log_id = null ) {

			// bail if logging isn't enabled
			if ( ! $this->get_integration() || ! $this->get_integration()->is_debug_mode_enabled() ) {
				return;
			}

			parent::log_api_request( $request, $response, $log_id );
		}


		/** Getter methods ********************************************************************************************/


		/**
		 * Gets the API instance.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\API
		 * @throws Framework\SV_WC_API_Exception
		 */
		public function get_api() {

			if ( ! is_object( $this->api ) ) {

				if ( ! $this->get_connection_handler()->get_access_token() ) {
					throw new Framework\SV_WC_API_Exception( __( 'Cannot create the API instance because the access token is missing.', 'facebook-for-woocommerce' ) );
				}

				if ( ! class_exists( API\Traits\Rate_Limited_API::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_API.php';
				}

				if ( ! class_exists( API\Traits\Rate_Limited_Request::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_Request.php';
				}

				if ( ! class_exists( API\Traits\Rate_Limited_Response::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_Response.php';
				}

				if ( ! trait_exists( API\Traits\Paginated_Response::class, false ) ) {
					require_once __DIR__ . '/includes/API/Traits/Paginated_Response.php';
				}

				if ( ! class_exists( API::class ) ) {
					require_once __DIR__ . '/includes/API.php';
				}

				if ( ! class_exists( API\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Request.php';
				}

				if ( ! class_exists( API\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Response.php';
				}

				if ( ! class_exists( API\Pixel\Events\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Pixel/Events/Request.php';
				}

				if ( ! class_exists( API\Business_Manager\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Business_Manager/Request.php';
				}

				if ( ! class_exists( API\Business_Manager\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Business_Manager/Response.php';
				}

				if ( ! class_exists( API\Catalog\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Request.php';
				}

				if ( ! class_exists( API\Catalog\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Response.php';
				}

				if ( ! class_exists( API\Catalog\Send_Item_Updates\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Send_Item_Updates/Request.php';
				}

				if ( ! class_exists( API\Catalog\Send_Item_Updates\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Send_Item_Updates/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Group\Products\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Group/Products/Read/Request.php';
				}

				if ( ! class_exists( API\Catalog\Product_Group\Products\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Group/Products/Read/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Item\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Item/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Item\Find\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Item/Find/Request.php';
				}

				if ( ! class_exists( API\User\Request::class ) ) {
					require_once __DIR__ . '/includes/API/User/Request.php';
				}

				if ( ! class_exists( API\User\Response::class ) ) {
					require_once __DIR__ . '/includes/API/User/Response.php';
				}

				if ( ! class_exists( API\User\Permissions\Delete\Request::class ) ) {
					require_once __DIR__ . '/includes/API/User/Permissions/Delete/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Read/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Read/Response.php';
				}

				if ( ! class_exists( API\Pages\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Pages/Read/Request.php';
				}

				if ( ! class_exists( API\Pages\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Pages/Read/Response.php';
				}

				if ( ! class_exists( API\Exceptions\Request_Limit_Reached::class ) ) {
					require_once __DIR__ . '/includes/API/Exceptions/Request_Limit_Reached.php';
				}

				$this->api = new SkyVerge\WooCommerce\Facebook\API( $this->get_connection_handler()->get_access_token() );
			}

			return $this->api;
		}


		/**
		 * Gets the admin handler instance.
		 *
		 * @since 1.10.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Admin|null
		 */
		public function get_admin_handler() {

			return $this->admin;
		}


		/**
		 * Gets the AJAX handler instance.
		 *
		 * @sinxe 1.10.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\AJAX|null
		 */
		public function get_ajax_handler() {

			return $this->ajax;
		}


		/**
		 * Gets the product feed handler.
		 *
		 * @since 1.11.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Feed
		 */
		public function get_product_feed_handler() {

			return $this->product_feed;
		}


		/**
		 * Gets the background disable virtual products sync handler instance.
		 *
		 * @since 1.11.3-dev.2
		 *
		 * @return Background_Disable_Virtual_Products_Sync
		 */
		public function get_background_disable_virtual_products_sync_instance() {

			return $this->background_disable_virtual_products_sync;
		}


		/**
		 * Gets the products sync handler.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Sync
		 */
		public function get_products_sync_handler() {

			return $this->products_sync_handler;
		}


		/**
		 * Gets the products sync background handler.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Sync\Background
		 */
		public function get_products_sync_background_handler() {

			return $this->sync_background_handler;
		}


		/**
		 * Gets the connection handler.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Handlers\Connection
		 */
		public function get_connection_handler() {

			return $this->connection_handler;
		}


		/**
		 * Gets the integration instance.
		 *
		 * @since 1.10.0
		 *
		 * @return \WC_Facebookcommerce_Integration instance
		 */
		public function get_integration() {

			if ( null === $this->integration ) {

				$integrations = null === WC()->integrations ? [] : WC()->integrations->get_integrations();
				$integration  = self::INTEGRATION_CLASS;

				if ( isset( $integrations[ self::INTEGRATION_ID ] ) && $integrations[ self::INTEGRATION_ID ] instanceof $integration ) {

					$this->integration = $integrations[ self::INTEGRATION_ID ];

				} else {

					$this->add_woocommerce_integration();

					$this->integration = new $integration();
				}
			}

			return $this->integration;
		}


		/**
		 * Gets the settings page URL.
		 *
		 * @since 1.10.0
		 *
		 * @param null $plugin_id unused
		 * @return string
		 */
		public function get_settings_url( $plugin_id = null ) {

			return admin_url( 'admin.php?page=wc-facebook' );
		}


		/**
		 * Gets the plugin's documentation URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_documentation_url() {

			return 'https://docs.woocommerce.com/document/facebook-for-woocommerce/';
		}


		/**
		 * Gets the plugin's support URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_support_url() {

			return 'https://wordpress.org/support/plugin/facebook-for-woocommerce/';
		}


		/**
		 * Gets the plugin's sales page URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_sales_page_url() {

			return 'https://woocommerce.com/products/facebook/';
		}


		/**
		 * Gets the plugin's reviews URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_reviews_url() {

			return 'https://wordpress.org/support/plugin/facebook-for-woocommerce/reviews/';
		}


		/**
		 * Gets the plugin name.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_plugin_name() {

			return __( 'Facebook for WooCommerce', 'facebook-for-woocommerce' );
		}


		/** Conditional methods ***************************************************************************************/


		/**
		 * Determines if viewing the plugin settings in the admin.
		 *
		 * @since 1.10.0
		 *
		 * @return bool
		 */
		public function is_plugin_settings() {

			return is_admin() && \SkyVerge\WooCommerce\Facebook\Admin\Settings::PAGE_ID === Framework\SV_WC_Helper::get_requested_value( 'page' );
		}


		/** Utility methods *******************************************************************************************/


		/**
		 * Initializes the lifecycle handler.
		 *
		 * @since 1.10.0
		 */
		protected function init_lifecycle_handler() {

			require_once __DIR__ . '/includes/Lifecycle.php';

			$this->lifecycle_handler = new Lifecycle( $this );
		}


		/**
		 * Gets the plugin singleton instance.
		 *
		 * @see \facebook_for_woocommerce()
		 *
		 * @since 1.10.0
		 *
		 * @return \WC_Facebookcommerce the plugin singleton instance
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Gets the plugin file.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		protected function get_file() {

			return __FILE__;
		}


		/** Deprecated methods ****************************************************************************************/


		/**
		 * Adds the settings link on the plugin page.
		 *
		 * @internal
		 *
		 * @since 1.10.0
		 * @deprecated 1.10.0
		 */
		public function add_settings_link() {

			wc_deprecated_function( __METHOD__, '1.10.0' );
		}


	}


	/**
	 * Gets the Facebook for WooCommerce plugin instance.
	 *
	 * @since 1.10.0
	 *
	 * @return \WC_Facebookcommerce instance of the plugin
	 */
	function facebook_for_woocommerce() {

		return \WC_Facebookcommerce::instance();
	}


endif;
