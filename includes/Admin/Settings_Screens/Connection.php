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
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Helper;

/**
 * The Connection settings screen object.
 */
class Connection extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'connection';


	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Connection', 'facebook-for-woocommerce' );
		$this->title = __( 'Connection', 'facebook-for-woocommerce' );

	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_settings() {

		return [

			[
				'title' => __( 'Debug', 'facebook-for-woocommerce' ),
				'type'  => 'title',
			],

			[
				'id'       => \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE,
				'title'    => __( 'Enable debug mode', 'facebook-for-woocommerce' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Log plugin events for debugging', 'facebook-for-woocommerce' ),
				'desc_tip' => __( 'Only enable this if you are experiencing problems with the plugin.', 'facebook-for-woocommerce' ),
				'default'  => 'no',
			],

			[ 'type' => 'sectionend' ],

		];
	}


}
