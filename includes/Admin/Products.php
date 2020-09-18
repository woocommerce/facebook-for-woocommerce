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

use SkyVerge\WooCommerce\Facebook\Products as Products_Handler;

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

		$field = new Google_Product_Category_Field();

		$field->render( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID );

		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>">
				<?php esc_html_e( 'Google product category', 'facebook-for-woocommerce' ); ?>
				<?php echo wc_help_tip( __( 'Choose the Google product category and (optionally) sub-categories associated with this product.', 'facebook-for-woocommerce' ) ); ?>
			</label>
			<input
				id="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"
				type="hidden"
				name="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"
				value="<?php echo esc_attr( Products_Handler::get_google_product_category_id( $product ) ); ?>"
			/>
		</p>
		<?php
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

		?>
		<p class="form-field <?php echo esc_attr( self::FIELD_COMMERCE_ENABLED ); ?>_field">
			<label for="<?php echo esc_attr( self::FIELD_COMMERCE_ENABLED ); ?>">
				<?php echo esc_html_e( 'Sell on Instagram', 'facebook-for-woocommerce' ); ?>
				<span class="woocommerce-help-tip"
				      data-tip="<?php echo esc_attr_e( 'Enable to sell this product on Instagram. Products that are hidden in the Facebook catalog can be synced, but won’t be available for purchase.', 'facebook-for-woocommerce' ); ?>"></span>
			</label>
			<input type="checkbox" class="enable-if-sync-enabled"
			       name="<?php echo esc_attr( self::FIELD_COMMERCE_ENABLED ); ?>"
			       id="<?php echo esc_attr( self::FIELD_COMMERCE_ENABLED ); ?>" value="yes"
			       checked="<?php echo Products_Handler::is_commerce_enabled_for_product( $product ) ? 'checked' : ''; ?>">
		</p>

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
