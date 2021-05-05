<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\Cancel;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API cancel request object.
 *
 * @since 2.1.0
 */
class Request extends API\Orders\Abstract_Request {


	use API\Traits\Idempotent_Request;


	/**
	 * API request constructor.
	 *
	 * @since 2.1.0
	 *
	 * @param string $remote_id remote order ID
	 * @param string $reason cancellation reason code
	 * @param bool   $restock_items whether or not the items were restocked
	 */
	public function __construct( $remote_id, $reason, $restock_items = true ) {

		parent::__construct( "/{$remote_id}/cancellations", 'POST' );

		$this->set_data(
			array(
				'cancel_reason'   => array(
					'reason_code' => $reason,
				),
				'restock_items'   => $restock_items,
				'idempotency_key' => $this->get_idempotency_key(),
			)
		);
	}


	/**
	 * Gets the rate limit ID.
	 *
	 * While this is the Orders API, orders belong to pages so this is where the rate limit comes from.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'pages';
	}


}
