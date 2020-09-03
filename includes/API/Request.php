<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * Base API request object.
 *
 * @since 2.0.0
 */
class Request extends Framework\SV_WC_API_JSON_Request {


	use Traits\Rate_Limited_Request;


	/** @var int number of retries to attempt if told to do so by Facebook */
	protected $retry_limit = 5;

	/** @var int number of times this request has been retried */
	protected $retry_count = 0;

	/** @var int[] the response codes that should trigger */
	protected $retry_codes = [];


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $path endpoint route
	 * @param string $method HTTP method
	 */
	public function __construct( $path, $method ) {

		$this->method = $method;
		$this->path   = $path;
	}


	/**
	 * Sets the request parameters.
	 *
	 * @since 2.0.0
	 *
	 * @param array $params request parameters
	 */
	public function set_params( $params ) {

		$this->params = $params;
	}


	/**
	 * Sets the request data.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data request data
	 */
	public function set_data( $data ) {

		$this->data = $data;
	}


	/**
	 * Gets the number of times this request has been retried.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return int
	 */
	public function get_retry_count() {

		return $this->retry_count;
	}


	/**
	 * Marks the request as having been retried.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function mark_retry() {

		$this->retry_count++;
	}


	/**
	 * Gets the number of retries to attempt if told to do so by Facebook.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return int
	 */
	public function get_retry_limit() {

		return $this->retry_limit;
	}


	/**
	 * Response codes that should trigger a retry for this request.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return int[]
	 */
	public function get_retry_codes() {

		return $this->retry_codes;
	}


}
