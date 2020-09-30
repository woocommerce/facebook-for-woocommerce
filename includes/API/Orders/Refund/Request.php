<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\Refund;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API refund request object.
 *
 * @since 2.1.0-dev.1
 */
class Request extends API\Orders\Abstract_Request  {


	use API\Traits\Idempotent_Request;


	/**
	 * API request constructor.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param array $refund_data refund data
	 */
	public function __construct( $remote_id, $refund_data ) {

		parent::__construct( "/{$remote_id}/refunds", 'POST' );

		$refund_data['idempotency_key'] = $this->get_idempotency_key();

		$this->set_data( $refund_data );
	}


	/**
	 * Gets the rate limit ID.
	 *
	 * While this is the Orders API, orders belong to pages so this is where the rate limit comes from.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'pages';
	}


}
