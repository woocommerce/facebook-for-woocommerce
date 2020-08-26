<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\List;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API list request object.
 *
 * @since 2.1.0-dev.1
 */
class Request extends API\Request  {


	/**
	 * API request constructor.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/order-api#get_orders
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $page_id page ID
	 * @param array $args optional additional arguments
	 */
	public function __construct( $page_id, $args = [] ) {

		parent::__construct( "/{$page_id}/commerce_orders", 'GET' );

		$params = [];

		if ( isset( $args['updated_before'] ) ) {

			$params['updated_before'] = $args['updated_before'];
		}

		if ( isset( $args['updated_after'] ) ) {

			$params['updated_after'] = $args['updated_after'];
		}

		if ( isset( $args['state'] ) ) {

			$params['state'] = is_array( $args['state'] ) ? implode( ',', $args['state'] ) : $args['state'];
		}

		if ( isset( $args['filters'] ) ) {

			$params['filters'] = is_array( $args['filters'] ) ? implode( ',', $args['filters'] ) : $args['filters'];
		}

		if ( ! empty( $args['fields'] ) ) {

			$params['fields'] = is_array( $args['fields'] ) ? implode( ',', $args['fields'] ) : $args['fields'];

		} else {

			// request all top-level fields
			$params['fields'] = implode( ',', [
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
		}

		$this->set_params( $params );
	}


}
