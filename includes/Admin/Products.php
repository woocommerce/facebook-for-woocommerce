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

use SkyVerge\WooCommerce\Facebook\Products as FacebookProducts;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

defined( 'ABSPATH' ) or exit;

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

		woocommerce_wp_select( [
			'id'          => self::FIELD_GENDER,
			'label'       => __( 'Gender', 'facebook-for-woocommerce' ),
			'description' => __( "Select the product's gender for sizing.", 'facebook-for-woocommerce' ),
			'desc_tip'    => true,
			'options'     => [
				'unisex' => __( 'Unisex', 'facebook-for-woocommerce' ),
				'female' => __( 'Female', 'facebook-for-woocommerce' ),
				'male'   => __( 'Male', 'facebook-for-woocommerce' ),
			],
			'value'       => FacebookProducts::get_product_gender( $product ),
		] );

		woocommerce_wp_select( [
			'id'                => self::FIELD_COLOR,
			'label'             => __( 'Color attribute', 'facebook-for-woocommerce' ),
			'description'       => __( "Optionally select the attribute associated with the product's colors.", 'facebook-for-woocommerce' ),
			'desc_tip'          => true,
			'class'             => 'sv-wc-enhanced-search select short',
			'style'             => 'width: 50%',
			'options'           => self::filter_available_product_attribute_names( $product, [ 'color', 'colour', __( 'color', 'facebook-for-woocommerce' ) ] ),
			'value'             => FacebookProducts::get_product_color_attribute( $product ),
			'custom_attributes' => [
				'data-allow_clear' => true,
				'data-placeholder' => __( 'Search attributes...', 'facebook-for-woocommerce' ),
				'data-action'      => '', // TODO: define the value for the data-action and data-nonce attributes {WV 2020-09-18}
				'data-nonce'       => wp_create_nonce( '' ),
			],
		] );

		woocommerce_wp_select( [
			'id'                => self::FIELD_SIZE,
			'label'             => __( 'Size attribute', 'facebook-for-woocommerce' ),
			'description'       => __( "Optionally select the attribute associated with the product's sizes.", 'facebook-for-woocommerce' ),
			'desc_tip'          => true,
			'class'             => 'sv-wc-enhanced-search select short',
			'style'             => 'width: 50%',
			'options'           => self::filter_available_product_attribute_names( $product, [ 'size', __( 'size', 'facebook-for-woocommerce' ) ] ),
			'value'             => FacebookProducts::get_product_size_attribute( $product ),
			'custom_attributes' => [
				'data-allow_clear' => true,
				'data-placeholder' => __( 'Search attributes...', 'facebook-for-woocommerce' ),
				'data-action'      => '', // TODO: define the value for the data-action and data-nonce attributes {WV 2020-09-18}
				'data-nonce'       => wp_create_nonce( '' ),
			],
		] );

		woocommerce_wp_select( [
			'id'                => self::FIELD_PATTERN,
			'label'             => __( 'Pattern attribute', 'facebook-for-woocommerce' ),
			'description'       => __( "Optionally select the attribute associated with the product's patterns.", 'facebook-for-woocommerce' ),
			'desc_tip'          => true,
			'class'             => 'sv-wc-enhanced-search select short',
			'style'             => 'width: 50%',
			'options'           => self::filter_available_product_attribute_names( $product, [ 'pattern', __( 'pattern', 'facebook-for-woocommerce' ) ] ),
			'value'             => FacebookProducts::get_product_pattern_attribute( $product ),
			'custom_attributes' => [
				'data-allow_clear' => true,
				'data-placeholder' => __( 'Search attributes...', 'facebook-for-woocommerce' ),
				'data-action'      => '', // TODO: define the value for the data-action and data-nonce attributes {WV 2020-09-18}
				'data-nonce'       => wp_create_nonce( '' ),
			],
		] );

		Framework\SV_WC_Helper::render_select2_ajax();
	}


	/**
	 * Gets a list of attribute names and labels that match any of the given words.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product the product object
	 * @param string $words a list of words used to filter attributes
	 * @return array
	 */
	private static function filter_available_product_attribute_names( \WC_Product $product, $words ) {

		$attributes = [];

		foreach ( self::get_available_product_attribute_names( $product ) as $name => $label ) {

			foreach ( $words as $word ) {

				if ( Framework\SV_WC_Helper::str_exists( wc_strtolower( $label ), $word ) || Framework\SV_WC_Helper::str_exists( wc_strtolower( $name ), $word ) ) {
					$attributes[ $name ] = $label;
				}
			}
		}

		return $attributes;
	}


	/**
	 * Gets a indexed list of available product attributes with the name of the attribute as key and the label as the value.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @param \WC_Product $product the product object
	 * @return array
	 */
	private static function get_available_product_attribute_names( \WC_Product $product ) {

		return array_map(
			function( $attribute ) use ( $product ) {
				return wc_attribute_label( $attribute->get_name(), $product );
			},
			FacebookProducts::get_available_product_attributes( $product )
		);
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
