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
 * Enhanced Catalog attribute fields.
 */
class Enhanced_Catalog_Attribute_Fields {
	const FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX = 'wc_facebook_enhanced_catalog_attribute_';
	const OPTIONAL_SELECTOR_KEY                   = '__optional_selector';

	public function render( $category_id, $term ) {
		$category_handler           = facebook_for_woocommerce()->get_facebook_category_handler();
		$category                   = $category_handler->get_category_with_attrs( $category_id );
		$all_attributes             = $category['attributes'];
		$all_attributes_with_values = array_map(
			function( $attribute ) use ( $term ) {
				return array_merge( $attribute, array( 'value' => $this->get_value( $attribute['key'], $term ) ) );
			},
			$all_attributes
		);
		$recommended_attributes     = array_filter(
			$all_attributes_with_values,
			function( $attr ) {
				return $attr['recommended'];
			}
		);
		$optional_attributes        = array_filter(
			$all_attributes_with_values,
			function( $attr ) {
				return ! $attr['recommended'];
			}
		);

		foreach ( $recommended_attributes as $attribute ) {
			$this->render_attribute( $attribute );
		}
		$selector_id = self::FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX . self::OPTIONAL_SELECTOR_KEY;

		$selector_label      = __( 'Show advanced options', 'facebook-for-woocommerce' );
		$selector_value      = $this->get_value( self::OPTIONAL_SELECTOR_KEY, $term );
		$is_showing_optional = $selector_value === 'on';
		$checked_attr        = $is_showing_optional ? 'checked="checked"' : '';
		?>
		<tr class="form-field wc-facebook-enhanced-catalog-attribute-row term-<?php echo esc_attr( $selector_id ); ?>-wrap">
			<th colspan="2" scope="row">
				<label for="<?php echo esc_attr( $selector_id ); ?>">
					<?php echo esc_html( $selector_label ); ?>
					<input type="checkbox" name="<?php echo esc_attr( $selector_id ); ?>" id="<?php echo esc_attr( $selector_id ); ?>" <?php echo esc_attr( $checked_attr ); ?>/>
				</label>
			</th>
		</tr>
		<?php

		foreach ( $optional_attributes as $attribute ) {
			$this->render_attribute( $attribute, true, $is_showing_optional );
		}
	}

	private function get_value( $attribute_key, $term ) {
		$meta_key = \SkyVerge\WooCommerce\Facebook\Products::ENHANCED_CATALOG_ATTRIBUTES_META_KEY_PREFIX . $attribute_key;
		// TODO check that value is valid for attribute
		return get_term_meta( $term->term_id, $meta_key, true );
	}

	private function render_attribute( $attribute, $optional = false, $is_showing_optional = false ) {
		$attr_id = self::FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX . $attribute['key'];
		$classes = array(
			'form-field',
			'wc-facebook-enhanced-catalog-attribute-row',
			'term-' . esc_attr( $attr_id ) . '-wrap',
		);
		if ( $optional ) {
			$classes[] = 'wc-facebook-enhanced-catalog-attribute-optional-row';
		}
		?>
		<tr
			style="display: <?php echo $optional && ! $is_showing_optional ? 'none' : 'table-row'; ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<th scope="row">
				<?php $this->render_label( $attr_id, $attribute ); ?>
			</th>
			<td>
				<?php $this->render_field( $attr_id, $attribute ); ?>
			</td>
		</tr>
		<?php
	}

	private function render_label( $attr_id, $attribute ) {
		$label = ucwords( str_replace( '_', ' ', $attribute['key'] ) );
		?>
		<label for="<?php echo $attr_id; ?>">
			<?php echo esc_html( $label ); ?>
			<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $attribute['description'] ); ?>"></span>
		</label>
		<?php
	}

	private function render_field( $attr_id, $attribute ) {
		$placeholder              = isset( $attribute['example'] ) ? $attribute['example'] : '';
		$can_have_multiple_values = isset( $attribute['can_have_multiple_values'] ) && $attribute['can_have_multiple_values'];
		switch ( $attribute['type'] ) {
			case 'enum':
				if ( $can_have_multiple_values ) {
					$this->render_text_field( $attr_id, $attribute, $placeholder );
				} else {
					$this->render_select_field( $attr_id, $attribute );
				}
				break;

			default:
				// string
				$this->render_text_field( $attr_id, $attribute, $placeholder );
		}

	}

	private function render_select_field( $attr_id, $attribute ) {
		?>
		<select name="<?php echo esc_attr( $attr_id ); ?>" id="<?php echo esc_attr( $attr_id ); ?>">
			<option value="">--</option>
			<?php
			foreach ( $attribute['enum_values'] as $opt ) {
				$is_selected   = $attribute['value'] === $opt;
				$selected_attr = $is_selected ? 'selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $opt ); ?>" <?php echo esc_attr( $selected_attr ); ?>> <?php echo esc_html( $opt ); ?></option>
			<?php } ?>
		</select/>
		<?php
	}

	private function render_text_field( $attr_id, $attribute, $placeholder ) {
		if ( is_array( $placeholder ) ) {
			$placeholder = implode( ', ', $placeholder );
		}
		?>
		<input type="text" value="<?php echo esc_attr( $attribute['value'] ); ?>" name="<?php echo esc_attr( $attr_id ); ?>" id="<?php echo esc_attr( $attr_id ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"/>
		<?php
	}
}
