<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Base;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Request;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin;

defined( 'ABSPATH' ) or exit;

/**
 * API handler.
 *
 * @since 2.0.0-dev.1
 */
class API extends SV_WC_API_Base {


	/** @var string the configured access token */
	protected $access_token;


	/**
	 * Returns a new request object.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $args optional request arguments
	 * @return SV_WC_API_Request|object
	 */
	protected function get_new_request( $args = array() ) {

		// TODO: Implement get_new_request() method.
	}


	/**
	 * Returns the plugin class instance associated with this API.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return SV_WC_Plugin
	 */
	protected function get_plugin() {

		return facebook_for_woocommerce();
	}


}
