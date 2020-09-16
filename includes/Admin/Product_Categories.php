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

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * General handler for the product category admin functionality.
 *
 * @since 2.1.0-dev.1
 */
class Product_Categories {


	/** @var string ID for the HTML field */
	const FIELD_GOOGLE_PRODUCT_CATEGORY_ID = 'wc_facebook_google_product_category_id';


	/**
	 * Handler constructor.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'product_cat_add_form_fields', [ $this, 'render_add_google_product_category_field' ] );
		add_action( 'product_cat_edit_form_fields', [ $this, 'render_edit_google_product_category_field' ] );

		add_action( 'created_term', [ $this, 'save_google_product_category' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'save_google_product_category' ], 10, 3 );
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
	 * Renders the Google product category field markup for the add form.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render_add_google_product_category_field() {

		$category_field = new Google_Product_Category_Field();

		?>
			<div class="form-field term-<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>-wrap">
				<label for="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>">
					<?php echo esc_html( $this->get_google_product_category_field_title() ); ?>
					<?php $this->render_google_product_category_tooltip(); ?>
				</label>
				<input type="hidden" id="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"
				       name="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"/>
				<?php $category_field->render( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>
			</div>
		<?php
	}


	/**
	 * Renders the Google product category field markup for the edit form.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render_edit_google_product_category_field() {

		$category_field = new Google_Product_Category_Field();

		?>
			<tr class="form-field term-<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>-wrap">
				<th scope="row">
					<label for="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>">
						<?php echo esc_html( $this->get_google_product_category_field_title() ); ?>
						<?php $this->render_google_product_category_tooltip(); ?>
					</label>
				</th>
				<td>
					<input type="hidden" id="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"
					       name="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"/>
					<?php $category_field->render( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>
				</td>
			</tr>
		<?php
	}


	/**
	 * Renders the common tooltip markup.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render_google_product_category_tooltip() {

		$tooltip_text = __( 'Choose a default Google product category for products in this category. Products need at least two category levels defined to be sold on Instagram.', 'facebook-for-woocommerce' );

		?>
			<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $tooltip_text ); ?>"></span>
		<?php
	}


	/**
	 * Gets the common field title.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public function get_google_product_category_field_title() {

		return __( 'Default Google product category', 'facebook-for-woocommerce' );
	}


	/**
	 * Saves the POSTed Google product category ID and triggers a sync for the affected products.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param int $term_id term ID
	 * @param int $tt_id term taxonomy ID
	 * @param string $taxonomy Taxonomy slug
	 * @return string
	 */
	public function save_google_product_category( $term_id, $tt_id, $taxonomy ) {

		$google_product_category_id = Framework\SV_WC_Helper::get_posted_value( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID );

		\SkyVerge\WooCommerce\Facebook\Product_Categories::update_google_product_category_id( $term_id, $google_product_category_id );

		$term = get_term( $term_id, $taxonomy, ARRAY_A );

		// get the products in the category being saved
		$product_ids = wc_get_products(
			[
				'return'   => 'ids',
				'category' => [ $term['slug'] ],
			]
		);
	}


}
