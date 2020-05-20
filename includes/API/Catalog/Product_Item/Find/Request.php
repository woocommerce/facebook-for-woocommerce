<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Find;

defined( 'ABSPATH' ) or exit;

/**
 * Find Product Item API request object.
 *
 * @since 2.0.0-dev.1
 */
class Request extends \SkyVerge\WooCommerce\Facebook\API\Request {


	/**
	 * Find Product Item API request constructor.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $catalog_id catalog ID
	 * @param string $retailer_id retailer ID of the product
	 */
	public function __construct( $catalog_id, $retailer_id ) {

		$path = "catalog:{$catalog_id}:" . base64_encode( $retailer_id );

		parent::__construct( $catalog_id, $path, 'GET' );
	}


	/**
	 * Gets the rate limit ID for this request.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'wc_facebook_ads_management_api_request';
	}


}
