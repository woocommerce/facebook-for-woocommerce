<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Traits;

defined( 'ABSPATH' ) or exit;


/**
 * Helper methods to traverse Graph API paged results.
 *
 * @link https://developers.facebook.com/docs/graph-api/using-graph-api/#paging
 *
 * @since 2.0.0
 */
trait Paginated_Response {


	/** @var string number of pages retrieved from this response */
	private $pages_retrieved = 1;

	/** @var mixed decoded response data */
	public $response_data;


	/**
	 * Sets the number of pages retrieved from this response.
	 *
	 * @since 2.0.0
	 *
	 * @param int $pages_retrieved
	 */
	public function set_pages_retrieved( $pages_retrieved ) {

		$this->pages_retrieved = $pages_retrieved;
	}


	/**
	 * Gets the number of pages retrieved from this response.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	public function get_pages_retrieved() {

		return $this->pages_retrieved;
	}


	/**
	 * Gets the response data.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_data() {

		return ! empty( $this->response_data->data ) ? $this->response_data->data : array();
	}


	/**
	 * Gets the pagination data.
	 *
	 * @since 2.0.0
	 *
	 * @return object
	 */
	public function get_pagination_data() {

		return ! empty( $this->response_data->paging ) ? $this->response_data->paging : new \stdClass();
	}


	/**
	 * Gets the API endpoint for the next page of results.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_next_page_endpoint() {

		return ! empty( $this->get_pagination_data()->next ) ? $this->get_pagination_data()->next : '';
	}


	/**
	 * Gets the API endpoint for the previous page of results.
	 *
	 * @since 2.0.0
	 *
	 * @param string
	 */
	public function get_previous_page_endpoint() {

		return ! empty( $this->get_pagination_data()->previous ) ? $this->get_pagination_data()->previous : '';
	}


}
