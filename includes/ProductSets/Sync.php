<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\ProductSets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The product set sync handler.
 *
 * @since 2.1.5
 */
class Sync {


	/**
	 * Prefix used with the product IDs
	 *
	 * @var string
	 */
	const PRODUCT_INDEX_PREFIX = 'wc_post_id_';

	/**
	 * Update action name
	 *
	 * @var string
	 */
	const ACTION_UPDATE = 'UPDATE';

	/**
	 * Delete action name
	 *
	 * @var string
	 */
	const ACTION_DELETE = 'DELETE';

	/**
	 * Requests array for sync schedule
	 *
	 * @var array
	 */
	protected $requests = array();

	/**
	 * Product's Category Previous List
	 *
	 * @since 2.1.5
	 *
	 * @var array
	 */
	protected static $prev_product_cat = array();

	/**
	 * Product's Category New List
	 *
	 * @since 2.1.5
	 *
	 * @var array
	 */
	protected static $new_product_cat = array();

	/**
	 * Product's Product Set Previous List
	 *
	 * @since 2.1.5
	 *
	 * @var array
	 */
	protected static $prev_product_set = array();

	/**
	 * Product's Product Set New List
	 *
	 * @since 2.1.5
	 *
	 * @var array
	 */
	protected static $new_product_set = array();

	/**
	 * Categories field name
	 *
	 * @since 2.1.5
	 *
	 * @var string
	 */
	protected $categories_field = '';


	/**
	 * Sync constructor.
	 *
	 * @since 2.1.5
	 */
	public function __construct() {

		$this->categories_field = \WC_Facebookcommerce::PRODUCT_SET_META;

		$this->add_hooks();
	}


	/**
	 * Adds needed hooks to support product set sync.
	 *
	 * @since 2.1.5
	 */
	public function add_hooks() {

		// product hooks, compare taxonomies the lists before and after saving product to see if must sync
		add_filter( 'wp_insert_post_data', array( $this, 'check_product_data_before_save' ), 99, 2 );
		add_action( 'save_post', array( $this, 'check_product_data_after_save' ), 99, 2 );//maybe_sync_product_sets

		// product hooks, sync product's product set when deleting or restoring product
		add_action( 'trashed_post', array( $this, 'sync_product_product_sets' ), 99 );
		add_action( 'untrashed_post', array( $this, 'sync_product_product_sets' ), 99 );

		// product set hooks, compare taxonomies the lists before and after saving product to see if must sync
		add_action( 'created_fb_product_set', array( $this, 'fb_product_set_hook_before' ), 1 );
		add_action( 'created_fb_product_set', array( $this, 'fb_product_set_hook_after' ), 99 );
		add_action( 'edited_fb_product_set', array( $this, 'fb_product_set_hook_before' ), 1 );
		add_action( 'edited_fb_product_set', array( $this, 'fb_product_set_hook_after' ), 99 );

		// product cat and product set delete hooks, remove or check if must remove any product set
		add_action( 'pre_delete_term', array( $this, 'sync_remove_product_set' ), 1, 2 );
		add_action( 'delete_product_cat', array( $this, 'maybe_sync_product_set_on_product_cat_remove' ), 99 );
	}


	/** Hook methods *******************************************************************************************/


	/**
	 * Stores the list of categories and product set of a product to compare later on 'save_post' hook.
	 *
	 * @since 2.1.5
	 *
	 * @param array $data Post Data.
	 * @param array $post Post.
	 */
	public function check_product_data_before_save( $data, $post ) {

		// gets product's current categories IDs
		if ( ! empty( $post ) && 'product' === $post['post_type'] ) {
			self::$prev_product_cat = wc_get_product_cat_ids( $post['ID'] );
			self::$prev_product_set = wc_get_product_term_ids( $post['ID'], 'fb_product_set' );
		}

		return $data;
	}

	/**
	 * Sync Product's Product Sets based on its Categories
	 *
	 * @param int $post_id Post ID.
	 */
	public function sync_product_product_sets( $post_id ) {

		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		// get product's categories and sets
		$product_cats = wc_get_product_cat_ids( $post_id );
		$product_sets = wc_get_product_term_ids( $post_id, 'fb_product_set' );

		$this->maybe_sync_product_sets( $product_cats, $product_sets );
	}


	/**
	 * Stores the list of categories and product set of a product to compare with the previous
	 *
	 * @since 2.1.5
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post Object.
	 */
	public function check_product_data_after_save( $post_id, $post = '' ) {

		if ( 'product' !== $post->post_type ) {
			return;
		}

		// gets product's new categories IDs to compare if there are differences to sync
		self::$new_product_cat = wc_get_product_cat_ids( $post_id );
		self::$new_product_set = wc_get_product_term_ids( $post_id, 'fb_product_set' );

		// get differences
		$product_cat_diffs = $this->get_all_diff( 'product_cat' );
		$product_set_diffs = $this->get_all_diff( 'product_set' );
		if ( ! $product_cat_diffs && ! $product_set_diffs ) {
			return;
		}

		$this->maybe_sync_product_sets( $product_cat_diffs, $product_set_diffs );
	}


	/**
	 * Check if must sync Product Sets from given lists
	 *
	 * @since 2.1.5
	 *
	 * @param array $product_cats Product Category Term IDs.
	 * @param array $product_sets Product Set Term IDs.
	 */
	public function maybe_sync_product_sets( $product_cats, $product_sets ) {

		if ( empty( $product_sets ) || ! is_array( $product_sets ) ) {
			$product_sets = array();
		}
		$product_set_term_ids = array_merge( array(), $product_sets );

		// check if product cat belongs to a product_set
		foreach ( $product_cats as $product_cat_id ) {

			$cat_product_sets = $this->get_product_cat_sets( $product_cat_id );
			if ( ! empty( $cat_product_sets ) ) {
				$product_set_term_ids = array_merge( $product_set_term_ids, $cat_product_sets );
			}
		}

		foreach ( $product_set_term_ids as $product_set_id ) {
			$this->maybe_sync_product_set( $product_set_id );
		}
	}


	/**
	 * Stores the list of categories of a product set to compare later.
	 *
	 * @since 2.1.5
	 *
	 * @param int $term_id Term ID.
	 */
	public function fb_product_set_hook_before( $term_id ) {
		self::$prev_product_cat = get_term_meta( $term_id, $this->categories_field, true );
	}


	/**
	 * Stores the list of categories of a product set to compare with the previous and check if must sync
	 *
	 * @since 2.1.5
	 *
	 * @param int $term_id Term ID.
	 */
	public function fb_product_set_hook_after( $term_id ) {
		self::$new_product_cat = get_term_meta( $term_id, $this->categories_field, true );
		if ( ! empty( $this->get_all_diff( 'product_cat' ) ) ) {
			$this->maybe_sync_product_set( $term_id );
		}
	}


	/**
	 * Check if must sync Product Set from FB when removing Product Cat
	 *
	 * @since 2.1.5
	 *
	 * @param int $term_id Product Cat Term ID.
	 */
	public function maybe_sync_product_set_on_product_cat_remove( $term_id ) {

		$product_sets = $this->get_product_cat_sets( $term_id );
		if ( empty( $product_sets ) ) {
			return;
		}

		foreach ( $product_sets as $product_set_id ) {
			$this->maybe_sync_product_set( $product_set_id );
		}
	}


	/**
	 * Call API to remove Product Set from FB
	 *
	 * @since 2.1.5
	 *
	 * @param int $product_set_term_id Product Set Term ID.
	 */
	public function sync_remove_product_set( $product_set_term_id, $taxonomy ) {

		if ( 'fb_product_set' !== $taxonomy ) {
			return;
		}

		// get product set FB ID
		$fb_product_set_id = get_term_meta( $product_set_term_id, \WC_Facebookcommerce_Integration::FB_PRODUCT_SET_ID, true );
		if ( empty( $fb_product_set_id ) ) {
			return;
		}

		do_action( 'fb_wc_product_set_delete', $fb_product_set_id );
	}


	/** Utility methods *******************************************************************************************/


	/**
	 * Maybe sync product set
	 *
	 * @since 2.1.5
	 *
	 * @param int $product_set_id FB Product Set Term ID.
	 */
	public function maybe_sync_product_set( $product_set_id ) {

		// get Product Set
		$term = get_term( $product_set_id );
		if ( empty( $term ) ) {
			return;
		}

		// get categories
		$product_cat_ids = get_term_meta( $product_set_id, $this->categories_field, true );

		// get products for the taxonomies
		$products      = array();
		$products_args = array(
			'fields'         => 'ids',
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query'      => array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'fb_product_set',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_set_id ),
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_cat_ids,
					'operator' => 'IN',
				),
			),
		);
		$product_ids   = get_posts( $products_args );

		if ( empty( $product_ids ) ) {
			$fb_product_set_id = get_term_meta( $product_set_id, \WC_Facebookcommerce_Integration::FB_PRODUCT_SET_ID, true );
			update_term_meta( $product_set_id, \WC_Facebookcommerce_Integration::FB_PRODUCT_SET_ID, '' );
			do_action( 'fb_wc_product_set_delete', $fb_product_set_id );
		}

		// gets products variations
		global $wpdb;
		$variation_ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'product_variation' AND post_parent IN (%s) ", implode( ',', $product_ids ) ) );
		if ( ! empty( $variation_ids ) ) {
			$product_ids = array_merge( $product_ids, wp_list_pluck( $variation_ids, 'ID' ) );
		}

		// formats filter value
		foreach ( $product_ids as $product_id ) {
			array_push(
				$products,
				array( 'retailer_id' => array( 'eq' => self::PRODUCT_INDEX_PREFIX . $product_id ) )
			);
		}
		$data = array(
			'name'   => $term->name,
			'filter' => wp_json_encode( array( 'or' => $products ) ),
		);

		do_action( 'fb_wc_product_set_sync', $data, $product_set_id );
	}


	/**
	 * Return the list of product sets of a given product category term
	 *
	 * @since 2.1.5
	 *
	 * @param string $product_cat_id product cat Term ID.
	 *
	 * @return array
	 */
	protected function get_product_cat_sets( $product_cat_id ) {

		$args = array(
			'fields'     => 'ids',
			'taxonomy'   => 'fb_product_set',
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'     => 'wc_product_cats',
					'value'   => sprintf( ':%d;', $product_cat_id ),
					'compare' => 'LIKE',
				),
			),
		);
		return get_terms( $args );
	}

	/**
	 * Return the list of differences between two list of terms
	 *
	 * @since 2.1.5
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected function get_all_diff( $taxonomy = 'product_cat' ) {

		$prev = empty( self::${ 'prev_' . $taxonomy } ) ? array() : self::${ 'prev_' . $taxonomy };
		$new  = empty( self::${ 'new_' . $taxonomy } ) ? array() : self::${ 'new_' . $taxonomy };

		$removed = array_diff( $prev, $new );
		$added   = array_diff( $new, $prev );

		return array_merge( $removed, $added );
	}


}
