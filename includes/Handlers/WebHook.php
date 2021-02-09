<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Handlers;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Helper;

defined( 'ABSPATH' ) or exit;

/**
 * The connection handler.
 *
 * @since 2.0.0
 */
class WebHook {

	/** @var string auth page ID */
	const WEBHOOK_PAGE_ID = 'wc-facebook-webhook';

	/**
	 * Constructs a new WebHook.
	 *
	 * @param \WC_Facebookcommerce $plugin Plugin instance.
	 *
	 * @since 2.2.1-dev.1
	 */
	public function __construct( \WC_Facebookcommerce $plugin ) {

		add_action( 'rest_api_init', array( $this, 'init_webhook_endpoint' ) );
	}


	/**
	 * Register WebHook REST API endpoint
	 *
	 * @since 2.2.1-dev.1
	 */
	public function init_webhook_endpoint() {

		register_rest_route(
			'facebook/v1',
			'webhook',
			array(
				array(
					'methods'             => array( 'GET', 'POST' ),
					'callback'            => array( $this, 'webhook_callback' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
	}


	/**
	 * Endpoint permissions
	 * Woo Connect Bridge is sending the WebHook request using generated key.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return boolean
	 */
	public function permission_callback() {

		add_filter( 'woocommerce_rest_is_request_to_rest_api', '__return_true' );

		$user = apply_filters( 'determine_current_user', null );

		remove_filter( 'woocommerce_rest_is_request_to_rest_api', '__return_true' );

		if ( empty( $user ) ) {
			return false;
		}

		return true;
	}


	/**
	 * WebHook Listener
	 *
	 * @since 2.2.1-dev.1
	 * @see SkyVerge\WooCommerce\Facebook\Handlers\Connection
	 *
	 * @param \WP_REST_Request $request The request.
	 */
	public function webhook_callback( \WP_REST_Request $request ) {

		$request_body = $this->parse_body( $request );
		if ( empty( $request_body ) ) {
			return;
		}

		do_action( 'fbe_webhook', json_decode( file_get_contents( 'php://input' ) ) );
	}


	/**
	 * Return request's body parsed
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return array
	 */
	protected function parse_body( \WP_REST_Request $request ) {

		$body = $request->get_body();

		// "Sanitize" JSON object (trim object, remove tabs)
		$body = trim( preg_replace( '/\t+/', '', $body ) );

		// Transform JSON into a PHP array
		return json_decode( $body, true );
	}
}
