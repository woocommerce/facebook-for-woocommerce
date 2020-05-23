<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin;

defined( 'ABSPATH' ) or exit;

/**
 * Admin settings handler.
 *
 * @since 2.0.0-dev.1
 */
class Settings {


	/**
	 * Settings constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

	}


	/**
	 * Adds the Facebook menu item.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function add_menu_item() {


	}


	/**
	 * Renders the settings page.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function render() {}


	/**
	 * Saves the settings page.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function save() {}


	/**
	 * Gets the tabs.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	private function get_tabs() {

		/**
		 * Filters the admin settings tabs.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $tabs tab data, as $id => $label
		 */
		return (array) apply_filters( 'wc_facebook_admin_settings_tabs', [], $this );
	}


}
