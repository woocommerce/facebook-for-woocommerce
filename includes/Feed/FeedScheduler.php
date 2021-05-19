<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SkyVerge\WooCommerce\Facebook\Utilities\Heartbeat;

/**
 * A class responsible for setting up feed generation schedule.
 */
class FeedScheduler {

	const FEED_SCHEDULE_ACTION = 'facebook_for_woocommerce_start_feed_generation';

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
		add_action( Heartbeat::HOURLY , array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
	}

	/**
	 * Schedule next feed generation if not scheduled already.
	 *
	 * @since 2.6.0
	 */
	public function maybe_schedule_feed_generation() {
		if ( false !== as_next_scheduled_action( self::FEED_SCHEDULE_ACTION ) ) {
			return;
		}
		$timestamp = strtotime( 'today midnight +1 day' );
		as_schedule_single_action( $timestamp, self::FEED_SCHEDULE_ACTION );
	}

	/**
	 * Feed file generation entry point. Triggering queue_start triggers the
	 * feed file generation job. Whole process is handled by the job class.
	 *
	 * @since 2.6.0
	 */
	public function prepare_feed_generation() {
		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$generate_feed_job->queue_start();
	}

}
