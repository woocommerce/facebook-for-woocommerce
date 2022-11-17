<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\FBE\Configuration\Read;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API;

/**
 * FBE Configuration API read response object.
 */
class Response extends API\Response {
	/**
	 * Gets the messenger configuration object.
	 *
	 * @return API\FBE\Configuration\Messenger
	 */
	public function get_messenger_configuration(): API\FBE\Configuration\Messenger {
		return new API\FBE\Configuration\Messenger( $this->response_data['messenger_chat'] ?? [] );
	}

	/**
	 * Is Instagram Shopping enabled?
	 *
	 * @return boolean
	 */
	public function is_ig_shopping_enabled(): bool {
		return ! ! $this->response_data['ig_shopping']['enabled'] ?? false;
	}

	/**
	 * Is Instagram CTA enabled?
	 *
	 * @return boolean
	 */
	public function is_ig_cta_enabled(): bool {
		return ! ! $this->response_data['ig_cta']['enabled'];
	}
}
