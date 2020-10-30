<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Locale;
use SkyVerge\WooCommerce\PluginFramework\v5_9_0;

/**
 * The Advertise settings screen object.
 */
class Advertise extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'advertise';


	/**
	 * Advertise settings constructor.
	 *
	 * @since 2.2.0-dev.1
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Advertise', 'facebook-for-woocommerce' );
		$this->title = __( 'Advertise', 'facebook-for-woocommerce' );

		$this->add_hooks();
	}


	/**
	 * Adds hooks.
	 *
	 * @since 2.2.0-dev.1
	 */
	private function add_hooks() {

		add_action( 'admin_head', [ $this, 'output_scripts' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}


	/**
	 * Enqueues assets for the current screen.
	 *
	 * @internal
	 *
	 * @since 2.2.0-dev.1
	 */
	public function enqueue_assets() {

		if ( ! $this->is_current_screen_page() ) {
			return;
		}

		wp_enqueue_style( 'wc-facebook-admin-advertise-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-advertise.css', [], \WC_Facebookcommerce::VERSION );
	}


	/**
	 * Outputs the LWI Ads script.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function output_scripts() {

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();

		if ( ! $connection_handler || ! $connection_handler->is_connected() || ! $this->is_current_screen_page() ) {
			return;
		}

		?>
		<script>
			window.fbAsyncInit = function() {
				FB.init( {
					appId            : '<?php echo esc_js( $connection_handler->get_client_id() ); ?>',
					autoLogAppEvents : true,
					xfbml            : true,
					version          : 'v8.0',
				} );
			};
		</script>
		<?php
	}


	/**
	 * Gets the LWI Ads configuration to output the FB iframes.
	 *
	 * @since 2.2.0-dev.1
	 *
	 * @return array
	 */
	private function get_lwi_ads_configuration_data() {

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();

		if ( ! $connection_handler || ! $connection_handler->is_connected() ) {
			return [];
		}

		return [
			'business_config' => [
				'business' => [
					'name' => $connection_handler->get_business_name(),
				],
			],
			'setup'           => [
				'external_business_id' => $connection_handler->get_external_business_id(),
				'timezone'             => $this->parse_timezone( wc_timezone_string(), wc_timezone_offset() ),
				'currency'             => get_woocommerce_currency(),
				'business_vertical'    => 'ECOMMERCE',
			],
			'repeat'          => false,
		];
	}


	/*
	 * Convert the given timezone string to a name if needed.
	 *
	 * @since 2.2.0-dev.1
	 *
	 * @param string $timezone_string Timezone string
	 * @param int|float $timezone_offset Timezone offset
	 * @return string timezone string
	 */
	private function parse_timezone( $timezone_string, $timezone_offset = 0 ) {

		// no need to look for the equivalent timezone
		if ( false !== strpos( $timezone_string, '/' ) ) {
			return $timezone_string;
		}

		// look up the timezones list based on the given offset
		$timezones_list = timezone_abbreviations_list();

		foreach ( $timezones_list as $timezone ) {
			foreach ( $timezone as $city ) {
				if ( isset( $city['offset'], $city['timezone_id'] ) && (int) $city['offset'] === (int) $timezone_offset ) {
					return $city['timezone_id'];
				}
			}
		}

		// fallback to default timezone
		return 'Etc/GMT';
	}


	/**
	 * Gets the LWI Ads SDK URL.
	 *
	 * @since 2.2.0-dev.1
	 *
	 * @return string
	 */
	private function get_lwi_ads_sdk_url() {

		$locale = get_user_locale();

		if ( ! Locale::is_supported_locale( $locale ) ) {
			$locale = Locale::DEFAULT_LOCALE;
		}

		return "https://connect.facebook.net/{$locale}/sdk.js";
	}


	/**
	 * Renders the screen HTML.
	 *
	 * The contents of the Facebook box will be populated by the LWI Ads script through iframes.
	 *
	 * @since 2.2.0-dev.1
	 */
	public function render() {

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();

		if ( ! $connection_handler || ! $connection_handler->is_connected() ) {

			printf(
				/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
				esc_html__( 'Please %1$sconnect your store%2$s to Facebook to create ads.', 'facebook-for-woocommerce' ),
				'<a href="' . esc_url( add_query_arg( [ 'tab' => Connection::ID ], facebook_for_woocommerce()->get_settings_url() ) ) . '">',
				'</a>'
			);

			return;
		}

		$fbe_extras = wp_json_encode( $this->get_lwi_ads_configuration_data() );

		?>
		<script async defer src="<?php echo esc_url( $this->get_lwi_ads_sdk_url() ); ?>"></script>
		<div
			class="fb-lwi-ads-creation"
			data-fbe-extras="<?php echo esc_attr( $fbe_extras ); ?>"
			data-fbe-scopes="ads_management"
			data-fbe-redirect-uri="https://mariner9.s3.amazonaws.com/"></div>
		<div
			class="fb-lwi-ads-insights"
			data-fbe-extras="<?php echo esc_attr( $fbe_extras ); ?>"
			data-fbe-scopes="ads_management"
			data-fbe-redirect-uri="https://mariner9.s3.amazonaws.com/"></div>
		<?php

		parent::render();
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.2.0-dev.1
	 *
	 * @return array
	 */
	public function get_settings() {

		return [];
	}


}
