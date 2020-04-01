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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The plugin's REST API handler.
 *
 * @since 1.11.0-dev.1
 */
class REST_API extends Framework\REST_API {


	/** @var string the base API namespace */
	const API_NAMESPACE = 'facebook-for-woocommerce/v1';


	/**
	 * Registers new WC REST API routes.
	 *
	 * @since 1.11.0-dev.1
	 */
	public function register_routes() {

		parent::register_routes();

		// the /feed route
		$feed_controller = new REST_API\Controllers\Feed();
		$feed_controller->register_routes();
	}


}
