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
 * General handler for product admin functionality.
 *
 * @since 2.1.0-dev.1
 */
class Products {


	/** @var string Commerce enabled field */
	const FIELD_COMMERCE_ENABLED = 'wc_facebook_commerce_enabled';

	/** @var string Google Product category ID field */
	const FIELD_GOOGLE_PRODUCT_CATEGORY_ID = 'wc_facebook_google_product_category_id';

	/** @var string gender field */
	const FIELD_GENDER = 'wc_facebook_gender';

	/** @var string color field */
	const FIELD_COLOR = 'wc_facebook_color';

	/** @var string size field */
	const FIELD_SIZE = 'wc_facebook_size';

	/** @var string pattern field */
	const FIELD_PATTERN = 'wc_facebook_pattern';


	/**
	 * Renders the Google product category fields.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	public static function render_google_product_category_fields( \WC_Product $product ) {

	}


	/**
	 * Renders the attribute fields.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	public static function render_attribute_fields( \WC_Product $product ) {

	}


	/**
	 * Renders the Commerce settings fields.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	public static function render_commerce_fields( \WC_Product $product ) {

	}


	/**
	 * Saves the Commerce settings.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	public static function save_commerce_fields( \WC_Product $product ) {

	}


}
