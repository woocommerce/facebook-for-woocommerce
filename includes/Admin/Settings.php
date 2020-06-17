<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

/**
 * Admin settings handler.
 *
 * @since 2.0.0-dev.1
 */
class Settings {


	/** @var string base settings page ID */
	const PAGE_ID = 'wc-facebook';


	/** @var Abstract_Settings_Screen[] */
	private $screens;


	/**
	 * Settings constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

		$this->screens = [
			Settings_Screens\Connection::ID => new Settings_Screens\Connection(),
			Settings_Screens\Product_Sync::ID => new Settings_Screens\Product_Sync(),
			Settings_Screens\Messenger::ID => new Settings_Screens\Messenger(),
		];

		add_action( 'admin_menu', [ $this, 'add_menu_item' ] );

		add_action( 'wp_loaded', [ $this, 'save' ] );
	}


	/**
	 * Adds the Facebook menu item.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function add_menu_item() {

		add_submenu_page( 'woocommerce', __( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ), __( 'Facebook', 'facebook-for-woocommerce' ), 'manage_woocommerce', self::PAGE_ID, [ $this, 'render' ], 5 );
	}


	/**
	 * Renders the settings page.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function render() {

		$tabs        = $this->get_tabs();
		$current_tab = SV_WC_Helper::get_requested_value( 'tab' );

		if ( ! $current_tab ) {
			$current_tab = current( array_keys( $tabs ) );
		}

		$screen = $this->get_screen( $current_tab );

		?>

		<div class="wrap woocommerce">

			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">

				<?php foreach ( $tabs as $id => $label ) : ?>
					<a href="<?php echo esc_html( admin_url( 'admin.php?page=' . self::PAGE_ID . '&tab=' . esc_attr( $id ) ) ); ?>" class="nav-tab <?php echo $current_tab === $id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>

			</nav>

			<?php facebook_for_woocommerce()->get_message_handler()->show_messages(); ?>

			<?php if ( $screen ) : ?>

				<h1 class="screen-reader-text"><?php echo esc_html( $screen->get_title() ); ?></h1>
				<p><?php echo wp_kses_post( $screen->get_description() ); ?></p>

				<?php $screen->render(); ?>

			<?php endif; ?>

		</div>

		<?php
	}


	/**
	 * Saves the settings page.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function save() {

		if ( ! is_admin() || SV_WC_Helper::get_requested_value( 'page' ) !== self::PAGE_ID ) {
			return;
		}

		$screen = $this->get_screen( SV_WC_Helper::get_posted_value( 'screen_id' ) );

		if ( ! $screen ) {
			return;
		}

		if ( ! SV_WC_Helper::get_posted_value( 'save_' . $screen->get_id() . '_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have permission to save these settings.', 'facebook-for-woocommerce' ) );
		}

		check_admin_referer( 'wc_facebook_admin_save_' . $screen->get_id() . '_settings' );

		try {

			$screen->save();

			facebook_for_woocommerce()->get_message_handler()->add_message( __( 'Your settings have been saved.', 'facebook-for-woocommerce' ) );

		} catch ( SV_WC_Plugin_Exception $exception ) {

			facebook_for_woocommerce()->get_message_handler()->add_error( sprintf(
				/* translators: Placeholders: %s - user-friendly error message */
				__( 'Your settings could not be saved. %s', 'facebook-for-woocommerce' ),
				$exception->getMessage()
			) );
		}
	}


	/**
	 * Gets a settings screen object based on ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $screen_id desired screen ID
	 * @return Abstract_Settings_Screen|null
	 */
	public function get_screen( $screen_id ) {

		$screens = $this->get_screens();

		return ! empty( $screens[ $screen_id ] ) && $screens[ $screen_id ] instanceof Abstract_Settings_Screen ? $screens[ $screen_id ] : null;
	}


	/**
	 * Gets the available screens.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return Abstract_Settings_Screen[]
	 */
	public function get_screens() {

		/**
		 * Filters the admin settings screens.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $screens available screen objects
		 */
		$screens = (array) apply_filters( 'wc_facebook_admin_settings_screens', $this->screens, $this );

		// ensure no bogus values are added via filter
		$screens = array_filter( $screens, function( $value ) {

			return $value instanceof Abstract_Settings_Screen;

		} );

		return $screens;
	}


	/**
	 * Gets the tabs.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_tabs() {

		$tabs = [];

		foreach ( $this->get_screens() as $screen_id => $screen ) {
			$tabs[ $screen_id ] = $screen->get_label();
		}

		/**
		 * Filters the admin settings tabs.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $tabs tab data, as $id => $label
		 */
		return (array) apply_filters( 'wc_facebook_admin_settings_tabs', $tabs, $this );
	}


}
