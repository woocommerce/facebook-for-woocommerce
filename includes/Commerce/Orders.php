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

		$order_id_array = get_posts( [
			'post_type'   => 'shop_order',
			'nopaging'    => true,
			'numberposts' => 1,
			'fields'      => 'ids',
			'post_status' => 'any',
			'meta_key'    => self::REMOTE_ID_META_KEY,
			'meta_value'  => $remote_id,
		] );

		if ( ! empty( $order_id_array ) ) {
			$order_id = current( $order_id_array );
		}

		return ! empty( $order_id ) ? wc_get_order( $order_id ) : null;
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
	 * @throws SV_WC_Plugin_Exception|\WC_Data_Exception
	 */
	public function update_local_order( Order $remote_order, \WC_Order $local_order ) {

		// add/update items
		foreach ( $remote_order->get_items() as $item ) {

			$wc_product_id = $item['retailer_id'];

			$matching_wc_order_item = false;

			// check if the local order already has this item
			foreach ( $local_order->get_items() as $wc_order_item ) {

				if ( $wc_order_item instanceof \WC_Order_Item_Product && $wc_product_id === (string) $wc_order_item->get_product_id() ) {
					$matching_wc_order_item = $wc_order_item;
					break;
				}
			}

			if ( empty( $matching_wc_order_item ) ) {

				$wc_product = wc_get_product( $wc_product_id );

				if ( ! $wc_product instanceof \WC_Product ) {
					throw new SV_WC_Plugin_Exception( "Product with WC ID $wc_product_id not found" );
				}

				$matching_wc_order_item = new \WC_Order_Item_Product();
				$local_order->add_item( $matching_wc_order_item );
			}

			$matching_wc_order_item->set_product_id( $wc_product_id );
			$matching_wc_order_item->set_quantity( $item['quantity'] );
			$matching_wc_order_item->set_subtotal( $item['quantity'] * $item['price_per_unit']['amount'] );
			// TODO: should we use the estimated_tax or the captured_tax on the line below?
			$matching_wc_order_item->set_subtotal_tax( $item['tax_details']['estimated_tax']['amount'] );
			$matching_wc_order_item->save();
		}

		// update information from selected_shipping_option
		$selected_shipping_option = $remote_order->get_selected_shipping_option();

		$local_order->set_shipping_total( $selected_shipping_option['price']['amount'] );
		$local_order->set_shipping_tax( $selected_shipping_option['calculated_tax']['amount'] );

		// update information from shipping_address
		$shipping_address = $remote_order->get_shipping_address();

		$local_order->set_shipping_last_name( $shipping_address['name'] );
		$local_order->set_shipping_address_1( $shipping_address['street1'] );
		$local_order->set_shipping_address_2( $shipping_address['street2'] );
		$local_order->set_shipping_city( $shipping_address['city'] );
		$local_order->set_shipping_state( $shipping_address['state'] );
		$local_order->set_shipping_postcode( $shipping_address['postal_code'] );
		$local_order->set_shipping_country( $shipping_address['country'] );

		// update information from estimated_payment_details
		$estimated_payment_details = $remote_order->get_estimated_payment_details();

		// TODO: confirm if we should use $estimated_payment_details['subtotal']['items'] for something
		// TODO: confirm if we should use $estimated_payment_details['subtotal']['shipping'] for something
		// TODO: confirm if we should use $estimated_payment_details['tax'] for something
		$local_order->set_total( $estimated_payment_details['total_amount']['amount'] );
		// TODO: confirm if we should use $estimated_payment_details['tax_remitted'] for something

		// update information from buyer_details
		$buyer_details = $remote_order->get_buyer_details();

		$local_order->set_billing_last_name( $buyer_details['name'] );
		$local_order->set_billing_email( $buyer_details['email'] );
		// TODO: confirm if we should use $buyer_details['email_remarketing_option'] for something

		// update order status
		if ( Order::STATUS_CREATED === $remote_order->get_status() ) {
			$local_order->set_status( 'processing' );
		}

		$local_order->save();

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
