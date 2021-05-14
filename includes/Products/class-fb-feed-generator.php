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

	const FEED_SCHEDULE_ACTION = 'wc_facebook_for_woocommerce_feed_schedule_action';

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

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
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

}
