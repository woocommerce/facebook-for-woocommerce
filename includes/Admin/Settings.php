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
	 * Gets a settings screen object based on ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $screen_id desired screen ID
	 * @return Abstract_Settings_Screen|null
	 */
	public function get_screen( $screen_id ) {

		$screens = $this->get_screens();

		return ! empty( $screens[ $screen_id ] ) && $screens[ $screen_id ] instanceof Abstract_Settings_Screen ? $screens[ $screen_id ] : null;
	}


	/**
	 * Gets the available screens.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return Abstract_Settings_Screen[]
	 */
	public function get_screens() {

		$screens = [
		];

		/**
		 * Filters the admin settings screens.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $screens available screen objects
		 */
		$screens = (array) apply_filters( 'wc_facebook_admin_settings_screens', $screens, $this );

		// ensure no bogus values are added via filter
		$screens = array_filter( $screens, function( $value ) {

			return $value instanceof Abstract_Settings_Screen;

		} );

		return $screens;
	}


	/**
	 * Gets the tabs.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_tabs() {

		$tabs = [];

		foreach ( $this->get_screens() as $screen_id => $screen ) {
			$tabs[ $screen_id ] = $screen->get_label();
		}

		/**
		 * Filters the admin settings tabs.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $tabs tab data, as $id => $label
		 */
		return (array) apply_filters( 'wc_facebook_admin_settings_tabs', $tabs, $this );
	}


}
