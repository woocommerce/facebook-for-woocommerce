<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Integrations;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * The integrations handler.
 *
 * @since 1.11.1
 */
class Integrations {


	/** @var Framework\SV_WC_Plugin plugin instance */
	private $plugin;

	/** @var object[] integration instances */
	private $integrations;


	/**
	 * Integrations constructor.
	 *
	 * @since 1.11.1
	 *
	 * @param Framework\SV_WC_Plugin $plugin plugin instance
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		$this->load_integrations();
	}


	/**
	 * Loads integration classes.
	 *
	 * @since 1.11.1
	 */
	private function load_integrations() {

		$registered_integrations = array(
			'WC_Facebook_WPML_Injector' => '/includes/fbwpml.php',
			Bookings::class             => '/includes/Integrations/Bookings.php',
		);

		foreach ( $registered_integrations as $class_name => $path ) {

			if ( ! class_exists( $class_name ) && ! is_readable( $path ) ) {

				$this->integrations[ $class_name ] = $this->plugin->load_class( $path, $class_name );
			}
		}
	}


}
