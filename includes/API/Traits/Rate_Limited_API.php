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
 * Rate limited API trait.
 *
 * @since 2.0.0-dev.1
 */
trait Rate_Limited_API {


	/**
	 * Stores the delay, in seconds, for requests with the given rate limit ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $rate_limit_id request ID for rate limiting
	 * @param int $delay delay in seconds
	 */
	public function set_rate_limit_delay( $rate_limit_id, $delay ) {

		update_option( "wc_facebook_rate_limit_${rate_limit_id}", $delay );
	}


	/**
	 * Gets the number of seconds before a new request with the given rate limit ID can be made.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $rate_limit_id request ID for rate limiting
	 * @return int
	 */
	public function get_rate_limit_delay( $rate_limit_id ) {

		return (int) get_option( "wc_facebook_rate_limit_${rate_limit_id}", 0 );
	}


}
