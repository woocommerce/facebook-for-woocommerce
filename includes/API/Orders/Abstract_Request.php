<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

abstract class Abstract_Request extends API\Request {


	/**
	 * Abstract_Request constructor.
	 *
	 * @param string $path request path
	 * @param string $method request method
	 */
	public function __construct( $path, $method ) {

		parent::__construct( $path, $method );

		/** @link https://developers.facebook.com/docs/commerce-platform/order-management/error-codes */
		$this->retry_codes[] = 2361081;
	}


}
