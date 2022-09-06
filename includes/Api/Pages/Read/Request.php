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
	 * @param string $page_id Facebook Page ID.
	 */
	public function __construct( $page_id ) {
		parent::__construct( "/{$page_id}/?fields=name,link", 'GET' );
	}
}
