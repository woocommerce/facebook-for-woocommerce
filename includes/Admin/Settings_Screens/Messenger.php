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
use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration;
use SkyVerge\WooCommerce\Facebook\Locale;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

/**
 * The Messenger settings screen object.
 */
class Messenger extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'messenger';


	/** @var null|Configuration\Messenger */
	private $remote_configuration;


	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Messenger', 'facebook-for-woocommerce' );
		$this->title = __( 'Messenger', 'facebook-for-woocommerce' );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'woocommerce_admin_field_messenger_locale',   [ $this, 'render_locale_field' ] );
		add_action( 'woocommerce_admin_field_messenger_greeting', [ $this, 'render_greeting_field' ] );
	}


	/**
	 * Enqueues the assets.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets() {

		// TODO: empty for now, until we add more robust Messenger settings {CW 2020-06-17}
	}


	/**
	 * Renders the custom locale field.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @param array $field field data
	 */
	public function render_locale_field( $field ) {

		if ( ! $this->remote_configuration ) {
			return;
		}

		$configured_locale = $this->remote_configuration->get_default_locale();
		$supported_locales = Locale::get_supported_locales();

		if ( ! empty( $supported_locales[ $configured_locale ] ) ) {
			$configured_locale = $supported_locales[ $configured_locale ];
		}

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
				<p>
					<?php echo esc_html( $configured_locale ); ?>
				</p>
			</td>
		</tr>
		<?php
	}


	/**
	 * Renders the custom greeting field.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @param array $field field data
	 */
	public function render_greeting_field( $field ) {

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
				<p>
					<?php printf(
						/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						esc_html__( '%1$sClick here%2$s to manage your Messenger greeting and colors.', 'facebook-for-woocommerce' ),
						'<a href="' . esc_url( facebook_for_woocommerce()->get_connection_handler()->get_manage_url() ) . '" target="_blank">', '</a>'
					); ?>
				</p>
			</td>
		</tr>
		<?php
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings() {

		$is_enabled = $this->remote_configuration && $this->remote_configuration->is_enabled();

		$settings = [

			[
				'title' => __( 'Messenger', 'facebook-for-woocommerce' ),
				'type'  => 'title',
			],

			[
				'id'      => \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER,
				'title'   => __( 'Enable Messenger', 'facebook-for-woocommerce' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Enable and customize Facebook Messenger on your store', 'facebook-for-woocommerce' ),
				'default' => 'no',
				'value'   => $is_enabled ? 'yes' : 'no',
			],
		];

		// only add the static configuration display if messenger is enabled
		if ( $is_enabled ) {

			$settings[] = [
				'title' => __( 'Language', 'facebook-for-woocommerce' ),
				'type'  => 'messenger_locale',
			];

			$settings[] = [
				'title' => __( 'Greeting & Colors', 'facebook-for-woocommerce' ),
				'type'  => 'messenger_greeting',
			];
		}

		$settings[] = [ 'type' => 'sectionend' ];

		return $settings;
	}


	/**
	 * Gets the "disconnected" message.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_disconnected_message() {

		return sprintf(
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			__( 'Please %1$sconnect to Facebook%2$s to enable and manage Facebook Messenger.', 'facebook-for-woocommerce' ),
			'<a href="' . esc_url( facebook_for_woocommerce()->get_connection_handler()->get_connect_url() ) . '">', '</a>'
		);
	}


	/**
	 * Renders the settings page.
	 *
	 * This is overridden to pull the latest FBE configuration so the settings can be populated correctly.
	 *
	 * @since 2.0.0
	 */
	public function render() {

		// if not connected, don't try and retrieve any settings and just fall back to standard display
		if ( ! facebook_for_woocommerce()->get_connection_handler()->is_connected() ) {
			parent::render();
			return;
		}

		$plugin = facebook_for_woocommerce();

		try {

			$response = $plugin->get_api()->get_business_configuration( $plugin->get_connection_handler()->get_external_business_id() );

			$configuration = $response->get_messenger_configuration();

			if ( ! $configuration ) {
				throw new Framework\SV_WC_API_Exception( 'Could not retrieve latest messenger configuration' );
			}

			update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, wc_bool_to_string( $configuration->is_enabled() ) );

			if ( $default_locale = $configuration->get_default_locale() ) {
				update_option( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE, $default_locale );
			}

			// set the remote configuration so other methods can use its values
			$this->remote_configuration = $configuration;

			parent::render();

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			?>
			<div class="notice notice-error">
				<p>
					<?php printf(
					/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						esc_html__( 'There was an error communicating with the Facebook Business Extension. %1$sClick here%2$s to manage your Messenger settings.', 'facebook-for-woocommerce' ),
						'<a href="' . esc_url( $plugin->get_connection_handler()->get_manage_url() ) . '" target="_blank">', '</a>'
					); ?>
				</p>
			</div>
			<?php

			// always log this error, regardless of debug setting
			$plugin->log( 'Could not display messenger settings. ' . $exception->getMessage() );
		}
	}


	/**
	 * Saves the settings.
	 *
	 * This is overridden to pull the latest from FBE and update that remotely via API
	 *
	 * @since 2.0.0
	 *
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function save() {

		$plugin               = facebook_for_woocommerce();
		$external_business_id = $plugin->get_connection_handler()->get_external_business_id();

		try {

			// first get the latest configuration details
			$response = $plugin->get_api()->get_business_configuration( $external_business_id );

			$configuration = $response->get_messenger_configuration();

			if ( ! $configuration ) {
				throw new Framework\SV_WC_API_Exception( 'Could not retrieve latest messenger configuration' );
			}

			$update          = false;
			$setting_enabled = wc_string_to_bool( Framework\SV_WC_Helper::get_posted_value( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER ) );

			// only consider updating if the setting has changed
			if ( $setting_enabled !== $configuration->is_enabled() ) {
				$update = true;
			}

			// also consider updating if the site's URL was removed from approved URLs
			if ( ! in_array( home_url( '/' ), $configuration->get_domains(), true ) ) {
				$update = true;
			}

			// make the API call if settings have changed
			if ( $update ) {

				$configuration->set_enabled( $setting_enabled );
				$configuration->add_domain( home_url( '/' ) );

				$plugin->get_api()->update_messenger_configuration( $external_business_id, $configuration );

				delete_transient( 'wc_facebook_business_configuration_refresh' );
			}

			// save any real settings
			parent::save();

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			// always log this error, regardless of debug setting
			$plugin->log( 'Could not update remote messenger settings. ' . $exception->getMessage() );

			throw new Framework\SV_WC_Plugin_Exception( __( 'Please try again.', 'facebook-for-woocommerce' ) );
		}
	}


}
