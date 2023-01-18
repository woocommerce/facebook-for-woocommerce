<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Adset\Create;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API\Response as ApiResponse;


/**
 * Response object for Facebook Adset Creation request.
 *
 * @since x.x.x
 */
class Response extends ApiResponse {

	public function get_data() {
		return $this->response_data;
	}
}
