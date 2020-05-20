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

use SkyVerge\WooCommerce\Facebook\Products\Sync;
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
	 * @param int|null $items_per_batch number of items to process in a single request (defaults to null for unlimited)
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
			$this->process_items( $job, $data, (int) $items_per_batch );
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
	 * @param int|null $items_per_batch number of items to process in a single request (defaults to null for unlimited)
	 */
	public function process_items( $job, $data, $items_per_batch = null ) {

		$processed = 0;

		foreach ( $data as $prefixed_product_id => $method ) {

			$product_id = (int) str_replace( Sync::PRODUCT_INDEX_PREFIX, '', $prefixed_product_id );

			// process the item
			$this->process_item( [ $product_id, $method ], $job );

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
	public function process_item( $item, $job ) {

		list( $product_id, $method ) = $item;

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			// TODO: throw an exception and add a test
			return;
		}

		if ( ! in_array( $method, [ Sync::ACTION_UPDATE, Sync::ACTION_DELETE ], true ) ) {
			// TODO: throw an exception and add a test
			return;
		}

		return $this->process_item_update( $product );
	}


	/**
	 * Processes an UPDATE sync request for the given product.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	private function process_item_update( $product ) {

		$product_data = $this->prepare_product_data( $product );

		return [
			'retailer_id' => $product_data['retailer_id'],
			'method'      => Sync::ACTION_UPDATE,
			'data'        => $product_data,
		];
	}


	/**
	 * Prepares the product data to be included in a sync request.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 */
	private function prepare_product_data( $product ) {

		if ( $product->is_type( 'variation' ) ) {

			$parent_product = wc_get_product( $product->get_parent_id() );

			if ( ! $parent_product instanceof \WC_Product ) {
				// TODO: throw an exception and add a test
				return;
			}

			$fb_parent_product = new \WC_Facebook_Product( $parent_product );
			$fb_product        = new \WC_Facebook_Product( $product->get_id(), $fb_parent_product );

			$retailer_product_group_id = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $parent_product );

		} else {

			$fb_product = new \WC_Facebook_Product( $product->get_id() );

			$retailer_product_group_id = null;
		}

		$data = $fb_product->prepare_product();

		// allowed values are 'refurbished', 'used', and 'new', but the plugin has always used the latter
		$data['condition'] = 'new';

		$data['product_type'] = $data['category'];

		$data['retailer_product_group_id'] = $retailer_product_group_id ?: $data['retailer_id'];

		return $data;
	}


	/**
	 * Processes a DELETE sync request for the given product.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	private function process_item_delete( $product ) {

		return [
			'retailer_id' => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
			'method'      => Sync::ACTION_DELETE,
		];
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
