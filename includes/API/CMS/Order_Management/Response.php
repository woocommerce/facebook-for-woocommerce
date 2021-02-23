<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\CMS\Order_Management;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Order Management Apps API response object.
 *
 * @since 2.4.0
 */
class Response extends API\Response  {


	/**
	 * Gets the App IDs
	 *
	 * @since 2.4.0
	 *
	 * @return string[]|null
	 */
	public function get_apps() {

		return wp_list_pluck( $this->data, 'id', 'id' );
	}


}
