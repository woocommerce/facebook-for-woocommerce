<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Orders\Order;

/**
 * Orders API read response object.
 *
 * @since 2.1.0
 */
class Response extends API\Response {


	/**
	 * Gets an order object from the response data.
	 *
	 * @since 2.1.0
	 *
	 * @return \SkyVerge\WooCommerce\Facebook\API\Orders\Order
	 */
	public function get_order() {

		return new Order( json_decode( json_encode( $this->response_data ), true ) );
	}


}
