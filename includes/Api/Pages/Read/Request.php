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

namespace WooCommerce\Facebook\Api\Pages\Read;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;

/**
 * Page API request object.
 *
 * @since 2.0.0
 */
class Request extends Api\Request {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $page_id page ID
	 */
	public function __construct( $page_id ) {

		parent::__construct( "/{$page_id}", 'GET' );
	}


	/**
	 * Gets the request parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_params() {

		return array( 'fields' => 'name,link' );
	}


}
