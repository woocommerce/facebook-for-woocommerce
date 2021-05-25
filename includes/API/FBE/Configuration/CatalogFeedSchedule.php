<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\FBE\Configuration;

defined( 'ABSPATH' ) || exit;

/**
 * The catalog feed configuration settings.
 *
 * @since x.x.x
 */
class CatalogFeedSchedule {

	/**
	 * True if feed-based catalog sync is enabled and configured in Facebook business.
	 *
	 * @var bool $enabled
	 */
	private $enabled;

	/**
	 * Construct from raw response data.
	 *
	 * @param array $data Response data (from `catalog_feed_scheduled` key).
	 */
	public function __construct( array $data = array() ) {

		$data = wp_parse_args(
			$data,
			array(
				'enabled' => false,
			)
		);

		// More settings to come in future (e.g. schedule, update or add etc).
		$this->enabled = $data['enabled'];
	}

	/**
	 * Determines if feed-based sync is enabled.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return $this->enabled;
	}
}
