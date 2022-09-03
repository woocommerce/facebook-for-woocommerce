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
 * @property string name Facebook Catalog Name.
 */
class Response extends ApiResponse {
	/**
	 * Gets Facebook Catalog Name.
	 *
	 * @return ?string
	 */
	public function get_name(): ?string {
		return $this->name;
	}
}
