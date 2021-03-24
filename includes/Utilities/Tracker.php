<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Utilities;

defined( 'ABSPATH' ) or exit;

/**
 * Class for adding diagnostic info to WooCommerce Tracker snapshot.
 *
 * See https://woocommerce.com/usage-tracking/ for more information.
 *
 * @since %VERSION%
 */
class Tracker {

	/**
	 * Constructor.
	 *
	 * @since %VERSION%
	 */
	public function __construct() {
		add_filter(
			'woocommerce_tracker_data',
			array( $this, 'add_tracker_data' )
		);
	}

	/**
	 * Append our tracker properties.
	 *
	 * @param array $data The current tracker snapshot data.
	 * @return array $data Snapshot updated with our data.
	 * @since %VERSION%
	 */
	public function add_tracker_data( array $data = array() ) {
		$connection_handler = facebook_for_woocommerce()->get_connection_handler();
		if ( ! $connection_handler ) {
			return $data;
		}

		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = array();
		}

		$connection_is_happy = $connection_handler->is_connected() && ! get_transient( 'wc_facebook_connection_invalid' );
		$data['extensions']['facebook-for-woocommerce']['is-connected'] = $connection_is_happy;

		return $data;
	}
}
