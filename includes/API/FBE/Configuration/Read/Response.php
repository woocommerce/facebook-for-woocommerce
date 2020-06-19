<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Read;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * FBE Configuration API read response object.
 *
 * @since 2.0.0-dev.1
 */
class Response extends API\Response  {


	/**
	 * Gets the messenger configuration object.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return null|API\FBE\Configuration\Messenger
	 */
	public function get_messenger_configuration() {

		$configuration = null;

		if ( ! empty( $this->response_data->messenger_chat ) && is_object( $this->response_data->messenger_chat ) ) {
			$configuration = new API\FBE\Configuration\Messenger( (array) $this->response_data->messenger_chat );
		}

		return $configuration;
	}


}
