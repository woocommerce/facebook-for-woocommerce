<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * Plugin Name: Facebook for WooCommerce
 * Plugin URI: https://github.com/facebookincubator/facebook-for-woocommerce/
 * Description: Grow your business on Facebook! Use this official plugin to help sell more of your products using Facebook. After completing the setup, you'll be ready to create ads that promote your products and you can also create a shop section on your Page where customers can browse your products on Facebook.
 * Author: Facebook
 * Author URI: https://www.facebook.com/
 * Version: 1.9.15
 * Woo: 2127297:0ea4fe4c2d7ca6338f8a322fb3e4e187
 * Text Domain: facebook-for-woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.5
 *
 * @package FacebookCommerce
 */


if ( ! class_exists( 'WC_Facebookcommerce' ) ) :
	include_once 'includes/fbutils.php';

	class WC_Facebookcommerce {

		// Change it above as well
		const PLUGIN_VERSION = WC_Facebookcommerce_Utils::PLUGIN_VERSION;

		/** @var string the plugin ID */
		const PLUGIN_ID = 'facebook_for_woocommerce';

		/** @var string the integration ID */
		const INTEGRATION_ID = 'facebookcommerce';

		/** @var string the integration class name (including namespaces) */
		const INTEGRATION_CLASS = '\\WC_Facebookcommerce_Integration';


		/** @var \WC_Facebookcommerce singleton instance */
		private static $instance;

		/** @var \WC_Facebookcommerce_Integration instance */
		private $integration;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin admin handler instance */
		private $admin;

		/** @var \SkyVerge\WooCommerce\Facebook\AJAX Ajax handler instance */
		private $ajax;


		/**
		 * Constructs the plugin.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action( 'plugins_loaded', [ $this, 'init' ] );
		}


		/**
		 * Initializes the plugin.
		 *
		 * @internal
		 */
		public function init() {

			if ( \WC_Facebookcommerce_Utils::isWoocommerceIntegration() ) {

				if ( ! defined( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL' ) ) {
					define( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL', get_admin_url() . '/admin.php?page=wc-settings&tab=integration' . '&section=facebookcommerce' );
				}

				include_once 'facebook-commerce.php';

				require_once __DIR__ . '/includes/Products.php';

				if ( is_ajax() ) {

					require_once __DIR__ . '/includes/AJAX.php';

					$this->ajax = new \SkyVerge\WooCommerce\Facebook\AJAX();
				}

				// initialize the admin handling
				add_action( 'admin_init', [ $this, 'init_admin'] );

				// register the WooCommerce integration
				add_filter( 'woocommerce_integrations', [ $this, 'add_woocommerce_integration' ] );
			}
		}


		/**
		 * Initializes the admin handling.
		 *
		 * @internal
		 *
		 * @since x.y.z
		 */
		public function init_admin() {

			require_once __DIR__ . '/includes/Admin.php';

			$this->admin = new \SkyVerge\WooCommerce\Facebook\Admin();

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
		}


		public function add_settings_link( $links ) {
			$settings = array(
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'admin.php?page=wc-settings&tab=integration&section=facebookcommerce' ),
					'Settings'
				),
			);
			return array_merge( $settings, $links );
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
		 * Gets the admin handler instance.
		 *
		 * @since x.y.z
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Admin|null
		 */
		public function get_admin_handler() {

			return $this->admin;
		}


		/**
		 * Gets the AJAX handler instance.
		 *
		 * @sinxe x.y.z
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\AJAX|null
		 */
		public function get_ajax_handler() {

			return $this->ajax;
		}


		/**
		 * Gets the integration instance.
		 *
		 * @since x.y.z
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
		 * Gets the plugin singleton instance.
		 *
		 * @see \facebook_for_woocommerce()
		 *
		 * @since x.y.z
		 *
		 * @return \WC_Facebookcommerce the plugin singleton instance
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


	}


	/**
	 * Gets the Facebook for WooCommerce plugin instance.
	 *
	 * @since x.y.z
	 *
	 * @return \WC_Facebookcommerce instance of the plugin
	 */
	function facebook_for_woocommerce() {

		return \WC_Facebookcommerce::instance();
	}


	// Loads and instantiates the plugin the first time is called here.
	// Subsequent calls to this function will return the plugin's current thread instance (singleton).
	facebook_for_woocommerce();


endif;
