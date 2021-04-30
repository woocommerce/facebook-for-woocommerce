<?php

namespace SkyVerge\WooCommerce\Facebook\Jobs;

use Automattic\WooCommerce\ActionSchedulerJobFramework\AbstractChainedJob;
use Automattic\WooCommerce\ActionSchedulerJobFramework\Utilities\BatchQueryOffset;
use Exception;
use WC_Facebookcommerce;
use WC_Product;

/**
 * Class GenerateProductFeed
 *
 * @since 2.5.0
 */
class GenerateProductFeed extends AbstractChainedJob {

	use BatchQueryOffset;

	/**
	 * Called before starting the job.
	 */
	protected function handle_start() {
		// Optionally override this method in child class.
	}

	/**
	 * Called after the finishing the job.
	 */
	protected function handle_end() {
		// Optionally override this method in child class.
	}

	/**
	 * Get a set of items for the batch.
	 *
	 * NOTE: when using an OFFSET based query to retrieve items it's recommended to order by the item ID while
	 * ASCENDING. This is so that any newly added items will not disrupt the query offset.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for the job.
	 *
	 * @throws Exception On error. The failure will be logged by Action Scheduler and the job chain will stop.
	 */
	protected function get_items_for_batch( int $batch_number, array $args ): array {
		global $wpdb;

		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post.ID
				FROM wp_posts as post
				LEFT JOIN wp_posts as parent ON post.post_parent = parent.ID
				WHERE
					( post.post_type = 'product_variation' AND parent.post_status = 'publish' )
				OR
					( post.post_type = 'product' AND post.post_status = 'publish' )
				ORDER BY post.ID ASC
				LIMIT %d OFFSET %d",
				$this->get_batch_size(),
				$this->get_query_offset( $batch_number )
			)
		);

		// Ensure some products were selected before calling wc_get_products()
		if ( empty( $product_ids ) ) {
			return [];
		}

		$product_ids = array_map( 'intval', $product_ids );

		return wc_get_products(
			[
				'type'    => [ 'simple', 'variation' ],
				'include' => $product_ids,
				'orderby' => 'none',
			]
		);
	}

	/**
	 * Process a single item.
	 *
	 * @param WC_Product $product A single item from the get_items_for_batch() method.
	 * @param array      $args The args for the job.
	 *
	 * @throws Exception On error. The failure will be logged by Action Scheduler and the job chain will stop.
	 */
	protected function process_item( $product, array $args ) {
		try {
			if ( ! $product ) {
				throw new Exception( 'Product not found.' );
			}

			facebook_for_woocommerce()->log(
				$product->get_id(),
				WC_Facebookcommerce::PLUGIN_ID . '_generate_feed_test'
			);

		} catch ( Exception $e ) {
			facebook_for_woocommerce()->log(
				sprintf(
					'Error processing item #%d - %s',
					$product instanceof WC_Product ? $product->get_id() : 0,
					$e->getMessage()
				),
				WC_Facebookcommerce::PLUGIN_ID . '_generate_feed'
			);
		}
	}

	/**
	 * Get the name/slug of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'generate_feed';
	}

	/**
	 * Get the name/slug of the plugin that owns the job.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return WC_Facebookcommerce::PLUGIN_ID;
	}

	/**
	 * Get the job's batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return 15;
	}

}
