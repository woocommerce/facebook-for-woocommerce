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
 * Request object for the API endpoint that returns a list of Product Items in a particular Product Group.
 *
 * @since 2.0.0
 */
class Request extends API\Request {


	/**
	 * Constructor for the Product Group Products read request.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id product group ID
	 * @param int $limit max number of results returned
	 */
	public function __construct( $product_group_id, $limit ) {

		parent::__construct( "/{$product_group_id}/products", 'GET' );

		$this->set_params( [
			'fields' => 'id,retailer_id',
			'limit'  => $limit,
		] );
	}


}
