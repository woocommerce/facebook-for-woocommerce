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

defined( 'ABSPATH' ) || exit;

/**
 * The main product feed handler.
 *
 * This will eventually replace \WC_Facebook_Product_Feed as we refactor and move its functionality here.
 *
 * @since 1.11.0
 */
class FBCategories {

	const ATTRIBUTES_FILE = 'fb_google_category_to_attribute_mapping.json';
	/**
	 * FBCategory constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->attributes_filepath = realpath( __DIR__ ) . '/' . self::ATTRIBUTES_FILE;
		$this->attributes_data     = null;
	}

	/**
	 * This function ensures that everything is loaded before the we start using the data.
	 */
	private function ensure_data_is_loaded() {
		// This makes the GoogleProductTaxonomy available.
		require_once __DIR__ . '/GoogleProductTaxonomy.php';
		if ( ! $this->attributes_data ) {
			$attr_file_contents    = @file_get_contents( $this->attributes_filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$this->attributes_data = json_decode( $attr_file_contents, true );
		}
	}

	/**
	 * Fetches the attribute from a category using attribute key.
	 *
	 * @param string $category_id   Id of the category for which attribute we want to fetch.
	 * @param string $attribute_key The key of the attribute.
	 *
	 * @return null|string Attribute.
	 */
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

	/**
	 * Checks if $value is correct for a given category attribute.
	 *
	 * @param string $category_id   Id of the category for which attribute we want to check the value.
	 * @param string $attribute_key The key of the attribute.
	 * @param string $value         Value of the attribute.
	 *
	 * @return boolean Is this a valid value for the attribute.
	 */
	public function is_valid_value_for_attribute( $category_id, $attribute_key, $value ) {
		$this->ensure_data_is_loaded();
		$attribute = $this->get_attribute( $category_id, $attribute_key );

		if ( is_null( $attribute ) ) {
			return false;
		}

		// TODO: can perform more validations here.
		switch ( $attribute['type'] ) {
			case 'enum':
				return in_array( $value, $attribute['enum_values'] );
			case 'boolean':
				return in_array( $value, array( 'yes', 'no' ) );
			default:
				return true;
		}
	}

	/**
	 * Fetches given category.
	 *
	 * @param string $category_id Id of the category we want to fetch.
	 *
	 * @return null|array Null if category was not found or the category array.
	 */
	public function get_category( $category_id ) {
		$this->ensure_data_is_loaded();
		if ( $this->is_category( $category_id ) ) {
			return GoogleProductTaxonomy::TAXONOMY[ $category_id ];
		} else {
			return null;
		}
	}

	/**
	 * Checks if category is root category - it has no parents.
	 *
	 * @param string $category_id   Id of the category for which attribute we want to check the value.
	 *
	 * @return null|boolean Null if category was not found or boolean that determines if this is a root category or not.
	 */
	public function is_root_category( $category_id ) {
		if ( ! $this->is_category( $category_id ) ) {
			return null;
		}

		$category = $this->get_category( $category_id );
		return empty( $category['parent'] );
	}

	/**
	 * Checks if category is root category - it has no parents.
	 *
	 * @param string $category_id   Id of the category for which attribute we want to check the value.
	 *
	 * @return null|boolean Null if category was not found or boolean that determines if this is a root category or not.
	 */
	public function get_category_with_attrs( $category_id ) {
		$this->ensure_data_is_loaded();
		if ( $this->is_category( $category_id ) ) {
			return $this->attributes_data[ $category_id ];
		} else {
			return null;
		}
	}

	/**
	 * Checks if given category id is valid.
	 *
	 * @param string $category_id   Id of the category which we check.
	 *
	 * @return boolean Is the id a valid category id.
	 */
	public function is_category( $category_id ) {
		$this->ensure_data_is_loaded();
		return isset( GoogleProductTaxonomy::TAXONOMY[ $category_id ] );
	}

	/**
	 * Get all categories.
	 *
	 * @return array All categories data.
	 */
	public function get_categories() {
		$this->ensure_data_is_loaded();
		return GoogleProductTaxonomy::TAXONOMY;
	}

}
