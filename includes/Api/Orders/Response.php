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

namespace WooCommerce\Facebook\Api\Orders;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;

/**
 * Orders API list response object.
 *
 * @since 2.1.0
 */
class Response extends Api\Response {


	use Api\Traits\Paginated_Response;


	/**
	 * Gets an array of order objects from the response data.
	 *
	 * @since 2.1.0
	 *
	 * @return \WooCommerce\Facebook\Api\Orders\Order[]
	 */
	public function get_orders() {

		$orders = array();

		foreach ( $this->get_data() as $order_data ) {
			$orders[] = new Order( json_decode( json_encode( $order_data ), true ) );
		}

		return $orders;
	}


}
