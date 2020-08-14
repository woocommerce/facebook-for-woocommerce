<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Business_Manager;

defined( 'ABSPATH' ) or exit;

/**
 * Business Manager API response object
 *
 * @since 2.0.0
 */
class Response extends \SkyVerge\WooCommerce\Facebook\API\Response {


	/**
	 * Gets the business manager name.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	public function get_name() {

		return $this->name;
	}


	/**
	 * Gets the business manager name.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	public function get_url() {

		return $this->link;
	}


}
