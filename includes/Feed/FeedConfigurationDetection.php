<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Error;
use SkyVerge\WooCommerce\Facebook\Utilities\Heartbeat;
use SkyVerge\WooCommerce\Facebook\Feed\FeedFileHandler;

/**
 * A class responsible detecting feed configuration.
 */
class FeedConfigurationDetection {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// add_action( Heartbeat::DAILY, array( $this, 'track_data_source_feed_tracker_info' ) );
		add_action( 'admin_init', array( $this, 'track_data_source_feed_tracker_info' ) );
	}

	/**
	 * Get config settings for feed-based sync for WooCommerce Tracker.
	 *
	 * Gets various settings related to the feed, and data about recent uploads.
	 * This is formatted into an array of keys/values, and saved to a transient for inclusion in tracker snapshot.
	 *
	 * @throws Error
	 * @since x.x.x
	 * @return Array Key-value array of various configuration settings.
	 */
	public function get_data_source_feed_tracker_info() {
		$integration         = facebook_for_woocommerce()->get_integration();
		$graph_api           = $integration->get_graph_api();
		$integration_feed_id = $integration->get_feed_id();
		$catalog_id          = $integration->get_product_catalog_id();

		$info = array();

		// No catalog id. Most probably means that we don't have a valid connection.
		if ( '' === $catalog_id ) {
			facebook_for_woocommerce()->log( 'No catalog ID!' );
			return false;
		}

		// Get all feeds configured for the catalog.
		try {
			$feed_nodes = $this->get_feed_nodes_for_catalog( $catalog_id, $graph_api );
		} catch ( \Error $th ) {
			throw $th;
		}

		$info['feed_count'] = count( $feed_nodes );

		// Check if the catalog has any feed configured.
		if ( empty( $feed_nodes ) ) {
			facebook_for_woocommerce()->log( 'No feed nodes!' );
			return false;
		}

		// Determine which is the most active feed (recently updated).
		// Or "the" feed, if there is only one!
		$active_feed_metadata = null;
		foreach ( $feed_nodes as $feed ) {
			try {
				$metadata                       = $this->get_feed_metadata( $feed['id'], $graph_api );
				$metadata['latest_upload_time'] = strtotime( $metadata['latest_upload'] );
				if ( ! $active_feed_metadata ||
					( $metadata['latest_upload_time'] > $active_feed_metadata['latest_upload_time'] ) ) {
					$active_feed_metadata = $metadata;
				}
			} catch ( \Error $th) {
				throw $th;
			}
		}

		$active_feed['created_time']  = $active_feed_metadata['created_time'];
		$active_feed['product_count'] = $active_feed_metadata['product_count'];
		if ( $active_feed_metadata['schedule'] ) {
			$active_feed['schedule']['interval']       = $active_feed_metadata['schedule']['interval'];
			$active_feed['schedule']['interval_count'] = $active_feed_metadata['schedule']['interval_count'];
		}
		if ( $active_feed_metadata['update_schedule'] ) {
			$active_feed['update_schedule']['interval']       = $active_feed_metadata['update_schedule']['interval'];
			$active_feed['update_schedule']['interval_count'] = $active_feed_metadata['update_schedule']['interval_count'];
		}

		$info['active_feed'] = $active_feed;

		$latest_upload      = $active_feed_metadata['latest_upload'];
		$upload['end_time'] = $latest_upload['end_time'];

		// Get more detailed metadata about the most recent feed upload.
		try {
			$upload_metadata = $this->get_feed_upload_metadata( $active_feed_metadata['latest_upload']['id'], $graph_api );
		} catch ( \Error $th ) {
			throw $th;
		}
		$upload['error_count']         = $upload_metadata['error_count'];
		$upload['warning_count']       = $upload_metadata['warning_count'];
		$upload['num_persisted_items'] = $upload_metadata['num_persisted_items'];

		$info['active_feed']['latest_upload'] = $upload;

		facebook_for_woocommerce()->get_tracker()->track_facebook_feed_config( $info );

		return $info;
	}

	private function get_feed_nodes_for_catalog( $catalog_id, $graph_api ) {
		// Read all the feed configurations specified for the catalog.
		$response = $graph_api->read_feeds( $catalog_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( 'Reading catalog feeds error', $code );
		}

		$response_body = wp_remote_retrieve_body( $response );

		$body = json_decode( $response_body, true );
		return $body['data'];
	}

	private function get_feed_metadata( $feed_id, $graph_api ) {
		$response = $graph_api->read_feed_metadata( $feed_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( 'Error reading feed metadata', $code );
		}
		$response_body = wp_remote_retrieve_body( $response );
		return json_decode( $response_body, true );
	}

	private function get_feed_upload_metadata( $upload_id, $graph_api ) {
		$response = $graph_api->read_upload_metadata( $upload_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( 'Error reading feed upload metadata', $code );
		}
		$response_body = wp_remote_retrieve_body( $response );
		return json_decode( $response_body, true );
	}

}
