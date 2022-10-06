<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Api\Orders\Read;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;
use WooCommerce\Facebook\Api\Orders\Order;

/**
 * Orders API read response object.
 *
 * @since 2.1.0
 */
class Response extends Api\Response {


	/**
	 * Gets an order object from the response data.
	 *
	 * @since 2.1.0
	 *
	 * @return \WooCommerce\Facebook\API\Orders\Order
	 */
	public function get_order() {

		return new Order( json_decode( json_encode( $this->response_data ), true ) );
	}


}
