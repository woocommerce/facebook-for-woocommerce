<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Tip\Log;

use WooCommerce\Facebook\API\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Tip > Log Graph Api.
 */
class Request extends ApiRequest {

	/**
	 * @param string $tip_id Tip ID.
	 * @param string $channel_id Channel ID.
	 * @param string $event Event Data.
	 */
	public function __construct( $tip_id, $channel_id, $event ) {
		parent::__construct( '/log_tip_events', 'POST' );
		$data = [
			'tip_id'     => $tip_id,
			'channel_id' => $channel_id,
			'event'      => $event,
		];
		parent::set_data( $data );
	}
}
