<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API read request object.
 *
 * @since 2.1.0-dev.1
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param array $fields fields to be returned
	 */
	public function __construct( $remote_id, $fields = [] ) {

		parent::__construct( "/{$remote_id}", 'GET' );

		if ( empty( $fields ) ) {

			// request all top-level fields
			$fields = implode( ',', [
				'id',
				'order_status',
				'created',
				'last_updated',
				'items',
				'ship_by_date',
				'merchant_order_id',
				'channel',
				'selected_shipping_option',
				'shipping_address',
				'estimated_payment_details',
				'buyer_details',
			] );

		} elseif ( is_array( $fields ) ) {

			$fields = implode( ',', $fields );
		}

		$this->set_params( [ 'fields' => $fields ] );
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
