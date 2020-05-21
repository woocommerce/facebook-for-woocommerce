<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * Base API response object
 *
 * @since 2.0.0-dev.1
 */
class Response extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Gets the response ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Determines whether the response includes an API error.
	 *
	 * @link https://developers.facebook.com/docs/graph-api/using-graph-api/error-handling#handling-errors
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return boolean
	 */
	public function has_api_error() {

		return (bool) $this->error;
	}


	/**
	 * Gets the API error type.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string|null
	 */
	public function get_api_error_type() {

		return isset( $this->error->type ) ? $this->error->type : null;
	}


}
