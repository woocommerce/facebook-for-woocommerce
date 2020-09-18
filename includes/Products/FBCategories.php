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
  const CATEGORY_FILE = 'fb_google_product_categories_en_US_simple.json';
  const ATTRIBUTES_FILE = 'fb_google_category_to_attribute_mapping.json';
  /**
	 * FBCategory constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
    $this->categories_filepath = realpath(__DIR__).'/'.self::CATEGORY_FILE;
    $this->attributes_filepath = realpath(__DIR__).'/'.self::ATTRIBUTES_FILE;
    $this->categories_data = null;
    $this->attributes_data = null;

		// add the necessary action and filter hooks
		$this->add_hooks();
	}

	/**
	 * Adds the necessary action and filter hooks.
	 *
	 * @since 1.11.0
	 */
	private function add_hooks() {
    add_action(
      'wp_ajax_wc_facebook_json_search_facebook_categories',
      [$this, 'handle_search_facebook_categories'],
      self::ACTION_PRIORITY,
    );

		add_action(
      'wp_ajax_wc_facebook_category_attributes',
      [$this, 'handle_category_attribute_selection'],
      self::ACTION_PRIORITY,
    );
	}

	private function ensure_data_is_loaded(){
    if(!$this->categories_data) {
      $cat_file_contents = @file_get_contents($this->categories_filepath);
      $this->categories_data = json_decode($cat_file_contents, true);
    }
    if(!$this->attributes_data) {
      $attr_file_contents = @file_get_contents($this->attributes_filepath);
      $this->attributes_data = json_decode($attr_file_contents, true);
    }
	}

	public function handle_category_attribute_selection(){
		$category_id = wc_clean(wp_unslash($_GET['category']));
		$product_item_id = wc_clean(wp_unslash($_GET['item_id']));
		$product = new \WC_Facebook_Product($product_item_id);

		wp_send_json($this->get_attributes_for_category($category_id, $product));
	}

	public function get_attributes_for_category($category_id, $product){
		$this->ensure_data_is_loaded();
		if(!$this->is_category($category_id)) {
			return [];
		}
		$all_attributes = $this->attributes_data[$category_id]['attributes'];

		$primary_attributes = $this->extract_attributes_from_keys(
			array_filter($all_attribtues, function($attr) { return $attr['recommended']; }),
			$product,
		);
		$secondary_attributes = $this->extract_attributes_from_keys(
			array_filter($all_attribtues, function($attr) { return !$attr['recommended']; }),
			$product,
		);

		return ['primary' => $primary_attributes, 'secondary' => $secondary_attributes];
	}

	private function extract_attributes_from_keys($keys, $product){
	 	$attributes = array_map(function($key) use ($attributes_data){
			return $attributes_data[$key];
		}, $keys);

		// $logger = new \WC_logger();
		// $logger->add('max-test', 'HELLO MAX, ABOUT TO FILTER');

		$attributes = array_map(function($attribute) use ($product) {
			// Clean out the data we don't need as it makes the payload
			// unecessarily large
			$attribute = array_filter($attribute, function($key) {
				return $key !== 'secondary_categories' && $key != 'primary_categories';
			}, ARRAY_FILTER_USE_KEY);

			$attribute['value'] = $product->get_fb_enhanced_attribute($attribute['key']);
			return $attribute;
		}, $attributes);

		return $attributes;
	}

	private function get_attribute($category_id, $attribute_key) {
		$this->ensure_data_is_loaded();
		if(!$this->is_category($category_id)) {
			return null;
		}
		$all_attributes =	$this->attributes_data[$category_id]['attributes'];
		$attributes = array_filter(
			$all_attributes,
			function($attr) use ($attribute_key) {
				return ($attribute_key === $attr['key']);
			}
		);
		if(empty($attributes)){
			return null;
		}
		return $attributes[0];
	}

	public function is_valid_value_for_attribute($category_id, $attribute_key, $value) {
		$this->ensure_data_is_loaded();
		$attribute = $this->get_attribute($category_id, $attribute_key);
		if(is_null($attribute)) {
			return false;
		}

		// TODO: can perform more validations here
		switch($attribute['type']) {
			case 'enum':
				return in_array($value, $attribute['enum_values']);
			case 'boolean':
				return in_array($value, ['yes', 'no']);
			default:
				return true;
		}
	}

	public function handle_search_facebook_categories(){
		$this->ensure_data_is_loaded();

		$lowercase_term = strtolower(wc_clean(wp_unslash($_GET['term'])));

		$filtered_data = array_filter($this->categories_data, function($val) use ($lowercase_term){
			return (strpos(strtolower($val), $lowercase_term) !== false);
		});

		wp_send_json($filtered_data);
	}

	public function get_category($id) {
		$this->ensure_data_is_loaded();
		if($this->is_category($id)){
			return $this->categories_data[$id];
		} else {
			return null;
		}
	}

	public function is_category($id) {
		$this->ensure_data_is_loaded();
		return isset($this->categories_data[$id]);
	}
}
