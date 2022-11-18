<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Tip\Read;

use WooCommerce\Facebook\API\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Tip > Read Graph Api.
 *
 * @property-read array  tip_title
 * @property-read array  tip_body
 * @property-read string tip_action_link
 * @property-read array  tip_action
 * @property-read string tip_img_url
 */
class Response extends ApiResponse {
	/**
	 * Returns tip title html content.
	 *
	 * @return string
	 */
	public function get_tip_title_html(): string {
		return $this->tip_title['__html'] ?? '';
	}

	/**
	 * Returns tip body html content.
	 *
	 * @return string
	 */
	public function get_tip_body_html(): string {
		return $this->tip_body['__html'] ?? '';
	}

	/**
	 * Returns tip action html content.
	 *
	 * @return string
	 */
	public function get_tip_action_html(): string {
		return $this->tip_action['_html'] ?? '';
	}
}
