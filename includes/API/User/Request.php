<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\User;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Request object for the User API.
 *
 * @since 2.0.0
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $user_id user ID
	 */
	public function __construct( $user_id = '' ) {

		if ( $user_id ) {
			$path = "/{$user_id}";
		} else {
			$path = '/me';
		}

		parent::__construct( $path, 'GET' );
	}


}
