<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Adset;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API\Response as ApiResponse;

/**
 * Generic response object for Facebook Adset request flows.
 *
 * @since x.x.x
 */
class Response extends ApiResponse {

	public function get_data() {
		return $this->response_data['data'];
	}
}
