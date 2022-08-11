<?php
/**
 * Facebook for WooCommerce
 */

namespace WooCommerce\Facebook\Framework\Plugin;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Compatibility Utility Class
 *
 * The unfortunate purpose of this class is to provide a single point of
 * compatibility functions for dealing with supporting multiple versions
 * of WooCommerce and various extensions.
 *
 * The expected procedure is to remove methods from this class, using the
 * latest ones directly in code, as support for older versions of WooCommerce
 * are dropped.
 *
 * Current Compatibility
 * + Core 3.0.9 - 3.7.x
 * + Subscriptions 2.2.x
 *
 * // TODO: move to /compatibility
 *
 * @since 2.0.0
 */
class Compatibility {
	/**
	 * Logs a doing_it_wrong message.
	 *
	 * @since 5.0.1
	 * @deprecated 5.5.0
	 *
	 * @param string $function function used
	 * @param string $message message to log
	 * @param string $version version the message was added in
	 */
	public static function wc_doing_it_wrong( $function, $message, $version ) {
		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_doing_it_wrong()' );
		wc_doing_it_wrong( $function, $message, $version );
	}

	/**
	 * Retrieves a list of the latest available WooCommerce versions.
	 *
	 * Excludes betas, release candidates and development versions.
	 * Versions are sorted from most recent to least recent.
	 *
	 * @since 5.4.1
	 *
	 * @return string[] array of semver strings
	 */
	public static function get_latest_wc_versions() {
		$latest_wc_versions = get_transient( 'sv_wc_plugin_wc_versions' );
		if ( ! is_array( $latest_wc_versions ) ) {
			/** @link https://codex.wordpress.org/WordPress.org_API */
			$wp_org_request = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json', [ 'timeout' => 1 ] );
			if ( is_array( $wp_org_request ) && isset( $wp_org_request['body'] ) ) {
				$plugin_info = json_decode( $wp_org_request['body'], true );
				if ( is_array( $plugin_info ) && ! empty( $plugin_info['versions'] ) && is_array( $plugin_info['versions'] ) ) {
					$latest_wc_versions = [];
					// reverse array as WordPress supplies oldest version first, newest last
					foreach ( array_keys( array_reverse( $plugin_info['versions'] ) ) as $wc_version ) {
						// skip trunk, release candidates, betas and other non-final or irregular versions
						if (
							is_string( $wc_version )
							&& '' !== $wc_version
							&& is_numeric( $wc_version[0] )
							&& false === strpos( $wc_version, '-' )
						) {
							$latest_wc_versions[] = $wc_version;
						}
					}
					set_transient( 'sv_wc_plugin_wc_versions', $latest_wc_versions, WEEK_IN_SECONDS );
				}
			}
		}
		return is_array( $latest_wc_versions ) ? $latest_wc_versions : [];
	}

	/**
	 * Gets the version of the currently installed WooCommerce.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null Woocommerce version number or null if undetermined
	 */
	public static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}


	/**
	 * Determines if the installed version of WooCommerce is less than 3.0.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @return bool
	 */
	public static function is_wc_version_lt_3_0() {

		wc_deprecated_function( __METHOD__, '5.5.0', __CLASS__ . '::is_wc_version_lt()' );

		return self::is_wc_version_lt( '3.0' );
	}


	/**
	 * Determines if the installed version of WooCommerce is 3.1 or greater.
	 *
	 * @since 4.6.5
	 * @deprecated 5.5.0
	 *
	 * @return bool
	 */
	public static function is_wc_version_gte_3_1() {

		wc_deprecated_function( __METHOD__, '5.5.0', __CLASS__ . '::is_wc_version_gte()' );

		return self::is_wc_version_gte( '3.1' );
	}


	/**
	 * Determines if the installed version of WooCommerce is equal or greater than a given version.
	 *
	 * @since 4.7.3
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_gte( $version ) {

		$wc_version = self::get_wc_version();

		return $wc_version && version_compare( $wc_version, $version, '>=' );
	}


	/**
	 * Determines if the installed version of WooCommerce is lower than a given version.
	 *
	 * @since 4.7.3
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_lt( $version ) {

		$wc_version = self::get_wc_version();

		return $wc_version && version_compare( $wc_version, $version, '<' );
	}

	/**
	 * Determines whether the enhanced admin is available.
	 *
	 * This checks both for WooCommerce v4.0+ and the underlying package availability.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	public static function is_enhanced_admin_available() {
		return self::is_wc_version_gte( '4.0' ) && function_exists( 'wc_admin_url' );
	}

	/**
	 * Normalizes a WooCommerce page screen ID.
	 *
	 * Needed because WordPress uses a menu title (which is translatable), not slug, to generate screen ID.
	 * See details in: https://core.trac.wordpress.org/ticket/21454
	 * TODO: Add WP version check when https://core.trac.wordpress.org/ticket/18857 is addressed {BR 2016-12-12}
	 *
	 * @since 4.6.0
	 *
	 * @param string $slug slug for the screen ID to normalize (minus `woocommerce_page_`)
	 * @return string normalized screen ID
	 */
	public static function normalize_wc_screen_id( $slug = 'wc-settings' ) {

		// The textdomain usage is intentional here, we need to match the menu title.
		$prefix = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		return $prefix . '_page_' . $slug;
	}


	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
	 *
	 * Based on ActionScheduler's compat wrapper for the same function:
	 * ActionScheduler_Compatibility::convert_hr_to_bytes()
	 *
	 * @link https://secure.php.net/manual/en/function.ini-get.php
	 * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 *
	 * @since 5.3.1
	 *
	 * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
	 * @return int An integer byte value.
	 */
	public static function convert_hr_to_bytes( $value ) {

		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {

			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {

			$bytes *= GB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'm' ) ) {

			$bytes *= MB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'k' ) ) {

			$bytes *= KB_IN_BYTES;
		}

		// deal with large (float) values which run into the maximum integer size
		return min( $bytes, PHP_INT_MAX );
	}
}
