<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SkyVerge\WooCommerce\Facebook\Utilities\Heartbeat;
use SkyVerge\WooCommerce\Facebook\Jobs\GenerateProductFeed;

/**
 * A class responsible for setting up feed generation schedule.
 */
class FeedScheduler {

	const FEED_SCHEDULE_ACTION = 'facebook_for_woocommerce_start_feed_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
	}

	/**
	 * Schedule next feed generation if not scheduled already.
	 *
	 * @since 2.6.0
	 */
	public function maybe_schedule_feed_generation() {
		$integration = facebook_for_woocommerce()->get_integration();
		$feed_id     = $integration->get_feed_id();
		if ( '' === $feed_id ) {
			// No feed id, no reason to generate the feed file.
			return;
		}
		if ( false !== as_next_scheduled_action( self::FEED_SCHEDULE_ACTION ) ) {
			// Feed generation already scheduled.
			return;
		}

		// Default start time.
		$generation_start_time = strtotime( 'today midnight +1 day' );

		// Calculate better time for the next feed generation.
		$feed_info = get_transient( FeedConfigurationDetection::TRANSIENT_ACTIVE_FEED_METADATA );
		if ( false !== $feed_info ) {
			try {
				$next_fetch = $this->convert_schedule_to_next_execution_timestamp( $feed_info['schedule'] );
				$time_spent = $this->get_feed_file_generation_wall_time();

				// Calculate the time when we should start the feed file generation to finish before the next upload request.
				$generation_start_time = $next_fetch - $time_spent;

				if ( $generation_start_time < time() ) {
					/*
					 * The generation time is probably too long. Or the site was idle for some time.
					 * Moving the generation time even further would not make sense.
					 * In case when the generation time is too long we would not finish generating before the next cycle should start.
					 * In case when the site was idle the next scheduled feed generation should be OK.
					 */
					facebook_for_woocommerce()->log(
						__( 'Could not schedule feed generation in advance.', 'facebook-for-woocommerce' )
					);
				}
			} catch ( \Throwable $th ) {
				facebook_for_woocommerce()->log(
					__( 'Could not calculate better feed schedule time, using default.', 'facebook-for-woocommerce' )
				);
				facebook_for_woocommerce()->log( $th->getMessage() );
			}
		}

		as_schedule_single_action( $generation_start_time, self::FEED_SCHEDULE_ACTION );
	}

	/**
	 * Get feed generation wall time and adjust for fluctuations.
	 *
	 * @since x.x.x
	 * @return int Feed generation wall time in seconds.
	 */
	private function get_feed_file_generation_wall_time() {
		$time_spent = GenerateProductFeed::feed_file_generation_wall_time();
		if ( 0 === $time_spent ) {
			// We have no data yet or there is something wrong. Just guess that it will take 1h.
			$time_spent = HOUR_IN_SECONDS;
		}
		// Adjust for uncertainty by 1.5 factor.
		$time_spent = (int) ( $time_spent * 1.5 );

		return $time_spent;
	}

	/**
	 * Given a feed schedule configuration calculate when the next feed fetch will happen.
	 *
	 * @since x.x.x
	 * @param array $schedule Facebook feed schedule.
	 * @return int Next feed fetch timestamp.
	 */
	private function convert_schedule_to_next_execution_timestamp( $schedule ) {
		switch ( $schedule['interval'] ) {
			case 'HOURLY':
				$start    = new \DateTime( "today  +{$schedule['hour']} hours +{$schedule['minute']} minutes", new \DateTimeZone( $schedule['timezone'] ) );
				$interval = HOUR_IN_SECONDS;
				break;
			case 'DAILY':
				$start    = new \DateTime( "today  +{$schedule['hour']} hours +{$schedule['minute']} minutes", new \DateTimeZone( $schedule['timezone'] ) );
				$interval = DAY_IN_SECONDS;
				break;
			case 'WEEKLY':
				$start    = new \DateTime( "next {$schedule['day_of_week']} +{$schedule['hour']} hours +{$schedule['minute']} minutes", new \DateTimeZone( $schedule['timezone'] ) );
				$interval = WEEK_IN_SECONDS;
				break;
			default:
				// We should never get here but in case we did let's just guess.
				$start    = new \DateTime( 'today' );
				$interval = HOUR_IN_SECONDS;
				facebook_for_woocommerce()->log( __( 'Unrecognized schedule interval', 'facebook-for-woocommerce' ) );
				break;
		}

		// Check if we are not past the start.
		$now        = time();
		$next_fetch = (int) $start->format( 'U' );
		if ( $next_fetch > $now ) {
			// The next fetch is in the future we can return.
			return $next_fetch;
		}

		// Move to the next scheduled fetch that is past now.
		$next_fetch += ceil( (float) ( $now - $next_fetch ) / ( $interval * $schedule['interval_count'] ) ) * ( $interval * $schedule['interval_count'] );

		return $next_fetch;
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
