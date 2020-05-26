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


	/**
	 * Gets the percentage of calls made by the app over a rolling one hour period.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $headers response headers
	 * @return int
	 */
	public function get_rate_limit_usage( $headers ) {

		$usage_data = $this->get_usage_data( $headers );

		return (int) $usage_data['call_count'] ?: 0;
	}


	/**
	 * Gets the percentage of total time allotted for query processing.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $headers response headers
	 * @return int
	 */
	public function get_rate_limit_total_time( $headers ) {

		$usage_data = $this->get_usage_data( $headers );

		return (int) $usage_data['total_time'] ?: 0;
	}


	/**
	 * Gets the percentage of CPU time allotted for query processing.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $headers response headers
	 * @return int
	 */
	public function get_rate_limit_total_cpu_time( $headers ) {

		$usage_data = $this->get_usage_data( $headers );

		return (int) $usage_data['total_cputime'] ?: 0;
	}


}
