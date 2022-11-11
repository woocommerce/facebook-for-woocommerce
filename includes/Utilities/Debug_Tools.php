<?php

namespace SkyVerge\WooCommerce\Facebook\Utilities;

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
			add_action( 'wc_facebook_delete_products_action_job', array( $this, 'cleanup_fb_catalog' ), 10, 1 );
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

		$tools['wc_facebook_settings_reset'] = array(
			'name'     => __( 'Reset Facebook for WooCommerce settings', 'facebook-for-woocommerce' ),
			'button'   => __( 'Reset settings', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will clear your Facebook settings to reset them, allowing you to rebuild your connection.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'clear_facebook_settings' ),
		);

		$tools['wc_facebook_delete_background_jobs'] = array(
			'name'     => __( 'Delete Background Sync Jobs', 'facebook-for-woocommerce' ),
			'button'   => __( 'Delete', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will clear your clear background sync jobs from the options table.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'clean_up_old_background_sync_options' ),
		);

		$tools['wc_facebook_delete_unlinked_products'] = array(
			'name'     => __( 'Delete unlinked products from your Facebook Catalog', 'facebook-for-woocommerce' ),
			'button'   => __( 'Delete unlinked products', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will delete all unlinked products from  your Facebook Catalog.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'delete_all_catalog_products' ),
		);

		$tools['wc_facebook_delete_all_products'] = array(
			'name'     => __( 'Delete all products from your Facebook Catalog', 'facebook-for-woocommerce' ),
			'button'   => __( 'Delete all products', 'facebook-for-woocommerce' ),
			'desc'     => __( 'This tool will delete all products from  your Facebook Catalog.', 'facebook-for-woocommerce' ),
			'callback' => array( $this, 'delete_all_catalog_products' ),
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

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc_facebook_background_sync_%'" );

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
	 * Runs the delete all catalog products tool.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function delete_all_catalog_products() {

		// Get products from Facebook catalog api wp_remote_get.
		$url      = 'https://graph.facebook.com/'
			. facebook_for_woocommerce()->get_integration()->get_graph_api()::API_VERSION . '/'
			. facebook_for_woocommerce()->get_integration()->get_product_catalog_id() . '/products/?access_token='
			. facebook_for_woocommerce()->get_connection_handler()->get_access_token();
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return esc_html__( "Couldn't fetch FB products", 'facebook-for-woocommerce' );
		}

		$body = json_decode( $response['body'], true );

		$products = $body['data'];

		if ( ! empty( $products ) ) {

			$product_ids = array_map(
				function ( $product ) {
					return $product['id'];
				},
				$products
			);

			// Delete products.
			as_enqueue_async_action( 'wc_facebook_delete_products_action_job', array( 'products' => $product_ids ), facebook_for_woocommerce()->get_id() );

		}

		return esc_html__( 'Deleted all products from Facebook!', 'facebook-for-woocommerce' );

	}

	/**
	 * Delete products from Facebook catalog.
	 *
	 * @since x.x.x
	 *
	 * @param array $products array of product ids.
	 * @return void
	 */
	public function cleanup_fb_catalog( $products ) {

		foreach ( $products as $product_id ) {
			\WC_Facebookcommerce_Utils::log( 'Deleted product with retail_id: ' . $product_id );
			facebook_for_woocommerce()->get_integration()->get_graph_api()->delete_product_item( $product_id );
		}

	}

}
