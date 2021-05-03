<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Pages\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Page API response object.
 *
 * @since 2.0.0
 */
class Response extends API\Response {


	/**
	 * Gets the page name.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	public function get_name() {

		return $this->name;
	}


	/**
	 * Gets the page URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	public function get_url() {

		return $this->link;
	}


}
