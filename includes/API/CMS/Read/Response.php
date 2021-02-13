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
 * @since 2.3.3
 */
class Response extends API\Response  {


	/**
	 * Gets the Shop call to action.
	 *
	 * @since 2.3.3
	 *
	 * @return string|null
	 */
	public function get_cta() {

		return $this->cta;
	}


	/**
	 * Gets the Shop's onsite intent
	 *
	 * @since 2.3.3
	 *
	 * @return string|null
	 */
	public function has_onsite_intent() {

		return $this->has_onsite_intent;
	}


	/**
	 * Gets the display name.
	 *
	 * @since 2.3.3
	 *
	 * @return string|null
	 */
	public function get_display_name() {

		return $this->display_name;
	}


	/**
	 * Gets the setup status.
	 *
	 * @since 2.3.3
	 *
	 * @return \stdClass
	 */
	public function get_setup_status() {

		$data = ! empty( $this->setup_status->data ) && is_array( $this->setup_status->data )
			? $this->setup_status->data[0] : null;

		return is_object( $data ) ? $data : new \stdClass();
	}


	/**
	 * Gets the Instagram Channel data
	 *
	 * @since 2.3.3
	 *
	 * @return \stdClass
	 */
	public function get_instagram_channel() {

		$data = ! empty( $this->instagram_channel->instagram_users->data ) && is_array( $this->instagram_channel->instagram_users->data )
			? $this->instagram_channel->instagram_users->data[0] : null;

		return is_object( $data ) ? $data : new \stdClass();
	}



	/**
	 * Gets the Facebook Channel data
	 *
	 * @since 2.3.3
	 *
	 * @return \stdClass
	 */
	public function get_facebook_channel() {

		$data = ! empty( $this->facebook_channel->pages->data ) && is_array( $this->facebook_channel->pages->data )
			? $this->facebook_channel->pages->data[0] : null;

		return is_object( $data ) ? $data : new \stdClass();
	}


}
