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


		/** @var \SkyVerge\WooCommerce\Facebook\Admin admin handler instance */
		private $admin;


		/**
		 * Construct the plugin.
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

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );

			if ( \WC_Facebookcommerce_Utils::isWoocommerceIntegration() ) {

				if ( ! defined( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL' ) ) {
					define( 'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL', get_admin_url() . '/admin.php?page=wc-settings&tab=integration' . '&section=facebookcommerce' );
				}

				include_once 'facebook-commerce.php';

				require_once __DIR__ . '/includes/Products.php';

				if ( is_admin() ) {

					require_once __DIR__ . '/includes/Admin.php';

					$this->admin = new \SkyVerge\WooCommerce\Facebook\Admin();
				}

				// register the WooCommerce integration
				add_filter( 'woocommerce_integrations', [ $this, 'add_woocommerce_integration' ] );
			}
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
		 * Add a new integration to WooCommerce.
		 */
		public function add_woocommerce_integration( $integrations ) {
			$integrations[] = 'WC_Facebookcommerce_Integration';
			return $integrations;
		}

		public function add_wordpress_integration() {
			new WP_Facebook_Integration();
		}


		/**
		 * Gets the admin handler instance.
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Admin|null
		 */
		public function get_admin_handler() {

			return $this->admin;
		}


	}

	$WC_Facebookcommerce = new WC_Facebookcommerce( __FILE__ );

endif;
