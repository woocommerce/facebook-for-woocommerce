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
	 * @since 2.1.5
	 */
	public function __construct( \WC_Facebookcommerce $plugin ) {

		add_action( 'rest_api_init', array( $this, 'init_webhook_endpoint' ) );
	}


	/**
	 * Register WebHook REST API endpoint
	 *
	 * @since 2.1.5
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
	 * @see SkyVerge\WooCommerce\Facebook\Handlers\Connection
	 */
	public function webhook_callback() {

		do_action( 'fbe_webhook', json_decode( file_get_contents( 'php://input' ) ) );
	}
}
