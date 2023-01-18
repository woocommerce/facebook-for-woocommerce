<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Adset\Update;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API\Response as ApiResponse;

/**
 * Response object for Facebook Adset Update request.
 *
 * @since x.x.x
 */
class Response extends ApiResponse {

	public function get_name() {
		return $this->response_data['name'];
	}

	public function get_id() {
		return $this->response_data['id'];
	}

	public function get_targeting() {
		return $this->response_data['targeting'];
	}
}
