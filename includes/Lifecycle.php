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
 * @since 1.10.0-dev.1
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @since 1.10.0-dev.1
	 *
	 * @param Framework\SV_WC_Plugin $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'1.10.0',
		];
	}


	/**
	 * Updates to v1.10.0.
	 *
	 * @since 1.10.0-dev.1
	 */
	protected function upgrade_to_1_10_0() {

		$values = get_option( 'woocommerce_facebookcommerce_settings', [] );

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
			if ( isset( $values[ $old_index ] ) ) {
				update_option( $new_option_name, $values[ $old_index ] );
			}
		}

		// migrate settings from woocommerce_facebookcommerce_settings
		$settings = [
			'fb_page_id'                       => \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID,
			'fb_pixel_id'                      => \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID,
			'fb_pixel_use_pii'                 => \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING,
			'is_messenger_chat_plugin_enabled' => \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER,
			'msger_chat_customization_locale'  => \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE,
		];

		$new_settings = [];

		foreach ( $settings as $old_index => $new_index ) {
			if ( isset( $values[ $old_index ] ) ) {
				$new_settings[ $new_index ] = $values[ $old_index ];
			}
		}

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', $new_settings );
	}


}
