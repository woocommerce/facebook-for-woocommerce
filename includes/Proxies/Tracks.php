<?php
declare( strict_types=1 );

namespace SkyVerge\WooCommerce\Facebook\Proxies;

use WC_Site_Tracking;
use WC_Tracks;

/**
 * Wrapper (proxy) class for WC_Tracks from WooCommerce Core.
 *
 * Supplies a standard prefix to all events.
 *
 * @package Automattic\WooCommerce\Facebook\Proxies
 */
class Tracks {

	/**
	 * Record a tracks event.
	 *
	 * @param string $name       The event name to record.
	 * @param array  $properties Array of properties to include with the event.
	 */
	public static function record_event( string $name, array $properties = [] ) {
		if ( ! class_exists( WC_Tracks::class ) || ! WC_Site_Tracking::is_tracking_enabled() ) {
			return;
		}

		$prefix = 'facebook_for_woocommerce_';

		// In future we might want some standard default properties here.
		// GLA has a more extensive API, not sure if we need that.

		WC_Tracks::record_event( $prefix . $name, $properties );
	}
}
