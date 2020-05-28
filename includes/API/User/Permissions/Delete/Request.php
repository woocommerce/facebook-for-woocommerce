<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\User\Permissions\Delete;

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
	 * @param string $user_id user ID
	 * @param string $permission permission to revoke
	 */
	public function __construct( $user_id, $permission ) {

		parent::__construct( "/{$user_id}/permissions/{$permission}", 'DELETE' );
	}


}
