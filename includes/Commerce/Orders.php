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

use SkyVerge\WooCommerce\Facebook\API\Orders\Cancel\Request;
use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception;
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

	/** @var string the meta key used to store the email remarketing option */
	const EMAIL_REMARKETING_META_KEY = '_wc_facebook_commerce_email_remarketing';


	/**
	 * Orders constructor.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function __construct() {

		$this->add_hooks();
	}


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
	 * @throws SV_WC_Plugin_Exception|\WC_Data_Exception
	 */
	public function update_local_order( Order $remote_order, \WC_Order $local_order ) {

		// add/update items
		foreach ( $remote_order->get_items() as $item ) {

			$product = Products::get_product_by_fb_product_id( $item['product_id'] );

			if ( empty( $product ) ) {
				$product = Products::get_product_by_fb_retailer_id( $item['retailer_id'] );
			}

			if ( ! $product instanceof \WC_Product ) {

				// add a note and skip this item
				$local_order->add_order_note( "Product with retailer ID {$item['retailer_id']} not found" );
				continue;
			}

			$matching_wc_order_item = false;

			// check if the local order already has this item
			foreach ( $local_order->get_items() as $wc_order_item ) {

				if ( $wc_order_item instanceof \WC_Order_Item_Product && $product->get_id() === $wc_order_item->get_product_id() ) {
					$matching_wc_order_item = $wc_order_item;
					break;
				}
			}

			if ( empty( $matching_wc_order_item ) ) {

				$matching_wc_order_item = new \WC_Order_Item_Product();
				$local_order->add_item( $matching_wc_order_item );
			}

			$matching_wc_order_item->set_product_id( $product->get_id() );
			$matching_wc_order_item->set_quantity( $item['quantity'] );
			$matching_wc_order_item->set_subtotal( $item['quantity'] * $item['price_per_unit']['amount'] );
			// we use the estimated_tax because the captured_tax represents the tax after the order/item has been shipped and we don't fulfill order at the line-item level
			$matching_wc_order_item->set_subtotal_tax( $item['tax_details']['estimated_tax']['amount'] );
			$matching_wc_order_item->save();
		}

		// update information from selected_shipping_option
		$selected_shipping_option = $remote_order->get_selected_shipping_option();

		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( $selected_shipping_option['name'] );
		$shipping_item->set_total( $selected_shipping_option['price']['amount'] );
		$shipping_item->set_taxes( [ $selected_shipping_option['calculated_tax']['amount'] ] );
		$shipping_item->save();
		$local_order->add_item( $shipping_item );

		$local_order->set_shipping_total( $selected_shipping_option['price']['amount'] );
		$local_order->set_shipping_tax( $selected_shipping_option['calculated_tax']['amount'] );

		// update information from shipping_address
		$shipping_address = $remote_order->get_shipping_address();

		if ( ! empty( $shipping_address['name'] ) ) {

			if ( strpos( $shipping_address['name'], ' ' ) !== false ) {

				list( $first_name, $last_name ) = explode( ' ', $shipping_address['name'], 2 );
				$local_order->set_shipping_first_name( $first_name );
				$local_order->set_shipping_last_name( $last_name );

			} else {

				$local_order->set_shipping_last_name( $shipping_address['name'] );
			}
		}

		if ( ! empty( $shipping_address['street1'] ) ) {
			$local_order->set_shipping_address_1( $shipping_address['street1'] );
		}

		if ( ! empty( $shipping_address['street2'] ) ) {
			$local_order->set_shipping_address_2( $shipping_address['street2'] );
		}

		if ( ! empty( $shipping_address['city'] ) ) {
			$local_order->set_shipping_city( $shipping_address['city'] );
		}

		if ( ! empty( $shipping_address['state'] ) ) {
			$local_order->set_shipping_state( $shipping_address['state'] );
		}

		if ( ! empty( $shipping_address['postal_code'] ) ) {
			$local_order->set_shipping_postcode( $shipping_address['postal_code'] );
		}

		if ( ! empty( $shipping_address['country'] ) ) {
			$local_order->set_shipping_country( $shipping_address['country'] );
		}

		// update information from estimated_payment_details
		$estimated_payment_details = $remote_order->get_estimated_payment_details();

		// we do not use subtotal values from the API because WC calculates them on the fly based on the items
		$local_order->set_total( $estimated_payment_details['total_amount']['amount'] );
		$local_order->set_currency( $estimated_payment_details['total_amount']['currency'] );

		// update information from buyer_details
		$buyer_details = $remote_order->get_buyer_details();

		if ( strpos( $buyer_details['name'], ' ' ) !== false ) {

			list( $first_name, $last_name ) = explode( ' ', $buyer_details['name'], 2 );
			$local_order->set_billing_first_name( $first_name );
			$local_order->set_billing_last_name( $last_name );

		} else {

			$local_order->set_billing_last_name( $buyer_details['name'] );
		}

		$local_order->set_billing_email( $buyer_details['email'] );
		$local_order->update_meta_data( self::EMAIL_REMARKETING_META_KEY, wc_bool_to_string( $buyer_details['email_remarketing_option'] ) );

		// update order status
		if ( Order::STATUS_CREATED === $remote_order->get_status() ) {
			$local_order->set_status( 'processing' );
		}

		// set remote ID
		$local_order->update_meta_data( self::REMOTE_ID_META_KEY, $remote_order->get_id() );

		$local_order->save();

		return $local_order;
	}


	/**
	 * Updates WooCommerce’s Orders by fetching orders from the API and either creating or updating local orders.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function update_local_orders() {

		$page_id = facebook_for_woocommerce()->get_integration()->get_facebook_page_id();

		try {

			$response = facebook_for_woocommerce()->get_api( facebook_for_woocommerce()->get_connection_handler()->get_page_access_token() )->get_new_orders( $page_id );

		} catch ( SV_WC_API_Exception $exception ) {

			facebook_for_woocommerce()->log( 'Error fetching Commerce orders from the Orders API: ' . $exception->getMessage() );

			return;
		}

		$remote_orders = $response->get_orders();

		foreach ( $remote_orders as $remote_order ) {

			$local_order = $this->find_local_order( $remote_order->get_id() );

			try {

				if ( empty( $local_order ) ) {
					$local_order = $this->create_local_order( $remote_order );
				} else {
					$local_order = $this->update_local_order( $remote_order, $local_order );
				}

			} catch ( \Exception $exception ) {

				if ( ! empty( $local_order ) ) {
					// add note to order
					$local_order->add_order_note( 'Error updating local order from Commerce order from the Orders API: ' . $exception->getMessage() );
				} else {
					facebook_for_woocommerce()->log( 'Error creating local order from Commerce order from the Orders API: ' . $exception->getMessage() );
				}

				continue;
			}

			if ( ! empty( $local_order ) && Order::STATUS_CREATED === $remote_order->get_status() ) {

				// acknowledge the order
				try {
					facebook_for_woocommerce()->get_api( facebook_for_woocommerce()->get_connection_handler()->get_page_access_token() )->acknowledge_order( $remote_order->get_id(), $local_order->get_id() );
				} catch ( SV_WC_API_Exception $exception ) {
					$local_order->add_order_note( 'Error acknowledging the order: ' . $exception->getMessage() );
				}
			}
		}
	}


	/**
	 * Frequency in seconds that orders are updated.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return int
	 */
	public function get_order_update_interval() {

		$default_interval = 5 * MINUTE_IN_SECONDS;

		/**
		 * Filters the interval between querying Facebook for new or updated orders.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param int $interval interval in seconds. Defaults to 5 minutes, and the minimum interval is 120 seconds.
		 */
		$interval = apply_filters( 'wc_facebook_commerce_order_update_interval', $default_interval );

		// if given a valid number, ensure it's 120 seconds at a minimum
		if ( is_numeric( $interval ) ) {
			$interval = max( 2 * MINUTE_IN_SECONDS, $interval );
		} else {
			$interval = $default_interval; // invalid values should get the default
		}

		return $interval;
	}


	/**
	 * Schedules a recurring ACTION_FETCH_ORDERS action, if not already scheduled.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function schedule_local_orders_update() {

		if ( false === as_next_scheduled_action( self::ACTION_FETCH_ORDERS, [], \WC_Facebookcommerce::PLUGIN_ID ) ) {

			$interval = $this->get_order_update_interval();

			as_schedule_recurring_action( time() + $interval, $interval, self::ACTION_FETCH_ORDERS, [], \WC_Facebookcommerce::PLUGIN_ID );
		}
	}


	/**
	 * Adds the necessary action & filter hooks.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function add_hooks() {

		// schedule a recurring ACTION_FETCH_ORDERS action, if not already scheduled
		add_action( 'init', [ $this, 'schedule_local_orders_update' ] );

		add_action( self::ACTION_FETCH_ORDERS, [ $this, 'update_local_orders' ] );
	}


	/**
	 * Fulfills an order via API.
	 *
	 * In addition to the exceptions we throw for missing data, the API request will also fail if:
	 * - The stored remote ID is invalid
	 * - The order has an item with a retailer ID that was not originally part of the order
	 * - An item has a different quantity than what was originally ordered
	 * - The remote order was already fulfilled
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Order $order order object
	 * @param string $tracking_number shipping tracking number
	 * @param string $carrier shipping carrier
	 * @throws SV_WC_Plugin_Exception
	 */
	public function fulfill_order( \WC_Order $order, $tracking_number, $carrier ) {

		try {

			$remote_id = $order->get_meta( self::REMOTE_ID_META_KEY );

			if ( ! $remote_id ) {
				throw new SV_WC_Plugin_Exception( __( 'Remote ID not found.', 'facebook-for-woocommerce' ) );
			}

			$items = [];

			/** @var \WC_Order_Item_Product $item */
			foreach ( $order->get_items() as $item ) {

				if ( $product = $item->get_product() ) {

					$items[] = [
						'retailer_id' => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
						'quantity'    => $item->get_quantity(),
					];
				}
			}

			if ( empty( $items ) ) {
				throw new SV_WC_Plugin_Exception( __( 'No valid Facebook products were found.', 'facebook-for-woocommerce' ) );
			}

			$fulfillment_data = [
				'items'         => $items,
				'tracking_info' => [
					'carrier'         => $carrier,
					'tracking_number' => $tracking_number,
				],
			];

			$plugin = facebook_for_woocommerce();

			$plugin->get_api( $plugin->get_connection_handler()->get_page_access_token() )->fulfill_order( $remote_id, $fulfillment_data );

			$order->add_order_note( __( 'Remote order fulfilled.', 'facebook-for-woocommerce' ) );

		} catch ( SV_WC_Plugin_Exception $exception ) {

			$order->add_order_note( sprintf( __( 'Remote order could not be fulfilled. %s', 'facebook-for-woocommerce' ), $exception->getMessage() ) );

			throw $exception;
		}
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
	 * @param string $reason_code cancellation reason code
	 * @throws SV_WC_Plugin_Exception
	 */
	public function cancel_order( \WC_Order $order, $reason_code ) {

		$plugin = facebook_for_woocommerce();

		$api = $plugin->get_api( $plugin->get_connection_handler()->get_page_access_token() );

		$valid_reason_codes = [
			Request::REASON_CUSTOMER_REQUESTED,
			Request::REASON_INVALID_ADDRESS,
			Request::REASON_OTHER,
			Request::REASON_OUT_OF_STOCK,
			Request::REASON_SUSPICIOUS_ORDER,
		];

		if ( ! in_array( $reason_code, $valid_reason_codes, true ) ) {
			$reason_code = Request::REASON_OTHER;
		}

		try {

			$remote_id = $order->get_meta( self::REMOTE_ID_META_KEY );

			if ( ! $remote_id ) {
				throw new SV_WC_Plugin_Exception( __( 'Remote ID not found.', 'facebook-for-woocommerce' ) );
			}

			$api->cancel_order( $remote_id, $reason_code );

			$order->add_order_note( __( 'Remote order cancelled.', 'facebook-for-woocommerce' ) );

		} catch ( SV_WC_Plugin_Exception $exception ) {

			$order->add_order_note( sprintf( __( 'Remote order could not be cancelled. %s', 'facebook-for-woocommerce' ), $exception->getMessage() ) );

			throw $exception;
		}
	}


}
