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
	const FIELD_ENHANCED_CATALOG_ATTRIBUTES_ID = 'wc_facebook_enhanced_catalog_attributes_id';


	/**
	 * Handler constructor.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'product_cat_add_form_fields', [ $this, 'render_add_google_product_category_field' ] );
		add_action( 'product_cat_edit_form_fields', [ $this, 'render_edit_google_product_category_field' ] );
		// add_action( 'product_cat_add_form_fields', [ $this, 'render_add_google_product_category_field' ] );
		add_action( 'product_cat_edit_form_fields', [ $this, 'render_edit_enhanced_catalog_attributes_field' ] );

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

		if ( $this->is_categories_screen() ) {

			wp_enqueue_style( 'wc-facebook-product-categories', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/products-categories.css', [], \WC_Facebookcommerce::PLUGIN_VERSION );

			wp_enqueue_script( 'wc-facebook-product-categories', facebook_for_woocommerce()->get_plugin_url() . '/assets/js/admin/product-categories.min.js', [
				'jquery',
				'wc-backbone-modal',
				'jquery-blockui',
				'jquery-tiptip',
				'facebook-for-woocommerce-modal',
			], \WC_Facebookcommerce::PLUGIN_VERSION );

			wp_localize_script( 'wc-facebook-product-categories', 'facebook_for_woocommerce_product_categories', [
				'default_google_product_category_modal_message' => $this->get_default_google_product_category_modal_message(),
				'default_google_product_category_modal_buttons' => $this->get_default_google_product_category_modal_buttons(),
			] );
		}
	}


	/**
	 * Gets the message for Default Google Product Category modal.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_message() {

		return wp_kses_post( __( 'Products and categories that inherit this global setting (i.e. they do not have a specific Google product category set) will use the new default immediately. Are you sure you want to proceed?', 'facebook-for-woocommerce' ) );
	}


	/**
	 * Gets the markup for the buttons used in the Default Google Product Category modal.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_buttons() {

		ob_start();

		?>
		<button
				class="button button-large"
				onclick="jQuery( '.modal-close' ).trigger( 'click' )"
		><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
		<button
				id="btn-ok"
				class="button button-large button-primary"
		><?php esc_html_e( 'Update default Google product category', 'facebook-for-woocommerce' ); ?></button>
		<?php

		return ob_get_clean();
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
	 *
	 * @param \WP_Term $term current taxonomy term object
	 */
	public function render_edit_google_product_category_field( \WP_Term $term ) {

		$category_field = new Google_Product_Category_Field();
		$value          = get_term_meta( $term->term_id, \SkyVerge\WooCommerce\Facebook\Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, true );

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
					       name="<?php echo esc_attr( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ); ?>"
					       value="<?php echo esc_attr( $value ); ?>"/>
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

		$tooltip_text = __( 'Choose a default Google product category for products in this category. Products need at least two category levels defined for tax to be correctly applied.', 'facebook-for-woocommerce' );

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
	 * Renders the Google product category field markup for the edit form.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WP_Term $term current taxonomy term object
	 */
	public function render_edit_enhanced_catalog_attributes_field( \WP_Term $term ) {

		$category_value 					 = get_term_meta( $term->term_id, \SkyVerge\WooCommerce\Facebook\Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, true );
		$enhanced_attribute_fields = new Enhanced_Catalog_Attribute_Fields();
		$category_handler 				 = facebook_for_woocommerce()->get_facebook_category_handler();

		if($category_handler->get_category_depth($category_value) < 2) {
			// show nothing
			return;
		}

		?>
			<tr class="form-field term-<?php echo esc_attr( self::FIELD_ENHANCED_CATALOG_ATTRIBUTES_ID ); ?>-title-wrap">
				<th colspan="2" scope="row">
					<label for="<?php echo esc_attr( self::FIELD_ENHANCED_CATALOG_ATTRIBUTES_ID ); ?>">
						<?php echo esc_html( $this->render_enhanced_catalog_attributes_title() ); ?>
						<?php $this->render_enhanced_catalog_attributes_tooltip(); ?>
					</label>
				</th>
			</tr>
		<?php

		echo $enhanced_attribute_fields->render($category_value);
	}

	/**
	 * Renders the common tooltip markup.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render_enhanced_catalog_attributes_tooltip() {

		$tooltip_text = __( 'Select default values for enhanced attributes within this category', 'facebook-for-woocommerce' );

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
	public function render_enhanced_catalog_attributes_title() {

		return __( 'Enhanced Catalog Attributes', 'facebook-for-woocommerce' );
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
	 */
	public function save_google_product_category( $term_id, $tt_id, $taxonomy ) {

		$google_product_category_id = wc_clean( Framework\SV_WC_Helper::get_posted_value( self::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ) );

		\SkyVerge\WooCommerce\Facebook\Product_Categories::update_google_product_category_id( $term_id, $google_product_category_id );

		$term = get_term( $term_id, $taxonomy );

		if ( $term instanceof \WP_Term ) {

			// get the products in the category being saved
			$products = wc_get_products( [
				'category' => [ $term->slug ],
			] );

			if ( ! empty( $products ) ) {

				$sync_product_ids = [];

				/**
				 * @var int $product_id
				 * @var \WC_Product $product
				 */
				foreach ( $products as $product_id => $product ) {

					if ( $product instanceof \WC_Product_Variable ) {

						// should sync the variations, not the variable product
						$sync_product_ids = array_merge( $sync_product_ids, $product->get_children() );

					} else {

						$sync_product_ids[] = $product_id;
					}
				}

				facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( $sync_product_ids );
			}
		}
	}


	/**
	 * Determines whether or not the current screen is a categories screen.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return bool
	 */
	public function is_categories_screen() {

		return Framework\SV_WC_Helper::is_current_screen( 'edit-product_cat' );
	}


}
