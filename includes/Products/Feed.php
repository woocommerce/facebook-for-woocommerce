<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\Utilities\Heartbeat;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * The main product feed handler.
 *
 * This will eventually replace \WC_Facebook_Product_Feed as we refactor and move its functionality here.
 *
 * @since 1.11.0
 */
class Feed {


	/** @var string the action callback for generating a feed */
	const GENERATE_FEED_ACTION = 'wc_facebook_regenerate_feed';

	/** @var string the action slug for getting the product feed */
	const REQUEST_FEED_ACTION = 'wc_facebook_get_feed_data';

	/** @var string the WordPress option name where the secret included in the feed URL is stored */
	const OPTION_FEED_URL_SECRET = 'wc_facebook_feed_url_secret';


	/**
	 * Feed constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {

		// add the necessary action and filter hooks
		$this->add_hooks();
	}


	/**
	 * Adds the necessary action and filter hooks.
	 *
	 * @since 1.11.0
	 */
	private function add_hooks() {

		// schedule the recurring feed generation
		add_action( Heartbeat::HOURLY, array( $this, 'schedule_feed_generation' ) );

		// regenerate the product feed
		add_action( self::GENERATE_FEED_ACTION, array( $this, 'regenerate_feed' ) );

		// handle the feed data request
		add_action( 'woocommerce_api_' . self::REQUEST_FEED_ACTION, array( $this, 'handle_feed_data_request' ) );
	}


	/**
	 * Handles the feed data request.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 */
	public function handle_feed_data_request() {

		\WC_Facebookcommerce_Utils::log( 'Facebook is requesting the product feed.' );
		facebook_for_woocommerce()->get_tracker()->track_feed_file_requested();

		$feed_handler = new \WC_Facebook_Product_Feed();
		$file_path    = $feed_handler->get_file_path();

		// regenerate if the file doesn't exist
		if ( ! empty( $_GET['regenerate'] ) || ! file_exists( $file_path ) ) {
			$feed_handler->generate_feed();
		}

		try {

			// bail early if the feed secret is not included or is not valid
			if ( self::get_feed_secret() !== Framework\SV_WC_Helper::get_requested_value( 'secret' ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Invalid feed secret provided.', 401 );
			}

			// bail early if the file can't be read
			if ( ! is_readable( $file_path ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'File is not readable.', 404 );
			}

			// set the download headers
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length:' . filesize( $file_path ) );

			$file = @fopen( $file_path, 'rb' );

			if ( ! $file ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Could not open feed file.', 500 );
			}

			// fpassthru might be disabled in some hosts (like Flywheel)
			if ( $this->is_fpassthru_disabled() || ! @fpassthru( $file ) ) {

				\WC_Facebookcommerce_Utils::log( 'fpassthru is disabled: getting file contents' );

				$contents = @stream_get_contents( $file );

				if ( ! $contents ) {
					throw new Framework\SV_WC_Plugin_Exception( 'Could not get feed file contents.', 500 );
				}

				echo $contents;
			}
		} catch ( \Exception $exception ) {

			\WC_Facebookcommerce_Utils::log( 'Could not serve product feed. ' . $exception->getMessage() . ' (' . $exception->getCode() . ')' );

			status_header( $exception->getCode() );
		}

		exit;
	}


	/**
	 * Regenerates the product feed.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 * @since 2.6.6 Enable new feed generation code if requested.
	 */
	public function regenerate_feed() {
		// Maybe use new ( experimental ), feed generation framework.
		if ( facebook_for_woocommerce()->get_integration()->is_new_style_feed_generation_enabled() ) {
			$generate_feed_job = facebook_for_woocommerce()->job_manager->generate_product_feed_job;
			$generate_feed_job->queue_start();
		} else {
			$feed_handler = new \WC_Facebook_Product_Feed();
			$feed_handler->generate_feed();
		}
	}

	/**
	 * Schedules the recurring feed generation.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 */
	public function schedule_feed_generation() {
		$integration   = facebook_for_woocommerce()->get_integration();
		$configured_ok = $integration && $integration->is_configured();

		// Only schedule feed job if store has not opted out of product sync.
		$store_allows_sync = $configured_ok && $integration->is_product_sync_enabled();
		// Only schedule if has not opted out of feed generation (e.g. large stores).
		$store_allows_feed = $configured_ok && $integration->is_legacy_feed_file_generation_enabled();
		if ( ! $store_allows_sync || ! $store_allows_feed ) {
			as_unschedule_all_actions( self::GENERATE_FEED_ACTION );
			return;
		}

		/**
		 * Filters the frequency with which the product feed data is generated.
		 *
		 * @since 1.11.0
		 * @since 2.5.0 Feed generation interval increased to 24h.
		 *
		 * @param int $interval the frequency with which the product feed data is generated, in seconds. Defaults to every 15 minutes.
		 */
		$interval = apply_filters( 'wc_facebook_feed_generation_interval', DAY_IN_SECONDS );

		if ( ! as_next_scheduled_action( self::GENERATE_FEED_ACTION ) ) {
			as_schedule_recurring_action( time(), max( 2, $interval ), self::GENERATE_FEED_ACTION, array(), facebook_for_woocommerce()->get_id_dasherized() );
		}
	}


	/**
	 * Checks whether fpassthru has been disabled in PHP.
	 *
	 * Helper method, do not open to public.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	private function is_fpassthru_disabled() {

		$disabled = false;

		if ( function_exists( 'ini_get' ) ) {

			$disabled_functions = @ini_get( 'disable_functions' );

			$disabled = is_string( $disabled_functions ) && in_array( 'fpassthru', explode( ',', $disabled_functions ), false );
		}

		return $disabled;
	}


	/**
	 * Gets the URL for retrieving the product feed data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_data_url() {

		$query_args = array(
			'wc-api' => self::REQUEST_FEED_ACTION,
			'secret' => self::get_feed_secret(),
		);

		return add_query_arg( $query_args, home_url( '/' ) );
	}


	/**
	 * Gets the secret value that should be included in the Feed URL.
	 *
	 * Generates a new secret and stores it in the database if no value is set.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_secret() {

		$secret = get_option( self::OPTION_FEED_URL_SECRET, '' );

		if ( ! $secret ) {

			$secret = wp_hash( 'products-feed-' . time() );

			update_option( self::OPTION_FEED_URL_SECRET, $secret );
		}

		return $secret;
	}


}
