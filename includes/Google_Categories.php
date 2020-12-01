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

	/** @var string the WordPress option name where the last time the full categories list get updated is stored */
	const OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED = 'wc_facebook_google_product_categories_update';


	/**
	 * Gets the categories list.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array
	 */
	public function get_categories() {

		// only fetch again if not fetched less than one week ago
		$last_updated = get_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED );

		if ( empty ( $last_updated ) ) {

			// fetch from the URL
			$categories = $this->fetch_categories_list_from_url();

			if ( ! empty( $categories ) ) {

				// store into database for later use
				$this->store_categories_list( $categories );

				// mark when it's stored
				set_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED, current_time( 'mysql' ), WEEK_IN_SECONDS );
			}
		}

		return $categories;
	}


	/**
	 * Fetches the full categories list from Google.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param array $categories fetched Google categories list to store
	 */
	private function store_categories_list( $categories ) {

		global $wpdb;

		$this->empty_db_table();

		foreach ( $categories as $category_id => $category ) {

			$this->store_item( $category_id, $category['label'], $category['parent'] );
		}
	}


	/**
	 * Stores given Google category into database.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param int $item_id category item ID
	 * @param string $item_label category item label
	 * @param int $item_parent category parent ID (optional)
	 */
	private function store_item( $item_id, $item_label, $item_parent = 0 ) {

		global $wpdb;

		$wpdb->insert( self::get_table_name(), [
			'id'        => $item_id,
			'parent_id' => $item_parent,
			'label'     => $item_label,
		], [ '%d', '%d', '%s' ] );

	}


	/**
	 * Cleans up all previous categories stored.
	 *
	 * @since 2.2.1-dev.1
	 */
	private function empty_db_table() {

		global $wpdb;

		$wpdb->query( 'TRUNCATE ' . self::get_table_name() );
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

		$response_body = wp_remote_retrieve_body( $response );
		// fail if response body is empty
		if ( empty( $response_body ) ) {
			return new \WP_Error( 'wc_facebook_google_categories_empty_response', __( 'Google categories response is empty.', 'facebook-for-woocommerce' ) );
		}

		return $this->parse_categories_response( $response_body );
	}


	/**
	 * Parses the categories response from Google.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param string $response_body categories response body from Google
	 * @return array
	 */
	protected function parse_categories_response( $response_body ) {

		$categories        = [];
		$categories_body   = explode( "\n", $response_body );
		$categories_labels = [];

		// format: ID - Top level category > ... > Parent category > Category label
		// example: 7385 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories
		foreach ( $categories_body as $category_line ) {

			if ( strpos( $category_line, ' - ' ) === false ) {

				// not a category, skip it
				continue;
			}

			list( $category_id, $category_tree ) = explode( ' - ', $category_line );

			$category_id    = (string) trim( $category_id );
			$category_tree  = explode( ' > ', $category_tree );
			$category_label = end( $category_tree );

			$categories_labels[ $category_label ] = $category_id;

			$category = [
				'label'   => $category_label,
				'options' => [],
			];

			if ( $category_label === $category_tree[0] ) {

				// top-level category
				$category['parent'] = '';

			} else {

				$parent_label       = $category_tree[ count( $category_tree ) - 2 ];
				$parent_category    = isset( $categories_labels[ $parent_label ] ) ? $categories_labels[ $parent_label ] : '0';
				$category['parent'] = (string) $parent_category;

				// add category label to the parent's list of options
				$categories[ $parent_category ]['options'][ $category_id ] = $category_label;
			}

			$categories[ $category_id ] = $category;
		}

		return $categories;
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


	/**
	 * Gets the table name used for storing categories.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return string table name
	 */
	public static function get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'wc_facebook_google_categories';
	}


	/**
	 * Returns the database schema needed to create the table.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return string database schema
	 */
	public static function get_table_schema() {

		global $wpdb;

		$table_name = self::get_table_name();
		$collate    = $collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return "CREATE TABLE {$table_name} (
  id BIGINT(20) unsigned NOT NULL,
  parent_id BIGINT(20) NOT NULL default 0,
  label VARCHAR(200) NOT NULL default '',
  PRIMARY KEY (id ASC),
  KEY parent_id (parent_id ASC)
) $collate;
		";
	}

}
