<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\CMS\Order_Management;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Order Management App API request object.
 *
 * @since 2.3.0
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.3.0
	 *
	 * @param string $commerce_manager_id Commerce Manager ID
	 */
	public function __construct( $commerce_manager_id ) {

		parent::__construct( "/{$commerce_manager_id}/order_management_apps", 'GET' );
	}


}
