<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Business_Manager;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Request object for the Business Manager API.
 *
 * @since 2.0.0-dev.1
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $business_manager_id business manager ID
	 */
	public function __construct( $business_manager_id ) {

		parent::__construct( "/{$business_manager_id}", 'GET' );
	}


	/**
	 * Gets the request parameters.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_params() {

		return [ 'fields' => 'name,link' ];
	}


}
