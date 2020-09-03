<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\Facebook\Events\Event;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * API handler.
 *
 * @since 2.0.0
 *
 * @method API\Request get_request()
 */
class API extends Framework\SV_WC_API_Base {


	use API\Traits\Rate_Limited_API;


	/** @var string URI used for the request */
	protected $request_uri = 'https://graph.facebook.com/v7.0';

	/** @var string the configured access token */
	protected $access_token;


	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $access_token access token to use for API requests
	 */
	public function __construct( $access_token ) {

		$this->access_token = $access_token;

		$this->request_headers = [
			'Authorization' => "Bearer {$access_token}",
		];

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );
	}


	/**
	 * Gets the access token being used for API requests.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public function get_access_token() {

		return $this->access_token;
	}


	/**
	 * Sets the access token to use for API requests.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $access_token access token to set
	 */
	public function set_access_token( $access_token ) {

		$this->access_token = $access_token;
	}


	/**
	 * Performs an API request.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param API\Request $request request object
	 * @return API\Response
	 * @throws API\Exceptions\Request_Limit_Reached|Framework\SV_WC_API_Exception
	 */
	public function perform_request( $request ) {

		$rate_limit_id   = $request::get_rate_limit_id();
		$delay_timestamp = $this->get_rate_limit_delay( $request::get_rate_limit_id() );

		// if there is a delayed timestamp in the future, throw an exception
		if ( $delay_timestamp >= time() ) {

			$this->handle_throttled_request( $rate_limit_id, $delay_timestamp );

		} else {

			$this->set_rate_limit_delay( $rate_limit_id, 0 );
		}

		return parent::perform_request( $request );
	}


	/**
	 * Validates a response after it has been parsed and instantiated.
	 *
	 * Throws an exception if a rate limit or general API error is included in the response.
	 *
	 * @since 2.0.0
	 *
	 * @throws Framework\SV_WC_API_Exception
	 */
	protected function do_post_parse_response_validation() {

		/** @var API\Response $response */
		$response = $this->get_response();
		$request  = $this->get_request();

		if ( $response && $response->has_api_error() ) {

			$code    = $response->get_api_error_code();
			$message = sprintf( '%s: %s', $response->get_api_error_type(), $response->get_api_error_message() );

			/**
			 * Graph API
			 *
			 * 4 - API Too Many Calls
			 * 17 - API User Too Many Calls
			 * 32 - Page-level throttling
			 * 613 - Custom-level throttling
			 *
			 * Marketing API (Catalog Batch API)
			 *
			 * 80004 - There have been too many calls to this ad-account
			 *
			 * @link https://developers.facebook.com/docs/graph-api/using-graph-api/error-handling#errorcodes
			 * @link https://developers.facebook.com/docs/graph-api/using-graph-api/error-handling#rate-limiting-error-codes
			 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/batch/#validation-rules
			 */
			if ( in_array( $code, [ 4, 17, 32, 613, 80001, 80004 ], true ) ) {

				$delay_in_seconds = $this->calculate_rate_limit_delay( $response, $this->get_response_headers() );

				if ( $delay_in_seconds > 0 ) {

					$rate_limit_id = $request::get_rate_limit_id();
					$timestamp     = time() + $delay_in_seconds;

					$this->set_rate_limit_delay( $rate_limit_id, $timestamp );

					$this->handle_throttled_request( $rate_limit_id, $timestamp );

				} else {

					throw new API\Exceptions\Request_Limit_Reached( $message, $code );
				}
			}

			/**
			 * Handle invalid token errors
			 *
			 * @link https://developers.facebook.com/docs/graph-api/using-graph-api/error-handling#errorcodes
			 */
			if ( ( $code >= 200 && $code < 300 ) || in_array( $code, [ 10, 102, 190 ], false ) ) {
				set_transient( 'wc_facebook_connection_invalid', time(), DAY_IN_SECONDS );
			} else {
				// this was an unrelated error, so the OAuth connection may still be valid
				delete_transient( 'wc_facebook_connection_invalid' );
			}

			throw new Framework\SV_WC_API_Exception( $message, $code );
		}

		// if we get this far we're connected, so delete any invalid connection flag
		delete_transient( 'wc_facebook_connection_invalid' );
	}


	/**
	 * Handles a throttled API request.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $rate_limit_id ID for the API request
	 * @param int $timestamp timestamp until the delay is over
	 * @throws API\Exceptions\Request_Limit_Reached
	 */
	private function handle_throttled_request( $rate_limit_id, $timestamp ) {

		if ( time() > $timestamp ) {
			return;
		}

		$exception = new API\Exceptions\Request_Limit_Reached( "{$rate_limit_id} requests are currently throttled.", 401 );

		$date_time = new \DateTime();
		$date_time->setTimestamp( $timestamp );

		$exception->set_throttle_end( $date_time );

		throw $exception;
	}


	/**
	 * Gets the FBE installation IDs.
	 *
	 * @since 2.0.0
	 *
	 * @param string $external_business_id external business ID
	 * @return API\FBE\Installation\Read\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_installation_ids( $external_business_id ) {

		$request = new API\FBE\Installation\Read\Request( $external_business_id );

		$this->set_response_handler( API\FBE\Installation\Read\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a Page object from Facebook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $page_id page ID
	 * @return API\Pages\Read\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_page( $page_id ) {

		$request = new API\Pages\Read\Request( $page_id );

		$this->set_response_handler( API\Pages\Read\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a business manager object from Facebook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $business_manager_id business manager ID
	 * @return API\Business_Manager\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_business_manager( $business_manager_id ) {

		$request = new API\Business_Manager\Request( $business_manager_id );

		$this->set_response_handler( API\Business_Manager\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a Catalog object from Facebook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 * @return API\Catalog\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_catalog( $catalog_id ) {

		$request = new API\Catalog\Request( $catalog_id );

		$this->set_response_handler( API\Catalog\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a user object from Facebook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $user_id user ID. Defaults to the currently authenticated user
	 * @return API\User\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_user( $user_id = '' ) {

		$request = new API\User\Request( $user_id );

		$this->set_response_handler( API\User\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Delete's a user's API permission.
	 *
	 * This is their form of "revoke".
	 *
	 * @since 2.0.0
	 *
	 * @param string $user_id user ID. Defaults to the currently authenticated user
	 * @param string $permission permission to delete
	 * @return API\User\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function delete_user_permission( $user_id, $permission ) {

		$request = new API\User\Permissions\Delete\Request( $user_id, $permission );

		$this->set_response_handler( API\User\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the business configuration.
	 *
	 * @since 2.0.0
	 *
	 * @param string $external_business_id external business ID
	 * @return API\FBE\Configuration\Read\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_business_configuration( $external_business_id ) {

		$request = new API\FBE\Configuration\Request( $external_business_id, 'GET' );

		$this->set_response_handler( API\FBE\Configuration\Read\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Updates the messenger configuration.
	 *
	 * @since 2.0.0
	 *
	 * @param string $external_business_id external business ID
	 * @param API\FBE\Configuration\Messenger $configuration messenger configuration
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_messenger_configuration(  $external_business_id, API\FBE\Configuration\Messenger $configuration ) {

		$request = new API\FBE\Configuration\Update\Request( $external_business_id );

		$request->set_messenger_configuration( $configuration );

		$this->set_response_handler( API\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Uses the Catalog Batch API to update or remove items from catalog.
	 *
	 * @see Sync::create_or_update_products()
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 * @param array $requests array of prefixed product IDs to create, update or remove
	 * @param bool $allow_upsert whether to allow updates to insert new items
	 * @return \SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function send_item_updates( $catalog_id, $requests, $allow_upsert ) {

		$request = new \SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates\Request( $catalog_id );

		$request->set_requests( $requests );
		$request->set_allow_upsert( $allow_upsert );

		$this->set_response_handler( \SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Creates a Product Group object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 * @param array $data product group data
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function create_product_group( $catalog_id, $data ) {

		$request = $this->get_new_request( [
			'path'   => "/{$catalog_id}/product_groups",
			'method' => 'POST',
		] );

		$request->set_data( $data );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Updates the default product item and the available variation attributes of a product group.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id product group ID
	 * @param array $data product group data
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_product_group( $product_group_id, $data ) {

		$request = $this->get_new_request( [
			'path'   => "/{$product_group_id}",
			'method' => 'POST',
		] );

		$request->set_data( $data );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Deletes a Product Group object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function delete_product_group( $product_group_id ) {

		$request = $this->get_new_request( [
			'path'   => "/{$product_group_id}",
			'method' => 'DELETE',
		] );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a list of Product Items in the given Product Group.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id product group ID
	 * @param int $limit max number of results returned per page of data
	 * @return API\Catalog\Product_Group\Products\Read\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_product_group_products( $product_group_id, $limit = 1000 ) {

		$request = new API\Catalog\Product_Group\Products\Read\Request( $product_group_id, $limit );

		$this->set_response_handler( API\Catalog\Product_Group\Products\Read\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Finds a Product Item using the Catalog ID and the Retailer ID of the product or product variation.
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 * @param string $retailer_id retailer ID of the product
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function find_product_item( $catalog_id, $retailer_id ) {

		$request = new \SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Find\Request( $catalog_id, $retailer_id );

		$this->set_response_handler( \SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Creates a Product Item object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id parent product ID
	 * @param array $data product data
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function create_product_item( $product_group_id, $data ) {

		$request = $this->get_new_request( [
			'path'   => "/{$product_group_id}/products",
			'method' => 'POST',
		] );

		$request->set_data( $data );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Updates a Product Item object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_item_id product item ID
	 * @param array $data product data
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_product_item( $product_item_id, $data ) {

		$request = $this->get_new_request( [
			'path'   => "/{$product_item_id}",
			'method' => 'POST',
		] );

		$request->set_data( $data );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Deletes a Product Item object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_item_id product item ID
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function delete_product_item( $product_item_id ) {

		$request = $this->get_new_request( [
			'path'   => "/{$product_item_id}",
			'method' => 'DELETE',
		] );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Sends Pixel events.
	 *
	 * @since 2.0.0
	 *
	 * @param string $pixel_id pixel ID
	 * @param Event[] $events events to send
	 * @return Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function send_pixel_events( $pixel_id, array $events ) {

		$request = new API\Pixel\Events\Request( $pixel_id, $events );

		$this->set_response_handler( Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the next page of results for a paginated response.
	 *
	 * @since 2.0.0
	 *
	 * @param API\Response $response previous response object
	 * @param int $additional_pages number of additional pages of results to retrieve
	 * @return API\Response|null
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function next( API\Response $response, $additional_pages = null ) {

		$next_response = null;

		// get the next page if we haven't reached the limit of pages to retrieve and the endpoint for the next page is available
		if ( ( null === $additional_pages || $response->get_pages_retrieved() <= $additional_pages ) && $response->get_next_page_endpoint() ) {

			$components = parse_url( str_replace( $this->request_uri, '', $response->get_next_page_endpoint() ) );

			$request = $this->get_new_request( [
				'path'   => isset( $components['path'] ) ? $components['path'] : '',
				'method' => 'GET',
				'params' => isset( $components['query'] ) ? wp_parse_args( $components['query'] ) : [],
			] );

			$this->set_response_handler( get_class( $response ) );

			$next_response = $this->perform_request( $request );

			// this is the n + 1 page of results for the original response
			$next_response->set_pages_retrieved( $response->get_pages_retrieved() + 1 );
		}

		return $next_response;
	}


	/**
	 * Gets all new orders.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $page_id page ID
	 * @return API\Orders\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_new_orders( $page_id ) {

		$request_args = [
			'state' => [
				Order::STATUS_PROCESSING,
				Order::STATUS_CREATED,
			]
		];

		$request = new API\Orders\Request( $page_id, $request_args );

		$this->set_response_handler( API\Orders\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets a single order based on its remote ID.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @return API\Orders\Read\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_order( $remote_id ) {

		$request = new API\Orders\Read\Request( $remote_id );

		$this->set_response_handler( API\Orders\Read\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Acknowledges the given order.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param string $merchant_order_reference WC order ID
	 * @return API\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function acknowledge_order( $remote_id, $merchant_order_reference ) {

		$request = new API\Orders\Acknowledge\Request( $remote_id, $merchant_order_reference );

		$this->set_response_handler( API\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Issues a fulfillment request for the given order.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/fulfillment-api#attach_shipment
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param array $fulfillment_data fulfillment data to be sent on the request
	 * @return API\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function fulfill_order( $remote_id, $fulfillment_data ) {

		$request = new API\Orders\Fulfillment\Request( $remote_id, $fulfillment_data );

		$this->set_response_handler( API\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Cancels the given order.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param string $reason cancellation reason
	 * @param bool $restock_items whether to restock items remotely
	 * @return API\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function cancel_order( $remote_id, $reason, $restock_items = true ) {

		$request = new API\Orders\Cancel\Request( $remote_id, $reason, $restock_items );

		$this->set_response_handler( API\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Issues a refund request for the given order.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/cancellation-refund-api#refund_order
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param string $remote_id remote order ID
	 * @param array $refund_data refund data to be sent on the request
	 * @return API\Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function add_order_refund( $remote_id, $refund_data ) {

		$request = new API\Orders\Refund\Request( $remote_id, $refund_data );

		$this->set_response_handler( API\Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Returns a new request object.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args {
	 *     Optional. An array of request arguments.
	 *
	 *     @type string $path request path
	 *     @type string $method request method
	 *     @type array $params request parameters
	 * }
	 * @return Request
	 */
	protected function get_new_request( $args = [] ) {

		$defaults = [
			'path'   => '/',
			'method' => 'GET',
			'params' => [],
		];

		$args    = wp_parse_args( $args, $defaults );
		$request = new Request( $args['path'], $args['method'] );

		if ( $args['params'] ) {
			$request->set_params( $args['params'] );
		}

		return $request;
	}


	/**
	 * Returns the plugin class instance associated with this API.
	 *
	 * @since 2.0.0
	 *
	 * @return \WC_Facebookcommerce
	 */
	protected function get_plugin() {

		return facebook_for_woocommerce();
	}


}
