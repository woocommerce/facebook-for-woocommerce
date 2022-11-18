<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\API\Catalog\Product_Item;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API\Response as BaseResponse;

/**
 * Response object for API requests that return a Product Item.
 *
 * @since 2.0.0
 */
class Response extends BaseResponse {

	/**
	 * Gets the Product Item group ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_group_id() {
		return $this->response_data['product_group']['id'] ?? '';
	}
}
