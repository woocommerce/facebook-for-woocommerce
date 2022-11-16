<?php

namespace SkyVerge\WooCommerce\Facebook\Utilities;

use Automattic\WooCommerce\ActionSchedulerJobFramework\Proxies\ActionScheduler;
use SkyVerge\WooCommerce\Facebook\Jobs\ResetAllProductsFBSettings;

/**
 * Class WC_Facebook_Debug_Tools
 *
 * @since x.x.x
 */
class WC_Facebook_Debug_Tools {

	/**
	 * Initialize the class.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		if ( is_admin() && ! is_ajax() ) {
			add_filter( 'woocommerce_debug_tools', array( $this, 'add_debug_tool' ) );
		}
	}

	/**
	 * Adds clear settings tool to WC system status -> tools page.
	 *
	 * @since x.x.x
	 *
	 * @param array $tools system status tools.
	 * @return array
	 */
	public function add_debug_tool( $tools ) {

		if ( ! facebook_for_woocommerce()->get_connection_handler()->is_connected()
			|| ! facebook_for_woocommerce()->get_integration()->is_debug_mode_enabled() ) {
			return $tools;
		}

		$tools['wc_facebook_settings_reset'] = array(
			'name'     => __( 'Facebook: Reset connection settings', 'facebook-for-woocommerce' ),
			'button'   => __( 'Reset settings', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will clear your Facebook settings to reset them, allowing you to rebuild your connection.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'clear_facebook_settings' ),
		);

		$tools['wc_facebook_delete_background_jobs'] = array(
			'name'     => __( 'Facebook: Delete Background Sync Jobs', 'facebook-for-woocommerce' ),
			'button'   => __( 'Clear Background Sync Jobs', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will clear your clear background sync jobs from the options table.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'clean_up_old_background_sync_options' ),
		);

		$tools['reset_all_product_fb_settings'] = array(
			'name'     => __( 'Facebook: Reset all products', 'facebook-for-woocommerce' ),
			'button'   => __( 'Reset products Facebook settings', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will reset Facebook settings for all product on your WooCommerce store.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'reset_all_product_fb_settings' ),
		);

		$tools['wc_facebook_delete_all_products'] = array(
			'name'     => __( 'Facebook: Delete all products from your Facebook Catalog', 'facebook-for-woocommerce' ),
			'button'   => __( 'Delete all products', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will delete all products from  your Facebook Catalog.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'delete_all_products' ),
		);

		return $tools;
	}

	/**
	 * Runs the Delete Background Sync Jobs tool.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function clean_up_old_background_sync_options() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wc_facebook_background_product_sync%'" );

		return __( 'Background sync jobs have been deleted.', 'facebook-for-woocommerce' );

	}

	/**
	 * Runs the clear settings tool.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function clear_facebook_settings() {

		// Disconnect FB.
		facebook_for_woocommerce()->get_connection_handler()->disconnect();

		return esc_html__( 'Cleared all Facebook settings!', 'facebook-for-woocommerce' );

	}

	/**
	 * Runs the reset all catalog products settings tool.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function reset_all_product_fb_settings() {
		facebook_for_woocommerce()->job_manager->reset_all_product_fb_settings->queue_start();
		return esc_html__( 'Reset products Facebook settings job started!', 'facebook-for-woocommerce' );

	}

	/**
	 * Delete products from Facebook catalog.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function delete_all_products() {
		facebook_for_woocommerce()->job_manager->delete_all_products->queue_start();
		return esc_html__( 'Delete products from Facebook catalog job started!', 'facebook-for-woocommerce' );
	}

}
