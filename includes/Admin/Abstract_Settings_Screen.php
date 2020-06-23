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

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

defined( 'ABSPATH' ) or exit;

/**
 * The base settings screen object.
 */
abstract class Abstract_Settings_Screen {


	/** @var string screen ID */
	protected $id;

	/** @var string screen label, for display */
	protected $label;

	/** @var string screen title, for display */
	protected $title;

	/** @var string screen description, for display */
	protected $description;


	/**
	 * Renders the screen.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function render() {

		/**
		 * Filters the screen settings.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $settings settings
		 */
		$settings = (array) apply_filters( 'wc_facebook_admin_' . $this->get_id() . '_settings', $this->get_settings(), $this );

		if ( empty( $settings ) ) {
			return;
		}

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();
		$is_connected       = $connection_handler->is_connected();

		?>

		<?php if ( ! $is_connected && ! $connection_handler->has_previously_connected_fbe_1() && $this->get_disconnected_message() ) : ?>
			<div class="notice notice-info"><p><?php echo wp_kses_post( $this->get_disconnected_message() ); ?></p></div>
		<?php endif; ?>

		<form class="wc-facebook-settings <?php echo $is_connected ? 'connected' : 'disconnected'; ?>" method="post" id="mainform" action="" enctype="multipart/form-data">

			<?php woocommerce_admin_fields( $settings ); ?>

			<?php if ( $is_connected ) : ?>
				<input type="hidden" name="screen_id" value="<?php echo esc_attr( $this->get_id() ); ?>">
				<?php wp_nonce_field( 'wc_facebook_admin_save_' . $this->get_id() . '_settings' ); ?>
				<?php submit_button( __( 'Save changes', 'facebook-for-woocommerce' ), 'primary', 'save_' . $this->get_id() . '_settings' ); ?>
			<?php endif; ?>

		</form>

		<?php
	}


	/**
	 * Saves the settings.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @throws SV_WC_Plugin_Exception
	 */
	public function save() {

		woocommerce_update_options( $this->get_settings() );
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the settings.
	 *
	 * Should return a multi-dimensional array of settings in the format expected by \WC_Admin_Settings
	 * @return array
	 */
	abstract public function get_settings();


	/**
	 * Gets the message to display when the plugin is disconnected.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_disconnected_message() {

		return '';
	}


	/**
	 * Gets the screen ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets the screen label.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_label() {

		/**
		 * Filters the screen label.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $label screen label, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_label', $this->label, $this );
	}


	/**
	 * Gets the screen title.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_title() {

		/**
		 * Filters the screen title.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $title screen title, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_title', $this->title, $this );
	}


	/**
	 * Gets the screen description.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_description() {

		/**
		 * Filters the screen description.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $description screen description, for display
		 */
		return (string) apply_filters( 'wc_facebook_admin_settings_' . $this->get_id() . '_screen_description', $this->description, $this );
	}


}
