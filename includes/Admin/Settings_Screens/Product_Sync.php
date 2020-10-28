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
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
use SkyVerge\WooCommerce\PluginFramework\v5_9_0\SV_WC_Helper;

/**
 * The Messenger settings screen object.
 */
class Product_Sync extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'product_sync';

	/** @var string the sync products action */
	const ACTION_SYNC_PRODUCTS = 'wc_facebook_sync_products';

	/** @var string the get sync status action */
	const ACTION_GET_SYNC_STATUS = 'wc_facebook_get_sync_status';


	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Product sync', 'facebook-for-woocommerce' );
		$this->title = __( 'Product sync', 'facebook-for-woocommerce' );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'woocommerce_admin_field_product_sync_title', [ $this, 'render_title' ] );

		add_action( 'woocommerce_admin_field_product_sync_google_product_categories', [ $this, 'render_google_product_category_field' ] );
	}


	/**
	 * Enqueues the assets.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets() {

		$tab = SV_WC_Helper::get_requested_value( 'tab' );

		if ( Admin\Settings::PAGE_ID !== SV_WC_Helper::get_requested_value( 'page' ) || ( $tab && self::ID !== $tab ) ) {
			return;
		}

		wp_enqueue_script( 'wc-backbone-modal', null, [ 'backbone' ] );
		wp_enqueue_script( 'facebook-for-woocommerce-modal', plugins_url( '/facebook-for-woocommerce/assets/js/facebook-for-woocommerce-modal.min.js' ), [ 'jquery', 'wc-backbone-modal', 'jquery-blockui' ], \WC_Facebookcommerce::PLUGIN_VERSION );
		wp_enqueue_script( 'facebook-for-woocommerce-settings-sync', plugins_url( '/facebook-for-woocommerce/assets/js/admin/facebook-for-woocommerce-settings-sync.min.js' ), [ 'jquery', 'wc-backbone-modal', 'jquery-blockui', 'jquery-tiptip', 'facebook-for-woocommerce-modal', 'wc-enhanced-select' ], \WC_Facebookcommerce::PLUGIN_VERSION );

		/* translators: Placeholders: {count} number of remaining items */
		$sync_remaining_items_string = _n_noop( '{count} item remaining.', '{count} items remaining.', 'facebook-for-woocommerce' );

		wp_localize_script( 'facebook-for-woocommerce-settings-sync', 'facebook_for_woocommerce_settings_sync', [
			'ajax_url'                        => admin_url( 'admin-ajax.php' ),
			'set_excluded_terms_prompt_nonce' => wp_create_nonce( 'set-excluded-terms-prompt' ),
			'sync_products_nonce'             => wp_create_nonce( self::ACTION_SYNC_PRODUCTS ),
			'sync_status_nonce'               => wp_create_nonce( self::ACTION_GET_SYNC_STATUS ),
			'sync_in_progress'                => Sync::is_sync_in_progress(),
			'excluded_category_ids'           => facebook_for_woocommerce()->get_integration()->get_excluded_product_category_ids(),
			'excluded_tag_ids'                => facebook_for_woocommerce()->get_integration()->get_excluded_product_tag_ids(),
			'i18n'                            => [
				/* translators: Placeholders %s - html code for a spinner icon */
				'confirm_resync'                => esc_html__( 'Your products will now be resynced to Facebook, this may take some time.', 'facebook-for-woocommerce' ),
				'confirm_sync'                  => esc_html__( "Facebook for WooCommerce automatically syncs your products on create/update. Are you sure you want to force product resync?\n\nThis will query all published products and may take some time. You only need to do this if your products are out of sync or some of your products did not sync.", 'facebook-for-woocommerce' ),
				'sync_in_progress'              => sprintf( esc_html__( 'Your products are syncing - you may safely leave this page %s', 'facebook-for-woocommerce' ), '<span class="spinner is-active"></span>' ),
				'sync_remaining_items_singular' => sprintf( esc_html( translate_nooped_plural( $sync_remaining_items_string, 1 ) ), '<strong>', '</strong>', '<span class="spinner is-active"></span>' ),
				'sync_remaining_items_plural'   => sprintf( esc_html( translate_nooped_plural( $sync_remaining_items_string, 2 ) ), '<strong>', '</strong>', '<span class="spinner is-active"></span>' ),
				'general_error'                 => esc_html__( 'There was an error trying to sync the products to Facebook.', 'facebook-for-woocommerce' ),
				'feed_upload_error'             => esc_html__( 'Something went wrong while uploading the product information, please try again.', 'facebook-for-woocommerce' ),
			],
			'default_google_product_category_modal_message'       => $this->get_default_google_product_category_modal_message(),
			'default_google_product_category_modal_message_empty' => $this->get_default_google_product_category_modal_message_empty(),
			'default_google_product_category_modal_buttons'       => $this->get_default_google_product_category_modal_buttons(),
		] );
	}

	/**
	 * Gets the message for Default Google Product Category modal.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_message() {

		return wp_kses_post( __( 'Products and categories that inherit this global setting (i.e. they do not have a specific Google product category set) will use the new default immediately. Are you sure you want to proceed?', 'facebook-for-woocommerce' ) );
	}


	/**
	 * Gets the message for Default Google Product Category modal when the selection is empty.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_message_empty() {

		return sprintf(
			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
			esc_html__( 'Products and categories that inherit this global setting (they do not have a specific Google product category set) will use the new default immediately.  %1$sIf you have cleared the Google Product Category%2$s, items inheriting the default will not be available for Instagram checkout. Are you sure you want to proceed?', 'facebook-for-woocommerce' ),
			'<strong>', '</strong>'
		);
	}


	/**
	 * Gets the markup for the buttons used in the Default Google Product Category modal.
	 *
	 * @since 2.1.0
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
	 * Renders the custom title.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @param array $field field data
	 */
	public function render_title( $field ) {

		?>

		<h2>

			<?php esc_html_e( 'Product sync', 'facebook-for-woocommerce' ); ?>

			<?php if ( facebook_for_woocommerce()->get_connection_handler()->is_connected() ) : ?>
				<a
					id="woocommerce-facebook-settings-sync-products"
					class="button product-sync-field"
					href="#"
					style="vertical-align: middle; margin-left: 20px;"
				><?php esc_html_e( 'Sync products', 'facebook-for-woocommerce' ); ?></a>
			<?php endif; ?>

		</h2>
		<div><p id="sync_progress" style="display: none"></p></div>
		<table class="form-table">

		<?php
	}


	/**
	 * Saves the Product Sync settings.
	 *
	 * @since 2.0.0
	 */
	public function save() {

		$integration = facebook_for_woocommerce()->get_integration();

		$previous_product_cat_ids = $integration->get_excluded_product_category_ids();
		$previous_product_tag_ids = $integration->get_excluded_product_tag_ids();

		parent::save();

		// when settings are saved, if there are new excluded categories/terms we should exclude corresponding products from sync
		$new_product_cat_ids = array_diff( $integration->get_excluded_product_category_ids(), $previous_product_cat_ids );
		$new_product_tag_ids = array_diff( $integration->get_excluded_product_tag_ids(), $previous_product_tag_ids );

		$this->disable_sync_for_excluded_products( $new_product_cat_ids, $new_product_tag_ids );
	}


	/**
	 * Disables sync for products that belong to any of the given categories or tags.
	 *
	 * @since 2.0.0
	 *
	 * @param array $product_cat_ids IDs of excluded categories
	 * @param array $product_tag_ids IDs of excluded tags
	 */
	private function disable_sync_for_excluded_products( $product_cat_ids, $product_tag_ids ) {

		// disable sync for all products belonging to excluded categories
		Products::disable_sync_for_products_with_terms( [
			'taxonomy'   => 'product_cat',
			'include'    => $product_cat_ids,
		] );

		// disable sync for all products belonging to excluded tags
		Products::disable_sync_for_products_with_terms( [
			'taxonomy'   => 'product_tag',
			'include'    => $product_tag_ids,
		] );
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings() {

		$term_query = new \WP_Term_Query( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'id=>name',
		] );

		$product_categories = $term_query->get_terms();

		$term_query = new \WP_Term_Query( [
			'taxonomy'     => 'product_tag',
			'hide_empty'   => false,
			'hierarchical' => false,
			'fields'       => 'id=>name',
		] );

		$product_tags = $term_query->get_terms();

		return [

			[
				'type'  => 'product_sync_title',
				'title' => __( 'Product sync', 'facebook-for-woocommerce' ),
			],

			[
				'id'      => \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC,
				'title'   => __( 'Enable product sync', 'facebook-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => ' ',
				'default' => 'yes',
			],

			[
				'id'                => \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS,
				'title'             => __( 'Exclude categories from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select product-sync-field',
				'css'               => 'min-width: 300px;',
				'desc_tip'          => __( 'Products in one or more of these categories will not sync to Facebook.', 'facebook-for-woocommerce' ),
				'default'           => [],
				'options'           => is_array( $product_categories ) ? $product_categories : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product category&hellip;', 'facebook-for-woocommerce' ),
				],
			],

			[
				'id'                => \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS,
				'title'             => __( 'Exclude tags from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select product-sync-field',
				'css'               => 'min-width: 300px;',
				'desc_tip'          => __( 'Products with one or more of these tags will not sync to Facebook.', 'facebook-for-woocommerce' ),
				'default'           => [],
				'options'           => is_array( $product_tags ) ? $product_tags : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product tag&hellip;', 'facebook-for-woocommerce' ),
				],
			],

			[
				'id'       => \WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE,
				'title'    => __( 'Product description sync', 'facebook-for-woocommerce' ),
				'type'     => 'select',
				'class'    => 'product-sync-field',
				'desc_tip' => __( 'Choose which product description to display in the Facebook catalog.', 'facebook-for-woocommerce' ),
				'default'  => \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD,
				'options'  => [
					\WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD => __( 'Standard description', 'facebook-for-woocommerce' ),
					\WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT    => __( 'Short description', 'facebook-for-woocommerce' ),
				],
			],
			[
				'id'       => \SkyVerge\WooCommerce\Facebook\Commerce::OPTION_GOOGLE_PRODUCT_CATEGORY_ID,
				'type'     => 'product_sync_google_product_categories',
				'title'    => __( 'Default Google product category', 'facebook-for-woocommerce' ),
				'desc_tip' => __( 'Choose a default Google product category for your products. Defaults can also be set for product categories. Products need at least two category levels defined for tax to be correctly applied.', 'facebook-for-woocommerce' ),
			],
			[ 'type' => 'sectionend' ],

		];
	}

	/**
	 * Renders the Google category field markup.
	 *
	 * @internal

	 * @since 2.1.0
	 *
	 * @param array $field field data
	 */
	public function render_google_product_category_field( $field ) {

		$category_field = new Admin\Google_Product_Category_Field();

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
				<?php $category_field->render( $field['id'] ); ?>
				<input id="<?php echo esc_attr( $field['id'] ); ?>" type="hidden" name="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Gets the "disconnected" message.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_disconnected_message() {

		return sprintf(
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			__( 'Please %1$sconnect to Facebook%2$s to enable and manage product sync.', 'facebook-for-woocommerce' ),
			'<a href="' . esc_url( facebook_for_woocommerce()->get_connection_handler()->get_connect_url() ) . '">', '</a>'
		);
	}


}
