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

		woocommerce_wp_checkbox( [
			'id'          => self::FIELD_COMMERCE_ENABLED,
			'value'       => \SkyVerge\WooCommerce\Facebook\Products::is_commerce_enabled_for_product( $product ) ? 'yes' : 'no',
			'label'       => __( 'Sell on Instagram', 'facebook-for-woocommerce' ),
			'class'       => 'enable-if-sync-enabled',
			'desc_tip'    => true,
			'description' => __( 'Enable to sell this product on Instagram. Products that are hidden in the Facebook catalog can be synced, but won’t be available for purchase.', 'facebook-for-woocommerce' ),
		] );

		?>

		<div id="product-not-ready-notice" style="display:none;">
			<p>
				<?php esc_html_e( 'This product does not meet the requirements to sell on Instagram.', 'facebook-for-woocommerce' ); ?>
				<a href="#" id="product-not-ready-notice-open-modal"><?php esc_html_e( 'Click here to learn more.', 'facebook-for-woocommerce' ); ?></a>
			</p>
		</div>

		<div id="variable-product-not-ready-notice" style="display:none;">
			<p><?php sprintf(
				/* translators: Placeholders %1$s - strong opening tag, %2$s - strong closing tag */
				__( 'To sell this product on Instagram, at least one variation must be synced to Facebook. You can control variation sync on the %1$sVariations%2$s tab with the %1$sFacebook Sync%2$s setting.', 'facebook-for-woocommerce' ),
				'<strong>',
				'</strong>'
			);?></p>
		</div>

		<div class='wc_facebook_commerce_fields'>
			<?php self::render_google_product_category_fields( $product ); ?>
			<?php self::render_attribute_fields( $product ); ?>
		</div>

		<?php
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
