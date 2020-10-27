<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The main product feed handler.
 *
 * This will eventually replace \WC_Facebook_Product_Feed as we refactor and move its functionality here.
 *
 * @since 1.11.0
 */
class FBCategories {
	const ACTION_PRIORITY = 9;
	const CATEGORY_FILE   = 'fb_google_product_categories_en_US_simple.json';
	const ATTRIBUTES_FILE = 'fb_google_category_to_attribute_mapping.json';
	/**
	 * FBCategory constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->categories_filepath = realpath( __DIR__ ) . '/' . self::CATEGORY_FILE;
		$this->attributes_filepath = realpath( __DIR__ ) . '/' . self::ATTRIBUTES_FILE;
		$this->categories_data     = null;
		$this->attributes_data     = null;
	}

	private function ensure_data_is_loaded() {
		if ( ! $this->categories_data ) {
			$cat_file_contents     = @file_get_contents( $this->categories_filepath );
			$this->categories_data = json_decode( $cat_file_contents, true );
		}
		if ( ! $this->attributes_data ) {
			$attr_file_contents    = @file_get_contents( $this->attributes_filepath );
			$this->attributes_data = json_decode( $attr_file_contents, true );
		}
	}

	private function get_attribute( $category_id, $attribute_key ) {
		$this->ensure_data_is_loaded();
		if ( ! $this->is_category( $category_id ) ) {
			return null;
		}
		$all_attributes = $this->attributes_data[ $category_id ]['attributes'];
		$attributes     = array_filter(
			$all_attributes,
			function( $attr ) use ( $attribute_key ) {
				return ( $attribute_key === $attr['key'] );
			}
		);
		if ( empty( $attributes ) ) {
			return null;
		}
		return array_shift( $attributes );
	}

	public function is_valid_value_for_attribute( $category_id, $attribute_key, $value ) {
		$this->ensure_data_is_loaded();
		$attribute = $this->get_attribute( $category_id, $attribute_key );

		if ( is_null( $attribute ) ) {
			return false;
		}

		// TODO: can perform more validations here
		switch ( $attribute['type'] ) {
			case 'enum':
				return in_array( $value, $attribute['enum_values'] );
			case 'boolean':
				return in_array( $value, array( 'yes', 'no' ) );
			default:
				return true;
		}
	}

	public function get_category( $id ) {
		$this->ensure_data_is_loaded();
		if ( $this->is_category( $id ) ) {
			return $this->categories_data[ $id ];
		} else {
			return null;
		}
	}

	public function get_category_depth( $id ) {
		if ( ! $this->is_category( $id ) ) {
			return 0;
		}
		$category = $this->get_category( $id );
		return count( explode( '>', $category ) );
	}

	public function get_category_with_attrs( $id ) {
		$this->ensure_data_is_loaded();
		if ( $this->is_category( $id ) ) {
			return $this->attributes_data[ $id ];
		} else {
			return null;
		}
	}

	public function is_category( $id ) {
		$this->ensure_data_is_loaded();
		return isset( $this->categories_data[ $id ] );
	}
}
