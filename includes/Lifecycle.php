<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * The Facebook for WooCommerce plugin lifecycle handler.
 *
 * @since 1.10.0-dev.1
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @since 1.10.0-dev.1
	 *
	 * @param Framework\SV_WC_Plugin $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'1.10.0',
		];
	}


	/**
	 * Updates to v1.10.0.
	 *
	 * @since 1.10.0-dev.1
	 */
	protected function upgrade_to_1_10_0() {

	}


}
