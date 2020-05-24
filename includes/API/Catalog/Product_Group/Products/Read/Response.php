<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Group\Products\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Response object for the API endpoint that returns a list of Product Items in a particular Product Group.
 *
 * @since 2.0.0-dev.1
 */
class Response extends API\Response {


	use API\Traits\Paginated_Response;


	/**
	 * Gets the Product Item IDs indexed by the retailer ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_product_item_ids() {

		$product_item_ids = [];

		foreach ( $this->get_data() as $entry ) {
			$product_item_ids[ $entry->retailer_id ] = $entry->id;
		}

		return $product_item_ids;
	}


}
