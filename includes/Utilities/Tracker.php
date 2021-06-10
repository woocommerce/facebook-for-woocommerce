<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Class for adding diagnostic info to WooCommerce Tracker snapshot.
 *
 * See https://woocommerce.com/usage-tracking/ for more information.
 *
 * @since 2.3.4
 */
class Tracker {

	/**
	 * Life time for transients used for temporary caching of values we want to add to tracker snapshot.
	 *
	 * @var string
	 */
	const TRANSIENT_WCTRACKER_LIFE_TIME = 2 * WEEK_IN_SECONDS;

	/**
	 * Transient key name; how long it took to generate the most recent feed file, or zero if it failed.
	 *
	 * @var string
	 */
	const TRANSIENT_WCTRACKER_FEED_GENERATION_TIME = 'facebook_for_woocommerce_wctracker_feed_generation_time';

	/**
	 * Transient key name; true if feed has been requested by Facebook.
	 *
	 * @var string
	 */
	const TRANSIENT_WCTRACKER_FEED_REQUESTED = 'facebook_for_woocommerce_wctracker_feed_requested';

	/**
	 * Transient key name; stores various FBE business settings.
	 *
	 * @var string
	 */
	const TRANSIENT_WCTRACKER_FBE_BUSINESS_CONFIG = 'facebook_for_woocommerce_wctracker_fbe_business_config';

	/**
	 * Transient key name; stores feed (data source) settings for catalog sync.
	 *
	 * @var string
	 */
	const TRANSIENT_WCTRACKER_FB_FEED_CONFIG = 'facebook_for_woocommerce_wctracker_fb_feed_config';

	/**
	 * Constructor.
	 *
	 * @since 2.3.4
	 */
	public function __construct() {
		add_filter(
			'woocommerce_tracker_data',
			array( $this, 'add_tracker_data' )
		);
	}

	/**
	 * Append our tracker properties.
	 *
	 * @param array $data The current tracker snapshot data.
	 * @return array $data Snapshot updated with our data.
	 * @since 2.3.4
	 */
	public function add_tracker_data( array $data = array() ) {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = array();
		}

		/**
		 * Is the site connected?
		 *
		 * @since 2.3.4
		 */
		$connection_is_happy = false;
		$connection_handler  = facebook_for_woocommerce()->get_connection_handler();
		if ( $connection_handler ) {
			$connection_is_happy = $connection_handler->is_connected() && ! get_transient( 'wc_facebook_connection_invalid' );
		}
		$data['extensions']['facebook-for-woocommerce']['is-connected'] = wc_bool_to_string( $connection_is_happy );

		/**
		 * What features are enabled on this site?
		 *
		 * @since 2.3.4
		 */
		$product_sync_enabled = facebook_for_woocommerce()->get_integration()->is_product_sync_enabled();
		$data['extensions']['facebook-for-woocommerce']['product-sync-enabled'] = wc_bool_to_string( $product_sync_enabled );
		$messenger_enabled = facebook_for_woocommerce()->get_integration()->is_messenger_enabled();
		$data['extensions']['facebook-for-woocommerce']['messenger-enabled'] = wc_bool_to_string( $messenger_enabled );

		/**
		 * How long did the last feed generation take (or did it fail - 0)?
		 *
		 * @since 2.6.0
		 */
		$feed_generation_time = get_transient( self::TRANSIENT_WCTRACKER_FEED_GENERATION_TIME );
		$data['extensions']['facebook-for-woocommerce']['feed-generation-time'] = floatval( $feed_generation_time );

		/**
		 * Has the feed file been requested since the last snapshot?
		 *
		 * @since 2.6.0
		 */
		$feed_file_requested = get_transient( self::TRANSIENT_WCTRACKER_FEED_REQUESTED );
		$data['extensions']['facebook-for-woocommerce']['feed-file-requested'] = wc_bool_to_string( $feed_file_requested );
		// Manually delete the transient. This prop tracks if feed has been requested _since last snapshot_.
		delete_transient( self::TRANSIENT_WCTRACKER_FEED_REQUESTED );

		/**
		 * Miscellaneous Facebook config settings.
		 *
		 * @since 2.6.0
		 */
		$config = get_transient( self::TRANSIENT_WCTRACKER_FBE_BUSINESS_CONFIG );
		$data['extensions']['facebook-for-woocommerce']['instagram-shopping-enabled'] = wc_bool_to_string( $config ?: $config->ig_shopping_enabled );
		$data['extensions']['facebook-for-woocommerce']['instagram-cta-enabled']      = wc_bool_to_string( $config ?: $config->ig_cta_enabled );

		/**
		 * Feed pull / upload settings configured in Facebook UI.
		 *
		 * @since 2.6.0
		 */
		$data['extensions']['facebook-for-woocommerce']['product-feed-config'] = get_transient( self::TRANSIENT_WCTRACKER_FB_FEED_CONFIG );

		return $data;
	}

	/**
	 * Update transient with feed file generation time (in seconds).
	 *
	 * Note this is used to clear the transient (set to -1) to track feed generation failure.
	 *
	 * @since 2.6.0
	 */
	public function track_feed_file_generation_time( $time_in_seconds ) {
		set_transient( self::TRANSIENT_WCTRACKER_FEED_GENERATION_TIME, $time_in_seconds, self::TRANSIENT_WCTRACKER_LIFE_TIME );
	}

	/**
	 * Store the fact that the feed has been requested by Facebook in a transient.
	 * This will later be added to next tracker snapshot.
	 *
	 * @since 2.6.0
	 */
	public function track_feed_file_requested() {
		set_transient( self::TRANSIENT_WCTRACKER_FEED_REQUESTED, true, self::TRANSIENT_WCTRACKER_LIFE_TIME );
	}

	/**
	 * Store some Facebook config settings for tracking.
	 *
	 * @param bool $ig_shopping_enabled True if Instagram Shopping is configured.
	 * @param bool $ig_cta_enabled True if `ig_cta` config option is enabled.
	 * @since 2.6.0
	 */
	public function track_facebook_business_config(
		bool $ig_shopping_enabled,
		bool $ig_cta_enabled
	) {
		$transient = array(
			'ig_shopping_enabled'   => $ig_shopping_enabled,
			'ig_cta_enabled'        => $ig_cta_enabled,
		);
		set_transient( self::TRANSIENT_WCTRACKER_FBE_BUSINESS_CONFIG, $transient, self::TRANSIENT_WCTRACKER_LIFE_TIME );
	}

	/**
	 * Store Facebook feed config for tracking.
	 *
	 * @param array $feed_settings Key-value array of settings to add to tracker snapshot.
	 * @since 2.6.0
	 */
	public function track_facebook_feed_config(
		array $feed_settings
	) {
		set_transient( self::TRANSIENT_WCTRACKER_FB_FEED_CONFIG, $feed_settings, self::TRANSIENT_WCTRACKER_LIFE_TIME );
	}
}
