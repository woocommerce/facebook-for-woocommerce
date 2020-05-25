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

use SkyVerge\WooCommerce\Facebook\Products;

/**
 * The product sync handler.
 *
 * @since 2.0.0-dev.1
 */
class Sync {


	/** @var string the prefix used in the array indexes */
	const PRODUCT_INDEX_PREFIX = 'p-';

	/** @var string the update action */
	const ACTION_UPDATE = 'UPDATE';

	/** @var string the delete action */
	const ACTION_DELETE = 'DELETE';

	/** @var array the array of requests to schedule for sync */
	protected $requests = [];


	/**
	 * Sync constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Adds needed hooks to support product sync.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function add_hooks() {

		add_action( 'shutdown', [ $this, 'schedule_sync' ] );
	}


	/**
	 * Adds all eligible product IDs to the requests array to be created or updated.
	 *
	 * Uses the same logic that the feed handler uses to get a list of product IDs to sync.
	 *
	 * TODO: consolidate the logic to decide whether a product should be synced in one or a couple of helper methods - right now we have slightly different versions of the same code in different places {WV 2020-05-25}
	 *
	 * @see \WC_Facebook_Product_Feed::get_product_ids()
	 * @see \WC_Facebook_Product_Feed::write_product_feed_file()
	 *
	 * @since 2.0.0-dev.1
	 */
	public function create_or_update_all_products() {

		$product_ids        = [];
		$parent_product_ids = [];

		// loop through all published products and product variations to get their IDs
		$args = [
			'fields'         => 'id=>parent',
			'post_status'    => 'publish',
			'post_type'      => [ 'product', 'product_variation' ],
			'posts_per_page' => -1,
		];

		foreach ( get_posts( $args ) as $post_id => $post_parent ) {

			$product_ids[] = $post_id;

			if ( 'product_variation' === get_post_type( $post_id ) ) {
				$parent_product_ids[] = $post_parent;
			}
		}

		// remove parent products because those can't be represented as Product Items
		$product_ids = array_diff( $product_ids, $parent_product_ids );

		// make sure the product should be synced and add it to the sync queue
		foreach ( $product_ids as $product_id ) {

			$woo_product = new \WC_Facebook_Product( $product_id );

			if ( $woo_product->is_hidden() ) {
				continue;
			}

			if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' && ! $woo_product->is_in_stock() ) {
				continue;
			}

			// skip if not enabled for sync
			if ( $woo_product->woo_product instanceof \WC_Product && ! Products::product_should_be_synced( $woo_product->woo_product ) ) {
				continue;
			}

			$this->create_or_update_products( [ $product_id ] );
		}
	}


	/**
	 * Adds the given product IDs to the requests array to be updated.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param int[] $product_ids
	 */
	public function create_or_update_products( array $product_ids ) {

		foreach ( $product_ids as $product_id ) {
			$this->requests[ $this->get_product_index( $product_id ) ] = self::ACTION_UPDATE;
		}
	}


	/**
	 * Adds the given product IDs to the requests array to be deleted.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param int[] $product_ids
	 */
	public function delete_products( array $product_ids ) {

		foreach ( $product_ids as $product_id ) {
			$this->requests[ $this->get_product_index( $product_id ) ] = self::ACTION_DELETE;
		}
	}


	/**
	 * Creates a background job to sync the products in the requests array.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return \stdClass|object|null
	 */
	public function schedule_sync() {

		if ( ! empty( $this->requests ) ) {

			return facebook_for_woocommerce()->get_products_sync_background_handler()->create_job( [
				'requests' => $this->requests
			] );
		}
	}


	/**
	 * Gets the prefixed product ID used as the array index.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param $product_id
	 * @return string
	 */
	private function get_product_index( $product_id ) {

		return self::PRODUCT_INDEX_PREFIX . $product_id;
	}


}
