<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * The Facebook for WooCommerce plugin lifecycle handler.
 *
 * @since 1.10.0
 *
 * @method \WC_Facebookcommerce get_plugin()
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @since 1.10.0
	 *
	 * @param Framework\SV_WC_Plugin $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'1.10.0',
			'1.10.1',
			'1.11.0',
			'1.11.3',
			'2.0.0',
		];
	}


	/**
	 * Migrates options from previous versions of the plugin, which did not use the Framework.
	 *
	 * @since 1.10.0
	 */
	protected function install() {

		/**
		 * Versions prior to 1.10.0 did not set a version option, so the upgrade method needs to be called manually.
		 * We do this by checking first if an old option exists, but a new one doesn't.
		 */
		if ( get_option( 'woocommerce_facebookcommerce_settings' ) && ! get_option( 'wc_facebook_page_access_token' ) ) {

			$this->upgrade( '1.9.15' );
		}
	}


	/**
	 * Upgrades to version 1.10.0.
	 *
	 * @since 1.10.0
	 */
	protected function upgrade_to_1_10_0() {

		$this->migrate_1_9_settings();
	}


	/**
	 * Migrates Facebook for WooCommerce options used in version 1.9.x to the options and settings used in 1.10.x.
	 *
	 * Some users who upgraded from 1.9.x to 1.10.0 ended up with an incomplete upgrade and could have configured the plugin from scratch after that.
	 * This routine will update the options and settings only if a previous value does not exists.
	 *
	 * @since 1.10.1
	 */
	private function migrate_1_9_settings() {

		$values = get_option( 'woocommerce_facebookcommerce_settings', [] );

		// preserve legacy values
		if ( false === get_option( 'woocommerce_facebookcommerce_legacy_settings' ) ) {
			update_option( 'woocommerce_facebookcommerce_legacy_settings', $values );
		}

		// migrate options from woocommerce_facebookcommerce_settings
		$options = [
			'fb_api_key'                       => \WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN,
			'fb_product_catalog_id'            => \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID,
			'fb_external_merchant_settings_id' => \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID,
			'fb_feed_id'                       => \WC_Facebookcommerce_Integration::OPTION_FEED_ID,
			'facebook_jssdk_version'           => \WC_Facebookcommerce_Integration::OPTION_JS_SDK_VERSION,
			'pixel_install_time'               => \WC_Facebookcommerce_Integration::OPTION_PIXEL_INSTALL_TIME,
		];

		foreach ( $options as $old_index => $new_option_name ) {

			if ( isset( $values[ $old_index ] ) && false === get_option( $new_option_name ) ) {

				$new_value = $values[ $old_index ];

				if ( 'pixel_install_time' === $old_index ) {

					// convert to UTC timestamp
					try {
						$pixel_install_time = \DateTime::createFromFormat( 'Y-m-d G:i:s', $new_value, new \DateTimeZone( wc_timezone_string() ) );
					} catch ( \Exception $e ) {
						$pixel_install_time = false;
					}

					$new_value = $pixel_install_time instanceof \DateTime ? $pixel_install_time->getTimestamp() : null;
				}

				update_option( $new_option_name, $new_value );
			}
		}

		$new_settings = get_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', [] );

		// migrate settings from woocommerce_facebookcommerce_settings
		$settings = [
			'fb_page_id'                                  => \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID,
			'fb_pixel_id'                                 => \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID,
			'fb_pixel_use_pii'                            => \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING,
			'is_messenger_chat_plugin_enabled'            => \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER,
			'msger_chat_customization_locale'             => \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE,
			'msger_chat_customization_greeting_text_code' => \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING,
			'msger_chat_customization_theme_color_code'   => \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX,
		];

		foreach ( $settings as $old_index => $new_index ) {

			if ( isset( $values[ $old_index ] ) && ! isset( $new_settings[ $new_index ] ) ) {
				$new_settings[ $new_index ] = $values[ $old_index ];
			}
		}

		// migrate settings from standalone options
		if ( ! isset( $new_settings[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC ] ) ) {

			$product_sync_enabled = empty( get_option( 'fb_disable_sync_on_dev_environment', 0 ) );

			$new_settings[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC ] = $product_sync_enabled ? 'yes' : 'no';
		}

		if ( ! isset( $new_settings[ \WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE ] ) ) {
			$new_settings[ \WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE ] = ! empty( get_option( 'fb_sync_short_description', 0 ) ) ? \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT : \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD;
		}

		if ( ! isset( $new_settings[ \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET ] ) ) {

			$autosync_time = get_option( 'woocommerce_fb_autosync_time' );
			$parsed_time   = ! empty( $autosync_time ) ? strtotime( $autosync_time ) : false;
			$resync_offset = null;

			if ( false !== $parsed_time ) {

				$midnight = ( new \DateTime() )->setTimestamp( $parsed_time )->setTime( 0, 0, 0 );

				$resync_offset = $parsed_time - $midnight->getTimestamp();
			}

			$new_settings[ \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET ] = $resync_offset;
		}

		// maybe remove old settings entries
		$old_indexes = array_merge( array_keys( $options ), array_keys( $settings ), [ 'fb_settings_heading', 'fb_upload_id', 'upload_end_time' ] );

		foreach ( $old_indexes as $old_index ) {
			unset( $new_settings[ $old_index ] );
		}

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', $new_settings );

		// schedule the next product resync action
		if ( $new_settings[ \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET ] && 'yes' === $new_settings[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC ] ) {

			$integration = $this->get_plugin()->get_integration();

			if ( ! $integration->is_resync_scheduled() ) {
				$integration->schedule_resync( $new_settings[ \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET ] );
			}
		}
	}


	/**
	 * Upgrades to version 1.10.1.
	 *
	 * @since 1.10.1
	 */
	protected function upgrade_to_1_10_1() {

		$this->migrate_1_9_settings();
	}


	/**
	 * Upgrades to version 1.11.0.
	 *
	 * @since 1.11.0
	 */
	protected function upgrade_to_1_11_0() {

		$settings = get_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', [] );

		// moves the upload ID to a standalone option
		if ( ! empty( $settings['fb_upload_id'] ) ) {
			$this->get_plugin()->get_integration()->update_upload_id( $settings['fb_upload_id'] );
		}
	}


	/**
	 * Upgrades to version 1.11.3.
	 *
	 * @since 1.11.3-dev.2
	 */
	protected function upgrade_to_1_11_3() {

		if ( $handler = $this->get_plugin()->get_background_disable_virtual_products_sync_instance() ) {

			// create_job() expects an non-empty array of attributes
			$handler->create_job( [ 'created_at' => current_time( 'mysql' ) ] );
			$handler->dispatch();
		}
	}


	/**
	 * Upgrades to version 2.0.0
	 *
	 * @since 2.0.0-dev.1
	 */
	protected function upgrade_to_2_0_0() {

		update_option( 'wc_facebook_has_connected_fbe_2', 'no' );
	}


}
