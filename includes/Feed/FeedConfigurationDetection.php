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
		add_action( 'admin_init', array( $this, 'has_valid_feed_config' ) );
		//add_action( Heartbeat::HOURLY, array( $this, 'check_feed_config' ) );
	}

	/**
	 * Check if we have a valid feed configuration.
	 *
	 * Steps:
	 * 1. Check if we have valid feed id.
	 *
	 * @since 2.6.0
	 */
	public function has_valid_feed_config() {
		$integration         = facebook_for_woocommerce()->get_integration();
		$graph_api           = $integration->get_graph_api();
		$integration_feed_id = $integration->get_feed_id();
		$catalog_id          = '206610441222887';
		$catalog_id          = $integration->get_product_catalog_id();

		// No catalog id. Most probably means that we don't have a valid connection.
		if ( '' === $catalog_id ) {
			return false;
		}

		// Get all feeds configured for the catalog.
		try {
			$feed_node = $this->get_feed_nodes_for_catalog( $catalog_id, $graph_api );
		} catch ( \Throwable $th ) {
			throw $th;
		}

		// Check if the catalog has any feed configured.
		if ( empty( $feed_nodes ) ) {
			return false;
		}

		// Check if any of the feeds is currently active.
		foreach ( $feed_nodes as $feed ) {
			try {
				$feed_information = $this->get_feed_information( $feed['id'], $graph_api );
			} catch ( \Throwable $th) {
				throw $th;
			}

			$feed_is_used     = $this->check_if_feed_recent_uploads( $feed_information );
			if ( ! $feed_is_used ) {
				// Check the next feed.
				continue;
			}

			/*
			 * Feed is used. Check if it is using a correct url.
			 */
			$url_is_correct = $this->is_feed_is_using_correct_url( $feed_information );
			if ( false === $url_is_correct ) {
				// URL is different, check next feed.
				continue;
			}

			// TODO check interval.
		}

		return false;
	}

	private function check_if_feed_recent_uploads( $feed_information ) {
		if ( empty( $feed_information['uploads'] ) ) {
			return false;
		}

		$current_time = time();
		foreach ( $feed_information['uploads'] as $upload ) {
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

	private function is_feed_is_using_correct_url( $feed_information ) {
		$feed_api_url = FeedFileHandler::get_feed_data_url();
		return $feed_information['url'] === $feed_api_url;
	}

	private function get_feed_nodes_for_catalog( $catalog_id, $graph_api ) {
		// Read all the feed configurations specified for the catalog.
		$response = $graph_api->read_feeds( $catalog_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( 'Reading catalog feeds error', $code );
		}

		$body       = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['data'];
	}

	private function get_feed_information( $feed_id, $graph_api ) {
		$response = $graph_api->read_feed_information( $feed_id );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new Error( 'Reading feed information error', $code );
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

}
