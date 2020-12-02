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

use SkyVerge\WooCommerce\Facebook\Google_Categories;

defined( 'ABSPATH' ) or exit;

/**
 * Google product category field.
 *
 * @since 2.1.0
 */
class Google_Product_Category_Field {


	/** @var string the WordPress option name where the full categories list is stored */
	const OPTION_GOOGLE_PRODUCT_CATEGORIES = 'wc_facebook_google_product_categories';


	/**
	 * Instantiates the JS handler for the Google product category field.
	 *
	 * @since 2.1.0
	 *
	 * @param string $input_id element that should receive the latest concrete category ID value
	 */
	public function render( $input_id ) {

		$js = sprintf(
			"window.wc_facebook_google_product_category_fields = new WC_Facebook_Google_Product_Category_Fields( %s, '%s' );",
			json_encode( $this->get_categories() ),
			esc_js( $input_id )
		);

		wc_enqueue_js( $js );

	}


	/**
	 * Gets the full categories list from Google and stores it.
	 *
	 * @since 2.1.0
	 */
	public function get_categories() {

		return facebook_for_woocommerce()->get_google_categories_handler()->get_categories();
	}


	/**
	 * Parses the categories response from Google.
	 *
	 * @since 2.1.0
	 *
	 * @param array|\WP_Error $categories_response categories response from Google
	 * @return array
	 */
	protected function parse_categories_response( $categories_response ) {

		$categories    = [];
		$response_body = $response_body = wp_remote_retrieve_body( $categories_response );

		if ( ! empty( $response_body ) ) {

			$categories = Google_Categories::parse_categories_response_body( $categories_response['body'] );
		}

		return $categories;
	}


	/**
	 * Gets the category options (children) for a given category.
	 *
	 * @since 2.1.0
	 *
	 * @param string $category_id category ID
	 * @param array $categories full category list
	 * @return array
	 */
	public function get_category_options( $category_id, $categories ) {

		return array_filter( array_map( static function ( $category ) use ( $category_id ) {

			return $category['parent'] === $category_id ? $category['label'] : false;
		}, $categories ) );
	}


}
