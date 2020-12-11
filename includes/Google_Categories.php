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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * Base handler for loading the list of defined Google categories.
 *
 * @since 2.2.1-dev.1
 */
class Google_Categories {

	/** @var string the WordPress option name where the last time the full categories list get updated is stored */
	const OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED = 'wc_facebook_google_product_categories_update';

	/** @var array 2nd level of cache for loading Google categories list */
	private $categories_list;


	/**
	 * Gets the categories list.
	 *
	 * TODO we need to figure out a way to of load the long loading time or
	 *      re-kickstart the process of the merchant reloaded the page {NM 2020-12-11}
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array
	 */
	public function get_categories() {

		if ( ! empty( $this->categories_list ) ) {
			return $this->categories_list;
		}

		// only fetch again if not fetched less than one week ago
		$last_updated = get_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED );

		if ( empty ( $last_updated ) ) {

			$categories = [];

			try {

				// fetch from the URL
				$categories = $this->fetch_categories_list_from_url();

			} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

				$error_message = sprintf(
					/* translators: Placeholders: %s - user-friendly error message */
					__( 'Fetching Google categories error: %s', 'facebook-for-woocommerce' ),
					$exception->getMessage()
				);

				// log exception error message
				facebook_for_woocommerce()->log( $error_message );

				// show notice error message
				$message_handler = facebook_for_woocommerce()->get_message_handler();
				$message_handler->add_error( $error_message );
				$message_handler->show_messages();
			}

			if ( ! empty( $categories ) ) {

				// store into database for later use
				$this->store_categories_list( $categories );

				// mark when it's stored
				set_transient( self::OPTION_GOOGLE_PRODUCT_CATEGORIES_UPDATED, current_time( 'mysql' ), WEEK_IN_SECONDS );
			}
		} else {

			// load from database/cached
			$categories = $this->get_cached_categories_list();

		}

		$this->categories_list = $categories;

		return $categories;
	}


	/**
	 * Gets cached categories list form database.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array
	 */
	private function get_cached_categories_list() {

		$raw_categories_items = $this->get_items();

		if ( empty( $raw_categories_items ) ) {
			return [];
		}

		$categories = [];

		// structure all categories semi-flat
		foreach ( $raw_categories_items as $category_item ) {
			$categories[ (int) $category_item['id'] ] = [
				'label'   => $category_item['label'],
				'options' => [],
				'parent'  => (int) $category_item['parent_id'],
			];
		}

		// then insert sub-categories as options
		foreach ( $raw_categories_items as $category_item ) {

			$parent_category_id = (int) $category_item['parent_id'];

			if ( isset( $categories[ $parent_category_id ] ) ) {
				$categories[ $parent_category_id ]['options'][ (int) $category_item['id'] ] = $category_item['label'];
			}
		}

		return $categories;
	}


	/**
	 * Queries full categories list from database.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @return array
	 */
	private function get_items() {

		global $wpdb;

		return (array) $wpdb->get_results( 'SELECT * FROM ' . self::get_table_name() . ' ORDER BY id ASC', ARRAY_A );
	}


	/**
	 * Fetches the full categories list from Google.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param array $categories fetched Google categories list to store
	 */
	private function store_categories_list( $categories ) {

		$this->empty_db_table();

		foreach ( $categories as $category_id => $category ) {
			// make sure to store the item if both label and parent keys defined
			if ( isset( $category['label'], $category['parent'] ) ) {
				$this->store_item( $category_id, $category['label'], $category['parent'] );
			}
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
	 * @return array
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function fetch_categories_list_from_url() {

		$url = $this->get_categories_list_url();

		// fail if URL is empty or undefined
		if ( empty( $url ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Google categories URL is missing.', 'facebook-for-woocommerce' ) );
		}

		// fetch from the URL
		$response = wp_remote_get( $url );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		// fail if response is not OK
		if ( 200 !== $response_code ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Google categories URL returned error.', 'facebook-for-woocommerce' ), $response_code );
		}

		$response_body = wp_remote_retrieve_body( $response );

		// fail if response body is empty
		if ( empty( $response_body ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Google categories response is empty.', 'facebook-for-woocommerce' ) );
		}

		return self::parse_categories_response_body( $response_body );
	}


	/**
	 * Parses the categories response from Google.
	 *
	 * @since 2.2.1-dev.1
	 *
	 * @param string $response_body categories response body from Google
	 * @return array
	 */
	public static function parse_categories_response_body( $response_body ) {

		$categories        = [];
		$categories_body   = explode( "\n", $response_body );
		$categories_labels = [];

		// format: ID - Top level category > ... > Parent category > Category label
		// example: 7385 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories
		foreach ( $categories_body as $category_line ) {

			// not a category, skip it
			if ( strpos( $category_line, ' - ' ) === false ) {
				continue;
			}

			list( $category_id, $category_tree ) = explode( ' - ', $category_line );

			// bail if category ID ot tree missing
			if ( empty( $category_id ) || empty( $category_tree ) ) {
				continue;
			}

			$category_id   = (string) trim( $category_id );
			$category_tree = explode( ' > ', $category_tree );

			// bail if category tree can't be split
			if ( empty( $category_tree ) ) {
				continue;
			}

			$category_label = end( $category_tree );

			// bail if category label isn't set or empty for some reason
			if ( empty( $category_label ) ) {
				continue;
			}

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
	 * Gets Google categories list URL.
	 *
	 * TODO maybe provide localized categories list, see https://support.google.com/merchants/answer/6324436?hl=en {NM 2020-12-11}
	 *
	 * @since 2.2.1-dev.1
	 *
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
