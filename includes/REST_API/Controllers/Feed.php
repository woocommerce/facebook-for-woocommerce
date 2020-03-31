<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\REST_API\Controllers;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\REST_API;

/**
 * The feed controller.
 *
 * This is a temporary workaround until FBE 2.0 is ready, and will likely be deprecated.
 *
 * @since 1.11.0-dev.1
 */
class Feed extends \WP_REST_Controller {


	/**
	 * Feed constructor.
	 *
	 * @since 1.11.0-dev.1
	 */
	public function __construct() {

		$this->namespace = REST_API::API_NAMESPACE;

		$this->rest_base = 'feed';
	}


	/**
	 * Registers the endpoint routes.
	 *
	 * @since 1.11.0-dev.1
	 */
	public function register_routes() {

		// /feed for getting the latest feed data
		register_rest_route(
			$this->namespace, "/{$this->rest_base}", [
				[
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_item' ],
				],
			]
		);

		// /feed/ping for Facebook to hit and get the estimated generation time before calling /feed
		register_rest_route(
			$this->namespace, "/{$this->rest_base}/ping", [
				[
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'ping' ],
				],
			]
		);
	}


	/**
	 * Gets the current feed data.
	 *
	 * @since 1.11.0-dev.1
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		// TODO: get the feed {CW 2020-03-30}

		return rest_ensure_response( [] );
	}


	/**
	 * Pings the feed generator to refresh the feed file.
	 *
	 * @since 1.11.0-dev.1
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function ping() {

		$estimated_time = 20; // TODO: get a real feed generation estimate, in seconds {CW 2020-03-30}

		return rest_ensure_response( $estimated_time );
	}


}
