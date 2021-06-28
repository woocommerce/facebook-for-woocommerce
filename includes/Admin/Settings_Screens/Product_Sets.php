<?php
// phpcs:ignoreFile
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
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Helper;

/**
 * The Product sets redirect object.
 */
class Product_Sets extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'product_sets';

	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Product sets', 'facebook-for-woocommerce' );
		$this->title = __( 'Product sets', 'facebook-for-woocommerce' );
	}

	public function render() {
		wp_safe_redirect( admin_url( 'edit-tags.php?taxonomy=fb_product_set&post_type=product' ) );
		exit;
	}

	public function get_settings() {}
}
