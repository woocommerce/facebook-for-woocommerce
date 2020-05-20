<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Utilities;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;


/**
 * Background job handler to exclude virtual products and virtual product variations from sync.
 *
 * @since 2.0.0-dev.1
 */
class Background_Disable_Virtual_Products_Sync extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Background job constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

		$this->prefix = 'wc_facebook';
		$this->action = 'background_disable_virtual_products_sync';

		parent::__construct();
	}


	/**
	 * Processes job.
	 *
	 * This job continues to update products and product variations meta data until we run out of memory
	 * or exceed the time limit. There is no list of items to loop over.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param object $job
	 * @param int $items_per_batch number of items to process in a single request. Defaults to unlimited.
	 * @return object
	 */
	public function process_job( $job, $items_per_batch = null ) {

		if ( ! isset( $job->total ) ) {
			$job->total = $this->count_remaining_products();

			if ( empty( $job->total ) ) {

				// no products or variations need to be excluded from sync, do not display admin notice
				update_option( 'wc_facebook_sync_virtual_products_disabled_skipped', 'yes' );
			}
		}

		if ( ! isset( $job->progress ) ) {
			$job->progress = 0;
		}

		$remaining_products = $job->total;
		$processed_products = 0;

		// disable sync until memory or time limit is exceeded
		while ( $processed_products < $remaining_products ) {

			$rows_updated = $this->disable_sync();

			$processed_products += $rows_updated;
			$job->progress      += $rows_updated;

			// update job progress
			$job = $this->update_job( $job );

			// memory or time limit reached
			if ( $this->time_exceeded() || $this->memory_exceeded() ) {
				break;
			}
		}

		// job complete! :)
		if ( $this->count_remaining_products() === 0 ) {

			update_option( 'wc_facebook_sync_virtual_products_disabled', 'yes' );

			$this->complete_job( $job );
		}

		return $job;
	}


	/**
	 * Counts the number of virtual products or product variations with sync enabled.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return bool
	 */
	private function count_remaining_products() {
		global $wpdb;

		$sql = "
			SELECT COUNT( posts.ID )
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->postmeta} AS virtual_meta ON ( posts.ID = virtual_meta.post_id AND virtual_meta.meta_key = '_virtual' AND virtual_meta.meta_value = 'yes' )
			LEFT JOIN {$wpdb->postmeta} AS sync_meta ON ( posts.ID = sync_meta.post_id AND sync_meta.meta_key = '_wc_facebook_sync_enabled' )
			WHERE posts.post_type IN ( 'product', 'product_variation' )
			AND ( sync_meta.meta_value IS NULL OR sync_meta.meta_value = 'yes' )
		";

		return (int) $wpdb->get_var( $sql );
	}


	/**
	 * Update rows into the postmeta table to disable sync.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return int
	 */
	private function disable_sync() {
		global $wpdb;

		$rows_inserted = 0;

		// get post IDs to update
		$sql = "
			SELECT DISTINCT( posts.ID )
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->postmeta} AS virtual_meta ON ( posts.ID = virtual_meta.post_id AND virtual_meta.meta_key = '_virtual' AND virtual_meta.meta_value = 'yes' )
			LEFT JOIN {$wpdb->postmeta} AS sync_meta ON ( posts.ID = sync_meta.post_id AND sync_meta.meta_key = '_wc_facebook_sync_enabled' )
			WHERE posts.post_type IN ( 'product', 'product_variation' )
			AND ( sync_meta.meta_value IS NULL OR sync_meta.meta_value = 'yes' )
			LIMIT 1000
		";

		$post_ids = $wpdb->get_col( $sql );

		if ( empty( $post_ids ) ) {

			facebook_for_woocommerce()->log( 'There are no products or products variations to update.' );

		} else {

			$post_ids_str = implode( "','", $post_ids );

			// delete the metadata so we can insert it without creating duplicates
			$sql = "
				DELETE FROM {$wpdb->postmeta}
					WHERE meta_key = '_wc_facebook_sync_enabled'
					AND post_id IN ( '{$post_ids_str}' )
			";

			$wpdb->query( $sql );

			$values = [];

			foreach ( $post_ids as $post_id ) {

				$values[] = "('$post_id', '_wc_facebook_sync_enabled', 'no')";
			}

			$values_str = implode( ',', $values );

			// we need to explicitly insert the metadata and set it to no, because not having it means sync is enabled
			$sql = "
				INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value )
					VALUES {$values_str}
			";

			$rows_inserted = $wpdb->query( $sql );

			if ( false === $rows_inserted ) {

				$message = sprintf( 'There was an error trying to set products and variations meta data. %s', $wpdb->last_error );

				facebook_for_woocommerce()->log( $message );
			}
		}

		return (int) $rows_inserted;
	}


	/**
	 * No-op
	 *
	 * @since 2.0.0-dev.1
	 */
	protected function process_item( $item, $job ) {
		// void
	}


}
