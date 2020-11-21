<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\CMS\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Commerce Merchant Settings API response object.
 *
 * @since 2.3.0
 */
class Response extends API\Response  {


	/**
	 * Gets the Shop call to action.
	 *
	 * @since 2.3.0
	 *
	 * @return string|null
	 */
	public function get_cta() {

		return $this->cta;
	}





	/**
	 * Gets the etup status
	 *
	 * @since 2.3.0
	 *
	 * @return \stdClass
	 */
	public function get_setup_status() {

		$data = ! empty( $this->setup_status->data ) && is_array( $this->setup_status->data ) ? $this->setup_status->data : [];

		return is_object( $data[0] ) ? $data[0] : new \stdClass();
	}


}
