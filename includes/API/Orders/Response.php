<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API list response object.
 *
 * @since 2.1.0-dev.1
 */
class Response extends API\Response {


	use API\Traits\Paginated_Response;


	/**
	 * Gets an array of order objects from the response data.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return \SkyVerge\WooCommerce\Facebook\API\Orders\Order[]
	 */
	public function get_orders() {

		$orders = [];

		foreach ( $this->get_data() as $order_data ) {
			$orders[] = new Order( json_decode( json_encode( $order_data ), true ) );
		}

		return $orders;
	}


}
