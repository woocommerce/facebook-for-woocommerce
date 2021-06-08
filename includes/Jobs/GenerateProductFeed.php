<?php

namespace SkyVerge\WooCommerce\Facebook\Jobs;

use Automattic\WooCommerce\ActionSchedulerJobFramework\Proxies\ActionSchedulerInterface;
use Automattic\WooCommerce\ActionSchedulerJobFramework\Utilities\BatchQueryOffset;
use Exception;
use SkyVerge\WooCommerce\Facebook\Feed\FeedDataExporter;
use SkyVerge\WooCommerce\Facebook\Feed\FeedFileHandler;
use WC_Facebookcommerce;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class GenerateProductFeed
 *
 * @since 2.5.0
 */
class GenerateProductFeed extends AbstractChainedJob {

	use BatchQueryOffset, LoggingTrait;

	/**
	 * Option used to accumulate feed execution time.
	 *
	 * @var string
	 */
	const OPTION_FEED_GENERATION_TIME = 'facebook_for_woocommerce_feed_generation_time';

	/**
	 * Transient key to store feed generation start timestamp.
	 *
	 * @var string
	 */
	const TRANSIENT_FEED_GENERATION_START_TIME = 'facebook_for_woocommerce_feed_generation_start_time';

	/**
	 * Transient key to store feed generation end timestamp.
	 *
	 * @var string
	 */
	const TRANSIENT_FEED_GENERATION_END_TIME = 'facebook_for_woocommerce_feed_generation_end_time';

	/**
	 * Feed file creation and manipulation utility.
	 *
	 * @var FeedFileHandler $feed_file_handler.
	 */
	protected $feed_file_handler;

	/**
	 * Exports data from WC_Product to format recognized by the feed.
	 *
	 * @var FeedDataExporter $feed_data_exporter.
	 */
	protected $feed_data_exporter;

	/**
	 * Constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler   Action Scheduler facade.
	 * @param FeedFileHandler          $feed_file_handler  Feed file creation and manipulation handler.
	 * @param FeedDataExporter         $feed_data_exporter Handling of file data.
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, $feed_file_handler, $feed_data_exporter ) {
		parent::__construct( $action_scheduler );
		$this->feed_file_handler  = $feed_file_handler;
		$this->feed_data_exporter = $feed_data_exporter;
	}

	/**
	 * Called before starting the job.
	 */
	protected function handle_start() {
		$this->feed_file_handler->prepare_new_temp_file();
		$this->feed_file_handler->write_to_temp_file(
			$this->feed_data_exporter->generate_header()
		);
		// Reset the statistics counters.
		facebook_for_woocommerce()->get_tracker()->track_feed_file_generation_time( 0 );
		$this->track_feed_file_generation_start( time() );
		$this->track_feed_file_generation_time( 0 );
	}

	/**
	 * Handle processing a chain batch.
	 *
	 * @hooked {plugin_name}/jobs/{job_name}/chain_batch
	 *
	 * @param int   $batch_number The batch number for the new batch.
	 * @param array $args         The args for the job.
	 *
	 * @throws Exception On error. The failure will be logged by Action Scheduler and the job chain will stop.
	 */
	public function handle_batch_action( int $batch_number, array $args ) {
		$start_time = microtime( true );
		parent::handle_batch_action( $batch_number, $args );
		$generation_time = microtime( true ) - $start_time;

		// Add processing time to previously accumulated execution times.
		$accumulated_time  = get_option( self::OPTION_FEED_GENERATION_TIME, 0 );
		$accumulated_time += $generation_time;
		$this->track_feed_file_generation_time( $accumulated_time );
	}

	/**
	 * Called after the finishing the job.
	 */
	protected function handle_end() {
		$this->feed_file_handler->replace_feed_file_with_temp_file();
		$this->track_feed_file_generation_end( time() );
		facebook_for_woocommerce()->get_tracker()->track_feed_file_generation_time(
			$this->get_feed_file_generation_time()
		);
	}

	/**
	 * Processes a batch of items.
	 *
	 * @since 1.1.0
	 *
	 * @param array $items The items of the current batch.
	 * @param array $args  The args for the job.
	 *
	 * @throws Exception On error. The failure will be logged by Action Scheduler and the job chain will stop.
	 */
	protected function process_items( array $items, array $args ) {
		/*
		 * Pre-fetch full product objects.
		 * Variable products will be filtered out here since we don't need them for the feed. It's important to not
		 * filter out variable products in ::get_items_for_batch() because if a batch only contains variable products
		 * the job will end prematurely thinking it has nothing more to process.
		 */
		$products = wc_get_products(
			array(
				'type'    => array( 'simple', 'variation' ),
				'include' => $items,
				'orderby' => 'none',
			)
		);

		$processed_items = array();

		foreach ( $products as $product ) {
			// Check if product is enabled for synchronization.
			if ( ! facebook_for_woocommerce()->get_product_sync_validator( $product )->passes_all_checks() ) {
				continue;
			}
			$processed_items[] = $this->process_item( $product, $args );
		}

		$this->write_processed_items_to_feed( $processed_items );
	}

	/**
	 * After processing send items to the feed file.
	 *
	 * @param array $processed_items Array of product fields to write to the feed file.
	 */
	protected function write_processed_items_to_feed( $processed_items ) {
		// Check if we have any items to write.
		if ( empty( $processed_items ) ) {
			return;
		}
		$this->feed_file_handler->write_to_temp_file(
			$this->feed_data_exporter->format_items_for_feed( $processed_items )
		);
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
				FROM {$wpdb->posts} as post
				LEFT JOIN {$wpdb->posts} as parent ON post.post_parent = parent.ID
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

		return array_map( 'intval', $product_ids );
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
			return $this->feed_data_exporter->generate_row_data( $product );

		} catch ( Exception $e ) {
			$this->log(
				sprintf(
					'Error processing item #%d - %s',
					$product instanceof WC_Product ? $product->get_id() : 0,
					$e->getMessage()
				)
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
	public function get_batch_size(): int {
		/**
		 * Feed batch size filter.
		 *
		 * This filter allows modification of how many items will be processed in one batch during the feed file generation.
		 * Increasing the number of items per batch can potentially speed up processing on some of the sites.
		 * Bigger number means reducing the number of required batches, but at the same time increase the memory requirements for the process.
		 * Smaller number of items per batch means that the memory requirements are smaller for the process and the stability of the system is better.
		 * The number of required batches increases and the the total time for processing may be longer.
		 * Some, especially big sites with big products catalog, may want to increase this value in order to process the catalog faster.
		 * This requires careful approach, bumping the value too high may lead to out of memory issues.
		 *
		 * @since x.x.x
		 *
		 * @param int  $batch_size Size of the feed processing batch.
		 */
		return apply_filters( 'facebook_for_woocommerce_feed_generation_num_products_per_batch', 15 );
	}

	/**
	 * Track feed file generation time.
	 *
	 * @since x.x.x
	 * @param Int $time Feed generation execution length.
	 */
	public function track_feed_file_generation_time( $time ) {
		update_option( self::OPTION_FEED_GENERATION_TIME, $time );
	}

	/**
	 * Get feed generation execution length.
	 *
	 * @since x.x.x
	 */
	public function get_feed_file_generation_time() {
		get_option( self::OPTION_FEED_GENERATION_TIME, 0 );
	}

	/**
	 * Update transient with feed file generation start time.
	 *
	 * @since x.x.x
	 * @param Int $timestamp Time when the generation has been started.
	 */
	public function track_feed_file_generation_start( $timestamp ) {
		set_transient( self::TRANSIENT_FEED_GENERATION_START_TIME, $timestamp, DAY_IN_SECONDS );
	}

	/**
	 * Update transient with feed file generation end (in seconds).
	 *
	 * @since x.x.x
	 * @param Int $timestamp Time when the generation has been ended.
	 */
	public function track_feed_file_generation_end( $timestamp ) {
		set_transient( self::TRANSIENT_FEED_GENERATION_END_TIME, $timestamp, DAY_IN_SECONDS );
	}

}
