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
		$requests  = [];

		foreach ( $data as $prefixed_product_id => $method ) {

			$product_id = (int) str_replace( Sync::PRODUCT_INDEX_PREFIX, '', $prefixed_product_id );

			try {

				$requests[] = $this->process_item( [ $product_id, $method ], $job );

			} catch ( Framework\SV_WC_Plugin_Exception $e )	{

				facebook_for_woocommerce()->log( "Background sync error: {$e->getMessage()}" );
			}

			$processed++;
			$job->progress++;

			// update job progress
			$job = $this->update_job( $job );

			// job limits reached
			if ( ( $items_per_batch && $processed >= $items_per_batch ) || $this->time_exceeded() || $this->memory_exceeded() ) {
				break;
			}
		}

		if ( ! empty( $requests ) ) {
			$this->send_item_updates($requests);
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
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function process_item( $item, $job ) {

		list( $product_id, $method ) = $item;

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			throw new Framework\SV_WC_Plugin_Exception( "No product found with ID equal to {$product_id}." );
		}

		if ( ! in_array( $method, [ Sync::ACTION_UPDATE, Sync::ACTION_DELETE ], true ) ) {
			throw new Framework\SV_WC_Plugin_Exception( "Invalid sync request method: {$method}." );
		}

		if ( Sync::ACTION_UPDATE === $method ) {
			$request = $this->process_item_update( $product );
		} else {
			$request = $this->process_item_delete( $product );
		}

		return $request;
	}


	/**
	 * Processes an UPDATE sync request for the given product.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function process_item_update( $product ) {

		if ( $product->is_type( 'variation' ) ) {
			$product_data = $this->prepare_product_variation_data( $product );
		} else {
			$product_data = $this->prepare_product_data( $product );
		}

		// extract the retailer_id
		$retailer_id = $product_data['retailer_id'];

		// retailer_id cannot be included in the data object
		unset( $product_data['retailer_id'] );

		$request = [
			'retailer_id' => $retailer_id,
			'method'      => Sync::ACTION_UPDATE,
			'data'        => $product_data,
		];

		/**
		 * Filters the data that will be included in a UPDATE sync request.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $request request data
		 * @param \WC_Product $product product object
		 */
		return apply_filters( 'wc_facebook_sync_background_item_update_request', $request, $product );
	}


	/**
	 * Prepares the data for a product variation to be included in a sync request.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function prepare_product_variation_data( $product ) {

		$parent_product = wc_get_product( $product->get_parent_id() );

		if ( ! $parent_product instanceof \WC_Product ) {
			throw new Framework\SV_WC_Plugin_Exception( "No parent product found with ID equal to {$product->get_parent_id()}." );
		}

		$fb_parent_product = new \WC_Facebook_Product( $parent_product );
		$fb_product        = new \WC_Facebook_Product( $product->get_id(), $fb_parent_product );

		$data = $fb_product->prepare_product();

		// product variations use the parent product's retailer ID as the retailer product group ID
		$data['retailer_product_group_id'] = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $parent_product );

		return $this->normalize_product_data( $data );
	}


	/**
	 * Normalizes product data to be included in a sync request.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 */
	private function normalize_product_data( $data ) {

		// allowed values are 'refurbished', 'used', and 'new', but the plugin has always used the latter
		$data['condition'] = 'new';

		$data['product_type'] = $data['category'];

		// attributes other than size, color, pattern, or gender need to be included in the additional_variant_attributes field
		if ( isset( $data['custom_data'] ) && is_array( $data['custom_data'] ) ) {

			$data['additional_variant_attributes'] = $data['custom_data'];
			unset( $data['custom_data'] );
		}

		return $data;
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

		$fb_product = new \WC_Facebook_Product( $product->get_id() );

		$data = $fb_product->prepare_product();

		// products that are not variations use their retailer retailer ID as the retailer product group ID
		$data['retailer_product_group_id'] = $data['retailer_id'];

		return $this->normalize_product_data( $data );
	}


	/**
	 * Processes a DELETE sync request for the given product.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product product object
	 */
	private function process_item_delete( $product ) {

		$request = [
			'retailer_id' => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
			'method'      => Sync::ACTION_DELETE,
		];

		/**
		 * Filters the data that will be included in a DELETE sync request.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $request request data
		 * @param \WC_Product $product product object
		 */
		return apply_filters( 'wc_facebook_sync_background_item_delete_request', $request, $product );
	}


	/**
	 * Sends item updates to Facebook.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \stdClass|object $job
	 * @param array $requests sync requests
	 * @return array
	 * @throws Framework\SV_WC_API_Exception
	 */
	private function send_item_updates( array $requests ) {

		$catalog_id = facebook_for_woocommerce()->get_integration()->get_product_catalog_id();
		$response   = facebook_for_woocommerce()->get_api()->send_item_updates( $catalog_id, $requests, true );

		return $response->get_handles();
	}


}
