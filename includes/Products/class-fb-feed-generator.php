<?php
/**
 * Handles Facebook Product feed CSV file generation.
 *
 * @version 2.5.0
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

use SkyVerge\WooCommerce\Facebook\Feed\FeedDataExporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FB_Feed_Generator Class.
 */
class FB_Feed_Generator {

	const FEED_SCHEDULE_ACTION    = 'wc_facebook_for_woocommerce_feed_schedule_action';
	const FEED_AJAX_GENERATE_FEED = 'facebook_for_woocommerce_do_ajax_feed';
	const FEED_GENERATION_NONCE   = 'wc_facebook_for_woocommerce_feed_generation_nonce';
	const FEED_FILE_INFO          = 'wc_facebook_for_woocommerce_feed_file_info';
	const OPTION_FEED_URL_SECRET  = 'wc_facebook_feed_url_secret';
	const FEED_NAME               = 'Facebook For WooCommerce Feed.';

	/**
	 * Should meta be exported?
	 *
	 * @var boolean
	 */
	protected $enable_meta_export = false;

	/**
	 * Products belonging to what category should be exported.
	 *
	 * @var string
	 */
	protected $product_category_to_export = array();

	// Refactor feed handler into this class;
	protected $feed_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_handler = new \WC_Facebook_Product_Feed();
		add_action( 'admin_init', array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
		add_action( 'wp_ajax_' . self::FEED_AJAX_GENERATE_FEED, array( $this, 'ajax_feed_handle' ) );
	}

	public function maybe_schedule_feed_generation() {
		if ( false !== as_next_scheduled_action( self::FEED_SCHEDULE_ACTION ) ) {
			return;
		}
		$timestamp = strtotime( 'today midnight +1 day' );
		as_schedule_single_action( $timestamp, self::FEED_SCHEDULE_ACTION );
	}

	public function prepare_feed_generation() {
		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$generate_feed_job->queue_start();
	}

	public function ajax_feed_handle() {
		if ( 'true' === $_POST['generate'] ) {
			$this->prepare_feed_generation();
		}

		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$processing_count  = FeedDataExporter::get_number_of_items_for_processing();
		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$processed         = $generate_feed_job->get_number_of_items_processed();
		$progress          = $processing_count ? intval( ( $processed / $processing_count ) * 100 ) : 0;

		$response = array(
			'done'       => ! $generate_feed_job->is_running(),
			'percentage' => $progress,
			'total'      => $processing_count,
			'processed'  => $processed,
			'file'       => get_option( self::FEED_FILE_INFO, null ),
		);

		wp_send_json_success( $response );
	}

}
