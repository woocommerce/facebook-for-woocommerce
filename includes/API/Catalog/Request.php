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

namespace SkyVerge\WooCommerce\Facebook\API\Catalog;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Request object for the Catalog API.
 *
 * @since 2.0.0
 */
class Request extends API\Request {


	/**
	 * Gets the rate limit ID.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'ads_management';
	}


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 */
	public function __construct( $catalog_id ) {

		parent::__construct( "/{$catalog_id}", 'GET' );
	}


	/**
	 * Gets the request parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_params() {

		return array( 'fields' => 'name' );
	}


}
