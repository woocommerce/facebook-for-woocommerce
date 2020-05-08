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
 * @since 1.11.3-dev.2
 */
class Background_Disable_Virtual_Products_Sync extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Background job constructor.
	 *
	 * @since 1.11.3-dev.2
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
	 * @since 1.11.3-dev.2
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
	 * @since 1.11.3-dev.2
	 *
	 * @return bool
	 */
	private function count_remaining_products() {
		global $wpdb;

		$sql = "
			SELECT COUNT( posts.ID )
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->postmeta} AS sync_meta ON ( posts.ID = sync_meta.post_id AND sync_meta.meta_key = '_wc_facebook_sync_enabled' AND sync_meta.meta_value = 'yes' )
			INNER JOIN {$wpdb->postmeta} AS virtual_meta ON ( posts.ID = virtual_meta.post_id AND virtual_meta.meta_key = '_virtual' AND virtual_meta.meta_value = 'yes' )
			WHERE posts.post_type IN ( 'product', 'product_variation' )
		";

		return (int) $wpdb->get_var( $sql );
	}


	/**
	 * Update rows into the postmeta table to disable sync.
	 *
	 * @since 1.11.3-dev.2
	 *
	 * @return int
	 */
	private function disable_sync() {
		global $wpdb;

		$sql = "
			UPDATE {$wpdb->postmeta} AS sync_meta
				INNER JOIN {$wpdb->posts} AS posts ON ( posts.ID = sync_meta.post_id )
				INNER JOIN {$wpdb->postmeta} AS virtual_meta ON ( posts.ID = virtual_meta.post_id AND virtual_meta.meta_key = '_virtual' AND virtual_meta.meta_value = 'yes' )
				SET sync_meta.meta_value = 'no'
				WHERE sync_meta.meta_key = '_wc_facebook_sync_enabled'
				AND sync_meta.meta_value = 'yes'
				AND posts.post_type IN ( 'product', 'product_variation' )
				LIMIT 1000
		";

		$rows_updated = $wpdb->query( $sql );

		if ( false === $rows_updated ) {

			$message = sprintf( 'There was an error trying to update products meta data. %s', $wpdb->last_error );

			facebook_for_woocommerce()->log( $message );
		}

		return (int) $rows_updated;
	}


	/**
	 * No-op
	 *
	 * @since 1.11.3-dev.2
	 */
	protected function process_item( $item, $job ) {
		// void
	}


}
