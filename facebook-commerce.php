<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Events\AAMSettings;
use SkyVerge\WooCommerce\Facebook\Handlers\Connection;
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\Facebook\Products\Feed;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'facebook-config-warmer.php';
require_once 'includes/fbproduct.php';
require_once 'facebook-commerce-pixel-event.php';

class WC_Facebookcommerce_Integration extends WC_Integration {


	/**
	 * @var string the WordPress option name where the page access token is stored
	 * @deprecated 2.1.0
	 */
	const OPTION_PAGE_ACCESS_TOKEN = 'wc_facebook_page_access_token';

	/** @var string the WordPress option name where the product catalog ID is stored */
	const OPTION_PRODUCT_CATALOG_ID = 'wc_facebook_product_catalog_id';

	/** @var string the WordPress option name where the external merchant settings ID is stored */
	const OPTION_EXTERNAL_MERCHANT_SETTINGS_ID = 'wc_facebook_external_merchant_settings_id';

	/** @var string the WordPress option name where the feed ID is stored */
	const OPTION_FEED_ID = 'wc_facebook_feed_id';

	/** @var string the WordPress option name where the upload ID is stored */
	const OPTION_UPLOAD_ID = 'wc_facebook_upload_id';

	/** @var string the WordPress option name where the JS SDK version is stored */
	const OPTION_JS_SDK_VERSION = 'wc_facebook_js_sdk_version';

	/** @var string the WordPress option name where the latest pixel install time is stored */
	const OPTION_PIXEL_INSTALL_TIME = 'wc_facebook_pixel_install_time';

	/** @var string the facebook page ID setting ID */
	const SETTING_FACEBOOK_PAGE_ID = 'wc_facebook_page_id';

	/** @var string the facebook pixel ID setting ID */
	const SETTING_FACEBOOK_PIXEL_ID = 'wc_facebook_pixel_id';

	/** @var string the "enable advanced matching" setting ID */
	const SETTING_ENABLE_ADVANCED_MATCHING = 'enable_advanced_matching';

	/** @var string the "use s2s" setting ID */
	const SETTING_USE_S2S = 'use_s2s';

	/** @var string the "access token" setting ID */
	const SETTING_ACCESS_TOKEN = 'access_token';

	/** @var string the "enable product sync" setting ID */
	const SETTING_ENABLE_PRODUCT_SYNC = 'wc_facebook_enable_product_sync';

	/** @var string the excluded product category IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS = 'wc_facebook_excluded_product_category_ids';

	/** @var string the excluded product tag IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_TAG_IDS = 'wc_facebook_excluded_product_tag_ids';

	/** @var string the product description mode setting ID */
	const SETTING_PRODUCT_DESCRIPTION_MODE = 'wc_facebook_product_description_mode';

	/** @var string the scheduled resync offset setting ID */
	const SETTING_SCHEDULED_RESYNC_OFFSET = 'scheduled_resync_offset';

	/** @var string the "enable messenger" setting ID */
	const SETTING_ENABLE_MESSENGER = 'wc_facebook_enable_messenger';

	/** @var string the messenger locale setting ID */
	const SETTING_MESSENGER_LOCALE = 'wc_facebook_messenger_locale';

	/** @var string the messenger greeting setting ID */
	const SETTING_MESSENGER_GREETING = 'wc_facebook_messenger_greeting';

	/** @var string the messenger color HEX setting ID */
	const SETTING_MESSENGER_COLOR_HEX = 'wc_facebook_messenger_color_hex';

	/** @var string the "debug mode" setting ID */
	const SETTING_ENABLE_DEBUG_MODE = 'wc_facebook_enable_debug_mode';

	/** @var string the standard product description mode name */
	const PRODUCT_DESCRIPTION_MODE_STANDARD = 'standard';

	/** @var string the short product description mode name */
	const PRODUCT_DESCRIPTION_MODE_SHORT = 'short';

	/** @var string the hook for the recurreing action that syncs products */
	const ACTION_HOOK_SCHEDULED_RESYNC = 'sync_all_fb_products_using_feed';

	/** @var string custom taxonomy FB product set ID */
	const FB_PRODUCT_SET_ID = 'fb_product_set_id';


	/** @var string|null the configured product catalog ID */
	public $product_catalog_id;

	/** @var string|null the configured external merchant settings ID */
	public $external_merchant_settings_id;

	/** @var string|null the configured feed ID */
	public $feed_id;

	/** @var string|null the configured upload ID */
	private $upload_id;

	/** @var string|null the configured pixel install time */
	public $pixel_install_time;

	/** @var string|null the configured JS SDK version */
	private $js_sdk_version;

	/** @var bool|null whether the feed has been migrated from FBE 1 to FBE 1.5 */
	private $feed_migrated;

	/** @var array the page name and url */
	private $page;


	/** Legacy properties *********************************************************************************************/


	// TODO probably some of these meta keys need to be moved to Facebook\Products {FN 2020-01-13}
	const FB_PRODUCT_GROUP_ID    = 'fb_product_group_id';
	const FB_PRODUCT_ITEM_ID     = 'fb_product_item_id';
	const FB_PRODUCT_DESCRIPTION = 'fb_product_description';

	/** @var string the API flag to set a product as visible in the Facebook shop */
	const FB_SHOP_PRODUCT_VISIBLE = 'published';

	/** @var string the API flag to set a product as not visible in the Facebook shop */
	const FB_SHOP_PRODUCT_HIDDEN = 'staging';

	/** @var string @deprecated  */
	const FB_CART_URL = 'fb_cart_url';

	const FB_MESSAGE_DISPLAY_TIME = 180;

	// Number of days to query tip.
	const FB_TIP_QUERY = 1;

	// TODO: this constant is no longer used and can probably be removed {WV 2020-01-21}
	const FB_VARIANT_IMAGE = 'fb_image';

	const FB_ADMIN_MESSAGE_PREPEND = '<b>Facebook for WooCommerce</b><br/>';

	const FB_SYNC_IN_PROGRESS = 'fb_sync_in_progress';
	const FB_SYNC_REMAINING   = 'fb_sync_remaining';
	const FB_SYNC_TIMEOUT     = 30;
	const FB_PRIORITY_MID     = 9;

	private $test_mode = false;


	public function init_pixel() {
		WC_Facebookcommerce_Pixel::initialize();

		// Migrate WC customer pixel_id from WC settings to WP options.
		// This is part of a larger effort to consolidate all the FB-specific
		// settings for all plugin integrations.
		if ( is_admin() ) {

			$pixel_id          = WC_Facebookcommerce_Pixel::get_pixel_id();
			$settings_pixel_id = $this->get_facebook_pixel_id();

			if (
			WC_Facebookcommerce_Utils::is_valid_id( $settings_pixel_id ) &&
			( ! WC_Facebookcommerce_Utils::is_valid_id( $pixel_id ) ||
			$pixel_id != $settings_pixel_id
			)
			) {
				WC_Facebookcommerce_Pixel::set_pixel_id( $settings_pixel_id );
			}

			// migrate Advanced Matching enabled (use_pii) from the integration setting to the pixel option,
			// so that it works the same way the pixel ID does
			$settings_advanced_matching_enabled = $this->is_advanced_matching_enabled();
			WC_Facebookcommerce_Pixel::set_use_pii_key( $settings_advanced_matching_enabled );

			$settings_use_s2s = $this->is_use_s2s_enabled();
			WC_Facebookcommerce_Pixel::set_use_s2s( $settings_use_s2s );

			$settings_access_token = $this->get_access_token();
			WC_Facebookcommerce_Pixel::set_access_token($settings_access_token);
		}
	}

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) {
			include_once 'facebook-commerce-events-tracker.php';
		}

		$this->id                 = WC_Facebookcommerce::INTEGRATION_ID;
		$this->method_title       = __(
			'Facebook for WooCommerce',
			'facebook-for-commerce'
		);
		$this->method_description = __(
			'Facebook Commerce and Dynamic Ads (Pixel) Extension',
			'facebook-for-commerce'
		);

		// Load the settings.
		$this->init_settings();

		$pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();

		// if there is a pixel option saved and no integration setting saved, inherit the pixel option
		if ( $pixel_id && ! $this->get_facebook_pixel_id() ) {
			$this->settings[ self::SETTING_FACEBOOK_PIXEL_ID ] = $pixel_id;
		}

		$advanced_matching_enabled = WC_Facebookcommerce_Pixel::get_use_pii_key();

		// if Advanced Matching (use_pii) is enabled on the saved pixel option and not on the saved integration setting,
		// inherit the pixel option
		if ( $advanced_matching_enabled && ! $this->is_advanced_matching_enabled() ) {
			$this->settings[ self::SETTING_ENABLE_ADVANCED_MATCHING ] = $advanced_matching_enabled;
		}

		//For now, the values of use s2s and access token will be the ones returned from WC_Facebookcommerce_Pixel
		$use_s2s = WC_Facebookcommerce_Pixel::get_use_s2s();
		$this->settings[self::SETTING_USE_S2S] = $use_s2s;

		$access_token = WC_Facebookcommerce_Pixel::get_access_token();
		$this->settings[self::SETTING_ACCESS_TOKEN] = $access_token;

		if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
			include_once 'includes/fbutils.php';
		}

		WC_Facebookcommerce_Utils::$ems = $this->get_external_merchant_settings_id();

		if ( ! class_exists( 'WC_Facebookcommerce_Graph_API' ) ) {
			include_once 'includes/fbgraph.php';
			$this->fbgraph = new WC_Facebookcommerce_Graph_API( facebook_for_woocommerce()->get_connection_handler()->get_access_token() );
		}

		WC_Facebookcommerce_Utils::$fbgraph = $this->fbgraph;

		// Hooks
		if ( is_admin() ) {

			$this->init_pixel();

			if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) {
				include_once 'includes/fbutils.php';
			}

			// Display an info banner for eligible pixel and user.
			if ( $this->get_external_merchant_settings_id()
			&& $this->get_facebook_pixel_id()
			&& $this->get_pixel_install_time() ) {
				$should_query_tip =
				WC_Facebookcommerce_Utils::check_time_cap(
					get_option( 'fb_info_banner_last_query_time', '' ),
					self::FB_TIP_QUERY
				);
				$last_tip_info    = WC_Facebookcommerce_Utils::get_cached_best_tip();

				if ( $should_query_tip || $last_tip_info ) {
					if ( ! class_exists( 'WC_Facebookcommerce_Info_Banner' ) ) {
						include_once 'includes/fbinfobanner.php';
					}
					WC_Facebookcommerce_Info_Banner::get_instance(
						$this->get_external_merchant_settings_id(),
						$this->fbgraph,
						$should_query_tip
					);
				}
			}

			if ( ! class_exists( 'WC_Facebook_Integration_Test' ) ) {
				include_once 'includes/test/facebook-integration-test.php';
			}
			$integration_test           = WC_Facebook_Integration_Test::get_instance( $this );
			$integration_test::$fbgraph = $this->fbgraph;

			if ( ! $this->get_pixel_install_time() && $this->get_facebook_pixel_id() ) {
				$this->update_pixel_install_time( time() );
			}

			add_action( 'admin_notices', array( $this, 'checks' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

			add_action(
				'wp_ajax_ajax_sync_all_fb_products',
				array( $this, 'ajax_sync_all_fb_products' ),
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_sync_all_fb_products_using_feed',
				array( $this, 'ajax_sync_all_fb_products_using_feed' ),
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_check_feed_upload_status',
				array( $this, 'ajax_check_feed_upload_status' ),
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_reset_all_fb_products',
				array( $this, 'ajax_reset_all_fb_products' ),
				self::FB_PRIORITY_MID
			);
			add_action(
				'wp_ajax_ajax_display_test_result',
				array( $this, 'ajax_display_test_result' )
			);

			add_action(
				'wp_ajax_ajax_schedule_force_resync',
				array( $this, 'ajax_schedule_force_resync' ),
				self::FB_PRIORITY_MID
			);

			// don't duplicate product FBID meta
			add_filter( 'woocommerce_duplicate_product_exclude_meta', [ $this, 'fb_duplicate_product_reset_meta' ] );

			// add product processing hooks if the plugin is configured only
			if ( $this->is_configured() && $this->get_product_catalog_id() ) {

				// on_product_save() must run with priority larger than 20 to make sure WooCommerce has a chance to save the submitted product information
				add_action( 'woocommerce_process_product_meta', [ $this, 'on_product_save' ], 40 );

				add_action(
					'woocommerce_product_quick_edit_save',
					array( $this, 'on_quick_and_bulk_edit_save' ),
					10,  // Action priority
					1    // Args passed to on_quick_and_bulk_edit_save ('product')
				);

				add_action(
					'woocommerce_product_bulk_edit_save',
					array( $this, 'on_quick_and_bulk_edit_save' ),
					10,  // Action priority
					1    // Args passed to on_quick_and_bulk_edit_save ('product')
				);

				add_action( 'before_delete_post', [ $this, 'on_product_delete' ] );

				add_action( 'add_meta_boxes', array( $this, 'fb_product_metabox' ), 10, 1 );

				add_action(
					'transition_post_status',
					array( $this, 'fb_change_product_published_status' ),
					10,
					3
				);

				add_action(
					'wp_ajax_ajax_fb_toggle_visibility',
					array( $this, 'ajax_fb_toggle_visibility' )
				);

				add_action(
					'wp_ajax_ajax_reset_single_fb_product',
					array( $this, 'ajax_reset_single_fb_product' )
				);

				add_action(
					'wp_ajax_ajax_delete_fb_product',
					array( $this, 'ajax_delete_fb_product' )
				);

				add_action(
					'pmxi_after_xml_import',
					array( $this, 'wp_all_import_compat' )
				);

				add_action(
					'wp_ajax_wpmelon_adv_bulk_edit',
					[ $this, 'ajax_woo_adv_bulk_edit_compat' ],
					self::FB_PRIORITY_MID
				);

				// used to remove the 'you need to resync' message
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['remove_sticky'] ) ) {
					$this->remove_sticky_message();
				}
			}

			$this->load_background_sync_process();
		}

		// Must be outside of admin for cron to schedule correctly.
		add_action( 'sync_all_fb_products_using_feed', [ $this, 'handle_scheduled_resync_action' ], self::FB_PRIORITY_MID );

		// handle the special background feed generation action
		add_action( 'wc_facebook_generate_product_catalog_feed', [ $this, 'handle_generate_product_catalog_feed' ] );

		if ( $this->get_facebook_pixel_id() ) {
			$aam_settings = $this->load_aam_settings_of_pixel();
			$user_info            = WC_Facebookcommerce_Utils::get_user_info( $aam_settings );
			$this->events_tracker = new WC_Facebookcommerce_EventsTracker( $user_info, $aam_settings );
		}

		// initialize the messenger chat features
		$this->messenger_chat = new WC_Facebookcommerce_MessengerChat( [
			'fb_page_id'             => $this->get_facebook_page_id(),
			'facebook_jssdk_version' => $this->get_js_sdk_version(),
		] );

		// Product Set hooks
		add_action( 'fb_wc_product_set_sync', array( $this, 'create_or_update_product_set_item' ), 99, 2 );
		add_action( 'fb_wc_product_set_delete', array( $this, 'delete_product_set_item' ), 99 );
	}

	/**
	 * Returns the Automatic advanced matching of this pixel
	 *
	 * @since 2.0.3
	 *
	 * @return AAMSettings
	 */
	private function load_aam_settings_of_pixel() {
		$installed_pixel = $this->get_facebook_pixel_id();
		// If no pixel is installed, reading the DB is not needed
		if(!$installed_pixel ){
			return null;
		}
		$config_key = 'wc_facebook_aam_settings';
		$saved_value = get_transient( $config_key );
		$refresh_interval = 20*MINUTE_IN_SECONDS;
		$aam_settings = null;
		// If wc_facebook_aam_settings is present in the DB
		// it is converted into an AAMSettings object
		if( $saved_value !== false ){
			$cached_aam_settings = new AAMSettings(json_decode($saved_value, true));
			// This condition is added because
			// it is possible that the AAMSettings saved do not belong to the current
			// installed pixel
			// because the admin could have changed the connection to Facebook
			// during the refresh interval
			if($cached_aam_settings->get_pixel_id() == $installed_pixel){
				$aam_settings = $cached_aam_settings;
			}
		}
		// If the settings are not present or invalid
		// they are fetched from Facebook domain
		// and cached in WP database if they are not null
		if(!$aam_settings){
			$aam_settings = AAMSettings::build_from_pixel_id( $installed_pixel );
			if($aam_settings){
				set_transient($config_key, strval($aam_settings), $refresh_interval);
			}
		}
		return $aam_settings;
	}

	public function load_background_sync_process() {
		// Attempt to load background processing (Woo 3.x.x only)
		include_once 'includes/fbbackground.php';
		if ( class_exists( 'WC_Facebookcommerce_Background_Process' ) ) {
			if ( ! isset( $this->background_processor ) ) {
				$this->background_processor =
				new WC_Facebookcommerce_Background_Process( $this );
			}
		}
		add_action(
			'wp_ajax_ajax_fb_background_check_queue',
			array( $this, 'ajax_fb_background_check_queue' )
		);
	}

	public function ajax_fb_background_check_queue() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'background check queue', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$request_time = null;
		if ( isset( $_POST['request_time'] ) ) {
			$request_time = esc_js( sanitize_text_field( wp_unslash( $_POST['request_time'] ) ) );
		}

		if ( facebook_for_woocommerce()->get_connection_handler()->get_access_token() ) {

			if ( isset( $this->background_processor ) ) {
				$is_processing = $this->background_processor->handle_cron_healthcheck();
				$remaining     = $this->background_processor->get_item_count();
				$response      = array(
					'connected'    => true,
					'background'   => true,
					'processing'   => $is_processing,
					'remaining'    => $remaining,
					'request_time' => $request_time,
				);
			} else {
				$response = array(
					'connected'  => true,
					'background' => false,
				);
			}
		} else {
			$response = array(
				'connected'  => false,
				'background' => false,
			);
		}

		printf( json_encode( $response ) );
		wp_die();
	}


	/**
	 * Adds a new tab to the Product edit page.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 *
	 * @param array $tabs array of tabs
	 * @return array
	 */
	public function fb_new_product_tab( $tabs ) {

		wc_deprecated_function( __METHOD__, '1.10.0', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_settings_tab()' );

		return $tabs;
	}


	/**
	 * Adds content to the new Facebook tab on the Product edit page.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 */
	public function fb_new_product_tab_content() {

		wc_deprecated_function( __METHOD__, '1.10.0', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_settings_tab_content()' );
	}


	/**
	 * Filters the product columns in the admin edit screen.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 *
	 * @param array $existing_columns array of columns and labels
	 * @return array
	 */
	public function fb_product_columns( $existing_columns ) {

		wc_deprecated_function( __METHOD__, '1.10.0', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_list_table_column()' );

		return $existing_columns;
	}


	/**
	 * Outputs content for the FB Shop column in the edit screen.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 *
	 * @param string $column name of the column to display
	 */
	public function fb_render_product_columns( $column ) {

		wc_deprecated_function( __METHOD__, '1.10.0', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_list_table_columns_content()' );
	}


	public function fb_product_metabox() {
		$ajax_data = array(
			'nonce' => wp_create_nonce( 'wc_facebook_metabox_jsx' ),
		);
		wp_enqueue_script(
			'wc_facebook_metabox_jsx',
			plugins_url(
				'/assets/js/facebook-metabox.min.js',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);
		wp_localize_script(
			'wc_facebook_metabox_jsx',
			'wc_facebook_metabox_jsx',
			$ajax_data
		);

		add_meta_box(
			'facebook_metabox', // Meta box ID
			'Facebook', // Meta box Title
			array( $this, 'fb_product_meta_box_html' ), // Callback
			'product', // Screen to which to add the meta box
			'side' // Context
		);
	}


	/**
	 * Renders the content of the product meta box.
	 */
	public function fb_product_meta_box_html() {
		global $post;

		$woo_product         = new WC_Facebook_Product( $post->ID );
		$fb_product_group_id = null;

		if ( $woo_product->woo_product instanceof \WC_Product && Products::product_should_be_synced( $woo_product->woo_product ) ) {
			$fb_product_group_id = $this->get_product_fbid( self::FB_PRODUCT_GROUP_ID, $post->ID, $woo_product );
		}

		?>
			<span id="fb_metadata">
		<?php

		if ( $fb_product_group_id ) {

			?>

			<?php echo esc_html__( 'Facebook ID:', 'facebook-for-woocommerce' ); ?> <a href="https://facebook.com/<?php echo esc_attr( $fb_product_group_id ); ?>"
			                                                                           target="_blank"><?php echo esc_html( $fb_product_group_id ); ?></a>

			<?php if ( WC_Facebookcommerce_Utils::is_variable_type( $woo_product->get_type() ) ) : ?>

				<?php if ( $product_item_ids_by_variation_id = $this->get_variation_product_item_ids( $woo_product, $fb_product_group_id ) ) : ?>

					<p>
						<?php echo esc_html__( 'Variant IDs:', 'facebook-for-woocommerce' ); ?><br/>

						<?php foreach ( $product_item_ids_by_variation_id as $variation_id => $product_item_id ) : ?>

							<?php echo esc_html( $variation_id ); ?>: <a href="https://facebook.com/<?php echo esc_attr( $product_item_id ); ?>"
							                                             target="_blank"><?php echo esc_html( $product_item_id ); ?></a><br/>

						<?php endforeach; ?>
					</p>

				<?php endif; ?>

			<?php endif; ?>

				<input name="is_product_page" type="hidden" value="1"/>

				<p/>
				<a href="#" onclick="fb_reset_product( <?php echo esc_js( $post->ID ); ?> )">
					<?php echo esc_html__( 'Reset Facebook metadata', 'facebook-for-woocommerce' ); ?>
				</a>

				<p/>
				<a href="#" onclick="fb_delete_product( <?php echo esc_js( $post->ID ); ?> )">
					<?php echo esc_html__( 'Delete product(s) on Facebook', 'facebook-for-woocommerce' ); ?>
				</a>

			<?php

		} else {

			?>
				<b><?php echo esc_html__( 'This product is not yet synced to Facebook.', 'facebook-for-woocommerce' ); ?></b>
			<?php
		}

		?>
			</span>
		<?php
	}


	/**
	 * Gets a list of Product Item IDs indexed by the ID of the variation.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id product group ID
	 * @return array
	 */
	private function get_variation_product_item_ids( $product, $product_group_id ) {

		$product_item_ids_by_variation_id = [];
		$missing_product_item_ids         = [];

		// get the product item IDs from meta data and build a list of variations that don't have a product item ID stored
		foreach ( $product->get_children() as $variation_id ) {

			if ( $variation = wc_get_product( $variation_id ) ) {

				if ( $product_item_id = $variation->get_meta( self::FB_PRODUCT_ITEM_ID ) ) {

					$product_item_ids_by_variation_id[ $variation_id ] = $product_item_id;

				} else {

					$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $variation );

					$missing_product_item_ids[ $retailer_id ] = $variation;

					$product_item_ids_by_variation_id[ $variation_id ] = null;
				}
			}
		}

		// use the Graph API to try to find and store the product item IDs for variations that don't have a value yet
		if ( $missing_product_item_ids ) {

			$product_item_ids = $this->find_variation_product_item_ids( $product_group_id );

			foreach ( $missing_product_item_ids as $retailer_id => $variation ) {

				if ( isset( $product_item_ids[ $retailer_id ] ) ) {

					$variation->update_meta_data( self::FB_PRODUCT_ITEM_ID, $product_item_ids[ $retailer_id ] );
					$variation->save_meta_data();

					$product_item_ids_by_variation_id[ $variation->get_id() ] = $product_item_ids[ $retailer_id ];
				}
			}
		}

		return $product_item_ids_by_variation_id;
	}


	/**
	 * Uses the Graph API to return a list of Product Item IDs indexed by the variation's retailer ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $product_group_id product group ID
	 * @return array
	 */
	private function find_variation_product_item_ids( $product_group_id ) {

		$product_item_ids = [];

		try {

			$response = facebook_for_woocommerce()->get_api()->get_product_group_products( $product_group_id );

			do {

				$product_item_ids = array_merge( $product_item_ids, $response->get_ids() );

			// get up to two additional pages of results
			} while ( $response = facebook_for_woocommerce()->get_api()->next( $response, 2 ) );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			$message = sprintf( 'There was an error trying to find the IDs for Product Items in the Product Group %s: %s', $product_group_id, $e->getMessage() );

			facebook_for_woocommerce()->log( $message );
		}

		return $product_item_ids;
	}


	/**
	 * Gets the total of published products.
	 *
	 * @return int
	 */
	public function get_product_count() {

		$args     = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$products = new WP_Query( $args );

		wp_reset_postdata();

		return $products->found_posts;
	}


	/**
	 * Load DIA specific JS Data
	 */
	public function load_assets() {

		$ajax_data = array(
      'nonce' => wp_create_nonce( 'wc_facebook_infobanner_jsx' ),
    );
		// load banner assets
		wp_enqueue_script(
			'wc_facebook_infobanner_jsx',
			plugins_url(
				'/assets/js/facebook-infobanner.min.js',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);
		wp_localize_script(
		 'wc_facebook_infobanner_jsx',
		 'wc_facebook_infobanner_jsx',
		 $ajax_data
	 );

		wp_enqueue_style(
			'wc_facebook_infobanner_css',
			plugins_url(
				'/assets/css/facebook-infobanner.css',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);

		if ( ! facebook_for_woocommerce()->is_plugin_settings() ) {
			return;
		}

		?>
	<script>

	window.facebookAdsToolboxConfig = {
		hasGzipSupport: '<?php echo extension_loaded( 'zlib' ) ? 'true' : 'false'; ?>',
		enabledPlugins: ['MESSENGER_CHAT','INSTAGRAM_SHOP', 'PAGE_SHOP'],
		enableSubscription: '<?php echo class_exists( 'WC_Subscriptions' ) ? 'true' : 'false'; ?>',
		popupOrigin: '<?php echo isset( $_GET['url'] ) ? esc_js( sanitize_text_field( wp_unslash( $_GET['url'] ) ) ) : 'https://www.facebook.com/'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>',
		feedWasDisabled: 'true',
		platform: 'WooCommerce',
		pixel: {
			pixelId: '<?php echo $this->get_facebook_pixel_id() ? esc_js( $this->get_facebook_pixel_id() ) : ''; ?>',
			advanced_matching_supported: true
		},
		diaSettingId: '<?php echo $this->get_external_merchant_settings_id() ? esc_js( $this->get_external_merchant_settings_id() ) : ''; ?>',
		store: {
			baseUrl: window.location.protocol + '//' + window.location.host,
			baseCurrency:'<?php echo esc_js( WC_Admin_Settings::get_option( 'woocommerce_currency' ) ); ?>',
			timezoneId: '<?php echo esc_js( date( 'Z' ) ); ?>',
			storeName: '<?php echo esc_js( WC_Facebookcommerce_Utils::get_store_name() ); ?>',
			version: '<?php echo esc_js( WC()->version ) ; ?>',
			php_version: '<?php echo PHP_VERSION; ?>',
			plugin_version: '<?php echo esc_js( WC_Facebookcommerce_Utils::PLUGIN_VERSION ); ?>'
		},
		feed: {
			totalVisibleProducts: '<?php echo esc_js( $this->get_product_count() ); ?>',
			hasClientSideFeedUpload: '<?php echo esc_js( ! ! $this->get_feed_id() ); ?>',
			enabled: true,
			format: 'csv'
		},
		feedPrepared: {
			feedUrl: '<?php echo esc_url_raw( Feed::get_feed_data_url() ); ?>',
			feedPingUrl: '',
			feedMigrated: <?php echo $this->is_feed_migrated() ? 'true' : 'false'; ?>,
			samples: <?php echo $this->get_sample_product_feed(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		},
	};

	</script>

		<?php
		$ajax_data = [
			'nonce' => wp_create_nonce( 'wc_facebook_settings_jsx' ),
		];
		wp_localize_script(
			'wc_facebook_settings_jsx',
			'wc_facebook_settings_jsx',
			$ajax_data
		);
		wp_enqueue_style(
			'wc_facebook_css',
			plugins_url(
				'/assets/css/facebook.css',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);
	}


	/**
	 * Gets the IDs of products marked for deletion from Facebook when removed from Sync.
	 *
	 * @internal
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	private function get_removed_from_sync_products_to_delete() {

		$posted_products = Framework\SV_WC_Helper::get_posted_value( WC_Facebook_Product::FB_REMOVE_FROM_SYNC );
		if ( empty( $posted_products ) ) {
			return [];
		}

		return array_map( 'absint', explode( ',', $posted_products ) );
	}


	/**
	 * Checks the product type and calls the corresponding on publish method.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param int $wp_id post ID
	 */
	public function on_product_save( $wp_id ) {

		$product = wc_get_product( $wp_id );

		if ( ! $product ) {
			return;
		}

		$sync_mode    = isset( $_POST['wc_facebook_sync_mode'] ) ? $_POST['wc_facebook_sync_mode'] : Admin::SYNC_MODE_SYNC_DISABLED;
		$sync_enabled = Admin::SYNC_MODE_SYNC_DISABLED !== $sync_mode;

		if ( Admin::SYNC_MODE_SYNC_AND_SHOW === $sync_mode && $product->is_virtual() ) {
			// force to Sync and hide
			$sync_mode = Admin::SYNC_MODE_SYNC_AND_HIDE;
		}

		$products_to_delete_from_facebook = $this->get_removed_from_sync_products_to_delete();

		if ( $product->is_type( 'variable' ) ) {

			// check variations for deletion
			foreach ( $products_to_delete_from_facebook as $delete_product_id ) {

				$delete_product = wc_get_product( $delete_product_id );

				if ( empty( $delete_product ) ) {
					continue;
				}

				if ( Products::is_sync_enabled_for_product( $delete_product ) ) {
					continue;
				}

				$this->delete_fb_product( $delete_product );
			}

		} else {

			if ( $sync_enabled ) {

				Products::enable_sync_for_products( [ $product ] );
				Products::set_product_visibility( $product, Admin::SYNC_MODE_SYNC_AND_HIDE !== $sync_mode );

				$this->save_product_settings( $product );

			} else {

				// if previously enabled, add a notice on the next page load
				if ( Products::is_sync_enabled_for_product( $product ) ) {
					Admin::add_product_disabled_sync_notice();
				}

				Products::disable_sync_for_products( [ $product ] );

				if ( in_array( $wp_id, $products_to_delete_from_facebook, true ) ) {

					$this->delete_fb_product( $product );
				}
			}
		}

		if ( $sync_enabled ) {

			Admin\Products::save_commerce_fields( $product );

			switch ( $product->get_type() ) {

				case 'simple':
				case 'booking':
				case 'external':
				case 'composite':
					$this->on_simple_product_publish( $wp_id );
				break;

				case 'variable':
					$this->on_variable_product_publish( $wp_id );
				break;

				case 'subscription':
				case 'variable-subscription':
				case 'bundle':
					$this->on_product_publish( $wp_id );
				break;
			}
		}
	}


	/**
	 * Saves the submitted Facebook settings for a product.
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product $product the product object
	 */
	private function save_product_settings( \WC_Product $product ) {

		$woo_product = new WC_Facebook_Product( $product->get_id() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ self::FB_PRODUCT_DESCRIPTION ] ) ) {
			$woo_product->set_description( sanitize_text_field( wp_unslash( $_POST[ self::FB_PRODUCT_DESCRIPTION ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_PRICE ] ) ) {
			$woo_product->set_price( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_PRICE ] ) ) );
		}

		if ( isset( $_POST[ 'fb_product_image_source' ] ) ) {
			$product->update_meta_data( Products::PRODUCT_IMAGE_SOURCE_META_KEY, sanitize_key( wp_unslash( $_POST[ 'fb_product_image_source' ] ) ) );
			$product->save_meta_data();
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) {
			$woo_product->set_product_image( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}


	/**
	 * Deletes a product from Facebook.
	 *
	 * @param int $product_id product ID
	 */
	public function on_product_delete( $product_id ) {

		$product = wc_get_product( $product_id );

		// bail if product does not exist
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		/**
		 * bail if not enabled for sync, except if explicitly deleting from the metabox
		 * @see ajax_delete_fb_product()
		 */
		if ( ( ! is_ajax() || ! isset( $_POST['action'] ) || 'ajax_delete_fb_product' !== $_POST['action'] )
		     && ! Products::published_product_should_be_synced( $product ) ) {

			return;
		}

		$this->delete_fb_product( $product );
	}


	/**
	 * Deletes Facebook product.
	 *
	 * @internal
	 *
	 * @since 2.3.0
	 *
	 * @param \WC_Product $product WooCommerce product object
	 */
	private function delete_fb_product( $product ) {

		$product_id = $product->get_id();

		if ( $product->is_type( 'variation' ) ) {

			$retailer_id = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product );

			// enqueue variation to be deleted in the background
			facebook_for_woocommerce()->get_products_sync_handler()->delete_products( [ $retailer_id ] );

		} elseif ( $product->is_type( 'variable' ) ) {

			$retailer_ids = [];

			foreach ( $product->get_children() as $variation_id ) {

				$variation = wc_get_product( $variation_id );

				if ( $variation instanceof \WC_Product ) {
					$retailer_ids[] = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $variation );
				}
			}

			// enqueue variations to be deleted in the background
			facebook_for_woocommerce()->get_products_sync_handler()->delete_products( $retailer_ids );

		} else {

			$this->delete_product_item( $product_id );
		}

		// clear out both item and group IDs
		delete_post_meta( $product_id, self::FB_PRODUCT_ITEM_ID );
		delete_post_meta( $product_id, self::FB_PRODUCT_GROUP_ID );
	}


	/**
	 * Updates Facebook Visibility upon trashing and restore.
	 *
	 * @internal
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param \WP_post $post
	 */
	public function fb_change_product_published_status( $new_status, $old_status, $post ) {

		if ( ! $post ) {
			return;
		}

		if ( ! $this->should_update_visibility_for_product_status_change( $new_status, $old_status ) ) {
			return;
		}

		$visibility = $new_status === 'publish' ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		$product = wc_get_product( $post->ID );

		// bail if we couldn't retrieve a valid product object or the product isn't enabled for sync
		//
		// Note that while moving a variable product to the trash, this method is called for each one of the
		// variations before it gets called with the variable product. As a result, Products::product_should_be_synced()
		// always returns false for the variable product (since all children are in the trash at that point).
		// This causes update_fb_visibility() to be called on simple products and product variations only.
		if ( ! $product instanceof \WC_Product || ! Products::published_product_should_be_synced( $product ) ) {
			return;
		}

		$this->update_fb_visibility( $product, $visibility );
	}


	/**
	 * Determines whether the product visibility needs to be updated for the given status change.
	 *
	 * Change from publish status -> unpublish status (e.g. trash, draft, etc.)
	 * Change from trash status -> publish status
	 * No need to update for change from trash <-> unpublish status
	 *
	 * @since 2.0.2
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @return bool
	 */
	private function should_update_visibility_for_product_status_change( $new_status, $old_status ) {

		return ( $old_status === 'publish' && $new_status !== 'publish' ) || ( $old_status === 'trash' && $new_status === 'publish' );
	}


	/**
	 * Generic function for use with any product publishing.
	 *
	 * Will determine product type (simple or variable) and delegate to
	 * appropriate handler.
	 *
	 * @param int $product_id product ID
	 */
	public function on_product_publish( $product_id ) {

		// bail if the plugin is not configured properly
		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $this->product_should_be_synced( $product ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			$this->on_variable_product_publish( $product_id );
		} else {
			$this->on_simple_product_publish( $product_id );
		}
	}


	/**
	 * If the user has opt-in to remove products that are out of stock,
	 * this function will delete the product from FB Page as well.
	 */
	function delete_on_out_of_stock( $wp_id, $woo_product ) {

		if ( Products::product_should_be_deleted( $woo_product ) ) {

			$this->delete_product_item( $wp_id );
			return true;
		}

		return false;
	}


	/**
	 * Syncs product to Facebook when saving a variable product.
	 *
	 * @param int $wp_id product post ID
	 * @param WC_Facebook_Product|null $woo_product product object
	 */
	function on_variable_product_publish( $wp_id, $woo_product = null ) {

		if ( ! $woo_product instanceof \WC_Facebook_Product ) {
			$woo_product = new \WC_Facebook_Product( $wp_id );
		}

		if ( ! $this->product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product->woo_product ) ) {
			return;
		}

		$variation_ids = [];

		// scheduled update for each variation that should be synced
		foreach ( $woo_product->get_children() as $variation_id ) {

			$variation = wc_get_product( $variation_id );

			if ( $variation instanceof \WC_Product && $this->product_should_be_synced( $variation ) && ! $this->delete_on_out_of_stock( $variation_id, $variation ) ) {
				$variation_ids[] = $variation_id;
			}
		}

		facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( $variation_ids );
	}


	/**
	 * Syncs product to Facebook when saving a simple product.
	 *
	 * @param int $wp_id product post ID
	 * @param WC_Facebook_Product|null $woo_product product object
	 * @param WC_Facebook_Product|null $parent_product parent object
	 * @return int|mixed|void|null
	 */
	function on_simple_product_publish( $wp_id, $woo_product = null, &$parent_product = null ) {

		if ( ! $woo_product instanceof \WC_Facebook_Product ) {
			$woo_product = new \WC_Facebook_Product( $wp_id, $parent_product );
		}

		if ( ! $this->product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product->woo_product ) ) {
			return;
		}

		// Check if this product has already been published to FB.
		// If not, it's new!
		$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $wp_id, $woo_product );

		if ( $fb_product_item_id ) {

			$woo_product->fb_visibility = Products::is_product_visible( $woo_product->woo_product );

			$this->create_or_update_product_item( $woo_product );

			return $fb_product_item_id;

		} else {

			return $this->create_or_update_product_item( $woo_product );
		}
	}


	/**
	 * Determines whether the product with the given ID should be synced.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Product|false $product product object
	 */
	public function product_should_be_synced( $product ) {

		$should_be_synced = $this->is_product_sync_enabled();

		// can't sync if we don't have a valid product object
		if ( $should_be_synced && ! $product instanceof \WC_Product ) {
			$should_be_synced = false;
		}

		// make sure the given product is enabled for sync
		if ( $should_be_synced && ! Products::product_should_be_synced( $product ) ) {
			$should_be_synced = false;
		}

		return $should_be_synced;
	}


	/**
	 * Creates or updates a product, store fb-specific info
	 *
	 * @param \WC_Facebook_Product $woo_product
	 */
	function create_or_update_product_item( $woo_product ) {
		$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );

		facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( [ $retailer_id ] );
	}


	/**
	 * Create or update product set
	 *
	 * @since 2.3.0
	 *
	 * @param array $product_set_data Product Set data.
	 * @param int   $product_set_id   Product Set Term Id.
	 **/
	public function create_or_update_product_set_item( $product_set_data, $product_set_id ) {

		// check if exists in FB
		$fb_product_set_id = get_term_meta( $product_set_id, self::FB_PRODUCT_SET_ID, true );

		// set data and execute API call
		$method = empty( $fb_product_set_id ) ? 'create' : 'update';
		$id     = empty( $fb_product_set_id ) ? $this->get_product_catalog_id() : $fb_product_set_id;
		$result = $this->check_api_result(
			call_user_func_array(
				array(
					$this->fbgraph,
					$method . '_product_set_item',
				),
				array(
					$id,
					$product_set_data,
				)
			)
		);

		// update product set to set FB Product Set ID
		if ( $result && empty( $fb_product_set_id ) ) {

			// decode and get ID from result body
			$decode_result     = WC_Facebookcommerce_Utils::decode_json( $result['body'] );
			$fb_product_set_id = $decode_result->id;

			update_term_meta(
				$product_set_id,
				self::FB_PRODUCT_SET_ID,
				$fb_product_set_id
			);
		}
	}


	/**
	 * Delete product set
	 *
	 * @since 2.3.0
	 *
	 * @param int $fb_product_set_id Facebook Product Set ID.
	 **/
	public function delete_product_set_item( $fb_product_set_id ) {
		$this->check_api_result( $this->fbgraph->delete_product_set_item( $fb_product_set_id ) );
	}

	/**
	 * Saves settings via AJAX (to preserve window context for onboarding).
	 *
	 * @internal
	 *
	 * @deprecated 2.0.0
	 */
	public function ajax_save_fb_settings() {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}

	/**
	 * Delete all settings via AJAX
	 *
	 * @deprecated 2.0.0
	 */
	function ajax_delete_fb_settings() {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}

	/**
	 * Checks the feed upload status (FBE v1.0).
	 *
	 * @internal
	 */
	public function ajax_check_feed_upload_status() {
		$response = array(
			'connected' => true,
			'status'    => 'complete',
		);
		printf( json_encode( $response ) );
		wp_die();
	}


	/**
	 * Check Feed Upload Status (FBE v2.0)
	 * TODO: When migrating to FBE v2.0, remove above function and rename
	 * below function to ajax_check_feed_upload_status()
	 **/
	public function ajax_check_feed_upload_status_v2() {

		\WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'check feed upload status', true );

		check_ajax_referer( 'wc_facebook_settings_jsx' );

		if ( $this->is_configured() ) {

			$response = [
				'connected' => true,
				'status'    => 'in progress',
			];

			if ( ! empty( $this->get_upload_id() ) ) {

				if ( ! isset( $this->fbproductfeed ) ) {

					if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) {
						include_once 'includes/fbproductfeed.php';
					}

					$this->fbproductfeed = new \WC_Facebook_Product_Feed(
						$this->get_product_catalog_id(),
						$this->fbgraph
					);
				}

				$status = $this->fbproductfeed->is_upload_complete( $this->settings );

				$response['status'] = $status;

			} else {

				$response = [
					'connected' => true,
					'status'    => 'error',
				];
			}

			if ( 'complete' === $response['status'] ) {

				update_option(
					$this->get_option_key(),
					apply_filters(
						'woocommerce_settings_api_sanitized_fields_' . $this->id,
						$this->settings
					)
				);
			}

		} else {

			$response = [ 'connected' => false ];
		}

		printf( json_encode( $response ) );
		wp_die();
	}

	/**
	 * Display custom success message (sugar)
	 **/
	function display_success_message( $msg ) {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_success',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Display custom warning message (sugar)
	 **/
	function display_warning_message( $msg ) {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_warning',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Display custom info message (sugar)
	 **/
	function display_info_message( $msg ) {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_info',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Display custom "sticky" info message.
	 * Call remove_sticky_message or wait for time out.
	 **/
	function display_sticky_message( $msg ) {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_sticky',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Remove custom "sticky" info message
	 **/
	function remove_sticky_message() {
		delete_transient( 'facebook_plugin_api_sticky' );
	}

	function remove_resync_message() {
		$msg = get_transient( 'facebook_plugin_api_sticky' );
		if ( $msg && strpos( $msg, 'Sync' ) !== false ) {
			delete_transient( 'facebook_plugin_resync_sticky' );
		}
	}


	/**
	 * Logs and stores custom error message (sugar).
	 *
	 * @param string $msg
	 */
	function display_error_message( $msg ) {

		WC_Facebookcommerce_Utils::log( $msg );

		set_transient( 'facebook_plugin_api_error', $msg, self::FB_MESSAGE_DISPLAY_TIME );
	}


	/**
	 * Displays error message from API result (sugar).
	 *
	 * @param array $result
	 */
	function display_error_message_from_result( $result ) {
		$error = json_decode( $result['body'] )->error;
		$msg   = ( 'Fatal' === $error->message && ! empty( $error->error_user_title ) ) ? $error->error_user_title : $error_message;
		$this->display_error_message( $msg );
	}


	/**
	 * Deals with FB API responses, displays error if FB API returns error.
	 *
	 * @param WP_Error|array $result API response
	 * @param array|null $logdata additional data for logging
	 * @param int|null $wpid post ID
	 * @return array|null|void result if response is 200, null otherwise
	 */
	function check_api_result( $result, $logdata = null, $wpid = null ) {

		if ( is_wp_error( $result ) ) {

			WC_Facebookcommerce_Utils::log( $result->get_error_message() );

			$message = sprintf(
				/* translators: Placeholders %1$s - original error message from Facebook API */
				esc_html__( 'There was an issue connecting to the Facebook API:  %1$s', 'facebook-for-woocommerce' ),
				$result->get_error_message()
			);

			$this->display_error_message( $message );

			return;
		}

		if ( $result['response']['code'] != '200' ) {

			// Catch 10800 fb error code ("Duplicate retailer ID") and capture FBID
			// if possible, otherwise let user know we found dupe SKUs
			$body = WC_Facebookcommerce_Utils::decode_json( $result['body'] );

			if ( $body && $body->error->code == '10800' ) {

				$error_data = $body->error->error_data; // error_data may contain FBIDs

				if ( $error_data && $wpid ) {

					$existing_id = $this->get_existing_fbid( $error_data, $wpid );

					if ( $existing_id ) {

						// Add "existing_id" ID to result
						$body->id       = $existing_id;
						$result['body'] = json_encode( $body );
						return $result;
					}
				}

			} else {

				$this->display_error_message_from_result( $result );
			}

			WC_Facebookcommerce_Utils::log( $result );

			$data = [
				'result' => $result,
				'data'   => $logdata,
			];
			WC_Facebookcommerce_Utils::fblog(
				'Non-200 error code from FB',
				$data,
				true
			);

			return null;
		}

		return $result;
	}


	/**
	 * Displays out of sync message if products are edited using WooCommerce Advanced Bulk Edit.
	 *
	 * @param $import_id
	 */
	function ajax_woo_adv_bulk_edit_compat( $import_id ) {

		if ( ! WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'adv bulk edit', false ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

		if ( strpos( $type, 'product' ) !== false && strpos( $type, 'load' ) === false ) {
			$this->display_out_of_sync_message( 'advanced bulk edit' );
		}
	}

	function wp_all_import_compat( $import_id ) {
		$import = new PMXI_Import_Record();
		$import->getById( $import_id );
		if ( ! $import->isEmpty() && in_array( $import->options['custom_type'], array( 'product', 'product_variation' ) ) ) {
			$this->display_out_of_sync_message( 'import' );
		}
	}

	function display_out_of_sync_message( $action_name ) {
		$this->display_sticky_message(
			sprintf(
				'Products may be out of Sync with Facebook due to your recent ' . $action_name . '.' .
				' <a href="%s&fb_force_resync=true&remove_sticky=true">Re-Sync them with FB.</a>',
				facebook_for_woocommerce()->get_settings_url()
			)
		);
	}

	/**
	 * If we get a product group ID or product item ID back for a dupe retailer
	 * id error, update existing ID.
	 *
	 * @return null
	 **/
	function get_existing_fbid( $error_data, $wpid ) {
		if ( isset( $error_data->product_group_id ) ) {
			update_post_meta(
				$wpid,
				self::FB_PRODUCT_GROUP_ID,
				(string) $error_data->product_group_id
			);
			return $error_data->product_group_id;
		} elseif ( isset( $error_data->product_item_id ) ) {
			update_post_meta(
				$wpid,
				self::FB_PRODUCT_ITEM_ID,
				(string) $error_data->product_item_id
			);
			return $error_data->product_item_id;
		} else {
			return;
		}
	}

	/**
	 * Checks for API key and other API errors.
	 */
	public function checks() {

		// TODO improve this by checking the settings page with Framework method and ensure error notices are displayed under the Integration sections {FN 2020-01-30}
		if ( isset( $_GET['page'] ) && 'wc-facebook' === $_GET['page'] ) {
			$this->display_errors();
		}

		$this->maybe_display_facebook_api_messages();
	}


	/**
	 * Gets a sample feed with up to 12 published products.
	 *
	 * @return string
	 */
	function get_sample_product_feed() {

		ob_start();

		// get up to 12 published posts that are products
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'fields'         => 'ids',
		];

		$post_ids = get_posts( $args );
		$items    = [];

		foreach ( $post_ids as $post_id ) {

			$woo_product  = new WC_Facebook_Product( $post_id );
			$product_data = $woo_product->prepare_product();

			$feed_item = [
				'title'        => strip_tags( $product_data['name'] ),
				'availability' => $woo_product->is_in_stock() ? 'in stock' :
				'out of stock',
				'description'  => strip_tags( $product_data['description'] ),
				'id'           => $product_data['retailer_id'],
				'image_link'   => $product_data['image_url'],
				'brand'        => Framework\SV_WC_Helper::str_truncate( wp_strip_all_tags( WC_Facebookcommerce_Utils::get_store_name() ), 100 ),
				'link'         => $product_data['url'],
				'price'        => $product_data['price'] . ' ' . get_woocommerce_currency(),
			];

			array_push( $items, $feed_item );
		}

		// https://codex.wordpress.org/Function_Reference/wp_reset_postdata
		wp_reset_postdata();

		ob_end_clean();

		return json_encode( [ $items ] );
	}

	/**
	 * Loop through array of WPIDs to remove metadata.
	 **/
	function delete_post_meta_loop( $products ) {
		foreach ( $products as $product_id ) {
			delete_post_meta( $product_id, self::FB_PRODUCT_GROUP_ID );
			delete_post_meta( $product_id, self::FB_PRODUCT_ITEM_ID );
			delete_post_meta( $product_id, Products::VISIBILITY_META_KEY );
		}
	}

	/**
	 * Remove FBIDs from all products when resetting store.
	 **/
	function reset_all_products() {
		if ( ! is_admin() ) {
			WC_Facebookcommerce_Utils::log(
				'Not resetting any FBIDs from products,
        must call reset from admin context.'
			);
			return false;
		}

		$test_instance   = WC_Facebook_Integration_Test::get_instance( $this );
		$this->test_mode = $test_instance::$test_mode;

		// Include draft products (omit 'post_status' => 'publish')
		WC_Facebookcommerce_Utils::log( 'Removing FBIDs from all products' );

		$post_ids = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$children = array();
		foreach ( $post_ids as $post_id ) {
			$children = array_merge(
				get_posts(
					array(
						'post_type'      => 'product_variation',
						'posts_per_page' => -1,
						'post_parent'    => $post_id,
						'fields'         => 'ids',
					)
				),
				$children
			);
		}
		$post_ids = array_merge( $post_ids, $children );
		$this->delete_post_meta_loop( $post_ids );

		WC_Facebookcommerce_Utils::log( 'Product FBIDs deleted' );
		return true;
	}

	/**
	 * Remove FBIDs from a single WC product
	 **/
	function reset_single_product( $wp_id ) {
		$woo_product = new WC_Facebook_Product( $wp_id );
		$products    = array( $woo_product->get_id() );
		if ( WC_Facebookcommerce_Utils::is_variable_type( $woo_product->get_type() ) ) {
			$products = array_merge( $products, $woo_product->get_children() );
		}

		$this->delete_post_meta_loop( $products );

		WC_Facebookcommerce_Utils::log( 'Deleted FB Metadata for product ' . $wp_id );
	}

	function ajax_reset_all_fb_products() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'reset products', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$this->reset_all_products();
		wp_reset_postdata();
		wp_die();
	}

	function ajax_reset_single_fb_product() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'reset single product', true );
		check_ajax_referer( 'wc_facebook_metabox_jsx' );
		if ( ! isset( $_POST['wp_id'] ) ) {
			wp_die();
		}

		$wp_id       = sanitize_text_field( wp_unslash( $_POST['wp_id'] ) );
		$woo_product = new WC_Facebook_Product( $wp_id );
		if ( $woo_product ) {
			$this->reset_single_product( $wp_id );
		}

		wp_reset_postdata();
		wp_die();
	}

	function ajax_delete_fb_product() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'delete single product', true );
		check_ajax_referer( 'wc_facebook_metabox_jsx' );
		if ( ! isset( $_POST['wp_id'] ) ) {
			wp_die();
		}

		$wp_id = sanitize_text_field( wp_unslash( $_POST['wp_id'] ) );
		$this->on_product_delete( $wp_id );
		$this->reset_single_product( $wp_id );
		wp_reset_postdata();
		wp_die();
	}

	/**
	 * Special function to run all visible products through on_product_publish
	 *
	 * @internal
	 */
	public function ajax_sync_all_fb_products() {

		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'syncall products', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );

		$this->sync_facebook_products( 'background' );
	}


	/**
	 * Syncs Facebook products using the GraphAPI.
	 *
	 * It can either use a Feed upload or update each product individually based on the selecetd method.
	 * Ends the request sending a JSON response indicating success or failure.
	 *
	 * @since 1.10.2
	 *
	 * @param string $method either 'feed' or 'background'
	 */
	private function sync_facebook_products( $method ) {

		try {

			if ( 'feed' === $method ) {

				$this->sync_facebook_products_using_feed();

			} elseif ( 'background' === $method ) {

				// if syncs starts, the background processor will continue executing until the request ends and no response will be sent back to the browser
				$this->sync_facebook_products_using_background_processor();
			}

			wp_send_json_success();

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			// Access token has expired
			if ( 190 === $e->getCode() ) {
				$error_message = __( 'Your connection has expired.', 'facebook-for-woocommerce' ) . ' <strong>' . __( 'Please click Manage connection > Advanced Options > Update Token to refresh your connection to Facebook.', 'facebook-for-woocommerce' ) . '</strong>';
			} else {
				$error_message = $e->getMessage();
			}

			$message = sprintf(
				/* translators: Placeholders %s - error message */
				__( 'There was an error trying to sync the products to Facebook. %s', 'facebook-for-woocommerce' ),
				$error_message
			);

			wp_send_json_error( [ 'error' => $message ] );
		}
	}


	/**
	 * Syncs Facebook products using the background processor.
	 *
	 * @since 1.10.2
	 *
	 * @throws Framework\SV_WC_Plugin_Exception
	 * @return bool
	 */
	private function sync_facebook_products_using_background_processor() {

		if ( ! $this->is_product_sync_enabled() ) {

			WC_Facebookcommerce_Utils::log( 'Sync to Facebook is disabled' );

			throw new Framework\SV_WC_Plugin_Exception( __( 'Product sync is disabled.', 'facebook-for-woocommerce' ) );
		}

		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {

			WC_Facebookcommerce_Utils::log( sprintf( 'Not syncing, the plugin is not configured or the Catalog ID is missing' ) );

			throw new Framework\SV_WC_Plugin_Exception( __( 'The plugin is not configured or the Catalog ID is missing.', 'facebook-for-woocommerce' ) );
		}

		$this->remove_resync_message();

		$currently_syncing = get_transient( self::FB_SYNC_IN_PROGRESS );

		if ( isset( $this->background_processor ) ) {
			if ( $this->background_processor->is_updating() ) {
				$this->background_processor->handle_cron_healthcheck();
				$currently_syncing = 1;
			}
		}

		if ( $currently_syncing ) {

			WC_Facebookcommerce_Utils::log( 'Not syncing again, sync already in progress' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync during an in-progress sync!',
				array(),
				true
			);

			throw new Framework\SV_WC_Plugin_Exception( __( 'A product sync is in progress. Please wait until the sync finishes before starting a new one.', 'facebook-for-woocommerce' ) );
		}

		if ( ! $this->fbgraph->is_product_catalog_valid( $this->get_product_catalog_id() ) ) {

			WC_Facebookcommerce_Utils::log( 'Not syncing, invalid product catalog!' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync with an invalid product catalog!',
				array(),
				true
			);

			throw new Framework\SV_WC_Plugin_Exception( __( "We've detected that your Facebook Product Catalog is no longer valid. This may happen if it was deleted, but could also be a temporary error. If the error persists, please click Manage connection > Advanced Options > Remove and setup the plugin again.", 'facebook-for-woocommerce' ) );
		}

		// Get all published posts. First unsynced then already-synced.
		$post_ids_new = WC_Facebookcommerce_Utils::get_wp_posts(
			self::FB_PRODUCT_GROUP_ID,
			'NOT EXISTS'
		);
		$post_ids_old = WC_Facebookcommerce_Utils::get_wp_posts(
			self::FB_PRODUCT_GROUP_ID,
			'EXISTS'
		);

		$total_new = count( $post_ids_new );
		$total_old = count( $post_ids_old );
		$post_ids  = array_merge( $post_ids_new, $post_ids_old );
		$total     = count( $post_ids );

		WC_Facebookcommerce_Utils::fblog(
			'Attempting to sync ' . $total . ' ( ' .
			$total_new . ' new) products with settings: ',
			$this->settings,
			false
		);

		// Check for background processing (Woo 3.x.x)
		if ( isset( $this->background_processor ) ) {
			$starting_message = sprintf(
				'Starting background sync to Facebook: %d products...',
				$total
			);

			set_transient(
				self::FB_SYNC_IN_PROGRESS,
				true,
				self::FB_SYNC_TIMEOUT
			);

			set_transient(
				self::FB_SYNC_REMAINING,
				(int) $total
			);

			$this->display_info_message( $starting_message );
			WC_Facebookcommerce_Utils::log( $starting_message );

			foreach ( $post_ids as $post_id ) {
				  WC_Facebookcommerce_Utils::log( 'Pushing post to queue: ' . $post_id );
				  $this->background_processor->push_to_queue( $post_id );
			}

			$this->background_processor->save()->dispatch();
			// reset FB_SYNC_REMAINING to avoid race condition
			set_transient(
				self::FB_SYNC_REMAINING,
				(int) $total
			);
			// handle_cron_healthcheck must be called
			// https://github.com/A5hleyRich/wp-background-processing/issues/34
			$this->background_processor->handle_cron_healthcheck();
		} else {
			// Oldschool sync for WooCommerce 2.x
			$count = ( $total_old === $total ) ? 0 : $total_old;
			foreach ( $post_ids as $post_id ) {
				// Repeatedly overwrite sync total while in actual sync loop
				set_transient(
					self::FB_SYNC_IN_PROGRESS,
					true,
					self::FB_SYNC_TIMEOUT
				);

				$this->display_sticky_message(
					sprintf(
						'Syncing products to Facebook: %d out of %d...',
						// Display different # when resuming to avoid confusion.
						min( $count, $total ),
						$total
					),
					true
				);

				$this->on_product_publish( $post_id );
				$count++;
			}
			WC_Facebookcommerce_Utils::log( 'Synced ' . $count . ' products' );
			$this->remove_sticky_message();
			$this->display_info_message( 'Facebook product sync complete!' );
			delete_transient( self::FB_SYNC_IN_PROGRESS );
			WC_Facebookcommerce_Utils::fblog(
				'Product sync complete. Total products synced: ' . $count
			);
		}

		// https://codex.wordpress.org/Function_Reference/wp_reset_postdata
		wp_reset_postdata();

		return true;
	}


	/**
	 * Special function to run all visible products by uploading feed.
	 *
	 * @internal
	 */
	public function ajax_sync_all_fb_products_using_feed() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions(
			'syncall products using feed',
			! $this->test_mode
		);
		check_ajax_referer( 'wc_facebook_settings_jsx' );

		$this->sync_facebook_products( 'feed' );
	}


	/**
	 * Syncs Facebook products using a Feed.
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/fbe/fbe1/guides/feed-approach
	 *
	 * @since 1.10.2
	 *
	 * @throws Framework\SV_WC_Plugin_Exception
	 * @return bool
	 */
	public function sync_facebook_products_using_feed() {

		if ( ! $this->is_product_sync_enabled() ) {
			WC_Facebookcommerce_Utils::log( 'Sync to Facebook is disabled' );

			throw new Framework\SV_WC_Plugin_Exception( __( 'Product sync is disabled.', 'facebook-for-woocommerce' ) );
		}

		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {

			WC_Facebookcommerce_Utils::log( sprintf( 'Not syncing, the plugin is not configured or the Catalog ID is missing' ) );

			throw new Framework\SV_WC_Plugin_Exception( __( 'The plugin is not configured or the Catalog ID is missing.', 'facebook-for-woocommerce' ) );
		}

		$this->remove_resync_message();

		if ( ! $this->fbgraph->is_product_catalog_valid( $this->get_product_catalog_id() ) ) {

			WC_Facebookcommerce_Utils::log( 'Not syncing, invalid product catalog!' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync with an invalid product catalog!',
				array(),
				true
			);

			throw new Framework\SV_WC_Plugin_Exception( __( "We've detected that your Facebook Product Catalog is no longer valid. This may happen if it was deleted, but could also be a temporary error. If the error persists, please click Manage connection > Advanced Options > Remove and setup the plugin again.", 'facebook-for-woocommerce' ) );
		}

		if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) {
			include_once 'includes/fbproductfeed.php';
		}
		if ( $this->test_mode ) {
			$this->fbproductfeed = new WC_Facebook_Product_Feed_Test_Mock(
				$this->get_product_catalog_id(),
				$this->fbgraph,
				$this->get_feed_id()
			);
		} else {
			$this->fbproductfeed = new WC_Facebook_Product_Feed(
				$this->get_product_catalog_id(),
				$this->fbgraph,
				$this->get_feed_id()
			);
		}

		if ( ! $this->fbproductfeed->sync_all_products_using_feed() ) {

			WC_Facebookcommerce_Utils::fblog( 'Sync all products using feed, curl failed', [], true );

			throw new Framework\SV_WC_Plugin_Exception( __( "We couldn't create the feed or upload the product information.", 'facebook-for-woocommerce' ) );
		}

		$this->update_feed_id( $this->fbproductfeed->feed_id );
		$this->update_upload_id( $this->fbproductfeed->upload_id );

		update_option(
			$this->get_option_key(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings )
		);

		wp_reset_postdata();

		return true;
	}


	/**
	 * Syncs Facebook products using a Feed.
	 *
	 * TODO: deprecate this methid in 1.11.0 or newer {WV 2020-03-12}
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/fbe/fbe1/guides/feed-approach
	 *
	 * @return bool
	 */
	public function sync_all_fb_products_using_feed() {

		try {
			$sync_started = $this->sync_facebook_products_using_feed();
		} catch ( Framework\SV_WC_Plugin_Exception $e ) {
			$sync_started = false;
		}

		return $sync_started;
	}


	/**
	 * Toggles product visibility via AJAX.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 **/
	public function ajax_fb_toggle_visibility() {

		wc_deprecated_function( __METHOD__, '1.10.0' );
	}


	/**
	 * Initializes the settings form fields.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0
	 *
	 * @internal
	 */
	public function init_form_fields() {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}


	/**
	 * Processes and saves options.
	 *
	 * TODO: remove this or move it to the new settings processing
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 * @deprecated 2.0.0
	 */
	public function process_admin_options() {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the page access token.
	 *
	 * TODO: remove this method by version 3.0.0 or by 2021-08-21 {WV 2020-08-21}
	 *
	 * @since 1.10.0
	 * @deprecated 2.1.0
	 *
	 * @return string
	 */
	public function get_page_access_token() {

		wc_deprecated_function( __METHOD__, '2.1.0', Connection::class . '::get_page_access_token()' );

		$access_token = facebook_for_woocommerce()->get_connection_handler()->get_page_access_token();

		/**
		 * Filters the Facebook page access token.
		 *
		 * @since 1.10.0
		 * @deprecated 2.1.0
		 *
		 * @param string $page_access_token Facebook page access token
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_page_access_token', ! $this->is_feed_migrated() ? $access_token : '', $this );
	}


	/**
	 * Gets the product catalog ID.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_product_catalog_id() {

		if ( ! is_string( $this->product_catalog_id ) ) {

			$value = get_option( self::OPTION_PRODUCT_CATALOG_ID, '' );

			$this->product_catalog_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook product catalog ID.
		 *
		 * @since 1.10.0
		 *
		 * @param string $product_catalog_id Facebook product catalog ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_product_catalog_id', $this->product_catalog_id, $this );
	}


	/**
	 * Gets the external merchant settings ID.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_external_merchant_settings_id() {

		if ( ! is_string( $this->external_merchant_settings_id ) ) {

			$value = get_option( self::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '' );

			$this->external_merchant_settings_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook external merchant settings ID.
		 *
		 * @since 1.10.0
		 *
		 * @param string $external_merchant_settings_id Facebook external merchant settings ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_external_merchant_settings_id', $this->external_merchant_settings_id, $this );
	}


	/**
	 * Gets the feed ID.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_feed_id() {

		if ( ! is_string( $this->feed_id ) ) {

			$value = get_option( self::OPTION_FEED_ID, '' );

			$this->feed_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook feed ID.
		 *
		 * @since 1.10.0
		 *
		 * @param string $feed_id Facebook feed ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_feed_id', $this->feed_id, $this );
	}


	/***
	 * Gets the Facebook Upload ID.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_upload_id() {

		if ( ! is_string( $this->upload_id ) ) {

			$value = get_option( self::OPTION_UPLOAD_ID, '' );

			$this->upload_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook upload ID.
		 *
		 * @since 1.11.0
		 *
		 * @param string $upload_id Facebook upload ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_upload_id', $this->upload_id, $this );
	}


	/**
	 * Gets the Facebook pixel install time in UTC seconds.
	 *
	 * @since 1.10.0
	 *
	 * @return int
	 */
	public function get_pixel_install_time() {

		if ( ! (int) $this->pixel_install_time ) {

			$value = (int) get_option( self::OPTION_PIXEL_INSTALL_TIME, 0 );

			$this->pixel_install_time = $value ?: null;
		}

		/**
		 * Filters the Facebook pixel install time.
		 *
		 * @since 1.10.0
		 *
		 * @param string $pixel_install_time Facebook pixel install time in UTC seconds, or null if none set
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (int) apply_filters( 'wc_facebook_pixel_install_time', $this->pixel_install_time, $this );
	}


	/**
	 * Gets the configured JS SDK version.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_js_sdk_version() {

		if ( ! is_string( $this->js_sdk_version ) ) {

			$value = get_option( self::OPTION_JS_SDK_VERSION, '' );

			$this->js_sdk_version = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook JS SDK version.
		 *
		 * @since 1.10.0
		 *
		 * @param string $js_sdk_version Facebook JS SDK version
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_js_sdk_version', $this->js_sdk_version, $this );
	}


	/**
	 * Gets the configured Facebook page ID.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_facebook_page_id() {

		/**
		 * Filters the configured Facebook page ID.
		 *
		 * @since 1.10.0
		 *
		 * @param string $page_id the configured Facebook page ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_page_id', get_option( self::SETTING_FACEBOOK_PAGE_ID, '' ), $this );
	}


	/**
	 * Gets the configured Facebook pixel ID.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_facebook_pixel_id() {

		/**
		 * Filters the configured Facebook pixel ID.
		 *
		 * @since 1.10.0
		 *
		 * @param string $pixel_id the configured Facebook pixel ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_pixel_id', get_option( self::SETTING_FACEBOOK_PIXEL_ID, '' ), $this );
	}

	/**
	 * Gets the configured use s2s flag.
	 *
	 * @return bool
	 */
	public function is_use_s2s_enabled() {
		return WC_Facebookcommerce_Pixel::get_use_s2s();
	}

	/**
	 * Gets the configured access token
	 *
	 * @return string
	 */
	public function get_access_token() {
		return WC_Facebookcommerce_Pixel::get_access_token();
	}


	/**
	 * Gets the IDs of the categories to be excluded from sync.
	 *
	 * @since 1.10.0
	 *
	 * @return int[]
	 */
	public function get_excluded_product_category_ids() {

		/**
		 * Filters the configured excluded product category IDs.
		 *
		 * @since 1.10.0
		 *
		 * @param int[] $category_ids the configured excluded product category IDs
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (array) apply_filters( 'wc_facebook_excluded_product_category_ids', get_option( self::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [] ), $this );
	}


	/**
	 * Gets the IDs of the tags to be excluded from sync.
	 *
	 * @since 1.10.0
	 *
	 * @return int[]
	 */
	public function get_excluded_product_tag_ids() {

		/**
		 * Filters the configured excluded product tag IDs.
		 *
		 * @since 1.10.0
		 *
		 * @param int[] $tag_ids the configured excluded product tag IDs
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (array) apply_filters( 'wc_facebook_excluded_product_tag_ids', get_option( self::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [] ), $this );
	}


	/**
	 * Gets the configured product description mode.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_product_description_mode() {

		/**
		 * Filters the configured product description mode.
		 *
		 * @since 1.10.0
		 *
		 * @param string $mode the configured product description mode
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		$mode = (string) apply_filters( 'wc_facebook_product_description_mode', get_option( self::SETTING_PRODUCT_DESCRIPTION_MODE, self::PRODUCT_DESCRIPTION_MODE_STANDARD ), $this );

		$valid_modes = [
			self::PRODUCT_DESCRIPTION_MODE_STANDARD,
			self::PRODUCT_DESCRIPTION_MODE_SHORT,
		];

		if ( ! in_array( $mode, $valid_modes, true ) ) {
			$mode = self::PRODUCT_DESCRIPTION_MODE_STANDARD;
		}

		return $mode;
	}


	/**
	 * Gets the configured scheduled re-sync offset in seconds.
	 *
	 * Returns null if no offset is configured.
	 *
	 * @since 1.10.0
	 * @deprecated 2.0.0
	 *
	 * @return int|null
	 */
	public function get_scheduled_resync_offset() {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}


	/**
	 * Gets the configured Facebook messenger locale.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_messenger_locale() {

		/**
		 * Filters the configured Facebook messenger locale.
		 *
		 * @since 1.10.0
		 *
		 * @param string $locale the configured Facebook messenger locale
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_messenger_locale', get_option( self::SETTING_MESSENGER_LOCALE, 'en_US' ), $this );
	}


	/**
	 * Gets the configured Facebook messenger greeting.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_messenger_greeting() {

		/**
		 * Filters the configured Facebook messenger greeting.
		 *
		 * @since 1.10.0
		 *
		 * @param string $greeting the configured Facebook messenger greeting
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		$greeting = (string) apply_filters( 'wc_facebook_messenger_greeting', get_option( self::SETTING_MESSENGER_GREETING, __( "Hi! We're here to answer any questions you may have.", 'facebook-for-woocommerce' ) ), $this );

		return Framework\SV_WC_Helper::str_truncate( $greeting, $this->get_messenger_greeting_max_characters(), '' );
	}


	/**
	 * Gets the maximum number of characters allowed in the messenger greeting.
	 *
	 * @since 1.10.0
	 *
	 * @return int
	 */
	public function get_messenger_greeting_max_characters() {

		$default = 80;

		/**
		 * Filters the maximum number of characters allowed in the messenger greeting.
		 *
		 * @since 1.10.0
		 *
		 * @param int $max the maximum number of characters allowed in the messenger greeting
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		$max = (int) apply_filters( 'wc_facebook_messenger_greeting_max_characters', $default, $this );

		return $max < 1 ? $default : $max;
	}


	/**
	 * Gets the configured Facebook messenger color hex.
	 *
	 * This is used to style the messenger UI.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_messenger_color_hex() {

		/**
		 * Filters the configured Facebook messenger color hex.
		 *
		 * @since 1.10.0
		 *
		 * @param string $hex the configured Facebook messenger color hex
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_messenger_color_hex', get_option( self::SETTING_MESSENGER_COLOR_HEX, '#0084ff' ), $this );
	}


	/** Setter methods ************************************************************************************************/


	/**
	 * Updates the Facebook page access token.
	 *
	 * TODO: remove this method by version 3.0.0 or by 2021-08-21 {WV 2020-08-21}
	 *
	 * @since 1.10.0
	 * @deprecated 2.1.0
	 *
	 * @param string $value page access token value
	 */
	public function update_page_access_token( $value ) {

		wc_deprecated_function( __METHOD__, '2.1.0', Connection::class . '::update_page_access_token()' );

		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( $value );
	}


	/**
	 * Updates the Facebook product catalog ID.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value product catalog ID value
	 */
	public function update_product_catalog_id( $value ) {

		$this->product_catalog_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_PRODUCT_CATALOG_ID, $this->product_catalog_id );
	}


	/**
	 * Updates the Facebook external merchant settings ID.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value external merchant settings ID value
	 */
	public function update_external_merchant_settings_id( $value ) {

		$this->external_merchant_settings_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, $this->external_merchant_settings_id );
	}


	/**
	 * Updates the Facebook feed ID.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value feed ID value
	 */
	public function update_feed_id( $value ) {

		$this->feed_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_FEED_ID, $this->feed_id );
	}


	/**
	 * Updates the Facebook upload ID.
	 *
	 * @since 1.11.0
	 *
	 * @param string $value upload ID value
	 */
	public function update_upload_id( $value ) {

		$this->upload_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_UPLOAD_ID, $this->upload_id );
	}


	/**
	 * Updates the Facebook pixel install time.
	 *
	 * @since 1.10.0
	 *
	 * @param int $value pixel install time, in UTC seconds
	 */
	public function update_pixel_install_time( $value ) {

		$value = (int) $value;

		$this->pixel_install_time = $value ?: null;

		update_option( self::OPTION_PIXEL_INSTALL_TIME, $value ?: '' );
	}


	/**
	 * Updates the Facebook JS SDK version.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value JS SDK version
	 */
	public function update_js_sdk_version( $value ) {

		$this->js_sdk_version = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_JS_SDK_VERSION, $this->js_sdk_version );
	}


	/**
	 * Sanitizes a value that's a Facebook credential.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value value to sanitize
	 * @return string
	 */
	private function sanitize_facebook_credential( $value ) {

		return wc_clean( is_string( $value ) ? $value : '' );
	}


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines whether Facebook for WooCommerce is configured.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_configured() {

		return $this->get_facebook_page_id() && facebook_for_woocommerce()->get_connection_handler()->is_connected();
	}


	/**
	 * Determines whether advanced matching is enabled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_advanced_matching_enabled() {

		/**
		 * Filters whether advanced matching is enabled.
		 *
		 * @since 1.10.0
		 *
		 * @param bool $is_enabled whether advanced matching is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (bool) apply_filters( 'wc_facebook_is_advanced_matching_enabled', true, $this );
	}


	/**
	 * Determines whether product sync is enabled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_product_sync_enabled() {

		/**
		 * Filters whether product sync is enabled.
		 *
		 * @since 1.10.0
		 *
		 * @param bool $is_enabled whether product sync is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (bool) apply_filters( 'wc_facebook_is_product_sync_enabled', 'yes' === get_option( self::SETTING_ENABLE_PRODUCT_SYNC, 'yes' ), $this );
	}


	/**
	 * Determines whether the scheduled re-sync is enabled.
	 *
	 * @since 1.10.0
	 * @deprecated 2.0.0
	 *
	 * @return bool
	 */
	public function is_scheduled_resync_enabled() {

		wc_deprecated_function( __METHOD__, '2.0.0' );

		return false;
	}


	/**
	 * Determines whether the Facebook messenger is enabled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_messenger_enabled() {

		/**
		 * Filters whether the Facebook messenger is enabled.
		 *
		 * @since 1.10.0
		 *
		 * @param bool $is_enabled whether the Facebook messenger is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (bool) apply_filters( 'wc_facebook_is_messenger_enabled', 'yes' === get_option( self::SETTING_ENABLE_MESSENGER ), $this );
	}


	/**
	 * Determines whether debug mode is enabled.
	 *
	 * @since 1.10.2
	 *
	 * @return bool
	 */
	public function is_debug_mode_enabled() {

		/**
		 * Filters whether debug mode is enabled.
		 *
		 * @since 1.10.2
		 *
		 * @param bool $is_enabled whether debug mode is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (bool) apply_filters( 'wc_facebook_is_debug_mode_enabled', 'yes' === get_option( self::SETTING_ENABLE_DEBUG_MODE ), $this );
	}


	/***
	 * Determines if the feed has been migrated from FBE 1 to FBE 1.5
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	public function is_feed_migrated() {

		if ( ! is_bool( $this->feed_migrated ) ) {

			$value = get_option( 'wc_facebook_feed_migrated', 'no' );

			$this->feed_migrated = wc_string_to_bool( $value );
		}

		return $this->feed_migrated;
	}


	/**
	 * Gets message HTML.
	 *
	 * @return string
	 */
	private function get_message_html( $message, $type = 'error' ) {
		ob_start();

		?>
			<div class="notice is-dismissible notice-<?php echo esc_attr( $type ); ?>">
				<p>
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $message;
				?>
				</p>
			</div>
		<?php

		return ob_get_clean();
	}


	/**
	 * Displays relevant messages to user from transients, clear once displayed.
	 */
	public function maybe_display_facebook_api_messages() {

		if ( $error_msg = get_transient( 'facebook_plugin_api_error' ) ) {

			$message = '<strong>' . __( 'Facebook for WooCommerce error:', 'facebook-for-woocommerce' ) . '</strong></br>' . $error_msg;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $message );

			delete_transient( 'facebook_plugin_api_error' );

			WC_Facebookcommerce_Utils::fblog(
				$error_msg,
				[],
				true
			);
		}

		$warning_msg = get_transient( 'facebook_plugin_api_warning' );

		if ( $warning_msg ) {

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $warning_msg, 'warning' );

			delete_transient( 'facebook_plugin_api_warning' );
		}

		$success_msg = get_transient( 'facebook_plugin_api_success' );

		if ( $success_msg ) {

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $success_msg, 'success' );

			delete_transient( 'facebook_plugin_api_success' );
		}

		$info_msg = get_transient( 'facebook_plugin_api_info' );

		if ( $info_msg ) {

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $info_msg, 'info' );

			delete_transient( 'facebook_plugin_api_info' );
		}

		$sticky_msg = get_transient( 'facebook_plugin_api_sticky' );

		if ( $sticky_msg ) {

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $sticky_msg, 'info' );

			// transient must be deleted elsewhere, or wait for timeout
		}
	}


	/**
	 * Gets the array that holds the name and url of the configured Facebook page.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_page() {

		if ( ! is_array( $this->page ) && $this->is_configured() ) {

			try {

				$response = facebook_for_woocommerce()->get_api()->get_page( $this->get_facebook_page_id() );

				$this->page = [
					'name' => $response->get_name(),
					'url'  => $response->get_url(),
				];

			} catch ( Framework\SV_WC_API_Exception $e ) {

				// we intentionally set $this->page to an empty array if an error occurs to avoid additional API requests
				// it's unlikely that we will get a different result if the exception was caused by an expired token, incorrect page ID, or rate limiting error
				$this->page = [];

				$message = sprintf( __( 'There was an error trying to retrieve information about the Facebook page: %s' ), $e->getMessage() );

				facebook_for_woocommerce()->log( $message );
			}
		}

		return is_array( $this->page ) ? $this->page : [];
	}


	/**
	 * Gets the name of the configured Facebook page.
	 *
	 * @return string
	 */
	public function get_page_name() {

		$page = $this->get_page();

		return isset( $page['name'] ) ? $page['name'] : '';
	}


	/**
	 * Gets the Facebook page URL.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_page_url() {

		$page = $this->get_page();

		return isset( $page['url'] ) ? $page['url'] : '';
	}


	/**
	 * Gets Messenger or Instagram tooltip message.
	 *
	 * @return string
	 */
	function get_nux_message_ifexist() {

		$nux_type_to_elemid_map = [
			'messenger_chat'     => 'connect_button',
			'instagram_shopping' => 'connect_button',
		];

		$nux_type_to_message_map = [
			'messenger_chat'     => __( 'Get started with Messenger Customer Chat' ),
			'instagram_shopping' => __( 'Get started with Instagram Shopping' ),
		];

		$message = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nux'] ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nux_type = sanitize_text_field( wp_unslash( $_GET['nux'] ) );

			ob_start();

			?>

			<div class="nux-message" style="display: none;"
			     data-target="<?php echo esc_attr( $nux_type_to_elemid_map[ $nux_type ] ); ?>">
				<div class="nux-message-text">
					<?php echo esc_attr( $nux_type_to_message_map[ $nux_type ] ); ?>
				</div>
				<div class="nux-message-arrow"></div>
				<i class="nux-message-close-btn">x</i>
			</div>
			<script>( function () { fbe_init_nux_messages(); } )();</script>

			<?php

			$message = ob_get_clean();
		}

		return $message;
	}


	/**
	 * Admin Panel Options
	 */
	function admin_options() {

		facebook_for_woocommerce()->get_message_handler()->show_messages();

		?>

		<div id="integration-settings" <?php echo ! $this->is_configured() ? 'style="display: none"' : ''; ?>>
			<table class="form-table"><?php $this->generate_settings_html( $this->get_form_fields() ); ?></table>
		</div>

		<?php
	}


	function delete_product_item( $wp_id ) {
		$fb_product_item_id = $this->get_product_fbid(
			self::FB_PRODUCT_ITEM_ID,
			$wp_id
		);
		if ( $fb_product_item_id ) {
			// TODO: Update this
			$pi_result =
			$this->fbgraph->delete_product_item( $fb_product_item_id );
			WC_Facebookcommerce_Utils::log( $pi_result );
		}
	}


	function fb_duplicate_product_reset_meta( $to_delete ) {
		array_push( $to_delete, self::FB_PRODUCT_ITEM_ID );
		array_push( $to_delete, self::FB_PRODUCT_GROUP_ID );
		return $to_delete;
	}


	/**
	 * Helper function to update FB visibility.
	 *
	 * @param int|\WC_Product $product_id product ID or product object
	 * @param string $visibility visibility
	 */
	function update_fb_visibility( $product_id, $visibility ) {

		// bail if the plugin is not configured properly
		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {
			return;
		}

		$product = $product_id instanceof \WC_Product ? $product_id : wc_get_product( $product_id );

		// bail if product isn't found
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$should_set_visible = $visibility === self::FB_SHOP_PRODUCT_VISIBLE;

		if ( $product->is_type( 'variation' ) ) {

			Products::set_product_visibility( $product, $should_set_visible );

			facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( [ $product->get_id() ] );

		} elseif ( $product->is_type( 'variable' ) ) {

			// parent product
			Products::set_product_visibility( $product, $should_set_visible );

			// we should not add the parent product ID to the array of product IDs to be
			// updated because product groups, which are used to represent the parent product
			// for variable products, don't have the visibility property on Facebook
			$product_ids = [];

			// set visibility for all children
			foreach ( $product->get_children() as $index => $id ) {

				$product = wc_get_product( $id );

				if ( ! $product instanceof \WC_Product ) {
				    continue;
				}

				Products::set_product_visibility( $product, $should_set_visible );

				$product_ids[] = $product->get_id();
			}

			// sync product with all variations
			facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( $product_ids );

		} else {

			$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $product_id );

			if ( ! $fb_product_item_id ) {
				\WC_Facebookcommerce_Utils::fblog( $fb_product_item_id . " doesn't exist but underwent a visibility transform.", [], true );
				 return;
			}

			// TODO: Update
			$set_visibility = facebook_for_woocommerce()->get_api()->update_product_item( $fb_product_item_id, [ 'visibility' => $visibility ] );
			if ( $this->check_api_result( $set_visibility ) ) {
				Products::set_product_visibility( $product, $should_set_visible );
			}
		}
	}


	/**
	 * Sync product upon quick or bulk edit save action.
	 *
	 * @internal
	 *
	 * @param \WC_Product $product product object
	 */
	public function on_quick_and_bulk_edit_save( $product ) {

		// bail if not a product or product is not enabled for sync
		if ( ! $product instanceof \WC_Product || ! Products::published_product_should_be_synced( $product ) ) {
			return;
		}

		$wp_id      = $product->get_id();
		$visibility = get_post_status( $wp_id ) === 'publish' ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		if ( $visibility === self::FB_SHOP_PRODUCT_VISIBLE ) {
			// - new status is 'publish' regardless of old status, sync to Facebook
			$this->on_product_publish( $wp_id );
		} else {
			// - product never published to Facebook, new status is not publish
			// - product new status is not publish but may have been published before
			$this->update_fb_visibility( $product, $visibility );
		}
	}


	/**
	 * Gets Facebook product ID from meta or from Facebook API.
	 *
	 * @param string $fbid_type ID type (group or item)
	 * @param int $wp_id post ID
	 * @param WC_Facebook_Product|null $woo_product product
	 * @return mixed|void|null
	 */
	public function get_product_fbid( $fbid_type, $wp_id, $woo_product = null ) {

		$fb_id = WC_Facebookcommerce_Utils::get_fbid_post_meta(
			$wp_id,
			$fbid_type
		);

		if ( $fb_id ) {
			return $fb_id;
		}

		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id );
		}

		$products = WC_Facebookcommerce_Utils::get_product_array( $woo_product );

		// if the product with ID equal to $wp_id is variable, $woo_product will be the first child
		$woo_product = new WC_Facebook_Product( current( $products ) );

		$fb_retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );

		$product_fbid_result = $this->fbgraph->get_facebook_id(
			$this->get_product_catalog_id(),
			$fb_retailer_id
		);

		if ( is_wp_error( $product_fbid_result ) ) {

			WC_Facebookcommerce_Utils::log( $product_fbid_result->get_error_message() );

			$this->display_error_message(
				sprintf(
					/* translators: Placeholders %1$s - original error message from Facebook API */
					esc_html__( 'There was an issue connecting to the Facebook API: %s', 'facebook-for-woocommerce' ),
					$product_fbid_result->get_error_message()
				)
			);

			return;
		}

		if ( $product_fbid_result && isset( $product_fbid_result['body'] ) ) {

			$body = WC_Facebookcommerce_Utils::decode_json( $product_fbid_result['body'] );

			if ( ! empty( $body->id ) ) {

				if ( $fbid_type == self::FB_PRODUCT_GROUP_ID ) {
					$fb_id = $body->product_group->id;
				} else {
					$fb_id = $body->id;
				}

				update_post_meta(
					$wp_id,
					$fbid_type,
					$fb_id
				);

				return $fb_id;
			}
		}

		return;
	}


	/**
	 * Display test result.
	 **/
	function ajax_display_test_result() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'test result', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$response  = array(
			'pass' => 'true',
		);
		$test_pass = get_option( 'fb_test_pass', null );
		if ( ! isset( $test_pass ) ) {
			$response['pass'] = 'in progress';
		} elseif ( $test_pass == 0 ) {
			$response['pass']        = 'false';
			$response['debug_info']  = get_transient( 'facebook_plugin_test_fail' );
			$response['stack_trace'] =
			get_transient( 'facebook_plugin_test_stack_trace' );
			$response['stack_trace'] =
			preg_replace( "/\n/", '<br>', $response['stack_trace'] );
			delete_transient( 'facebook_plugin_test_fail' );
			delete_transient( 'facebook_plugin_test_stack_trace' );
		}
		delete_option( 'fb_test_pass' );
		printf( json_encode( $response ) );
		wp_die();
	}


	/**
	 * Schedules a recurring event to sync products.
	 *
	 * @deprecated 1.10.0
	 */
	function ajax_schedule_force_resync() {

		wc_deprecated_function( __METHOD__, '1.10.0' );
		die;
	}


	/**
	 * Adds an recurring action to sync products.
	 *
	 * The action is scheduled using a cron schedule instead of a recurring interval (see https://en.wikipedia.org/wiki/Cron#Overview).
	 * A cron schedule should allow for the action to run roughly at the same time every day regardless of the duration of the task.
	 *
	 * @since 1.10.0
	 *
	 * @param int $offset number of seconds since the beginning of the daay
	 */
	public function schedule_resync( $offset ) {

		try {

			$current_time         = new DateTime( 'now', new DateTimeZone( wc_timezone_string() ) );
			$first_scheduled_time = new DateTime( "today +{$offset} seconds", new DateTimeZone( wc_timezone_string() ) );
			$next_scheduled_time  = new DateTime( "today +1 day {$offset} seconds", new DateTimeZone( wc_timezone_string() ) );

		} catch ( \Exception $e ) {
			// TODO: log an error indicating that it was not possible to schedule a recurring action to sync products {WV 2020-01-28}
			return;
		}

		// unschedule previously scheduled resync actions
		$this->unschedule_resync();

		$timestamp = $first_scheduled_time >= $current_time ? $first_scheduled_time->getTimestamp() : $next_scheduled_time->getTimestamp();

		// TODO: replace 'facebook-for-woocommerce' with the plugin ID once we stat using the Framework {WV 2020-01-30}
		as_schedule_single_action( $timestamp, self::ACTION_HOOK_SCHEDULED_RESYNC, [], 'facebook-for-woocommerce' );
	}


	/**
	 * Removes the recurring action that syncs products.
	 *
	 * @since 1.10.0
	 */
	private function unschedule_resync() {

		// TODO: replace 'facebook-for-woocommerce' with the plugin ID once we stat using the Framework {WV 2020-01-30}
		as_unschedule_all_actions( self::ACTION_HOOK_SCHEDULED_RESYNC, [], 'facebook-for-woocommerce' );
	}


	/**
	 * Determines whether a recurring action to sync products is scheduled and not running.
	 *
	 * @see \as_next_scheduled_action()
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_resync_scheduled() {

		// TODO: replace 'facebook-for-woocommerce' with the plugin ID once we stat using the Framework {WV 2020-01-30}
		return is_int( as_next_scheduled_action( self::ACTION_HOOK_SCHEDULED_RESYNC, [], 'facebook-for-woocommerce' ) );
	}


	/**
	 * Handles the scheduled action used to sync products daily.
	 *
	 * It will schedule a new action if product sync is enabled and the plugin is configured to resnyc procucts daily.
	 *
	 * @internal
	 *
	 * @see \WC_Facebookcommerce_Integration::schedule_resync()
	 *
	 * @since 1.10.0
	 */
	public function handle_scheduled_resync_action() {

		try {
			$this->sync_facebook_products_using_feed();
		} catch ( Framework\SV_WC_Plugin_Exception $e ) {}

		$resync_offset = $this->get_scheduled_resync_offset();

		// manually schedule the next product resync action if possible
		if ( null !== $resync_offset && $this->is_product_sync_enabled() && ! $this->is_resync_scheduled() ) {
			$this->schedule_resync( $resync_offset );
		}
	}

	/**
	 * Handles the schedule feed generation action, triggered by the REST API.
	 *
	 * @since 1.11.0
	 */
	public function handle_generate_product_catalog_feed() {

		$feed_handler = new WC_Facebook_Product_Feed();

		try {

			$feed_handler->generate_feed();

		} catch ( \Exception $exception ) {

			WC_Facebookcommerce_Utils::log( 'Error generating product catalog feed. ' . $exception->getMessage() );
		}
	}


	/** Deprecated methods ********************************************************************************************/


	/**
	 * Enables product sync delay notice when a post is moved to the trash.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 * @deprecated 2.0.0
	 *
	 * @param int $post_id the post ID
	 */
	public function on_product_trash( $post_id ) {

		wc_deprecated_function( __METHOD__, '2.0.0' );
	}


}
