<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * The Commerce settings screen object.
 */
class Commerce extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'commerce';


	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Instagram Checkout', 'facebook-for-woocommerce' );
		$this->title = __( 'Instagram Checkout', 'facebook-for-woocommerce' );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'woocommerce_admin_field_commerce_google_product_categories', [ $this, 'render_google_product_category_field' ] );
	}


	/**
	 * Enqueues the assets.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function enqueue_assets() {

	}


	/**
	 * Renders the screen.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render() {

		parent::render();
	}


	/**
	 * Renders the Google category field markup.
	 *
	 * @internal

	 * @since 2.1.0-dev.1
	 */
	public function render_google_product_category_field() {

	}


	/**
	 * Builds the connect URL.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public function get_connect_url() {

		return '';
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return array
	 */
	public function get_settings() {

		return [
			[
				'id'   => \SkyVerge\WooCommerce\Facebook\Commerce::OPTION_GOOGLE_PRODUCT_CATEGORY_ID,
				'type' => 'commerce_google_product_categories',
			],
		];
	}


}
