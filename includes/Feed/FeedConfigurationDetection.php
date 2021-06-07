<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Error;
use SkyVerge\WooCommerce\Facebook\Utilities\Heartbeat;

/**
 * A class responsible detecting feed configuration.
 */
class FeedConfigurationDetection {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( Heartbeat::DAILY, array( $this, 'track_data_source_feed_tracker_info' ) );
		add_action( 'init', array( $this, 'has_valid_feed_config' ) );
	}

	/**
	 * Store config settings for feed-based sync for WooCommerce Tracker.
	 *
	 * Gets various settings related to the feed, and data about recent uploads.
	 * This is formatted into an array of keys/values, and saved to a transient for inclusion in tracker snapshot.
	 * Note this does not send the data to tracker - this happens later (see Tracker class).
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function track_data_source_feed_tracker_info() {
		try {
			$info = $this->get_data_source_feed_tracker_info();
			facebook_for_woocommerce()->get_tracker()->track_facebook_feed_config( $info );
		} catch ( \Error $error ) {
			facebook_for_woocommerce()->log( 'Unable to detect valid feed configuration: ' . $error->getMessage() );
		}
	}

	/**
	 * Get config settings for feed-based sync for WooCommerce Tracker.
	 *
	 * @throws Error Catalog id missing.
	 * @return Array Key-value array of various configuration settings.
	 */
	private function get_data_source_feed_tracker_info() {
		$integration         = facebook_for_woocommerce()->get_integration();
		$graph_api           = $integration->get_graph_api();
		$integration_feed_id = $integration->get_feed_id();
		$catalog_id          = $integration->get_product_catalog_id();

		$info                 = array();
		$info['site-feed-id'] = $integration_feed_id;

		// No catalog id. Most probably means that we don't have a valid connection.
		if ( '' === $catalog_id ) {
			throw new Error( __( 'No catalog ID.', 'facebook-for-woocommerce' ) );
		}

		// Get all feeds configured for the catalog.
		$feed_nodes = $this->get_feed_nodes_for_catalog( $catalog_id, $graph_api );

		$info['feed-count'] = count( $feed_nodes );

		// Check if the catalog has any feed configured.
		if ( empty( $feed_nodes ) ) {
			throw new Error( __( 'No feed nodes for catalog.', 'facebook-for-woocommerce' ) );
		}

		/*
		 * We will only track settings for one feed config (for now at least).
		 * So we need to determine which is the most relevant feed.
		 * If there is only one, we use that.
		 * If one has the same ID as $integration_feed_id, we use that.
		 * Otherwise we pick the one that was most recently updated.
		 */
		$active_feed_metadata = null;
		foreach ( $feed_nodes as $feed ) {
			$metadata = $this->get_feed_metadata( $feed['id'], $graph_api );

			if ( $feed['id'] === $integration_feed_id ) {
				$active_feed_metadata = $metadata;
				break;
			}

			if ( ! array_key_exists( 'latest_upload', $metadata ) || ! array_key_exists( 'start_time', $metadata['latest_upload'] ) ) {
				continue;
			}
			$metadata['latest_upload_time'] = strtotime( $metadata['latest_upload']['start_time'] );
			if ( ! $active_feed_metadata ||
				( $metadata['latest_upload_time'] > $active_feed_metadata['latest_upload_time'] ) ) {
				$active_feed_metadata = $metadata;
			}
		}

		$active_feed['created-time']  = gmdate( 'Y-m-d H:i:s', strtotime( $active_feed_metadata['created_time'] ) );
		$active_feed['product-count'] = $active_feed_metadata['product_count'];

		/*
		 * Upload schedule settings can be in two keys:
		 * `schedule` => full replace of catalog with items in feed (including delete).
		 * `update_schedule` => append any new or updated products to catalog.
		 * These may both be configured; we will track settings for each individually (i.e. both).
		 * https://developers.facebook.com/docs/marketing-api/reference/product-feed/
		 */
		if ( array_key_exists( 'schedule', $active_feed_metadata ) ) {
			$active_feed['schedule']['interval']       = $active_feed_metadata['schedule']['interval'];
			$active_feed['schedule']['interval-count'] = $active_feed_metadata['schedule']['interval_count'];
		}
		if ( array_key_exists( 'update_schedule', $active_feed_metadata ) ) {
			$active_feed['update-schedule']['interval']       = $active_feed_metadata['update_schedule']['interval'];
			$active_feed['update-schedule']['interval-count'] = $active_feed_metadata['update_schedule']['interval_count'];
		}

		$info['active-feed'] = $active_feed;

		$latest_upload = $active_feed_metadata['latest_upload'];
		if ( array_key_exists( 'latest_upload', $active_feed_metadata ) ) {
			$upload = array();

			if ( array_key_exists( 'end_time', $latest_upload ) ) {
				$upload['end-time'] = gmdate( 'Y-m-d H:i:s', strtotime( $latest_upload['end_time'] ) );
			}

			// Get more detailed metadata about the most recent feed upload.
			$upload_metadata = $this->get_feed_upload_metadata( $latest_upload['id'], $graph_api );

			$upload['error-count']         = $upload_metadata['error_count'];
			$upload['warning-count']       = $upload_metadata['warning_count'];
			$upload['num-detected-items']  = $upload_metadata['num_detected_items'];
			$upload['num-persisted-items'] = $upload_metadata['num_persisted_items'];

			// True if the feed upload url (Facebook side) matches the feed endpoint URL and secret.
			// If it doesn't match, it's likely it's unused.
			$upload['url-matches-site-endpoint'] = wc_bool_to_string(
				FeedFileHandler::get_feed_data_url() === $upload_metadata['url']
			);

			$info['active-feed']['latest-upload'] = $upload;
		}

		return $info;
	}

	/**
	 * Check if we have a valid feed configuration.
	 *
	 * Steps:
	 * 1. Check if we have valid catalog id.
	 *  - No catalog id ( probably not connected ): false
	 * 2. Check if we have feed configured.
	 *  - No feeds configured ( we can configure automatically ): false
	 * 3. Loop over feed configurations.
	 *   4. Check if feed has recent uploads
	 *    - No recent uploads ( feed is not working correctly ): false
	 *   5. Check if feed uses correct url.
	 *    - Wrong url ( maybe different integration ): false
	 *   6. Check if feed id matches the one used by the site.
	 *    a) If site has no id stored maybe use this one.
	 *    b) If site has an id stored compare.
	 *       - Wrong id ( active feed from different integration ): false
	 * 7. Everything matches we have found a valid feed.
	 *
	 * For schedule checks we are only interested in `schedule` and not in `update_schedule`.
	 *
	 * @throws Error Partial feed configuration.
	 * @return bool True value means that we have a valid configuration.
	 *                         False means that we have no configuration at all.
	 * @since x.x.x
	 */
	public function has_valid_feed_config() {
		$integration         = facebook_for_woocommerce()->get_integration();
		$graph_api           = $integration->get_graph_api();
		$integration_feed_id = $integration->get_feed_id();
		$catalog_id          = $integration->get_product_catalog_id();

		// No catalog id. Most probably means that we don't have a valid connection.
		if ( '' === $catalog_id ) {
			throw new Error( __( 'No catalog ID.', 'facebook-for-woocommerce' ) );
		}

		// Check if our stored feed ( if we have one ) represents a valid feed configuration.
		if ( $integration_feed_id ) {
			try {
				$is_integration_feed_config_valid = $this->is_feed_config_valid( $integration_feed_id, $graph_api );
			} catch ( Error $th ) {
				throw $th;
			}
		}

		if ( $is_integration_feed_config_valid ) {
			// Our stored feed id represents a valid feed configuration.
			return true;
		}

		// Get all feeds configured for the catalog.
		try {
			$feed_nodes = $this->get_feed_nodes_for_catalog( $catalog_id, $graph_api );
		} catch ( Error $er ) {
			throw $er;
		}

		// Check if the catalog has any feed configured.
		if ( empty( $feed_nodes ) ) {
			return false;
		}

		// Check if any of the feeds is currently active.
		foreach ( $feed_nodes as $feed ) {

			try {
				$is_integration_feed_config_valid = $this->is_feed_config_valid( $feed['id'], $graph_api );
			} catch ( Error $er ) {
				throw $er;
			}
		}
		return false;
	}

	/**
	 * This function validates the Facebook feed for any configurations issues.
	 *
	 * @throws Error Feed not configured correctly.
	 * @param String                        $feed_id Facebook Feed ID.
	 * @param WC_Facebookcommerce_Graph_API $graph_api Facebook Graph handler instance.
	 * @since x.x.x
	 *
	 * @return bool True means that this feed is configured correctly
	 *              False means that we have no configuration at all.
	 */
	private function is_feed_config_valid( $feed_id, $graph_api ) {
		try {
			$feed_information = $this->get_feed_information( $feed_id, $graph_api );
		} catch ( Error $th ) {
			throw $th;
		}

		$feed_has_correct_schedule = $this->feed_has_correct_schedule( $feed_information );
		$feed_is_using_correct_url = $this->feed_is_using_correct_url( $feed_information );
		$feed_has_recent_uploads   = $this->feed_has_recent_uploads( $feed_information );

		return $feed_has_recent_uploads && $feed_is_using_correct_url && $feed_has_correct_schedule;
	}

	/**
	 * Check if feed schedule configuration matches our recommendation.
	 *
	 * @param Array $feed_information Feed configuration information.
	 * @since x.x.x
	 *
	 * @return bool Is the feed schedule configured correctly.
	 */
	private function feed_has_correct_schedule( $feed_information ) {
		$schedule = $feed_information['schedule'] ?? null;
		if ( null === $schedule ) {
			return false;
		}

		/**
		 * Filters what interval should be used for the scheduled evaluation.
		 * Allows for fine tuning the upload schedule.
		 *
		 * @param string $interval Interval used for schedule.
		 * @since x.x.x
		 */
		$feed_has_correct_interval = apply_filters( 'facebook_for_woocommerce_feed_interval', 'DAILY' ) === $schedule['interval'];

		/**
		 * Filters what interval count should be used for the scheduled evaluation.
		 * Allows for fine tuning the upload schedule.
		 *
		 * @param int $interval_count Interval count used for schedule.
		 * @since x.x.x
		 */
		$feed_has_correct_interval_count = apply_filters( 'facebook_for_woocommerce_feed_interval', 1 ) === $schedule['interval_count'];

		return $feed_has_correct_interval && $feed_has_correct_interval_count;
	}

	/**
	 * Does feed contains any recent uploads.
	 *
	 * @param Array $feed_information Feed configuration information.
	 * @since x.x.x
	 *
	 * @return bool Feed has valid uploads in the 2 weeks time span.
	 */
	private function feed_has_recent_uploads( $feed_information ) {
		if ( empty( $feed_information['uploads']['data'] ) ) {
			return false;
		}

		$current_time = time();
		foreach ( $feed_information['uploads']['data'] as $upload ) {
			$end_time = strtotime( $upload['end_time'] );

			/*
			 * Maximum interval is a weak.
			 * We check for two weeks to take into account a possible failure of the last upload.
			 * Highly unlikely but possible.
			 */
			if ( ( ( $end_time + 2 * WEEK_IN_SECONDS ) > $current_time ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine if the feed uses correct upload URL.
	 *
	 * @param Array $feed_information Feed configuration information.
	 * @since x.x.x
	 *
	 * @return bool True if this site URL matches feed configured URL. False otherwise.
	 */
	private function feed_is_using_correct_url( $feed_information ) {
		$schedule = $feed_information['schedule'] ?? null;
		if ( null === $schedule ) {
			return false;
		}
		$feed_api_url = FeedFileHandler::get_feed_data_url();
		return $schedule['url'] === $feed_api_url;
	}

	/**
	 * Given a Feed ID fetches feed configuration information from Facebook.
	 *
	 * @throws Error Could not fetch feed information.
	 * @param String                        $feed_id Facebook Feed ID.
	 * @param WC_Facebookcommerce_Graph_API $graph_api Facebook Graph handler instance.
	 * @since x.x.x
	 *
	 * @return array Feed configuration.
	 */
	private function get_feed_information( $feed_id, $graph_api ) {
		$response = $graph_api->read_feed_information( $feed_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( __( 'Reading feed information error.', 'facebook-for-woocommerce' ), $code );
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Given catalog id this function fetches all feed configurations defined for this catalog.
	 *
	 * @throws Error Feed configurations fetch was not successful.
	 * @param String                        $catalog_id Facebook Catalog ID.
	 * @param WC_Facebookcommerce_Graph_API $graph_api Facebook Graph handler instance.
	 *
	 * @return Array Array of feed configurations.
	 */
	private function get_feed_nodes_for_catalog( $catalog_id, $graph_api ) {
		// Read all the feed configurations specified for the catalog.
		$response = $graph_api->read_feeds( $catalog_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( __( 'Reading catalog feeds error.', 'facebook-for-woocommerce' ), $code );
		}

		$response_body = wp_remote_retrieve_body( $response );

		$body = json_decode( $response_body, true );
		return $body['data'];
	}

	/**
	 * Given feed id fetch this feed configuration metadata.
	 *
	 * @throws Error Feed metadata fetch was not successful.
	 * @param String                        $feed_id Facebook Feed ID.
	 * @param WC_Facebookcommerce_Graph_API $graph_api Facebook Graph handler instance.
	 *
	 * @return Array Array of feed configurations.
	 */
	private function get_feed_metadata( $feed_id, $graph_api ) {
		$response = $graph_api->read_feed_metadata( $feed_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( __( 'Error reading feed metadata.', 'facebook-for-woocommerce' ), $code );
		}
		$response_body = wp_remote_retrieve_body( $response );
		return json_decode( $response_body, true );
	}

	/**
	 * Given upload id fetch this upload execution metadata.
	 *
	 * @throws Error Upload metadata fetch was not successful.
	 * @param String                        $upload_id Facebook Feed upload ID.
	 * @param WC_Facebookcommerce_Graph_API $graph_api Facebook Graph handler instance.
	 *
	 * @return Array Array of feed configurations.
	 */
	private function get_feed_upload_metadata( $upload_id, $graph_api ) {
		$response = $graph_api->read_upload_metadata( $upload_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( __( 'Error reading feed upload metadata.', 'facebook-for-woocommerce' ), $code );
		}
		$response_body = wp_remote_retrieve_body( $response );
		return json_decode( $response_body, true );
	}

}
