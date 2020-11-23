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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * General handler for the product set admin functionality.
 *
 * @since 2.1.5
 */
class Product_Sets {

	/**
	 * Allowed HTML for wp_kses
	 *
	 * @since 2.1.5
	 *
	 * @var array
	 */
	protected $allowed_html = array(
		'label' => array(
			'for' => array(),
		),
		'input' => array(
			'type' => array(),
			'name' => array(),
			'id'   => array(),
		),
		'p'     => array(
			'class' => array(),
		),
	);

	/**
	 * Categories field name
	 *
	 * @since 2.1.5
	 *
	 * @var string
	 */
	protected $categories_field = '';


	/**
	 * Handler constructor.
	 *
	 * @since 2.1.5
	 */
	public function __construct() {

		$this->categories_field = \WC_Facebookcommerce::PRODUCT_SET_META;

		// add taxonomy custom field
		add_action( 'fb_product_set_add_form_fields', array( $this, 'category_field_on_new' ) );
		add_action( 'fb_product_set_edit_form', array( $this, 'category_field_on_edit' ) );

		// save custom field data
		add_action( 'created_fb_product_set', array( $this, 'save_custom_field' ), 10, 2 );
		add_action( 'edited_fb_product_set', array( $this, 'save_custom_field' ), 10, 2 );
	}


	/**
	 * Add field to FB Product Set new term
	 *
	 * @since 2.1.5
	 */
	public function category_field_on_new() {
		?>
		<div class="form-field">
			<?php echo wp_kses( $this->get_field_label(), $this->allowed_html ); ?>
			<?php echo wp_kses( $this->get_field(), $this->allowed_html ); ?>
		</div>
		<?php
	}


	/**
	 * Add field to FB Product Set new term
	 *
	 * @since 2.1.5
	 *
	 * @param WP_Term $term Term object.
	 */
	public function category_field_on_edit( $term ) {

		// gets term id
		$term_id = empty( $term->term_id ) ? '' : $term->term_id;

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="form-field product-categories-wrap">
					<th scope="row"><?php echo wp_kses( $this->get_field_label(), $this->allowed_html ); ?></th>
					<td><?php echo wp_kses( $this->get_field( $term_id ), $this->allowed_html ); ?></td>
				</tr>
			</tbody>
		</table>

		<?php
	}


	/**
	 * Saves custom field data
	 *
	 * @since 2.1.5
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id Term taxonomy ID.
	 */
	public function save_custom_field( $term_id, $tt_id ) {

		$wc_product_cats = empty( $_POST[ $this->categories_field ] ) ? '' : $_POST[ $this->categories_field ]; //phpcs:ignore
		if ( ! empty( $wc_product_cats ) ) {

			$wc_product_cats = array_map(
				function( $item ) {
					return absint( $item );
				},
				$wc_product_cats
			);
		}

		update_term_meta( $term_id, $this->categories_field, $wc_product_cats );
	}


	/**
	 * Return field label HTML
	 *
	 * @since 2.1.5
	 */
	protected function get_field_label() {
		?>
		<label for="<?php echo esc_attr( $this->categories_field ); ?>"><?php echo esc_html__( 'WC Product Categories', 'facebook-for-woocommerce' ); ?></label>
		<?php
	}


	/**
	 * Return field HTML
	 *
	 * @since 2.1.5
	 *
	 * @param int $term_id The Term ID that is editing.
	 */
	protected function get_field( $term_id = '' ) {

		$saved_items  = get_term_meta( $term_id, $this->categories_field, true );
		$product_cats = get_terms( 'product_cat', array( 'hide_empty' => 0 ) );

		?>
		<div class="select2 updating-message"><p></p></div>
		<select
		id="<?php echo esc_attr( $this->categories_field ); ?>"
		name="<?php echo esc_attr( $this->categories_field ); ?>[]"
		multiple="multiple"
		disabled="disabled"
		class="select2 wc-facebook product_cats"
		style="display:none;max-width: 25em;width: 540px"
		>
		<?php foreach ( $product_cats as $product_cat ) : ?>
			<?php $selected = ( is_array( $saved_items ) && in_array( $product_cat->term_id, $saved_items, true ) ) ? ' selected="selected"' : ''; ?>
			<option value="<?php echo esc_attr( $product_cat->term_id ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $product_cat->name ); ?></option>
		<?php endforeach; ?>
		<select>
		<p class="description"><?php echo esc_html__( 'Map FB Product Set to WC Product Categories', 'facebook-for-woocommerce' ); ?>.</p>
		<?php
	}
}
