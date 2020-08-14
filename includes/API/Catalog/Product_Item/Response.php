<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Response object for API requests that return a Product Item.
 *
 * @since 2.0.0
 */
class Response extends API\Response {


	/**
	 * Gets the Product Item group ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_group_id() {

		$product_group_id = '';

		if ( isset( $this->response_data->product_group->id ) ) {
			$product_group_id = $this->response_data->product_group->id;
		}

		return $product_group_id;
	}


}
