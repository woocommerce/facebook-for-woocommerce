<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\Log\Create;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Log > Create Graph Api.
 */
class Request extends ApiRequest {

	/**
	 * @param string $facebook_external_merchant_settings_id Facebook External Merchant Settings ID.
	 * @param string $message Message.
	 * @param string $error Error.
	 */
	public function __construct( $facebook_external_merchant_settings_id, $message, $error ) {
		parent::__construct( "/{$facebook_external_merchant_settings_id}/log_events", 'POST' );
		$data = [
			'message' => $message,
			'error'   => $error,
		];
		parent::set_data( $data );
	}
}
