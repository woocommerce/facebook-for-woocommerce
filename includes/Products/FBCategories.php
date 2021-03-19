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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * The main product feed handler.
 *
 * This will eventually replace \WC_Facebook_Product_Feed as we refactor and move its functionality here.
 *
 * @since 1.11.0
 */
class FBCategories {
	/** @var string the WordPress option name where the full categories list is stored */
	const OPTION_GOOGLE_PRODUCT_CATEGORIES = 'wc_facebook_google_product_categories';

	const ACTION_PRIORITY = 9;
	const CATEGORY_FILE   = 'taxonomy-with-ids.en-US.txt';
	const ATTRIBUTES_FILE = 'fb_google_category_to_attribute_mapping.json';
	/**
	 * FBCategory constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->categories_filepath = realpath( __DIR__ ) . '/' . self::CATEGORY_FILE;
		$this->attributes_filepath = realpath( __DIR__ ) . '/' . self::ATTRIBUTES_FILE;
		$this->attributes_data     = null;
		$this->categories          = null;
	}

	private function ensure_data_is_loaded() {
		if ( empty( $this->categories ) ) {
			$this->categories = $this->prepare_categories();
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

	public function is_valid_value_for_attribute( $category_id, $attribute_key, $value ) { //OK
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
			return $this->categories[ $id ];
		} else {
			return null;
		}
	}

	public function is_root_category( $id ) {
		if ( ! $this->is_category( $id ) ) {
			return null;
		}

		$category = $this->get_category( $id );
		return empty( $category[ 'parent' ] );
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
		return isset( $this->categories[ $id ] );
	}

	public function get_categories() {
		$this->ensure_data_is_loaded();

		return $this->categories;
	}

	public function prepare_categories() {
		$categories = get_option( self::OPTION_GOOGLE_PRODUCT_CATEGORIES, [] );

		if ( empty ( $categories ) ) {
			$categories_data = $this->load_categories();
			$categories = $this->parse_categories( $categories_data );

			if ( ! empty( $categories ) ) {
				update_option( self::OPTION_GOOGLE_PRODUCT_CATEGORIES, $categories, 'no' );
			}
		}
		return $categories;
	}

	protected function load_categories() {
		$category_file_contents = @file_get_contents( $this->categories_filepath );
		$category_file_lines    = explode( "\n", $category_file_contents );
		$raw_categories         = array();
		foreach ( $category_file_lines as $category_line ) {

			if ( strpos( $category_line, ' - ' ) === false ) {
				// not a category, skip it
				continue;
			}

			list( $category_id, $category_name ) = explode( ' - ', $category_line );

			$raw_categories[ (string) trim( $category_id ) ] = trim( $category_name );
		}
		return $raw_categories;
	}

	protected function parse_categories( $raw_categories ) {
		$categories = [];
		foreach ( $raw_categories as $category_id => $category_tree ) {

			$category_tree  = explode( ' > ', $category_tree );
			$category_label = end( $category_tree );

			$category = [
				'label'   => $category_label,
				'options' => [],
			];

			if ( $category_label === $category_tree[0] ) {

				// top-level category
				$category['parent'] = '';

			} else {

				$parent_label = $category_tree[ count( $category_tree ) - 2 ];

				$parent_category = array_search( $parent_label, array_map( function ( $item ) {

					return $item['label'];
				}, $categories ) );

				$category['parent'] = (string) $parent_category;

				// add category label to the parent's list of options
				$categories[ $parent_category ]['options'][ $category_id ] = $category_label;
			}

			$categories[ (string) $category_id ] = $category;
		}

		return $categories;
	}

}
