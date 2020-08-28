<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Commerce;

use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

/**
 * General Commerce orders handler.
 *
 * @since 2.1.0-dev.1
 */
class Orders {


	/** @var string the fetch orders action */
	const ACTION_FETCH_ORDERS = 'wc_facebook_commerce_fetch_orders';

	/** @var string the meta key used to store the remote order ID */
	const REMOTE_ID_META_KEY = '_wc_facebook_commerce_remote_id';


	/**
	 * Finds a local order based on the Commerce ID stored in REMOTE_ID_META_KEY.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id Commerce order ID
	 * @return \WC_Order|null
	 */
	public function find_local_order( $remote_id ) {

		$orders = wc_get_orders( [
			'limit'      => 1,
			'status'     => 'any',
			'meta_key'   => self::REMOTE_ID_META_KEY,
			'meta_value' => $remote_id,
		] );

		return ! empty( $orders ) ? current( $orders ) : null;
	}


	/**
	 * Creates a local WooCommerce order based on an Orders API order object.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param Order $remote_order Orders API order object
	 * @return \WC_Order
	 * @throws SV_WC_Plugin_Exception|\WC_Data_Exception
	 */
	public function create_local_order( Order $remote_order ) {

		$local_order = new \WC_Order();
		$local_order->set_created_via( $remote_order->get_channel() );
		$local_order->set_status( 'pending' );
		$local_order->save();

		$local_order = $this->update_local_order( $remote_order, $local_order );

		return $local_order;
	}


	/**
	 * Updates a local WooCommerce order based on an Orders API order object.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param Order $remote_order Orders API order object
	 * @param \WC_Order $local_order local order object
	 * @return \WC_Order
	 */
	public function update_local_order( Order $remote_order, \WC_Order $local_order ) {

		// TODO: implement
		return $local_order;
	}


	/**
	 * Updates WooCommerce’s Orders by fetching orders from the API and either creating or updating local orders.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function update_local_orders() {

		// TODO: implement
	}


	/**
	 * Frequency in seconds that orders are updated.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return int
	 */
	public function get_order_update_interval() {

		// TODO: implement
		return 5 * MINUTE_IN_SECONDS;
	}


	/**
	 * Schedules a recurring ACTION_FETCH_ORDERS action, if not already scheduled.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function schedule_local_orders_update() {

		// TODO: implement
	}


	/**
	 * Adds the necessary action & filter hooks.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function add_hooks() {

		// TODO: implement
	}


	/**
	 * Fulfills an order via API.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Order $order order object
	 * @param string $tracking_number shipping tracking number
	 * @param string $carrier shipping carrier
	 * @throws SV_WC_Plugin_Exception
	 */
	public function fulfill_order( \WC_Order $order, $tracking_number, $carrier ) {

		// TODO: implement
	}


	/**
	 * Refunds an order.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Order_Refund $refund order refund object
	 * @param mixed $args
	 * @throws SV_WC_Plugin_Exception
	 */
	public function add_order_refund( \WC_Order_Refund $refund, $args ) {

		// TODO: implement
	}


	/**
	 * Cancels an order.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Order $order order object
	 * @throws SV_WC_Plugin_Exception
	 */
	public function cancel_order( \WC_Order $order ) {

		// TODO: implement
	}


}
