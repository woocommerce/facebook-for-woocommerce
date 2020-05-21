<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Pages\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Page API request object.
 *
 * @since 2.0.0-dev.1
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $page_id page ID
	 */
	public function __construct( $page_id ) {

		parent::__construct( $page_id, 'GET' );
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
