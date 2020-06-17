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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The messenger configuration object.
 *
 * @since 2.0.0-dev.1
 */
class Messenger extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Determines if messenger is enabled.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return (bool) $this->enabled;
	}


	/**
	 * Gets the default locale.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_default_locale() {

		return (string) $this->default_locale;
	}


	/**
	 * Gets the domains that messenger is configured for.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string[]
	 */
	public function get_domains() {

		if ( is_array( $this->domains ) ) {
			$domains = array_map( 'trailingslashit', $this->domains );
		} else {
			$domains = [];
		}

		return $domains;
	}


}
