<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\FBE\Configuration;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * FBE API request object.
 *
 * @since 2.0.0-dev.1
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $external_business_id external business ID
	 * @param string $method request method
	 */
	public function __construct( $external_business_id, $method ) {

		parent::__construct( '/fbe_business', $method );

		$this->params = [
			'fbe_external_business_id' => $external_business_id,
		];
	}


}
