<?php

namespace SkyVerge\WooCommerce\Facebook\Jobs;

use Automattic\WooCommerce\ActionSchedulerJobFramework\AbstractChainedJob;
use Automattic\WooCommerce\ActionSchedulerJobFramework\Utilities\BatchQueryOffset;
use Exception;
use WC_Facebookcommerce;
use WP_Query;

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
		$product_args = [
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'post_type'      => [ 'product', 'product_variation' ],
			'posts_per_page' => $this->get_batch_size(),
			'offset'         => $this->get_query_offset( $batch_number ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];

		$query = new WP_Query( $product_args );
		return $query->posts;
	}

	/**
	 * Process a single item.
	 *
	 * @param string|int|array $item A single item from the get_items_for_batch() method.
	 * @param array            $args The args for the job.
	 *
	 * @throws Exception On error. The failure will be logged by Action Scheduler and the job chain will stop.
	 */
	protected function process_item( $item, array $args ) {
		try {
			$product = wc_get_product( $item );
			if ( ! $product ) {
				throw new Exception( 'Product not found.' );
			}

			// TODO

		} catch ( Exception $e ) {
			facebook_for_woocommerce()->log(
				sprintf(
					'Error processing item #%d - %s',
					$item,
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

}
