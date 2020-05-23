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
 * The base settings screen object.
 */
abstract class Abstract_Settings_Screen {


	/** @var string screen ID */
	protected $id;

	/** @var string screen label, for display */
	protected $label;

	/** @var string screen title, for display */
	protected $title;

	/** @var string screen description, for display */
	protected $description;


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the settings.
	 *
	 * Should return a multi-dimensional array of settings in the format expected by \WC_Admin_Settings
	 * @return array
	 */
	abstract public function get_settings();


	/**
	 * Gets the message to display when the plugin is disconnected.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_disconnected_message() {

		return '';
	}


	/**
	 * Gets the screen ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets the screen label.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_label() {

		/**
		 * Filters the screen label.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $label screen label, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_label', $this->label, $this );
	}


	/**
	 * Gets the screen title.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_title() {

		/**
		 * Filters the screen title.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $title screen title, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_title', $this->title, $this );
	}


	/**
	 * Gets the screen description.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_description() {

		/**
		 * Filters the screen description.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $description screen description, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_description', $this->description, $this );
	}


}
