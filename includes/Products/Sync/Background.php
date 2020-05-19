<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Products\Sync;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The background sync handler.
 *
 * @since 2.0.0-dev.1
 */
class Background extends Framework\SV_WP_Background_Job_Handler {


	/** @var string async request prefix */
	protected $prefix = 'wc_facebook';

	/** @var string async request action */
	protected $action = 'background_product_sync';

	/** @var string data key */
	protected $data_key = 'requests';


	/**
	 * Processes a job.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \stdClass|object $job
	 * @param int $items_per_batch number of items to process in a single request. Defaults to unlimited.
	 * @throws \Exception when job data is incorrect
	 * @return \stdClass $job
	 */
	public function process_job( $job, $items_per_batch = null ) {

		if ( ! $this->start_time ) {
			$this->start_time = time();
		}

		// Indicate that the job has started processing
		if ( 'processing' !== $job->status ) {

			$job->status                = 'processing';
			$job->started_processing_at = current_time( 'mysql' );

			$job = $this->update_job( $job );
		}

		$data_key = $this->data_key;

		if ( ! isset( $job->{$data_key} ) ) {
			throw new \Exception( sprintf( __( 'Job data key "%s" not set', 'woocommerce-plugin-framework' ), $data_key ) );
		}

		if ( ! is_array( $job->{$data_key} ) ) {
			throw new \Exception( sprintf( __( 'Job data key "%s" is not an array', 'woocommerce-plugin-framework' ), $data_key ) );
		}

		$data = $job->{$data_key};

		$job->total = count( $data );

		// progress indicates how many items have been processed, it
		// does NOT indicate the processed item key in any way
		if ( ! isset( $job->progress ) ) {
			$job->progress = 0;
		}

		// skip already processed items
		if ( $job->progress && ! empty( $data ) ) {
			$data = array_slice( $data, $job->progress, null, true );
		}

		// loop over unprocessed items and process them
		if ( ! empty( $data ) ) {

			$items_per_batch = (int) $items_per_batch;

			foreach ( $data as $item ) {

				// process the item
				$this->process_item( $item, $job );

				$processed++;
				$job->progress++;

				// update job progress
				$job = $this->update_job( $job );

				// job limits reached
				if ( ( $items_per_batch && $processed >= $items_per_batch ) || $this->time_exceeded() || $this->memory_exceeded() ) {
					break;
				}
			}
		}

		// complete current job
		if ( $job->progress >= count( $job->{$data_key} ) ) {
			$job = $this->complete_job( $job );
		}

		return $job;
	}


	/**
	 * Processes multiple items.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \stdClass|object $job
	 * @param array $data
	 * @param int $items_per_batch number of items to process in a single request. Defaults to unlimited.
	 */
	public function process_items( $job, $data, $items_per_batch = null ) {

		$processed = 0;

		foreach ( $data as $item ) {

			// process the item
			$this->process_item( $item, $job );

			$processed++;
			$job->progress++;

			// update job progress
			$job = $this->update_job( $job );

			// job limits reached
			if ( ( $items_per_batch && $processed >= $items_per_batch ) || $this->time_exceeded() || $this->memory_exceeded() ) {
				break;
			}
		}
	}


	/**
	 * Processes a single item.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param mixed $item
	 * @param object|\stdClass $job
	 * @return array
	 */
	public function process_item($item, $job) {
		// TODO
	}


	/**
	 * Sends item updates to Facebook.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param $requests
	 */
	public function send_item_updates( $requests ) {
		// TODO
	}


}
