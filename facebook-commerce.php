<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;
use SkyVerge\WooCommerce\Facebook\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'facebook-config-warmer.php';
require_once 'includes/fbproduct.php';
require_once 'facebook-commerce-pixel-event.php';

class WC_Facebookcommerce_Integration extends WC_Integration {


	/** @var string the WordPress option name where the page access token is stored */
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
	const SETTING_FACEBOOK_PAGE_ID = 'facebook_page_id';

	/** @var string the facebook pixel ID setting ID */
	const SETTING_FACEBOOK_PIXEL_ID = 'facebook_pixel_id';

	/** @var string the "enable advanced matching" setting ID */
	const SETTING_ENABLE_ADVANCED_MATCHING = 'enable_advanced_matching';

	/** @var string the "enable product sync" setting ID */
	const SETTING_ENABLE_PRODUCT_SYNC = 'enable_product_sync';

	/** @var string the excluded product category IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS = 'excluded_product_category_ids';

	/** @var string the excluded product tag IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_TAG_IDS = 'excluded_product_tag_ids';

	/** @var string the product description mode setting ID */
	const SETTING_PRODUCT_DESCRIPTION_MODE = 'product_description_mode';

	/** @var string the scheduled resync offset setting ID */
	const SETTING_SCHEDULED_RESYNC_OFFSET = 'scheduled_resync_offset';

	/** @var string the "enable messenger" setting ID */
	const SETTING_ENABLE_MESSENGER = 'enable_messenger';

	/** @var string the messenger locale setting ID */
	const SETTING_MESSENGER_LOCALE = 'messenger_locale';

	/** @var string the messenger greeting setting ID */
	const SETTING_MESSENGER_GREETING = 'messenger_greeting';

	/** @var string the messenger color HEX setting ID */
	const SETTING_MESSENGER_COLOR_HEX = 'messenger_color_hex';

	/** @var string the "debug mode" setting ID */
	const SETTING_ENABLE_DEBUG_MODE = 'enable_debug_mode';

	/** @var string the standard product description mode name */
	const PRODUCT_DESCRIPTION_MODE_STANDARD = 'standard';

	/** @var string the short product description mode name */
	const PRODUCT_DESCRIPTION_MODE_SHORT = 'short';

	/** @var string the hook for the recurreing action that syncs products */
	const ACTION_HOOK_SCHEDULED_RESYNC = 'sync_all_fb_products_using_feed';


	/** @var string|null the configured page access token */
	private $page_access_token;

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


	/** Legacy properties *********************************************************************************************/


	// TODO probably some of these meta keys need to be moved to Facebook\Products {FN 2020-01-13}
	const FB_PRODUCT_GROUP_ID    = 'fb_product_group_id';
	const FB_PRODUCT_ITEM_ID     = 'fb_product_item_id';
	const FB_PRODUCT_DESCRIPTION = 'fb_product_description';

	/** @var string the API flag to set a product as visible in the Facebook shop */
	const FB_SHOP_PRODUCT_VISIBLE = 'published';

	/** @var string the API flag to set a product as not visible in the Facebook shop */
	const FB_SHOP_PRODUCT_HIDDEN = 'staging';

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

		if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
			include_once 'includes/fbutils.php';
		}

		WC_Facebookcommerce_Utils::$ems = $this->get_external_merchant_settings_id();

		if ( ! class_exists( 'WC_Facebookcommerce_Graph_API' ) ) {
			include_once 'includes/fbgraph.php';
			$this->fbgraph = new WC_Facebookcommerce_Graph_API( $this->get_page_access_token() );
		}

		WC_Facebookcommerce_Utils::$fbgraph = $this->fbgraph;

		// Hooks
		if ( is_admin() ) {

			$this->init_pixel();

			$this->init_form_fields();

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
			add_action(
				'woocommerce_update_options_integration_facebookcommerce',
				array( $this, 'process_admin_options' )
			);
			add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

			add_action(
				'wp_ajax_ajax_save_fb_settings',
				array( $this, 'ajax_save_fb_settings' ),
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_delete_fb_settings',
				array( $this, 'ajax_delete_fb_settings' ),
				self::FB_PRIORITY_MID
			);

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

			add_action(
				'wp_ajax_ajax_update_fb_option',
				array( $this, 'ajax_update_fb_option' ),
				self::FB_PRIORITY_MID
			);

			// Only load product processing hooks if we have completed setup.
			if ( $this->get_page_access_token() && $this->get_product_catalog_id() ) {

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

				add_action( 'trashed_post', [ $this, 'on_product_trash' ] );

				add_action(
					'before_delete_post',
					array( $this, 'on_product_delete' ),
					10,
					1
				);

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

				add_filter(
					'woocommerce_duplicate_product_exclude_meta',
					array( $this, 'fb_duplicate_product_reset_meta' )
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

				if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
					include_once 'includes/fbwpml.php';
					new WC_Facebook_WPML_Injector();
				}
			}
			$this->load_background_sync_process();
		}
		// Must be outside of admin for cron to schedule correctly.
		add_action( 'sync_all_fb_products_using_feed', [ $this, 'handle_scheduled_resync_action' ], self::FB_PRIORITY_MID );

		if ( $this->get_facebook_pixel_id() ) {
			$user_info            = WC_Facebookcommerce_Utils::get_user_info( $this->is_advanced_matching_enabled() );
			$this->events_tracker = new WC_Facebookcommerce_EventsTracker( $user_info );
		}

		if ( $this->is_messenger_enabled() ) {

			$this->messenger_chat = new WC_Facebookcommerce_MessengerChat( [
				'fb_page_id'                                  => $this->get_facebook_page_id(),
				'is_messenger_chat_plugin_enabled'            => wc_bool_to_string( $this->is_messenger_enabled() ),
				'msger_chat_customization_greeting_text_code' => $this->get_messenger_greeting(),
				'msger_chat_customization_locale'             => $this->get_messenger_locale(),
				'msger_chat_customization_theme_color_code'   => $this->get_messenger_color_hex(),
				'facebook_jssdk_version'                      => $this->get_js_sdk_version(),
			] );
		}
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
		if ( $this->get_page_access_token() ) {
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
				'/assets/js/facebook-metabox.min.js?ts=' . time(),
				__FILE__
			)
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
		$fb_product_group_id = $this->get_product_fbid(
			self::FB_PRODUCT_GROUP_ID,
			$post->ID,
			$woo_product
		);

		?>
			<span id="fb_metadata">
		<?php

		if ( $fb_product_group_id ) {

			?>
				<?php echo esc_html__( 'Facebook ID:', 'facebook-for-woocommerce' ); ?>
				<a href="https://facebook.com/<?php echo esc_attr( $fb_product_group_id ); ?>"
				target="_blank">
					<?php echo esc_html( $fb_product_group_id ); ?>
				</a>
				<p/>
			<?php

			if ( WC_Facebookcommerce_Utils::is_variable_type( $woo_product->get_type() ) ) {

				?>
					<p><?php echo esc_html__( 'Variant IDs:', 'facebook-for-woocommerce' ); ?><br/>
				<?php

				$children = $woo_product->get_children();

				foreach ( $children as $child_id ) {

					$fb_product_item_id = $this->get_product_fbid(
						self::FB_PRODUCT_ITEM_ID,
						$child_id
					);

					?>
						<?php echo esc_html( $child_id ); ?>:
						<a href="https://facebook.com/<?php echo esc_attr( $fb_product_item_id ); ?>"
						target="_blank">
							<?php echo esc_html( $fb_product_item_id ); ?>
						</a><br/>
					<?php
				}

				?>
					</p>
				<?php
			}

			?>
				<?php /* ?>
				<?php echo esc_html__( 'Visible:', 'facebook-for-woocommerce' ); ?>
				<input name="<?php echo esc_attr( Products::VISIBILITY_META_KEY ); ?>"
				type="checkbox"
				value="1"
				<?php echo checked( ! $woo_product->woo_product instanceof \WC_Product || Products::is_product_visible( $woo_product->woo_product ) ); ?>/>

				<p/>
				<?php */ ?>
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
	 * Gets the total of published products.
	 *
	 * @return int
	 */
	private function get_product_count() {

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
				'/assets/js/facebook-infobanner.min.js?ts=' . time(),
				__FILE__
			)
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
			)
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
			hasClientSideFeedUpload: '<?php echo esc_js( ! ! $this->get_feed_id() ); ?>'
		},
		feedPrepared: {
			feedUrl: '',
			feedPingUrl: '',
			samples: <?php echo $this->get_sample_product_feed(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		},
		/**tokenExpired: '<?php echo $this->get_page_access_token() && ! $this->get_page_name(); ?>',*/
		excludedCategoryIDs: <?php echo json_encode( $this->get_excluded_product_category_ids() ); ?>,
		excludedTagIDs: <?php echo json_encode( $this->get_excluded_product_tag_ids() ); ?>,
		messengerGreetingMaxCharacters: <?php echo esc_js( $this->get_messenger_greeting_max_characters() ); ?>
	};

	</script>

		<?php
		$ajax_data = [
			'nonce' => wp_create_nonce( 'wc_facebook_settings_jsx' ),
		];
		wp_enqueue_script(
			'wc_facebook_settings_jsx',
			plugins_url(
				'/assets/js/facebook-settings.min.js?ts=' . time(),
				__FILE__
			)
		);
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
			)
		);
	}


	/**
	 * Checks the product type and calls the corresponding on publish method.
	 *
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param int $wp_id post ID
	 */
	function on_product_save( $wp_id ) {

		$product = wc_get_product( $wp_id );

		if ( ! $product ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sync_enabled = ! empty( $_POST['fb_sync_enabled'] );
		$is_visible   = ! empty( $_POST[ Products::VISIBILITY_META_KEY ] );

		if ( ! $product->is_type( 'variable' ) ) {

			if ( $sync_enabled ) {

				Products::enable_sync_for_products( [ $product ] );

				$this->save_product_settings( $product );

			} else {

				Products::disable_sync_for_products( [ $product ] );
			}
		}

		// do not attempt to update product visibility during FBE 1.5: the Visible setting was removed so it always seems as if the visibility had been disabled
		// $this->update_fb_visibility( $product->get_id(), $is_visible ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN );

		if ( $sync_enabled ) {

			switch ( $product->get_type() ) {

				case 'simple':
				case 'booking':
				case 'external':
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

		$this->enable_product_sync_delay_admin_notice();
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
	 * Enables product sync delay notice when a post is moved to the trash.
	 *
	 * @internal
	 *
	 * @since x.y.z
	 *
	 * @param int $post_id the post ID
	 */
	public function on_product_trash( $post_id ) {

		$product = wc_get_product( $post_id );

		if ( $product instanceof \WC_Product ) {
			$this->enable_product_sync_delay_admin_notice();
		}
	}


	/**
	 * Deletes a product from Facebook.
	 *
	 * @param int $wp_id product ID
	 */
	public function on_product_delete( $wp_id ) {

		$woo_product = new WC_Facebook_Product( $wp_id );

		if ( ! $woo_product->exists() ) {
			// This happens when the wp_id is not a product or it's already
			// been deleted.
			return;
		}

		// skip if not enabled for sync
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! Products::product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		$fb_product_group_id = $this->get_product_fbid(
			self::FB_PRODUCT_GROUP_ID,
			$wp_id,
			$woo_product
		);
		$fb_product_item_id  = $this->get_product_fbid(
			self::FB_PRODUCT_ITEM_ID,
			$wp_id,
			$woo_product
		);
		if ( ! ( $fb_product_group_id || $fb_product_item_id ) ) {
			return;  // No synced product, no-op.
		}
		$products = array( $wp_id );
		if ( WC_Facebookcommerce_Utils::is_variable_type( $woo_product->get_type() ) ) {
			$children = $woo_product->get_children();
			$products = array_merge( $products, $children );
		}
		foreach ( $products as $item_id ) {
			$this->delete_product_item( $item_id );
		}
		if ( $fb_product_group_id ) {
			$pg_result = $this->fbgraph->delete_product_group( $fb_product_group_id );
			WC_Facebookcommerce_Utils::log( $pg_result );
		}

		$this->enable_product_sync_delay_admin_notice();
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
		global $post;

		if ( ! $post ) {
			return;
		}

		$visibility = $new_status === 'publish' ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		$product = wc_get_product( $post->ID );

		// bail if this product isn't enabled for sync
		if ( ! $product instanceof \WC_Product || ! Products::product_should_be_synced( $product ) ) {
			return;
		}

		// change from publish status -> unpublish status (e.g. trash, draft, etc.)
		// change from trash status -> publish status
		// no need to update for change from trash <-> unpublish status
		if ( ( $old_status === 'publish' && $new_status !== 'publish' ) || ( $old_status === 'trash' && $new_status === 'publish' ) ) {
			$this->update_fb_visibility( $post->ID, $visibility );
		}
	}


	/**
	 * Generic function for use with any product publishing.
	 *
	 * Will determine product type (simple or variable) and delegate to
	 * appropriate handler.
	 *
	 * @param int $wp_id product ID
	 */
	public function on_product_publish( $wp_id ) {

		if ( get_post_status( $wp_id ) !== 'publish' ) {
			return;
		}

		$woo_product = new WC_Facebook_Product( $wp_id );

		// skip if not enabled for sync
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! Products::product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		if ( $woo_product->woo_product->is_type( 'variable' ) ) {
			$this->on_variable_product_publish( $wp_id, $woo_product );
		} else {
			$this->on_simple_product_publish( $wp_id, $woo_product );
		}
	}


	/**
	 * If the user has opt-in to remove products that are out of stock,
	 * this function will delete the product from FB Page as well.
	 */
	function delete_on_out_of_stock( $wp_id, $woo_product ) {
		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' &&
		! $woo_product->is_in_stock() ) {
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

		if ( ! $this->is_product_sync_enabled() ) {
			return;
		}

		if ( get_post_status( $wp_id ) != 'publish' ) {
			return;
		}

		// Check if product group has been published to FB.  If not, it's new.
		// If yes, loop through variants and see if product items are published.
		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id );
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product ) ) {
			return;
		}

		$fb_product_group_id = $this->get_product_fbid(
			self::FB_PRODUCT_GROUP_ID,
			$wp_id,
			$woo_product
		);

		if ( $fb_product_group_id ) {

			$woo_product->fb_visibility = Products::is_product_visible( $woo_product->woo_product );

			$this->update_product_group( $woo_product );

			$child_products = $woo_product->get_children();
			$variation_id   = $woo_product->find_matching_product_variation();

			// check if item_id is default variation. If yes, update in the end.
			// If default variation value is to update, delete old fb_product_item_id
			// and create new one in order to make it order correctly.
			foreach ( $child_products as $item_id ) {

				$fb_product_item_id = $this->on_simple_product_publish( $item_id, null, $woo_product );

				if ( $item_id == $variation_id && $fb_product_item_id ) {
					$this->set_default_variant( $fb_product_group_id, $fb_product_item_id );
				}
			}

		} else {

			$this->create_product_variable( $woo_product );
		}
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

		if ( ! $this->is_product_sync_enabled() ) {
			return;
		}

		if ( get_post_status( $wp_id ) != 'publish' ) {
			return;
		}

		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id, $parent_product );
		}

		// skip if not enabled for sync
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! Products::product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product ) ) {
			return;
		}

		// Check if this product has already been published to FB.
		// If not, it's new!
		$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $wp_id, $woo_product );

		if ( $fb_product_item_id ) {

			$woo_product->fb_visibility = Products::is_product_visible( $woo_product->woo_product );

			$this->update_product_item( $woo_product, $fb_product_item_id );

			return $fb_product_item_id;

		} else {

			// Check if this is a new product item for an existing product group
			if ( $woo_product->get_parent_id() ) {

				$fb_product_group_id = $this->get_product_fbid(
					self::FB_PRODUCT_GROUP_ID,
					$woo_product->get_parent_id(),
					$woo_product
				);

				// New variant added
				if ( $fb_product_group_id ) {

					return $this->create_product_simple( $woo_product, $fb_product_group_id );

				} else {

					WC_Facebookcommerce_Utils::fblog(
						'Wrong! simple_product_publish called without group ID for
              a variable product!',
						[],
						true
					);
				}

			} else {

				return $this->create_product_simple( $woo_product );  // new product
			}
		}
	}

	function create_product_variable( $woo_product ) {
		$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );

		$fb_product_group_id = $this->create_product_group(
			$woo_product,
			$retailer_id,
			true
		);

		if ( $fb_product_group_id ) {
			$child_products = $woo_product->get_children();
			$variation_id   = $woo_product->find_matching_product_variation();
			foreach ( $child_products as $item_id ) {
				$child_product      = new WC_Facebook_Product( $item_id, $woo_product );
				$retailer_id        =
				WC_Facebookcommerce_Utils::get_fb_retailer_id( $child_product );
				$fb_product_item_id = $this->create_product_item(
					$child_product,
					$retailer_id,
					$fb_product_group_id
				);
				if ( $item_id == $variation_id && $fb_product_item_id ) {
						$this->set_default_variant( $fb_product_group_id, $fb_product_item_id );
				}
			}
		}
	}

	/**
	 * Create product group and product, store fb-specific info
	 **/
	function create_product_simple( $woo_product, $fb_product_group_id = null ) {
		$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );

		if ( ! $fb_product_group_id ) {
			$fb_product_group_id = $this->create_product_group(
				$woo_product,
				$retailer_id
			);
		}

		if ( $fb_product_group_id ) {
			$fb_product_item_id = $this->create_product_item(
				$woo_product,
				$retailer_id,
				$fb_product_group_id
			);
			return $fb_product_item_id;
		}
	}

	function create_product_group( $woo_product, $retailer_id, $variants = false ) {

		$product_group_data = array(
			'retailer_id' => $retailer_id,
		);

		// Default visibility on create = published
		$woo_product->fb_visibility = true;
		update_post_meta( $woo_product->get_id(), Products::VISIBILITY_META_KEY, true );

		if ( $variants ) {
			$product_group_data['variants'] =
			$woo_product->prepare_variants_for_group();
		}

		$create_product_group_result = $this->check_api_result(
			$this->fbgraph->create_product_group(
				$this->get_product_catalog_id(),
				$product_group_data
			),
			$product_group_data,
			$woo_product->get_id()
		);

		// New variant added
		if ( $create_product_group_result ) {
			$decode_result       = WC_Facebookcommerce_Utils::decode_json( $create_product_group_result['body'] );
			$fb_product_group_id = $decode_result->id;
			// update_post_meta is actually more of a create_or_update
			update_post_meta(
				$woo_product->get_id(),
				self::FB_PRODUCT_GROUP_ID,
				$fb_product_group_id
			);

			$this->display_success_message(
				'Created product group <a href="https://facebook.com/' .
				$fb_product_group_id . '" target="_blank">' .
				$fb_product_group_id . '</a> on Facebook.'
			);

			return $fb_product_group_id;
		}
	}

	function create_product_item( $woo_product, $retailer_id, $product_group_id ) {
		// Default visibility on create = published
		$woo_product->fb_visibility = true;
		$product_data               = $woo_product->prepare_product( $retailer_id );
		if ( ! $product_data['price'] ) {
			return 0;
		}

		update_post_meta( $woo_product->get_id(), Products::VISIBILITY_META_KEY, true );

		$product_result = $this->check_api_result(
			$this->fbgraph->create_product_item(
				$product_group_id,
				$product_data
			),
			$product_data,
			$woo_product->get_id()
		);

		if ( $product_result ) {
			$decode_result      = WC_Facebookcommerce_Utils::decode_json( $product_result['body'] );
			$fb_product_item_id = $decode_result->id;

			update_post_meta(
				$woo_product->get_id(),
				self::FB_PRODUCT_ITEM_ID,
				$fb_product_item_id
			);

			$this->display_success_message(
				'Created product item <a href="https://facebook.com/' .
				$fb_product_item_id . '" target="_blank">' .
				$fb_product_item_id . '</a> on Facebook.'
			);

			return $fb_product_item_id;
		}
	}


	/**
	 * Update existing product group (variant data only)
	 **/
	function update_product_group( $woo_product ) {
		$fb_product_group_id = $this->get_product_fbid(
			self::FB_PRODUCT_GROUP_ID,
			$woo_product->get_id(),
			$woo_product
		);

		if ( ! $fb_product_group_id ) {
			return;
		}

		$variants = $woo_product->prepare_variants_for_group();

		if ( ! $variants ) {
			WC_Facebookcommerce_Utils::log(
				sprintf(
					__(
						'Nothing to update for product group for %1$s',
						'facebook-for-woocommerce'
					),
					$fb_product_group_id
				)
			);
			return;
		}

		$product_group_data = array(
			'variants' => $variants,
		);

		$result = $this->check_api_result(
			$this->fbgraph->update_product_group(
				$fb_product_group_id,
				$product_group_data
			)
		);

		if ( $result ) {
			$this->display_success_message(
				'Updated product group <a href="https://facebook.com/' .
				$fb_product_group_id . '" target="_blank">' . $fb_product_group_id .
				'</a> on Facebook.'
			);
		}
	}

	/**
	 * Update existing product
	 **/
	function update_product_item( $woo_product, $fb_product_item_id ) {
		$product_data = $woo_product->prepare_product();

		// send an empty string to clear the additional_image_urls property if the product has no additional images
		if ( empty( $product_data['additional_image_urls'] ) ) {
			$product_data['additional_image_urls'] = '';
		}

		$result = $this->check_api_result(
			$this->fbgraph->update_product_item(
				$fb_product_item_id,
				$product_data
			)
		);

		if ( $result ) {
			$this->display_success_message(
				'Updated product  <a href="https://facebook.com/' . $fb_product_item_id .
				'" target="_blank">' . $fb_product_item_id . '</a> on Facebook.'
			);
		}
	}

	/**
	 * Saves settings via AJAX (to preserve window context for onboarding).
	 *
	 * @internal
	 */
	public function ajax_save_fb_settings() {

		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'save settings', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );

		if ( ! isset( $_REQUEST['facebook_for_woocommerce'] ) ) {
			// This is not a request from our plugin,
			// some other handler or plugin probably
			// wants to handle it and wp_die() after.
			return;
		}

		if ( isset( $_REQUEST['api_key'] ) ) {

			$api_key = sanitize_text_field( wp_unslash( $_REQUEST['api_key'] ) );

			if ( ctype_alnum( $api_key ) ) {
				$this->update_page_access_token( $api_key );
			}
		}

		if ( isset( $_REQUEST['product_catalog_id'] ) ) {

			$product_catalog_id = sanitize_text_field( wp_unslash( $_REQUEST['product_catalog_id'] ) );

			if ( ctype_digit( $product_catalog_id ) ) {

				if ( ! empty( $this->get_product_catalog_id() ) && $_REQUEST['product_catalog_id'] !== $this->get_product_catalog_id() ) {
					$this->reset_all_products();
				}

				$this->update_product_catalog_id( sanitize_text_field( wp_unslash( $_REQUEST['product_catalog_id'] ) ) );
			}
		}

		if ( isset( $_REQUEST['pixel_id'] ) ) {

			$pixel_id = sanitize_text_field( wp_unslash( $_REQUEST['pixel_id'] ) );

			if ( ctype_digit( $pixel_id ) ) {

				// to prevent race conditions with pixel-only settings, only save a pixel if we already have an access token
				if ( $this->get_page_access_token() ) {

					if ( $this->get_facebook_pixel_id() !== $pixel_id ) {
						$this->update_pixel_install_time( time() );
					}

					$this->settings[ self::SETTING_FACEBOOK_PIXEL_ID ] = $pixel_id;

				} else {

					WC_Facebookcommerce_Utils::log( 'Got pixel-only settings, doing nothing' );
					echo 'Not saving pixel-only settings';

					wp_die();
				}
			}
		}

		if ( isset( $_REQUEST['pixel_use_pii'] ) ) {
			$this->settings[ self::SETTING_ENABLE_ADVANCED_MATCHING ] = wc_bool_to_string( wc_clean( wp_unslash( $_REQUEST['pixel_use_pii'] ) ) );
		}

		if ( isset( $_REQUEST['page_id'] ) ) {

			$page_id = sanitize_text_field( wp_unslash( $_REQUEST['page_id'] ) );

			if ( ctype_digit( $page_id ) ) {
				$this->settings[ self::SETTING_FACEBOOK_PAGE_ID ] = $page_id;
			}
		}

		if ( isset( $_REQUEST['external_merchant_settings_id'] ) ) {

			$external_merchant_settings_id = sanitize_text_field( wp_unslash( $_REQUEST['external_merchant_settings_id'] ) );

			if ( ctype_digit( $external_merchant_settings_id ) ) {
				$this->update_external_merchant_settings_id( $external_merchant_settings_id );
			}
		}

		if ( isset( $_REQUEST['is_messenger_chat_plugin_enabled'] ) ) {
			$this->settings[ self::SETTING_ENABLE_MESSENGER ] = wc_bool_to_string( wc_clean( wp_unslash( $_REQUEST['is_messenger_chat_plugin_enabled'] ) ) );
		}

		if ( isset( $_REQUEST['facebook_jssdk_version'] ) ) {
			$this->update_js_sdk_version( sanitize_text_field( wp_unslash( $_REQUEST['facebook_jssdk_version'] ) ) );
		}

		if ( ! empty( $_REQUEST['msger_chat_customization_greeting_text_code'] ) ) {
			$this->settings[ self::SETTING_MESSENGER_GREETING ] = sanitize_text_field( wp_unslash( $_REQUEST['msger_chat_customization_greeting_text_code'] ) );
		}

		if ( isset( $_REQUEST['msger_chat_customization_locale'] ) ) {
			$this->settings[ self::SETTING_MESSENGER_LOCALE ] = sanitize_text_field( wp_unslash( $_REQUEST['msger_chat_customization_locale'] ) );
		}

		if ( ! empty( $_REQUEST['msger_chat_customization_theme_color_code'] ) ) {
			$this->settings[ self::SETTING_MESSENGER_COLOR_HEX ] = sanitize_hex_color( wp_unslash( $_REQUEST['msger_chat_customization_theme_color_code'] ) );
		}

		/** This filter is defined by WooCommerce in includes/abstracts/abstract-wc-settings-api.php */
		update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ) );

		WC_Facebookcommerce_Utils::log( 'Settings saved!' );
		echo 'settings_saved';

		wp_die();
	}

	/**
	 * Delete all settings via AJAX
	 **/
	function ajax_delete_fb_settings() {
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		if ( ! WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'delete settings', false ) ) {
			return;
		}

		// Do not allow reset in the middle of product sync
		$currently_syncing = get_transient( self::FB_SYNC_IN_PROGRESS );
		if ( $currently_syncing ) {
			wp_send_json(
				'A Facebook product sync is currently in progress.
        Deleting settings during product sync may cause errors.'
			);
			return;
		}

		if ( isset( $_REQUEST ) ) {
			$ems = $this->get_external_merchant_settings_id();
			if ( $ems ) {
				WC_Facebookcommerce_Utils::fblog(
					'Deleted all settings!',
					array(),
					false,
					$ems
				);
			}

			$this->init_settings();
			$this->update_page_access_token( '' );
			$this->update_product_catalog_id( '' );

			$this->settings[ self::SETTING_FACEBOOK_PIXEL_ID ] = '';
			$this->settings[ self::SETTING_ENABLE_ADVANCED_MATCHING ] = 'no';
			$this->settings[ self::SETTING_FACEBOOK_PAGE_ID ]         = '';

			unset( $this->settings[ self::SETTING_ENABLE_MESSENGER ] );
			unset( $this->settings[ self::SETTING_MESSENGER_GREETING ] );
			unset( $this->settings[ self::SETTING_MESSENGER_LOCALE ] );
			unset( $this->settings[ self::SETTING_MESSENGER_COLOR_HEX ] );

			$this->update_external_merchant_settings_id( '' );
			$this->update_pixel_install_time( 0 );
			$this->update_feed_id( '' );
			$this->update_upload_id( '' );
			$this->settings['upload_end_time'] = '';

			WC_Facebookcommerce_Pixel::set_pixel_id( 0 );

			update_option(
				$this->get_option_key(),
				apply_filters(
					'woocommerce_settings_api_sanitized_fields_' . $this->id,
					$this->settings
				)
			);

			// Clean up old  messages
			delete_transient( 'facebook_plugin_api_error' );
			delete_transient( 'facebook_plugin_api_success' );
			delete_transient( 'facebook_plugin_api_warning' );
			delete_transient( 'facebook_plugin_api_info' );
			delete_transient( 'facebook_plugin_api_sticky' );

			$this->reset_all_products();

			WC_Facebookcommerce_Utils::log( 'Settings deleted' );
			echo 'Settings Deleted';

		}

		wp_die();
	}

	/**
	 * Checks the feed upload status.
	 *
	 * @internal
	 */
	public function ajax_check_feed_upload_status() {

		\WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'check feed upload status', true );

		check_ajax_referer( 'wc_facebook_settings_jsx' );

		if ( $this->get_page_access_token() ) {

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

		$msg = json_decode( $result['body'] )->error->message;
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
				WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL
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
		if ( isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && \WC_Facebookcommerce::INTEGRATION_ID === $_GET['section'] ) {
			$this->display_errors();
		}

		// check required fields
		if ( ! $this->get_page_access_token() || ! $this->get_product_catalog_id() ) {

			$message = sprintf(
				/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
				esc_html__(
					'%1$sFacebook for WooCommerce is almost ready.%2$s To complete your configuration, %3$scomplete the setup steps%4$s.',
					'facebook-for-woocommerce'
				),
				'<strong>',
				'</strong>',
				'<a href="' . esc_url( WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL ) . '">',
				'</a>'
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $message, 'info' );
		}

		// WooCommerce 2.x upgrade nag
		if ( $this->get_page_access_token() && ( ! isset( $this->background_processor ) ) ) {

			$message = sprintf(
				/* translators: Placeholders %1$s - WooCommerce version */
				esc_html__(
					'Facebook product sync may not work correctly in WooCommerce version %1$s. Please upgrade to WooCommerce 3.',
					'facebook-for-woocommerce'
				),
				esc_html( WC()->version )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $message, 'warning' );
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
				'brand'        => strip_tags( WC_Facebookcommerce_Utils::get_store_name() ),
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

		if ( ! $this->get_page_access_token() || ! $this->get_product_catalog_id() ) {

			WC_Facebookcommerce_Utils::log( sprintf( 'No API key or Catalog ID: %s and %s', $this->get_page_access_token(), $this->get_product_catalog_id() ) );

			throw new Framework\SV_WC_Plugin_Exception( __( 'The page access token or product catalog ID are missing.', 'facebook-for-woocommerce' ) );
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

		// Cache the cart URL to display a warning in case it changes later
		$cart_url = get_option( self::FB_CART_URL );
		if ( $cart_url != wc_get_cart_url() ) {
			update_option( self::FB_CART_URL, wc_get_cart_url() );
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

		if ( ! $this->get_page_access_token() || ! $this->get_product_catalog_id() ) {

			WC_Facebookcommerce_Utils::log( sprintf( 'No API key or Catalog ID: %s and %s', $this->get_page_access_token(), $this->get_product_catalog_id() ) );

			throw new Framework\SV_WC_Plugin_Exception( __( 'The page access token or product catalog ID are missing.', 'facebook-for-woocommerce' ) );
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

		// Cache the cart URL to display a warning in case it changes later
		$cart_url = get_option( self::FB_CART_URL );
		if ( $cart_url != wc_get_cart_url() ) {
			update_option( self::FB_CART_URL, wc_get_cart_url() );
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
	 *
	 * @internal
	 */
	public function init_form_fields() {

		$term_query = new \WP_Term_Query( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'id=>name',
		] );

		$product_categories = $term_query->get_terms();

		$term_query = new \WP_Term_Query( [
			'taxonomy'     => 'product_tag',
			'hide_empty'   => false,
			'hierarchical' => false,
			'fields'       => 'id=>name',
		] );

		$product_tags = $term_query->get_terms();

		$messenger_locales = \WC_Facebookcommerce_MessengerChat::get_supported_locales();

		// tries matching with WordPress locale, otherwise English, otherwise first available language
		if ( isset( $messenger_locales[ get_locale() ] ) ) {
			$default_locale = get_locale();
		} elseif ( isset( $messenger_locales[ 'en_US' ] ) ) {
			$default_locale = 'en_US';
		} elseif ( ! empty( $messenger_locales ) && is_array( $messenger_locales ) ) {
			$default_locale = key( $messenger_locales );
		} else {
			// fallback to English in case of invalid/empty filtered list of languages
			$messenger_locales = [ 'en_US' => _x( 'English (United States)', 'language', 'facebook-for-woocommerce' ) ];
			$default_locale    = 'en_US';
		}

		$default_messenger_greeting = __( "Hi! We're here to answer any questions you may have.", 'facebook-for-woocommerce' );
		$default_messenger_color    = '#0084ff';

		$form_fields = [

			/** @see \WC_Facebookcommerce_Integration::generate_manage_connection_title_html() */
			[
				'type'  => 'manage_connection_title',
			],

			/** @see \WC_Facebookcommerce_Integration::generate_facebook_page_name_html() */
			self::SETTING_FACEBOOK_PAGE_ID => [
				'type'    => 'facebook_page_name',
				'default' => '',
			],

			/** @see \WC_Facebookcommerce_Integration::generate_facebook_pixel_id_html() */
			self::SETTING_FACEBOOK_PIXEL_ID => [
				'type'    => 'facebook_pixel_id',
				'default' => '',
			],

			self::SETTING_ENABLE_ADVANCED_MATCHING => [
				'title'       => __( 'Use Advanced Matching', 'facebook-for-woocommerce' ),
				'description' => sprintf(
					/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
					__( 'Improve the ability to match site visitors to people on Facebook by passing additional site visitor information (such as email address or phone number). %1$sLearn more%2$s.', 'facebook-for-woocommerce' ),
					'<a href="https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching" target="_blank">',
					'</a>'
				),
				'type'        => 'checkbox',
				'label'       => ' ',
				'default'     => 'yes',
			],

			/** @see \WC_Facebookcommerce_Integration::generate_create_ad_html() */
			[
				'type'  => 'create_ad',
			],

			/** @see \WC_Facebookcommerce_Integration::generate_product_sync_title_html() */
			[
				'type'  => 'product_sync_title',
			],

			self::SETTING_ENABLE_PRODUCT_SYNC => [
				'title'   => __( 'Enable product sync', 'facebook-for-woocommerce' ),
				'type'    => 'checkbox',
				'class'   => 'product-sync-field toggle-fields-group',
				'label'   => ' ',
				'default' => 'yes',
			],

			self::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => [
				'title'             => __( 'Exclude categories from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select product-sync-field',
				'css'               => 'min-width: 300px;',
				'desc_tip'          => __( 'Products in one or more of these categories will not sync to Facebook.', 'facebook-for-woocommerce' ),
				'default'           => [],
				'options'           => is_array( $product_categories ) ? $product_categories : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product category&hellip;', 'facebook-for-woocommerce' ),
				],
			],

			self::SETTING_EXCLUDED_PRODUCT_TAG_IDS => [
				'title'             => __( 'Exclude tags from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select product-sync-field',
				'css'               => 'min-width: 300px;',
				'desc_tip'          => __( 'Products with one or more of these tags will not sync to Facebook.', 'facebook-for-woocommerce' ),
				'default'           => [],
				'options'           => is_array( $product_tags ) ? $product_tags : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product tag&hellip;', 'facebook-for-woocommerce' ),
				],
			],

			self::SETTING_PRODUCT_DESCRIPTION_MODE => [
				'title'    => __( 'Product description sync', 'facebook-for-woocommerce' ),
				'type'     => 'select',
				'class'   => 'product-sync-field',
				'desc_tip' => __( 'Choose which product description to display in the Facebook catalog.', 'facebook-for-woocommerce' ),
				'default'  => self::PRODUCT_DESCRIPTION_MODE_STANDARD,
				'options'  => [
					self::PRODUCT_DESCRIPTION_MODE_STANDARD => __( 'Standard description', 'facebook-for-woocommerce' ),
					self::PRODUCT_DESCRIPTION_MODE_SHORT    => __( 'Short description', 'facebook-for-woocommerce' ),
				],
			],

			/** @see \WC_Facebookcommerce_Integration::generate_resync_schedule_html() */
			/** @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field() */
			self::SETTING_SCHEDULED_RESYNC_OFFSET => [
				'title' => __( 'Force daily resync at', 'facebook-for-woocommerce' ),
				'class' => 'product-sync-field resync-schedule-fieldset',
				'type'  => 'resync_schedule',
			],

			[
				'title' => __( 'Messenger', 'facebook-for-woocommerce' ),
				'type'  => 'title',
			],

			self::SETTING_ENABLE_MESSENGER => [
				'title'    => __( 'Enable Messenger', 'facebook-for-woocommerce' ),
				'type'     => 'checkbox',
				'class'    => 'messenger-field toggle-fields-group',
				'label'    => ' ',
				'desc_tip' => __( 'Enable and customize Facebook Messenger on your store.', 'facebook-for-woocommerce' ),
				'default'  => 'no',
			],

			self::SETTING_MESSENGER_LOCALE => [
				'title'   => __( 'Language', 'facebook-for-woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select messenger-field',
				'default' => $default_locale,
				'options' => $messenger_locales,
				'custom_attributes' => [
					'data-default' => $default_locale,
				],
			],

			/** @see \WC_Facebookcommerce_Integration::generate_messenger_greeting_html() */
			/** @see \WC_Facebookcommerce_Integration::validate_messenger_greeting_field() */
			self::SETTING_MESSENGER_GREETING => [
				'title'             => __( 'Greeting', 'facebook-for-woocommerce' ),
				'type'              => 'messenger_greeting',
				'class'             => 'messenger-field',
				'default'           => $default_messenger_greeting,
				'css'               => 'max-width: 400px; margin-bottom: 10px',
				'custom_attributes' => [
					'maxlength'    => $this->get_messenger_greeting_max_characters(),
					'data-default' => $default_messenger_greeting,
				],
			],

			self::SETTING_MESSENGER_COLOR_HEX => [
				'title'             => __( 'Colors', 'facebook-for-woocommerce' ),
				'type'              => 'color',
				'class'             => 'messenger-field',
				'default'           => $default_messenger_color,
				'css'               => 'width: 6em;',
				'custom_attributes' => [
					'data-default' => $default_messenger_color,
				],
			],

			[
				'title' => __( 'Debug', 'facebook-for-woocommerce' ),
				'type'  => 'title',
			],

			self::SETTING_ENABLE_DEBUG_MODE => [
				'title'    => __( 'Enable debug mode', 'facebook-for-woocommerce' ),
				'type'     => 'checkbox',
				'label'    => __( 'Log plugin events for debugging', 'facebook-for-woocommerce' ),
				'desc_tip' => __( 'Only enable this if you are experiencing problems with the plugin.', 'facebook-for-woocommerce' ),
				'default'  => 'no',
			],

		];

		$this->form_fields = $form_fields;
	}


	/**
	 * Gets the "Manage connection" field HTML.
	 *
	 * @see \WC_Settings_API::generate_title_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_manage_connection_title_html( $key, array $args = [] ) {

		$key = $this->get_field_key( $key );

		ob_start();

		?>
		</table>
		<h3 class="wc-settings-sub-title" id="<?php echo esc_attr( $key ); ?>">
			<?php esc_html_e( 'Connection', 'facebook-for-woocommerce' ); ?>
			<a
				id="woocommerce-facebook-settings-manage-connection"
				class="button"
				href="#"
				style="vertical-align: middle; margin-left: 20px;"
				onclick="facebookConfig();"
			><?php esc_html_e( 'Manage connection', 'facebook-for-woocommerce' ); ?></a>
		</h3>
		<?php if ( empty( $this->get_page_name() ) ) : ?>
		<?php
/**
			<div id="connection-message-invalid">
				<p style="color: #DC3232;">
					<?php esc_html_e( 'Your connection has expired.', 'facebook-for-woocommerce' ); ?>
					<strong>
						<?php esc_html_e( 'Please click Manage connection > Advanced Options > Update Token to refresh your connection to Facebook.', 'facebook-for-woocommerce' ); ?>
					</strong>
				</p>
			</div>
			<div id="connection-message-refresh" style="display: none;">
				<p>
					<?php esc_html_e( 'Your access token has been updated.', 'facebook-for-woocommerce' ); ?>
					<strong>
						<?php esc_html_e( 'Please refresh the page.', 'facebook-for-woocommerce' ); ?>
					</strong>
				</p>
			</div>
 */
		?>
		<?php endif; ?>
		<table class="form-table">
		<?php

		return ob_get_clean();
	}


	/**
	 * Gets the "Facebook page" field HTML.
	 *
	 * @see \WC_Settings_API::generate_settings_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_facebook_page_name_html( $key, array $args = [] ) {

		$key       = $this->get_field_key( $key );
		// $page_name = $this->get_page_name();
		// $page_url  = $this->get_page_url();

		ob_start();

		/*?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Facebook page', 'facebook-for-woocommerce' ); ?>
			</th>
			<td class="forminp">
				<?php if ( $page_name ) : ?>

					<?php if ( $page_url ) : ?>

						<a
							href="<?php echo esc_url( $page_url ); ?>"
							target="_blank"
							style="text-decoration: none;">
							<?php echo esc_html( $page_name ); ?>
							<span
								class="dashicons dashicons-external"
								style="margin-right: 8px; vertical-align: bottom;"
							></span>
						</a>

					<?php else : ?>

						<?php echo esc_html( $page_name ); ?>

					<?php endif; ?>

				<?php else : ?>

					&mdash;

				<?php endif;*/ ?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $key ); ?>"
					id="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( $this->get_facebook_page_id() ); ?>"
				/>
				<?php /*
			</td>
		</tr>
		<?php */

		return ob_get_clean();
	}


	/**
	 * Gets the "Facebook Pixel" field HTML.
	 *
	 * @see \WC_Settings_API::generate_settings_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_facebook_pixel_id_html( $key, array $args = [] ) {

		$key      = $this->get_field_key( $key );
		$pixel_id = $this->get_facebook_pixel_id();

		ob_start();

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Pixel', 'facebook-for-woocommerce' ); ?>
			</th>
			<td class="forminp">
				<?php if ( $pixel_id ) : ?>
					<code style="padding: 4px 8px; color: #333;"><?php echo esc_html( $pixel_id ); ?></code>
				<?php else : ?>
					&mdash;
				<?php endif; ?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $key ); ?>"
					id="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( $pixel_id ); ?>"
				/>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	/**
	 * Gets the "Create ad" field HTML.
	 *
	 * @see \WC_Settings_API::generate_settings_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_create_ad_html( $key, array $args = [] ) {

		$create_ad_url = sprintf( 'https://www.facebook.com/ads/dia/redirect/?settings_id=%s&version=2&entry_point=admin_panel', rawurlencode( $this->get_external_merchant_settings_id() ) );

		ob_start();

		?>
		<tr valign="top">
			<th class="forminp" colspan="2">
				<a
					class="button button-primary"
					target="_blank"
					href="<?php echo esc_url( $create_ad_url ); ?>"
				><?php esc_html_e( 'Create ad', 'facebook-for-woocommerce' ); ?></a>
			</th>
		</tr>
		<?php

		return ob_get_clean();
	}


	/**
	 * Gets the "Sync products" field HTML.
	 *
	 * @see \WC_Settings_API::generate_title_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_product_sync_title_html( $key, array $args = [] ) {

		$key = $this->get_field_key( $key );

		ob_start();

		?>
		</table>
		<h3 class="wc-settings-sub-title" id="<?php echo esc_attr( $key ); ?>">
			<?php esc_html_e( 'Product sync', 'facebook-for-woocommerce' ); ?>
			<a
				id="woocommerce-facebook-settings-sync-products"
				class="button product-sync-field"
				href="#"
				style="vertical-align: middle; margin-left: 20px;"
			><?php esc_html_e( 'Sync products', 'facebook-for-woocommerce' ); ?></a>
		</h3>
		<div><p id="sync_progress" style="display: none"></p></div>
		<table class="form-table">
		<?php

		return ob_get_clean();
	}


	/**
	 * Processes and saves options.
	 *
	 * @see \WC_Settings_API::process_admin_options()
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function process_admin_options() {

		$current_resync_offset = $this->get_scheduled_resync_offset();

		parent::process_admin_options();

		$saved_resync_offset = $this->get_scheduled_resync_offset();

		if ( null === $saved_resync_offset || ! $this->is_product_sync_enabled()  ) {
			$this->unschedule_resync();
		} elseif ( $saved_resync_offset !== $current_resync_offset || false === $this->is_resync_scheduled() ) {
			$this->schedule_resync( $saved_resync_offset );
		}

		// when settings are saved, if there are excluded categories/terms we can exclude corresponding products from sync
		$product_cats    = $product_tags = [];
		$product_cat_ids = $this->get_excluded_product_category_ids();
		$product_tag_ids = $this->get_excluded_product_tag_ids();

		$disable_sync_for_products = [];

		// get all products belonging to excluded categories
		if ( ! empty( $product_cat_ids ) ) {

			foreach ( $product_cat_ids as $tag_id ) {

				$term = get_term_by( 'id', $tag_id, 'product_cat' );

				if ( $term instanceof \WP_Term ) {
					$product_cats[] = $term->slug;
				}
			}

			if ( ! empty( $product_cats ) ) {

				$disable_sync_for_products = wc_get_products( [
					'category' => $product_cats,
					'limit'    => -1,
					'return'   => 'ids',
				] );
			}
		}

		// get all products belonging to excluded tags
		if ( ! empty( $product_tag_ids ) ) {

			foreach ( $product_tag_ids as $tag_id ) {

				$term = get_term_by( 'id', $tag_id, 'product_tag' );

				if ( $term instanceof \WP_Term ) {
					$product_tags[] = $term->slug;
				}
			}

			if ( ! empty( $product_tags ) ) {

				$disable_sync_for_products = array_merge( wc_get_products( [
					'tag'    => $product_tags,
					'limit'  => -1,
					'return' => 'ids',
				] ), $disable_sync_for_products );
			}
		}

		if ( ! empty( $disable_sync_for_products ) ) {

			// disable sync for found products that match any excluded term
			Products::disable_sync_for_products( wc_get_products( [
				'limit'   => -1,
				'include' => array_unique( $disable_sync_for_products ),
			] ) );
		}
	}


    /**
	 * Generates the force resync fieldset HTML.
	 *
	 * @see \WC_Settings_API::generate_settings_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string $key field key
	 * @param array $data field data
	 * @return string HTML
	 */
	protected function generate_resync_schedule_html( $key, array $data ) {

		$fieldset_key       = $this->get_field_key( $key );
		$enabled_field_key  = $this->get_field_key( 'scheduled_resync_enabled' );
		$hours_field_key    = $this->get_field_key( 'scheduled_resync_hours' );
		$minutes_field_key  = $this->get_field_key( 'scheduled_resync_minutes' );
		$meridiem_field_key = $this->get_field_key( 'scheduled_resync_meridiem' );

		// check if the sites uses 12-hours or 24-hours time format
		$time_format = wc_time_format();
		// TODO replace these string search functions with Framework string helpers {FN 2020-01-31}
		$is_12_hours = ( false !== strpos( $time_format, 'g' ) || false !== strpos( $time_format, 'h' ) );
		// default to 24-hours format if no hour specifier is found on the string
		$is_24_hours = ! $is_12_hours;

		if ( $this->is_scheduled_resync_enabled() ) {
			try {
				$offset         = $this->get_scheduled_resync_offset();
				$resync_time    = ( new DateTime( 'today' ) )->add( new DateInterval( "PT${offset}S" ) );
				$resync_hours   = $is_24_hours ? $resync_time->format( 'G' ) : $resync_time->format( 'g' );
				$resync_minutes = $resync_time->format( 'i' );
			} catch ( \Exception $e ) {}
		}

		// default to 23:00
		if ( empty( $resync_hours ) ) {
			$resync_hours    = $is_24_hours ? '23' : '11';
			$resync_minutes  = '00';
			$resync_meridiem = $is_24_hours ? '' : 'pm';
		}

		$defaults  = [
			'title'    => '',
			'disabled' => false,
			'class'    => '',
			'css'      => '',
			'desc_tip' => false,
		];

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $fieldset_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
			</th>
			<td class="forminp">
				<fieldset class="<?php echo esc_attr( $data['class'] ); ?>">
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input
						class="toggle-fields-group resync-schedule-field"
						<?php disabled( $data['disabled'], true ); ?>
						type="checkbox"
						name="<?php echo esc_attr( $enabled_field_key ); ?>"
						id="<?php echo esc_attr( $enabled_field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value="1"
						<?php checked( $this->is_scheduled_resync_enabled() ); ?>
					/>
					<input
						class="input-number regular-input resync-schedule-field"
						type="number"
						min="0"
						max="<?php echo $is_24_hours ? 24 : 12; ?>"
						name="<?php echo esc_attr( $hours_field_key ); ?>"
						id="<?php echo esc_attr( $hours_field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value="<?php echo ! empty( $resync_hours ) ? esc_attr( $resync_hours ) : ''; ?>"
						<?php disabled( $data['disabled'], true ); ?>
					/>
					<strong>:</strong>
					<input
						class="input-number regular-input resync-schedule-field"
						type="number"
						min="0"
						max="59"
						name="<?php echo esc_attr( $minutes_field_key ); ?>"
						id="<?php echo esc_attr( $minutes_field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value="<?php echo ! empty( $resync_minutes ) ? esc_attr( $resync_minutes ) : ''; ?>"
						<?php disabled( $data['disabled'], true ); ?>
					/>
					<?php if ( ! $is_24_hours ) : ?>

						<select
							class="resync-schedule-field"
							name="<?php echo esc_attr( $meridiem_field_key ); ?>"
							id="<?php echo esc_attr( $meridiem_field_key ); ?>"
							style="<?php echo esc_attr( $data['css'] ); ?>"
							<?php disabled( $data['disabled'], true ); ?>>
							<option
								<?php selected( true, $this->get_scheduled_resync_offset() < 12 * HOUR_IN_SECONDS, true ); ?>
								value="am">
								<?php esc_html_e( 'am', 'facebook-for-woocommerce' ); ?>
							</option>
							<option
								<?php selected( true, $this->get_scheduled_resync_offset() >= 12 * HOUR_IN_SECONDS || ( ! empty( $resync_meridiem ) && 'pm' === $resync_meridiem ), true ); ?>
								value="pm">
								<?php esc_html_e( 'pm', 'facebook-for-woocommerce' ); ?>
							</option>
						</select>

					<?php endif; ?>
					<br/>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	/**
	 * Validates force resync field.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param string $key field key
	 * @param string $value posted value
	 * @return int|string timestamp or empty string
	 * @throws \Exception
	 */
	public function validate_resync_schedule_field( $key, $value ) {

		$enabled_field_key  = $this->get_field_key( 'scheduled_resync_enabled' );
		$hours_field_key    = $this->get_field_key( 'scheduled_resync_hours' );
		$minutes_field_key  = $this->get_field_key( 'scheduled_resync_minutes' );
		$meridiem_field_key = $this->get_field_key( 'scheduled_resync_meridiem' );

		// if not enabled or time is empty, return a blank string
		if ( empty( $_POST[ $enabled_field_key ] ) || empty( $_POST[ $hours_field_key ] ) ) {
			return '';
		}

		$posted_hours    = (int) sanitize_text_field( wp_unslash( $_POST[ $hours_field_key ] ) );
		$posted_minutes  = (int) sanitize_text_field( wp_unslash( $_POST[ $minutes_field_key ] ) );
		$posted_minutes  = str_pad( $posted_minutes, 2, '0', STR_PAD_LEFT );
		$posted_meridiem = ! empty( $_POST[ $meridiem_field_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meridiem_field_key ] ) ) : '';

		// attempts to parse the time (not using date_create_from_format because it considers 30:00 to be a valid time)
		$parsed_time = strtotime( "$posted_hours:$posted_minutes $posted_meridiem" );

		if ( false === $parsed_time ) {
			throw new \Exception( "Invalid resync schedule time: $posted_hours:$posted_minutes $posted_meridiem" );
		}

		$midnight = ( new DateTime() )->setTimestamp( $parsed_time )->setTime( 0,0,0 );

		return $parsed_time - $midnight->getTimestamp();
	}


	/**
	 * Gets the "Messenger greeting" field HTML.
	 *
	 * @see \WC_Settings_API::generate_textarea_html()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param array $args associative array of field arguments
	 * @return string HTML
	 */
	protected function generate_messenger_greeting_html( $key, array $args = [] ) {

		// TODO replace strlen() here with Framework helper method to account for multibyte characters {FN 2020-01-30}
		$chars         = max( 0, strlen( $this->get_messenger_greeting() ) );
		$max_chars     = max( 0, $this->get_messenger_greeting_max_characters() );
		$field_id      = $this->get_field_key( $key );
		$counter_class = $field_id . '-characters-count';

		wc_enqueue_js( "
			jQuery( document ).ready( function( $ ) {
				$( 'span." . esc_js( $counter_class ) . "' ).insertAfter( 'textarea#" . esc_js( $field_id ) . "' );
			} );
		" );

		ob_start();

		?>
		<span
			style="display: none; font-family: monospace; font-size: 0.9em;"
			class="<?php echo sanitize_html_class( $counter_class ); ?> characters-counter"
		><?php echo esc_html( $chars . ' / ' . $max_chars ); ?> <span style="display:none;"><?php echo esc_html( $this->get_messenger_greeting_long_warning_text() ); ?></span></span>
		<?php

		$counter = ob_get_clean();

		return $this->generate_textarea_html( $key, $args ) . $counter;
	}


	/**
	 * Validates the Messenger greeting field.
	 *
	 * @see \WC_Settings_API::validate_textarea_field()
	 *
	 * @since 1.10.0
	 *
	 * @param string|int $key field key or index
	 * @param string $value field submitted value
	 * @throws \Exception on validation errors
	 * @return string some HTML allowed
	 */
	protected function validate_messenger_greeting_field( $key, $value ) {

		$value = is_string( $value ) ? trim( sanitize_text_field( wp_unslash( $value ) ) ) : '';

		$max_chars    = $this->get_messenger_greeting_max_characters();
		$value_length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, Framework\SV_WC_Helper::MB_ENCODING ) : strlen( $value );

		if ( $value_length > $max_chars ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				$this->get_messenger_greeting_long_warning_text() . ' %s',
				__( "The greeting hasn't been updated.", 'facebook-for-woocommerce' )
			) );
		}

		return $value;
	}


	/**
	 * Gets a warning text to be displayed when the Messenger greeting text exceeds the maximum length.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	private function get_messenger_greeting_long_warning_text() {

		return sprintf(
			/* translators: Placeholder: %d - maximum number of allowed characters */
			__( 'The Messenger greeting must be %d characters or less.', 'facebook-for-woocommerce' ),
			$this->get_messenger_greeting_max_characters()
		);
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the page access token.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_page_access_token() {

		if ( ! is_string( $this->page_access_token ) ) {

			$value = get_option( self::OPTION_PAGE_ACCESS_TOKEN, '' );

			$this->page_access_token = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook page access token.
		 *
		 * @since 1.10.0
		 *
		 * @param string $page_access_token Facebook page access token
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (string) apply_filters( 'wc_facebook_page_access_token', $this->page_access_token, $this );
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
	 * @since 1.11.0-dev.1
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
		 * @since 1.11.0-dev.1
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
		return (string) apply_filters( 'wc_facebook_page_id', $this->get_option( self::SETTING_FACEBOOK_PAGE_ID, '' ), $this );
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
		return (string) apply_filters( 'wc_facebook_pixel_id', $this->get_option( self::SETTING_FACEBOOK_PIXEL_ID, '' ), $this );
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
		return (array) apply_filters( 'wc_facebook_excluded_product_category_ids', $this->get_option( self::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [] ), $this );
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
		return (array) apply_filters( 'wc_facebook_excluded_product_tag_ids', $this->get_option( self::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [] ), $this );
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
		$mode = (string) apply_filters( 'wc_facebook_product_description_mode', $this->get_option( self::SETTING_PRODUCT_DESCRIPTION_MODE, self::PRODUCT_DESCRIPTION_MODE_STANDARD ), $this );

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
	 *
	 * @return int|null
	 */
	public function get_scheduled_resync_offset() {

		/**
		 * Filters the configured scheduled re-sync offset.
		 *
		 * @since 1.10.0
		 *
		 * @param int|null $offset the configured scheduled re-sync offset in seconds
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		$offset = (int) apply_filters( 'wc_facebook_scheduled_resync_offset', $this->get_option( self::SETTING_SCHEDULED_RESYNC_OFFSET, null ), $this );

		if ( ! $offset ) {
			$offset = null;
		}

		return $offset;
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
		return (string) apply_filters( 'wc_facebook_messenger_locale', $this->get_option( self::SETTING_MESSENGER_LOCALE, 'en_US' ), $this );
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
		$greeting = (string) apply_filters( 'wc_facebook_messenger_greeting', $this->get_option( self::SETTING_MESSENGER_GREETING, '' ), $this );

		// TODO: update to SV_WC_Helper::str_truncate() when frameworked {CW 2020-01-22}
		return substr( $greeting, 0, $this->get_messenger_greeting_max_characters() );
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
		return (string) apply_filters( 'wc_facebook_messenger_color_hex', $this->get_option( self::SETTING_MESSENGER_COLOR_HEX, '' ), $this );
	}


	/** Setter methods ************************************************************************************************/


	/**
	 * Updates the Facebook page access token.
	 *
	 * @since 1.10.0
	 *
	 * @param string $value page access token value
	 */
	public function update_page_access_token( $value ) {

		$this->page_access_token = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_PAGE_ACCESS_TOKEN, $this->page_access_token );
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
	 * @since 1.11.0-dev.1
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

		return (bool) $this->get_facebook_page_id();
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
		return (bool) apply_filters( 'wc_facebook_is_advanced_matching_enabled', 'yes' === $this->get_option( self::SETTING_ENABLE_ADVANCED_MATCHING ), $this );
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
		return (bool) apply_filters( 'wc_facebook_is_product_sync_enabled', 'yes' === $this->get_option( self::SETTING_ENABLE_PRODUCT_SYNC ), $this );
	}


	/**
	 * Determines whether the scheduled re-sync is enabled.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function is_scheduled_resync_enabled() {

		/**
		 * Filters whether the scheduled re-sync is enabled.
		 *
		 * @since 1.10.0
		 *
		 * @param bool $is_enabled whether the scheduled re-sync is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 */
		return (bool) apply_filters( 'wc_facebook_is_scheduled_resync_enabled', $this->get_scheduled_resync_offset(), $this );
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
		return (bool) apply_filters( 'wc_facebook_is_messenger_enabled', 'yes' === $this->get_option( self::SETTING_ENABLE_MESSENGER ), $this );
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
		return (bool) apply_filters( 'wc_facebook_is_debug_mode_enabled', 'yes' === $this->get_option( self::SETTING_ENABLE_DEBUG_MODE ), $this );
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
	 * Gets the name of the configured Facebook page.
	 *
	 * @return string
	 */
	public function get_page_name() {

		if ( $this->is_configured() ) {
			$page_name = $this->fbgraph->get_page_name( $this->get_facebook_page_id(), $this->get_page_access_token() );
		} else {
			$page_name = '';
		}

		return is_string( $page_name ) ? $page_name : '';
	}


	/**
	 * Gets the Facebook page URL.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_page_url() {

		if ( $this->is_configured() ) {
			$page_url = $this->fbgraph->get_page_url( $this->get_facebook_page_id(), $this->get_page_access_token() );
		} else {
			$page_url = '';
		}

		return is_string( $page_url ) ? $page_url : '';
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

		$page_name      = $this->get_page_name();
		$can_manage     = current_user_can( 'manage_woocommerce' );
		$pre_setup      = empty( $this->get_facebook_page_id() ) || empty( $this->get_page_access_token() );
		$apikey_invalid = ! $pre_setup && $this->get_page_access_token() && ! $page_name;

		$remove_http_active     = is_plugin_active( 'remove-http/remove-http.php' );
		$https_will_be_stripped = $remove_http_active && ! get_option( 'factmaven_rhttp' )['external'];

		if ( $https_will_be_stripped ) {

			$this->display_sticky_message(
				__( 'You\'re using Remove HTTP which has incompatibilities with our extension. Please disable it, or select the "Ignore external links" option on the Remove HTTP settings page.' )
			);
		}

		?>

		<h2><?php esc_html_e( 'Facebook', 'facebook-for-woocommerce' ); ?></h2>

		<p>
			<?php esc_html_e( 'Control how WooCommerce integrates with your Facebook store.', 'facebook-for-woocommerce' ); ?>
		</p>

		<div><input type="hidden" name="section" value="<?php echo esc_attr( $this->id ); ?>" /></div>

		<div id="fbsetup" <?php echo $this->is_configured() ? 'style="display: none"' : ''; ?>>
			<div class="wrapper">
				<header>
					<div class="help-center">
						<a href="https://www.facebook.com/business/help/900699293402826" target="_blank">Help Center <i class="help-center-icon"></i></a>
					</div>
				</header>

				<div class="content">
					<h1 id="setup_h1"><?php esc_html_e( 'Grow your business on Facebook', 'facebook-for-woocommerce' ); ?></h1>

					<h2>
						<?php esc_html_e( 'Use this WooCommerce and Facebook integration to:', 'facebook-for-woocommerce' ); ?>
					</h2>

					<ul>
						<li id="setup_l1"><?php esc_html_e( 'Easily install a tracking pixel', 'facebook-for-woocommerce' ); ?></li>
						<li id="setup_l2"><?php esc_html_e( 'Upload your products and create a shop', 'facebook-for-woocommerce' ); ?></li>
						<li id="setup_l3"><?php esc_html_e( 'Create dynamic ads with your products and pixel', 'facebook-for-woocommerce' ); ?></li>
					</ul>

					<span
						<?php $external_merchant_settings_id = $this->get_external_merchant_settings_id(); ?>
						<?php echo ( ! $can_manage || $apikey_invalid || ! isset( $external_merchant_settings_id ) ) ? ' style="pointer-events: none;"' : ''; ?>>

						<a href="#" class="btn pre-setup" onclick="facebookConfig()" id="cta_button">
							<?php esc_html_e( 'Get Started', 'facebook-for-woocommerce' ); ?>
						</a>

					</span>

					<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $this->get_nux_message_ifexist();
					?>

				</div>
			</div>
		</div>

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
	 */
	function update_fb_visibility( $wp_id, $visibility ) {
		$woo_product = new WC_Facebook_Product( $wp_id );
		if ( ! $woo_product->exists() ) {
			// This function can be called for non-woo products.
			return;
		}

		$products = WC_Facebookcommerce_Utils::get_product_array( $woo_product );
		foreach ( $products as $item_id ) {
			$fb_product_item_id = $this->get_product_fbid(
				self::FB_PRODUCT_ITEM_ID,
				$item_id
			);

			if ( ! $fb_product_item_id ) {
				WC_Facebookcommerce_Utils::fblog(
					$fb_product_item_id . " doesn't exist but underwent a visibility transform.",
					array(),
					true
				);
				  continue;
			}
			$result = $this->check_api_result(
				$this->fbgraph->update_product_item(
					$fb_product_item_id,
					array( 'visibility' => $visibility )
				)
			);
			if ( $result ) {

				$is_visible = $visibility === self::FB_SHOP_PRODUCT_VISIBLE;

				update_post_meta( $item_id, Products::VISIBILITY_META_KEY, wc_bool_to_string( $is_visible ) );
				update_post_meta( $wp_id, Products::VISIBILITY_META_KEY, wc_bool_to_string( $is_visible ) );
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
		if ( ! $product instanceof \WC_Product || ! Products::product_should_be_synced( $product ) ) {
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
			$this->update_fb_visibility( $wp_id, $visibility );
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

		if ( ! isset( $this->settings['upload_end_time'] ) ) {
			return null;
		}

		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id );
		}

		$products    = WC_Facebookcommerce_Utils::get_product_array( $woo_product );
		$woo_product = new WC_Facebook_Product( current( $products ) );

		// This is a generalized function used elsewhere
		// Cannot call is_hidden for VC_Product_Variable Object
		if ( $woo_product->is_hidden() ) {
			return null;
		}

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

				update_post_meta( $wp_id, Products::VISIBILITY_META_KEY, true );

				return $fb_id;
			}
		}

		return;
	}


	private function set_default_variant( $product_group_id, $product_item_id ) {
		$result = $this->check_api_result(
			$this->fbgraph->set_default_variant(
				$product_group_id,
				array( 'default_product_id' => $product_item_id )
			)
		);
		if ( ! $result ) {
			WC_Facebookcommerce_Utils::fblog(
				'Fail to set default product item',
				array(),
				true
			);
		}
	}

	private function fb_wp_die() {
		if ( ! $this->test_mode ) {
			wp_die();
		}
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


	function ajax_update_fb_option() {

		check_ajax_referer( 'wc_facebook_settings_jsx' );
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'update fb options', true );

		if ( isset( $_POST ) && ! empty( $_POST['option'] ) && isset( $_POST['option_value'] ) ) {

			$option_name  = sanitize_text_field( wp_unslash( $_POST['option'] ) );
			$option_value = sanitize_text_field( wp_unslash( $_POST['option_value'] ) );

			if ( stripos( $option_name, 'fb_' ) === 0  ) {
				update_option( $option_name, $option_value );
			}
		}

		wp_die();
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
	 * Enables product sync delay admin notice.
	 *
	 * @since x.y.z
	 */
	private function enable_product_sync_delay_admin_notice() {

		set_transient( 'wc_' . facebook_for_woocommerce()->get_id() . '_show_product_sync_delay_notice_' . get_current_user_id(), true, MINUTE_IN_SECONDS );
	}


}
