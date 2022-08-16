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

namespace WooCommerce\Facebook\Api\FBE\Configuration;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;

/**
 * FBE Configuration API request object.
 *
 * @since 2.0.0
 */
class Request extends Api\Request {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $external_business_id external business ID
	 * @param string $method request method
	 */
	public function __construct( $external_business_id, $method ) {

		parent::__construct( '/fbe_business', $method );

		$this->params = array(
			'fbe_external_business_id' => $external_business_id,
		);
	}


}
