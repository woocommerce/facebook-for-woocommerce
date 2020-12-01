<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

/**
 * Base handler for loading the list of defined Google categories.
 *
 * @since 2.2.1-dev.1
 */
class Google_Categories {

	/** @var string the WordPress option name where the full categories list is stored */
	const OPTION_GOOGLE_PRODUCT_CATEGORIES = 'wc_facebook_google_product_categories';


	/**
	 * Gets the categories list.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array
	 */
	public function get_categories() {

		// only fetch again if not fetched less than one week ago
		// $categories = get_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES );
		$categories = '';

		if ( empty ( $categories ) ) {

			// fetch from the URL
			$categories = $this->fetch_categories_list_from_url();

			if ( ! empty( $categories ) ) {
				set_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES, $categories, WEEK_IN_SECONDS );
			}
		}

		return $categories;
	}


	/**
	 * Fetches the full categories list from Google.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array|\WP_Error
	 */
	private function fetch_categories_list_from_url() {

		$url = $this->get_categories_list_url();

		// fail if URL is empty or undefined
		if ( empty( $url ) ) {
			return new \WP_Error( 'wc_facebook_google_categories_missing_url', __( 'Google categories URL is missing.', 'facebook-for-woocommerce' ) );
		}

		// fetch from the URL
		$response = wp_remote_get( $url );

		// fail if response is not OK
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new \WP_Error( 'wc_facebook_google_categories_response_error', __( 'Google categories URL returned error.', 'facebook-for-woocommerce' ), [
				'response_code'       => $response_code,
				'categories_list_url' => $url,
			] );
		}

		return wp_remote_retrieve_body( $response );
	}


	/**
	 * @return string
	 */
	private function get_categories_list_url() {

		/**
		 * Filters the Google categories list URL.
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @param array $categories_list_url Google categories list URL
		 */
		return (string) apply_filters( 'wc_facebook_google_categories_list_url', 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt' );
	}

}
