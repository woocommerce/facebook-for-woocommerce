<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\Lifecycle;
use SkyVerge\WooCommerce\Facebook\Utilities\Background_Disable_Virtual_Products_Sync;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

if ( ! class_exists( 'WC_Facebookcommerce' ) ) :

	include_once 'includes/fbutils.php';

	class WC_Facebookcommerce extends Framework\SV_WC_Plugin {


		/** @var string the plugin version */
		const VERSION = '1.11.3-dev.1';

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

		/** @var \WC_Facebookcommerce_Integration instance */
		private $integration;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin admin handler instance */
		private $admin;

		/** @var \SkyVerge\WooCommerce\Facebook\AJAX Ajax handler instance */
		private $ajax;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Feed product feed handler */
		private $product_feed;

		/** @var Background_Disable_Virtual_Products_Sync instance */
		protected $background_disable_virtual_products_sync;


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

				if ( ! defined( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL' ) ) {
					define( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL', admin_url( 'admin.php?page=wc-settings&tab=integration&section=facebookcommerce' ) );
				}

				include_once 'facebook-commerce.php';

				require_once __DIR__ . '/includes/Integrations/Integrations.php';
				require_once __DIR__ . '/includes/Products.php';
				require_once __DIR__ . '/includes/Products/Feed.php';
				require_once __DIR__ . '/includes/fbproductfeed.php';
				require_once __DIR__ . '/facebook-commerce-messenger-chat.php';

				$this->product_feed = new \SkyVerge\WooCommerce\Facebook\Products\Feed();

				if ( is_ajax() ) {

					require_once __DIR__ . '/includes/AJAX.php';

					$this->ajax = new \SkyVerge\WooCommerce\Facebook\AJAX();
				}

				// register the WooCommerce integration
				add_filter( 'woocommerce_integrations', [ $this, 'add_woocommerce_integration' ] );

				$this->integrations = new \SkyVerge\WooCommerce\Facebook\Integrations\Integrations( $this );

				if ( 'yes' !== get_option( 'wc_facebook_sync_virtual_products_disabled', 'no' ) ) {

					require_once __DIR__ . '/vendor/skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-async-request.php';
					require_once __DIR__ . '/vendor/skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-background-job-handler.php';
					require_once __DIR__ . '/includes/Utilities/Background_Disable_Virtual_Products_Sync.php';

					$this->background_disable_virtual_products_sync = new Background_Disable_Virtual_Products_Sync();
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

			$integration = $this->get_integration();

			// if the feed hasn't been migrated to FBE 1.5 and the access token is bad, display a notice
			if ( $integration && $integration->is_configured() && ! $integration->is_feed_migrated() && ! $integration->get_page_name() ) {

				$docs_url = 'https://docs.woocommerce.com/document/facebook-for-woocommerce/#faq-security';

				if ( $this->is_plugin_settings() ) {

					$message = sprintf(
						/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag, %5$s - <a> tag, %6$s - </a> tag */
						__( '%1$sHeads up!%2$s Facebook for WooCommerce is migrating to a more secure connection experience. Please %3$sclick here%4$s and go to %1$sAdvanced Options%2$s > %1$sReconnect Catalog%2$s to securely reconnect. %5$sLearn more%6$s.', 'facebook-for-woocommerce' ),
						'<strong>', '</strong>',
						'<a href="#" class="wc-facebook-manage-connection">', '</a>',
						'<a href="' . esc_url( $docs_url ) . '" target="_blank">', '</a>'
					);

				} else {

					$message = sprintf(
						/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag, %5$s - <a> tag, %6$s - </a> tag */
						__( '%1$sHeads up!%2$s Facebook for WooCommerce is migrating to a more secure connection experience. Please %3$sclick here%4$s and go to %1$sManage Connection%2$s > %1$sAdvanced Options%2$s > %1$sReconnect Catalog%2$s to securely reconnect. %5$sLearn more%6$s.', 'facebook-for-woocommerce' ),
						'<strong>', '</strong>',
						'<a href="' . esc_url( $this->get_settings_url() ) . '">', '</a>',
						'<a href="' . esc_url( $docs_url ) . '" target="_blank">', '</a>'
					);
				}

				$this->get_admin_notice_handler()->add_admin_notice( $message, self::PLUGIN_ID . '_migrate_to_v1_5', [
					'dismissible'  => false,
					'notice_class' => 'notice-info wc-facebook-migrate-notice',
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


		/** Getter methods ********************************************************************************************/


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
		 * @since 1.11.1-dev.1
		 */
		public function get_background_disable_virtual_products_sync_instance() {

			return $this->background_disable_virtual_products_sync;
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

			return admin_url( 'admin.php?page=wc-settings&tab=integration&section=' . self::INTEGRATION_ID );
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

			return 'https://woocommerce.com/my-account/tickets/';
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

			$page    = Framework\SV_WC_Helper::get_requested_value( 'page' );
			$tab     = Framework\SV_WC_Helper::get_requested_value( 'tab' );
			$section = Framework\SV_WC_Helper::get_requested_value( 'section' );

			return is_admin() && 'wc-settings' === $page && 'integration' === $tab && self::INTEGRATION_ID === $section;
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
