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

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}


	/**
	 * Enqueue the assets.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function enqueue_assets() {

		if ( Admin\Settings::PAGE_ID !== SV_WC_Helper::get_requested_value( 'page' ) || $this->get_id() !== SV_WC_Helper::get_requested_value( 'tab' ) ) {
			return;
		}

		wp_enqueue_style( 'wc-facebook-admin-connection-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-connection.css', [], \WC_Facebookcommerce::VERSION );
	}


	/**
	 * Renders the screen.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function render() {

		$is_connected = facebook_for_woocommerce()->get_connection_handler()->is_connected();

		// always render the CTA box
		$this->render_facebook_box( $is_connected );

		// don't proceed further if not connected
		if ( ! $is_connected ) {
			return;
		}

		$static_items = [
			'page' => [
				'label' => __( 'Page', 'facebook-for-woocommerce' ),
				'value' => facebook_for_woocommerce()->get_integration()->get_facebook_page_id(),
			],
			'pixel' => [
				'label' => __( 'Pixel', 'facebook-for-woocommerce' ),
				'value' => facebook_for_woocommerce()->get_integration()->get_facebook_pixel_id(),
			],
			'catalog' => [
				'label' => __( 'Catalog', 'facebook-for-woocommerce' ),
				'value' => facebook_for_woocommerce()->get_integration()->get_product_catalog_id(),
				'url'   => 'https://facebook.com/products',
			],
			'business-manager' => [
				'label' => __( 'Business manager', 'facebook-for-woocommerce' ),
				'value' => facebook_for_woocommerce()->get_connection_handler()->get_business_manager_id(),
			],
		];

		?>

		<table class="form-table">
			<tbody>

				<?php foreach ( $static_items as $id => $item ) :

					$item = wp_parse_args( $item, [
						'label' => '',
						'value' => '',
						'url'   => '',
					] );

					?>

					<tr valign="top" class="wc-facebook-connected-<?php echo esc_attr( $id ); ?>">

						<th scope="row" class="titledesc">
							<?php echo esc_html( $item['label'] ); ?>
						</th>

						<td class="forminp">

							<?php if ( $item['url'] ) : ?>

								<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank">

									<?php echo esc_html( $item['value'] ); ?>

									<span
										class="dashicons dashicons-external"
										style="margin-right: 8px; vertical-align: bottom; text-decoration: none;"
									></span>

								</a>

							<?php elseif ( is_numeric( $item['value'] ) ) : ?>

								<code><?php echo esc_html( $item['value'] ); ?></code>

							<?php elseif ( ! empty( $item['value'] ) ) : ?>

								<?php echo esc_html( $item['value'] ); ?>

							<?php else : ?>

								<?php echo '-' ?>

							<?php endif; ?>

						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>

		<?php

		parent::render();
	}


	/**
	 * Renders the Facebook CTA box.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param bool $is_connected whether the plugin is connected
	 */
	public function render_facebook_box( $is_connected ) {

		if ( $is_connected ) {
			$title = __( 'Reach the Right People and Sell More Online', 'facebook-for-woocommerce' );
		} else {
			$title = __( 'Grow your business on Facebook', 'facebook-for-woocommerce' );
		}

		$subtitle = __( 'Use this WooCommerce and Facebook integration to:', 'facebook-for-woocommerce' );

		$benefits = [
			__( 'Create an ad in a few steps', 'facebook-for-woocommerce'),
			__( 'Use built-in best practices for online sales', 'facebook-for-woocommerce'),
			__( 'Get reporting on sales and revenue', 'facebook-for-woocommerce'),
		];

		if ( $is_connected ) {

			$actions = [
				'create-ad' => [
					'label' => __( 'Create Ad', 'facebook-for-woocommerce' ),
					'type'  => 'primary',
					'url'   => 'https://www.facebook.com/ad_center/create/ad/?entry_point=facebook_ads_extension&page_id=' . facebook_for_woocommerce()->get_integration()->get_facebook_page_id(),
				],
				'manage' => [
					'label' => __( 'Manage', 'facebook-for-woocommerce' ),
					'type'  => 'secondary',
					'url'   => facebook_for_woocommerce()->get_connection_handler()->get_manage_url(),
				],
			];

		} else {

			$actions = [
				'get-started' => [
					'label' => __( 'Get Started', 'facebook-for-woocommerce' ),
					'type'  => 'primary',
					'url'   => facebook_for_woocommerce()->get_connection_handler()->get_connect_url(),
				],
			];
		}

		?>

		<div id="wc-facebook-connection-box">

			<h1><?php echo esc_html( $title ); ?></h1>
			<h2><?php echo esc_html( $subtitle ); ?></h2>

			<ul class="benefits">
				<?php foreach ( $benefits as $benefit ) : ?>
					<li><?php echo esc_html( $benefit ); ?></li>
				<?php endforeach; ?>
			</ul>

			<div class="actions">

				<?php foreach ( $actions as $action ) : ?>

					<a
						href="<?php echo esc_url( $action['url'] ); ?>"
						class="<?php echo ( 'internal' !== $action['type'] ) ? 'button' : ''; ?> button-<?php echo esc_attr( $action['type'] ); ?>"
						<?php echo ( 'internal' !== $action['type'] ) ? 'target="_blank"' : ''; ?>
					>
						<?php echo esc_html( $action['label'] ); ?>
					</a>

				<?php endforeach; ?>

				<?php if ( $is_connected ) : ?>

					<a href="<?php echo esc_url( facebook_for_woocommerce()->get_connection_handler()->get_disconnect_url() ); ?>" class="uninstall">
						<?php esc_html_e( 'Uninstall', 'facebook-for-woocommerce' ); ?>
					</a>

				<?php endif; ?>

			</div>

		</div>

		<?php
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
