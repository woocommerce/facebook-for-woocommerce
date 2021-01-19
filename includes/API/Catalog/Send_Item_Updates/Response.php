<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * Send_Item_Updates API response object
 *
 * @since 2.0.0
 */
class Response extends \SkyVerge\WooCommerce\Facebook\API\Response {


	/**
	 * Gets the handles field from the response.
	 *
	 * @since 2.0.0
	 *
	 * @return array|null
	 */
	public function get_handles() {

		return $this->handles;
	}


}
