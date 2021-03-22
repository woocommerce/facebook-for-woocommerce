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
			[ $this, 'add_tracker_data' ]
		);
	}

	public function add_tracker_data( array $data = [] ) {
		$connection_handler = facebook_for_woocommerce()->get_connection_handler();
		if ( ! $connection_handler ) {
			return;
		}

		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = [];
		}

		$data['extensions']['facebook-for-woocommerce']['is-connected'] = $connection_handler->is_connected();

		wc_get_logger()->debug( print_r( $data, true ) );

		return $data;
	}
}
