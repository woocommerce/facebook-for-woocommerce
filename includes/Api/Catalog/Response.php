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

namespace WooCommerce\Facebook\Api\Catalog;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api\Response as ApiResponse;

/**
 * Catalog API response object
 *
 * @since 2.0.0
 */
class Response extends ApiResponse {
	/**
	 * Gets the catalog name.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	public function get_name() {
		return $this->name;
	}
}
