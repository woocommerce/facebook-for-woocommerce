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

		add_action( 'admin_head', [ $this, 'output_scripts' ] );
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
