<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * FBE Configuration API read response object.
 *
 * @since 2.0.0
 */
class Response extends API\Response {


	/**
	 * Gets the messenger configuration object.
	 *
	 * @since 2.0.0
	 *
	 * @return null|API\FBE\Configuration\Messenger
	 */
	public function get_messenger_configuration() {

		$configuration = null;

		if ( ! empty( $this->response_data->messenger_chat ) && is_object( $this->response_data->messenger_chat ) ) {
			$configuration = new API\FBE\Configuration\Messenger( (array) $this->response_data->messenger_chat );
		}

		return $configuration;
	}

	/**
	 * Is Instagram Shopping enabled?
	 *
	 * @since 2.6.0
	 *
	 * @return boolean
	 */
	public function is_ig_shopping_enabled() {

		$ig_shopping_enabled = false;

		if ( ! empty( $this->response_data->ig_shopping ) && is_object( $this->response_data->ig_shopping ) ) {
			$ig_shopping_enabled = ! ! $this->response_data->ig_shopping->enabled;
		}

		return $ig_shopping_enabled;
	}

	/**
	 * Is Instagram CTA enabled?
	 *
	 * @since 2.6.0
	 *
	 * @return boolean
	 */
	public function is_ig_cta_enabled() {

		$ig_cta_enabled = false;

		if ( ! empty( $this->response_data->ig_cta ) && is_object( $this->response_data->ig_cta ) ) {
			$ig_cta_enabled = ! ! $this->response_data->ig_cta->enabled;
		}

		return $ig_cta_enabled;
	}

}
