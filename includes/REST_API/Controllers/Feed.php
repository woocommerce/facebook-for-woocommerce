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
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

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

		/** @see Feed::get_item() /feed for getting the latest feed data */
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

		$feed_handler = new \WC_Facebook_Product_Feed();

		$file_path = $feed_handler->get_file_path();

		try {

			// try regenerating the file if it doesn't already exist
			if ( ! file_exists( $file_path ) ) {
				$feed_handler->generate_feed();
			}

			if ( ! is_readable( $file_path ) ) {
				throw new SV_WC_Plugin_Exception( 'File is not readable', 400 );
			}

		} catch ( \Exception $exception ) {

			return new \WP_Error( 'wc_facebook_could_not_get_feed_file', sprintf( 'Could not get feed file. %s', $exception->getMessage() ), [ 'status' => $exception->getCode() ] );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . basename($file_path ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length:'. filesize( $file_path ) );

		readfile( $file_path, 'rb' );

		exit;
	}


	/**
	 * Pings the feed generator to refresh the feed file.
	 *
	 * @since 1.11.0-dev.1
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function ping() {

		$feed_handler = new \WC_Facebook_Product_Feed();

		$feed_handler->schedule_feed_generation();

		return rest_ensure_response( $feed_handler->get_estimated_feed_generation_time() );
	}


	/**
	 * Gets the feed data URL.
	 *
	 * @since 1.11.0-dev.1
	 *
	 * @return string
	 */
	public static function get_feed_url() {

		return get_rest_url( null, REST_API::API_NAMESPACE . '/feed' );
	}


	/**
	 * Gets the feed ping URL.
	 *
	 * @since 1.11.0-dev.1
	 *
	 * @return string
	 */
	public static function get_feed_ping_url() {

		return self::get_feed_url() . '/ping';
	}


}
