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

use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;


/**
 * API handler.
 *
 * @since 2.0.0-dev.1
 *
 * @method Response perform_request( $request )
 */
class API extends Framework\SV_WC_API_Base {


	/** @var string URI used for the request */
	protected $request_uri = 'https://graph.facebook.com/v7.0';

	/** @var string the configured access token */
	protected $access_token;


	/**
	 * Constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct( $access_token ) {

		$this->access_token = $access_token;

		$this->request_headers = [
			'Authorization' => "Bearer {$access_token}",
		];
	}


	/**
	 * Gets a Page object from Facebook.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param $page_id page ID
	 * @return
	 */
	public function get_page( $page_id ) {

		// TODO: Implement get_page() method.
	}


	/**
	 * Uses the Catalog Batch API to update or remove items from catalog.
	 *
	 * @see Sync::create_or_update_products()
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $catalog_id catalog ID
	 * @param array $requests array of prefixed product IDs to create, update or remove
	 * @param bool $allow_upsert whether to allow updates to insert new items
	 * @return Response
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
	 * @since 2.0.0-dev.1
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
	 * @since 2.0.0-dev.1
	 *
	 * @param string $product_group_id
	 * @param array $data
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
	 * @since 2.0.0-dev.1
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
	 * Finds a Product Item using the Catalog ID and the Retailer ID of the product or product variation.
	 *
	 * @since 2.0.0-dev.1
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
	 * @since 2.0.0-dev.1
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
	 * @since 2.0.0-dev.1
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
	 * @since 2.0.0-dev.1
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
	 * Stores an option with the delay, in seconds, for requests with the given rate limit ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $rate_limit_id
	 * @param int $delay
	 */
	public function set_rate_limit_delay( $rate_limit_id, $delay ) {

		// TODO: Implement set_rate_limit_delay() method.
	}


	/**
	 * Gets the number of seconds before a new request with the given rate limit ID can be made again
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $rate_limit_id
	 * @return int
	 */
	public function get_rate_limit_delay( $rate_limit_id ) {

		// TODO: Implement get_rate_limit_delay() method.
	}


	/**
	 * Uses the information in a Rate_Limited_Response object to calculate the next delay for requests of the same type.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param Rate_Limited_Response $response
	 * @return int
	 */
	protected function calculate_rate_limit_delay( $response ) {

		// TODO: Implement calculate_rate_limit_delay() method.
	}


	/**
	 * Returns a new request object.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $args {
	 *     Optional. An array of request arguments.
	 *
	 *     @type string $path request path
	 *     @type string $method request method
	 * }
	 * @return Request
	 */
	protected function get_new_request( $args = [] ) {

		return new Request( $args['path'] ?: '/', $args['method'] ?: 'GET' );
	}


	/**
	 * Returns the plugin class instance associated with this API.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return \WC_Facebookcommerce
	 */
	protected function get_plugin() {

		return facebook_for_woocommerce();
	}


}
