<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Traits;

defined( 'ABSPATH' ) or exit;

/**
 * Rate limited response trait.
 *
 * @since 2.0.0-dev.1
 */
trait Rate_Limited_Response {


	/**
	 * Gets usage information from the response headers.
	 *
	 * @see https://developers.facebook.com/docs/graph-api/overview/rate-limiting#headers-2
	 * @see https://developers.facebook.com/docs/graph-api/overview/rate-limiting#headers
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $headers response headers
	 *
	 * @return array
	 */
	private function get_usage_data( $headers ) {

		$usage_data = [];

		if ( ! empty( $headers['X-Business-Use-Case-Usage'] ) ) {

			$usage_data = $headers['X-Business-Use-Case-Usage'];

		} elseif ( ! empty( $headers['X-App-Usage'] ) ) {

			$usage_data = $headers['X-App-Usage'];
		}

		return $usage_data;
	}


}
