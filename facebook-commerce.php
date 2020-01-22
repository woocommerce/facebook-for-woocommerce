<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

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

	/** @var string the WordPress option name where the latest pixel install time is stored */
	const OPTION_PIXEL_INSTALL_TIME = 'wc_facebook_pixel_install_time';


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
			$settings_pixel_id = isset( $this->settings['fb_pixel_id'] ) ?
			(string) $this->settings['fb_pixel_id'] : null;
			if (
			WC_Facebookcommerce_Utils::is_valid_id( $settings_pixel_id ) &&
			( ! WC_Facebookcommerce_Utils::is_valid_id( $pixel_id ) ||
			$pixel_id != $settings_pixel_id
			)
			) {
				WC_Facebookcommerce_Pixel::set_pixel_id( $settings_pixel_id );
			}
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

		$this->page_id = isset( $this->settings['fb_page_id'] )
		? $this->settings['fb_page_id']
		: '';

		$this->api_key = isset( $this->settings['fb_api_key'] )
		? $this->settings['fb_api_key']
		: '';

		$pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
		if ( ! $pixel_id ) {
			$pixel_id = isset( $this->settings['fb_pixel_id'] ) ?
				  $this->settings['fb_pixel_id'] : '';
		}
		$this->pixel_id = isset( $pixel_id )
		? $pixel_id
		: '';

		$this->pixel_install_time = isset( $this->settings['pixel_install_time'] )
		? $this->settings['pixel_install_time']
		: '';

		$this->use_pii = isset( $this->settings['fb_pixel_use_pii'] )
		&& $this->settings['fb_pixel_use_pii'] === 'yes'
		? true
		: false;

		$this->product_catalog_id = isset( $this->settings['fb_product_catalog_id'] )
		? $this->settings['fb_product_catalog_id']
		: '';

		$this->external_merchant_settings_id =
		isset( $this->settings['fb_external_merchant_settings_id'] )
		? $this->settings['fb_external_merchant_settings_id']
		: '';

		if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
			include_once 'includes/fbutils.php';
		}

		WC_Facebookcommerce_Utils::$ems = $this->external_merchant_settings_id;

		if ( ! class_exists( 'WC_Facebookcommerce_Graph_API' ) ) {
			include_once 'includes/fbgraph.php';
			$this->fbgraph = new WC_Facebookcommerce_Graph_API( $this->api_key );
		}

		WC_Facebookcommerce_Utils::$fbgraph = $this->fbgraph;
		$this->feed_id                      = isset( $this->settings['fb_feed_id'] )
		? $this->settings['fb_feed_id']
		: '';

		// Hooks
		if ( is_admin() ) {
			$this->init_pixel();
			$this->init_form_fields();
			// Display an info banner for eligible pixel and user.
			if ( $this->external_merchant_settings_id
			&& $this->pixel_id
			&& $this->pixel_install_time ) {
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
						$this->external_merchant_settings_id,
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

			if ( ! $this->pixel_install_time && $this->pixel_id ) {
				$this->pixel_install_time             = current_time( 'mysql' );
				$this->settings['pixel_install_time'] = $this->pixel_install_time;
				update_option(
					$this->get_option_key(),
					apply_filters(
						'woocommerce_settings_api_sanitized_fields_' . $this->id,
						$this->settings
					)
				);
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
			if ( $this->api_key && $this->product_catalog_id ) {

				add_action( 'woocommerce_process_product_meta', [ $this, 'on_product_save' ], 20 );

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
		add_action(
			'sync_all_fb_products_using_feed',
			array( $this, 'sync_all_fb_products_using_feed' ),
			self::FB_PRIORITY_MID
		);

		if ( $this->pixel_id ) {
			$user_info            = WC_Facebookcommerce_Utils::get_user_info( $this->use_pii );
			$this->events_tracker = new WC_Facebookcommerce_EventsTracker( $user_info );
		}

		if ( isset( $this->settings['is_messenger_chat_plugin_enabled'] ) &&
		$this->settings['is_messenger_chat_plugin_enabled'] === 'yes' ) {
			if ( ! class_exists( 'WC_Facebookcommerce_MessengerChat' ) ) {
				include_once 'facebook-commerce-messenger-chat.php';
			}
			$this->messenger_chat = new WC_Facebookcommerce_MessengerChat( $this->settings );
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
		if ( $this->settings['fb_api_key'] ) {
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
	 * @deprecated since x.y.z
	 *
	 * @param array $tabs array of tabs
	 * @return array
	 */
	public function fb_new_product_tab( $tabs ) {

		wc_deprecated_function( __METHOD__, 'x.y.z', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_settings_tab()' );

		return $tabs;
	}


	/**
	 * Adds content to the new Facebook tab on the Product edit page.
	 *
	 * @internal
	 * @deprecated since x.y.z
	 */
	public function fb_new_product_tab_content() {

		wc_deprecated_function( __METHOD__, 'x.y.z', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_settings_tab_content()' );
	}


	/**
	 * Filters the product columns in the admin edit screen.
	 *
	 * @internal
	 * @deprecated since x.y.z
	 *
	 * @param array $existing_columns array of columns and labels
	 * @return array
	 */
	public function fb_product_columns( $existing_columns ) {

		wc_deprecated_function( __METHOD__, 'x.y.z', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_list_table_column()' );

		return $existing_columns;
	}


	/**
	 * Outputs content for the FB Shop column in the edit screen.
	 *
	 * @internal
	 * @deprecated since x.y.z
	 *
	 * @param string $column name of the column to display
	 */
	public function fb_render_product_columns( $column ) {

		wc_deprecated_function( __METHOD__, 'x.y.z', '\\SkyVerge\\WooCommerce\\Facebook\\Admin::add_product_list_table_columns_content()' );
	}


	public function fb_product_metabox() {
		$ajax_data = array(
			'nonce' => wp_create_nonce( 'wc_facebook_metabox_jsx' ),
		);
		wp_enqueue_script(
			'wc_facebook_metabox_jsx',
			plugins_url(
				'/assets/js/facebook-metabox.js?ts=' . time(),
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
				<?php echo esc_html__( 'Visible:', 'facebook-for-woocommerce' ); ?>
				<input name="<?php echo esc_attr( Products::VISIBILITY_META_KEY ); ?>"
				type="checkbox"
				value="1"
				<?php echo checked( ! $woo_product->woo_product instanceof \WC_Product || Products::is_product_visible( $woo_product->woo_product ) ); ?>/>

				<p/>
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

	private function get_global_feed_url() {

		$http       = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://";
		$index      = ! empty( $_SERVER['REQUEST_URI'] ) ? strrpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-admin/' ) : null;
		$begin_path = ! empty( $_SERVER['REQUEST_URI'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 0, $index ) : '';
		$url        = $http . ( ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . $begin_path . WC_Facebook_Product_Feed::FACEBOOK_CATALOG_FEED_FILEPATH;

		return $url;
	}

	/**
	 * Load DIA specific JS Data
	 */
	public function load_assets() {
		$screen = get_current_screen();
		$ajax_data = array(
      'nonce' => wp_create_nonce( 'wc_facebook_infobanner_jsx' ),
    );
		// load banner assets
		wp_enqueue_script(
			'wc_facebook_infobanner_jsx',
			plugins_url(
				'/assets/js/facebook-infobanner.js?ts=' . time(),
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

		if ( strpos( $screen->id, 'page_wc-settings' ) == 0 ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'integration' !== $_GET['tab'] ) {
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
			pixelId: '<?php echo $this->pixel_id ? esc_js( $this->pixel_id ) : ''; ?>',
			advanced_matching_supported: true
		},
		diaSettingId: '<?php echo $this->external_merchant_settings_id ? esc_js( $this->external_merchant_settings_id ) : ''; ?>',
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
			hasClientSideFeedUpload: '<?php echo esc_js( ! ! $this->feed_id ); ?>'
		},
		feedPrepared: {
			feedUrl: '<?php echo esc_js( $this->get_global_feed_url() ); ?>',
			feedPingUrl: '',
			samples: <?php echo $this->get_sample_product_feed(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		}
	};

	</script>

		<?php
		$ajax_data = array(
			'nonce' => wp_create_nonce( 'wc_facebook_settings_jsx' ),
		);
		wp_enqueue_script(
			'wc_facebook_settings_jsx',
			plugins_url(
				'/assets/js/facebook-settings.js?ts=' . time(),
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
	 * @since x.y.z
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
		$is_visible   = ! empty( $_POST['fb_visibility'] );

		if ( ! $product->is_type( 'variable' ) ) {

			if ( $sync_enabled ) {

				Products::enable_sync_for_products( [ $product ] );

				$this->save_product_settings( $product );

			} else {

				Products::disable_sync_for_products( [ $product ] );
			}
		}

		$this->update_fb_visibility( $product->get_id(), $is_visible ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN );

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
	}


	/**
	 * Saves the submitted Facebook settings for a product.
	 *
	 * @since x.y.z
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

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) {
			$woo_product->set_product_image( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
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
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! \SkyVerge\WooCommerce\Facebook\Products::product_should_be_synced( $woo_product->woo_product ) ) {
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
	}

	/**
	 * Update FB visibility for trashing and restore.
	 */
	function fb_change_product_published_status( $new_status, $old_status, $post ) {
		global $post;

		if ( ! $post ) {
			return;
		}

		$visibility = $new_status === 'publish' ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		$product = wc_get_product( $post->ID );

		// bail if this product isn't enabled for sync
		if ( ! $product instanceof \WC_Product || ! Products::is_sync_enabled_for_product( $product ) ) {
			return;
		}

		// change from publish status -> unpublish status, e.g. trash, draft, etc.
		// change from trash status -> publish status
		// no need to update for change from trash <-> unpublish status
		if ( ( $old_status == 'publish' && $new_status != 'publish' ) ||
		( $old_status == 'trash' && $new_status == 'publish' ) ) {
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

		if ( get_post_status( $wp_id ) != 'publish' ) {
			return;
		}

		$woo_product  = new WC_Facebook_Product( $wp_id );

		// skip if not enabled for sync
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! \SkyVerge\WooCommerce\Facebook\Products::product_should_be_synced( $woo_product->woo_product ) ) {
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

		if ( get_option( 'fb_disable_sync_on_dev_environment', false ) ) {
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

		$woo_product->set_use_parent_image(
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			( isset( $_POST[ self::FB_VARIANT_IMAGE ] ) ) ?
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			sanitize_text_field( wp_unslash( $_POST[ self::FB_VARIANT_IMAGE ] ) ) :
			null
		);

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

		if ( get_option( 'fb_disable_sync_on_dev_environment', false ) ) {
			return;
		}

		if ( get_post_status( $wp_id ) != 'publish' ) {
			return;
		}

		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id, $parent_product );
		}

		// skip if not enabled for sync
		if ( ! $woo_product->woo_product instanceof \WC_Product || ! \SkyVerge\WooCommerce\Facebook\Products::product_should_be_synced( $woo_product->woo_product ) ) {
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
				$this->product_catalog_id,
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
	 */
	function ajax_save_fb_settings() {

		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'save settings', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );

		if ( isset( $_REQUEST ) ) {

			if ( ! isset( $_REQUEST['facebook_for_woocommerce'] ) ) {
				// This is not a request from our plugin,
				// some other handler or plugin probably
				// wants to handle it and wp_die() after.
				return;
			}

			if ( isset( $_REQUEST['api_key'] ) ) {

				$api_key = sanitize_text_field( wp_unslash( $_REQUEST['api_key'] ) );

				if ( ctype_alnum( $api_key ) ) {
					$this->settings['fb_api_key'] = $api_key;
				}
			}

			if ( isset( $_REQUEST['product_catalog_id'] ) ) {

				$product_catalog_id = sanitize_text_field( wp_unslash( $_REQUEST['product_catalog_id'] ) );

				if ( ctype_digit( $product_catalog_id ) ) {

					if ( $this->product_catalog_id != '' && $this->product_catalog_id != $_REQUEST['product_catalog_id'] ) {
						$this->reset_all_products();
					}

					$this->settings['fb_product_catalog_id'] = sanitize_text_field( wp_unslash( $_REQUEST['product_catalog_id'] ) );
				}
			}

			if ( isset( $_REQUEST['pixel_id'] ) ) {

				$pixel_id = sanitize_text_field ( wp_unslash( $_REQUEST['pixel_id'] ) );

				if ( ctype_digit( $pixel_id ) ) {

					// To prevent race conditions with pixel-only settings,
					// only save a pixel if we already have an API key.
					if ( $this->settings['fb_api_key'] ) {

						$this->settings['fb_pixel_id'] = $pixel_id;

						if ( $this->pixel_id != $pixel_id ) {
							$this->settings['pixel_install_time'] = current_time( 'mysql' );
						}

					} else {

						WC_Facebookcommerce_Utils::log( 'Got pixel-only settings, doing nothing' );
						echo 'Not saving pixel-only settings';

						wp_die();
					}
				}
			}

			if ( isset( $_REQUEST['pixel_use_pii'] ) ) {
				$this->settings['fb_pixel_use_pii'] = ( $_REQUEST['pixel_use_pii'] === 'true' || $_REQUEST['pixel_use_pii'] === true ) ? 'yes' : 'no';
			}

			if ( isset( $_REQUEST['page_id'] ) ) {

				$page_id = sanitize_text_field( wp_unslash( $_REQUEST['page_id'] ) );

				if ( ctype_digit( $page_id ) ) {
					$this->settings['fb_page_id'] = $page_id;
				}
			}

			if ( isset( $_REQUEST['external_merchant_settings_id'] ) ) {

				$external_merchant_settings_id = sanitize_text_field( wp_unslash( $_REQUEST['external_merchant_settings_id'] ) );

				if ( ctype_digit( $external_merchant_settings_id ) ) {
					$this->settings['fb_external_merchant_settings_id'] = $external_merchant_settings_id;
				}
			}

			if ( isset( $_REQUEST['is_messenger_chat_plugin_enabled'] ) ) {
				$this->settings['is_messenger_chat_plugin_enabled'] = ( $_REQUEST['is_messenger_chat_plugin_enabled'] === 'true' || $_REQUEST['is_messenger_chat_plugin_enabled'] === true ) ? 'yes' : 'no';
			}

			if ( isset( $_REQUEST['facebook_jssdk_version'] ) ) {
				$this->settings['facebook_jssdk_version'] = sanitize_text_field( wp_unslash( $_REQUEST['facebook_jssdk_version'] ) );
			}

			if ( isset( $_REQUEST['msger_chat_customization_greeting_text_code'] ) ) {

				$greeting_text_code = sanitize_text_field( wp_unslash( $_REQUEST['msger_chat_customization_greeting_text_code'] ) );

				if ( ctype_digit( $greeting_text_code ) ) {
					$this->settings['msger_chat_customization_greeting_text_code'] = $greeting_text_code;
				}
			}

			if ( isset( $_REQUEST['msger_chat_customization_locale'] ) ) {
				$this->settings['msger_chat_customization_locale'] = sanitize_text_field( wp_unslash( $_REQUEST['msger_chat_customization_locale'] ) );
			}

			if ( isset( $_REQUEST['msger_chat_customization_theme_color_code'] ) ) {

				$theme_color_code = sanitize_text_field( wp_unslash( $_REQUEST['msger_chat_customization_theme_color_code'] ) );

				if ( ctype_digit( $theme_color_code ) ) {
					$this->settings['msger_chat_customization_theme_color_code'] = $theme_color_code;
				}
			}

			update_option(
				$this->get_option_key(),
				apply_filters(
					'woocommerce_settings_api_sanitized_fields_' . $this->id,
					$this->settings
				)
			);

			WC_Facebookcommerce_Utils::log( 'Settings saved!' );
			echo 'settings_saved';

		} else {
			echo 'No Request';
		}

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
			$ems = $this->settings['fb_external_merchant_settings_id'];
			if ( $ems ) {
				WC_Facebookcommerce_Utils::fblog(
					'Deleted all settings!',
					array(),
					false,
					$ems
				);
			}

			$this->init_settings();
			$this->settings['fb_api_key']            = '';
			$this->settings['fb_product_catalog_id'] = '';

			$this->settings['fb_pixel_id']      = '';
			$this->settings['fb_pixel_use_pii'] = 'no';

			$this->settings['fb_page_id']                       = '';
			$this->settings['fb_external_merchant_settings_id'] = '';
			$this->settings['pixel_install_time']               = '';
			$this->settings['fb_feed_id']                       = '';
			$this->settings['fb_upload_id']                     = '';
			$this->settings['upload_end_time']                  = '';

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
	 * Check Feed Upload Status
	 **/
	function ajax_check_feed_upload_status() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'check feed upload status', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		if ( $this->settings['fb_api_key'] ) {
			$response = array(
				'connected' => true,
				'status'    => 'in progress',
			);
			if ( $this->settings['fb_upload_id'] ) {
				if ( ! isset( $this->fbproductfeed ) ) {
					if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) {
						include_once 'includes/fbproductfeed.php';
					}
					$this->fbproductfeed = new WC_Facebook_Product_Feed(
						$this->product_catalog_id,
						$this->fbgraph
					);
				}
				$status = $this->fbproductfeed->is_upload_complete( $this->settings );

				$response['status'] = $status;
			} else {
				$response = array(
					'connected' => true,
					'status'    => 'error',
				);
			}
			if ( $response['status'] == 'complete' ) {
				update_option(
					$this->get_option_key(),
					apply_filters(
						'woocommerce_settings_api_sanitized_fields_' . $this->id,
						$this->settings
					)
				);
			}
		} else {
			$response = array(
				'connected' => false,
			);
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

		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;

		WC_Facebookcommerce_Utils::log( $msg );

		set_transient(
			'facebook_plugin_api_error',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
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
	function checks() {

		// check required fields
		if ( ! $this->api_key || ! $this->product_catalog_id ) {

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
		if ( $this->api_key && ( ! isset( $this->background_processor ) ) ) {

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
	 **/
	function ajax_sync_all_fb_products() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'syncall products', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		if ( get_option( 'fb_disable_sync_on_dev_environment', false ) ) {
			WC_Facebookcommerce_Utils::log(
				'Sync to FB Page is not allowed in Dev Environment'
			);
			wp_die();
			return;
		}

		if ( ! $this->api_key || ! $this->product_catalog_id ) {
			WC_Facebookcommerce_Utils::log(
				'No API key or catalog ID: ' .
				$this->api_key . ' and ' . $this->product_catalog_id
			);
			wp_die();
			return;
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
			WC_Facebookcommerce_Utils::log( 'Not syncing, sync in progress' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync during an in-progress sync!',
				array(),
				true
			);
			$this->display_warning_message(
				'A product sync is in progress.
        Please wait until the sync finishes before starting a new one.'
			);
			wp_die();
			return;
		}

		$is_valid_product_catalog =
		$this->fbgraph->validate_product_catalog( $this->product_catalog_id );

		if ( ! $is_valid_product_catalog ) {
			WC_Facebookcommerce_Utils::log( 'Not syncing, invalid product catalog!' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync with an invalid product catalog!',
				array(),
				true
			);
			$this->display_warning_message(
				'We\'ve detected that your
        Facebook Product Catalog is no longer valid. This may happen if it was
        deleted, or this may be a transient error.
        If this error persists please remove your settings via
        "Advanced Options > Advanced Settings > Remove"
        and try setup again'
			);
			wp_die();
			return;
		}

		// Cache the cart URL to display a warning in case it changes later
		$cart_url = get_option( self::FB_CART_URL );
		if ( $cart_url != wc_get_cart_url() ) {
			update_option( self::FB_CART_URL, wc_get_cart_url() );
		}

		$sanitized_settings = $this->settings;
		unset( $sanitized_settings['fb_api_key'] );

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
			$sanitized_settings,
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

		// This is important, for some reason.
		// See https://codex.wordpress.org/AJAX_in_Plugins
		wp_die();
	}

	/**
	 * Special function to run all visible products by uploading feed.
	 **/
	function ajax_sync_all_fb_products_using_feed() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions(
			'syncall products using feed',
			! $this->test_mode
		);
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		return $this->sync_all_fb_products_using_feed();
	}

	// Separate entry point that bypasses permission check for use in cron.
	function sync_all_fb_products_using_feed() {
		if ( get_option( 'fb_disable_sync_on_dev_environment', false ) ) {
			WC_Facebookcommerce_Utils::log(
				'Sync to FB Page is not allowed in Dev Environment'
			);
			$this->fb_wp_die();
			return false;
		}

		if ( ! $this->api_key || ! $this->product_catalog_id ) {
			self::log(
				'No API key or catalog ID: ' . $this->api_key .
				' and ' . $this->product_catalog_id
			);
			$this->fb_wp_die();
			return false;
		}
		$this->remove_resync_message();
		$is_valid_product_catalog =
		$this->fbgraph->validate_product_catalog( $this->product_catalog_id );

		if ( ! $is_valid_product_catalog ) {
			WC_Facebookcommerce_Utils::log( 'Not syncing, invalid product catalog!' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync with an invalid product catalog!',
				array(),
				true
			);
			$this->display_warning_message(
				'We\'ve detected that your
        Facebook Product Catalog is no longer valid. This may happen if it was
        deleted, or this may be a transient error.
        If this error persists please remove your settings via
        "Advanced Options > Advanced Settings > Remove"
        and try setup again'
			);
			$this->fb_wp_die();
			return false;
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
				$this->product_catalog_id,
				$this->fbgraph,
				$this->feed_id
			);
		} else {
			$this->fbproductfeed = new WC_Facebook_Product_Feed(
				$this->product_catalog_id,
				$this->fbgraph,
				$this->feed_id
			);
		}

		$upload_success = $this->fbproductfeed->sync_all_products_using_feed();
		if ( $upload_success ) {
			$this->settings['fb_feed_id']   = $this->fbproductfeed->feed_id;
			$this->settings['fb_upload_id'] = $this->fbproductfeed->upload_id;
			update_option(
				$this->get_option_key(),
				apply_filters(
					'woocommerce_settings_api_sanitized_fields_' .
					$this->id,
					$this->settings
				)
			);
			wp_reset_postdata();
			$this->fb_wp_die();
			return true;
		}
		WC_Facebookcommerce_Utils::fblog(
			'Sync all products using feed, curl failed',
			array(),
			true
		);
		return false;
	}

	/**
	 * Toggles product visibility via AJAX.
	 *
	 * @internal
	 * @deprecated since x.y.z
	 **/
	public function ajax_fb_toggle_visibility() {

		wc_deprecated_function( __METHOD__, 'x.y.z' );
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

		$this->form_fields = [
			'fb_settings_heading'              => [
				'title' => __( 'Debug Mode', 'facebook-for-woocommerce' ),
				'type'  => 'title',
			],
			'fb_page_id'                       => [
				'title'       => __( 'Facebook Page ID', 'facebook-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier for your Facebook page.', 'facebook-for-woocommerce' ),
				'default'     => '',
			],
			'fb_product_catalog_id'            => [
				'title'       => __( 'Product Catalog ID', 'facebook-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier for your product catalog, on Facebook.', 'facebook-for-woocommerce' ),
				'default'     => '',
			],
			'fb_pixel_id'                      => [
				'title'       => __( 'Pixel ID', 'facebook-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier for your Facebook pixel', 'facebook-for-woocommerce' ),
				'default'     => '',
			],
			'fb_pixel_use_pii'                 => [
				'title'       => __( 'Use Advanced Matching on pixel?', 'facebook-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enabling Advanced Matching improves audience building.', 'facebook-for-woocommerce' ),
				'default'     => 'yes',
			],
			'fb_external_merchant_settings_id' => [
				'title'       => __( 'External Merchant Settings ID', 'facebook-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier for your external merchant settings, on Facebook.', 'facebook-for-woocommerce' ),
				'default'     => '',
			],
			'fb_api_key'                       => [
				'title'       => __( 'API Key', 'facebook-for-woocommerce' ),
				'type'        => 'text',
				'description' => sprintf(
					/* translators: Placeholders: %s - Facebook Login permissions */
					__( 'A non-expiring Page Token with %s permissions.', 'facebook-for-woocommerce' ),
					'<code>manage_pages</code>'
				),
				'default'     => '',
			],
			'fb_sync_options'                  => [
				'title' => __( 'Sync', 'facebook-for-woocommerce' ),
				'type'  => 'title'
			],
			'fb_sync_exclude_categories'       => [
				'title'             => __( 'Exclude categories from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width: 300px;',
				'default'           => [],
				'options'           => is_array( $product_categories ) ? $product_categories : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product category&hellip;', 'facebook-for-woocommerce' ),
				],
			],
			'fb_sync_exclude_tags'             => [
				'title'             => __( 'Exclude tags from sync', 'facebook-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width: 300px;',
				'default'           => [],
				'options'           => is_array( $product_tags ) ? $product_tags : [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Search for a product tag&hellip;', 'facebook-for-woocommerce' ),
				],
			],
		];

		if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) {
			include_once 'includes/fbutils.php';
		}
	}


	/**
	 * Gets the IDs of the categories to be excluded from sync.
	 *
	 * @since x.y.z
	 *
	 * @return int[]
	 */
	public function get_excluded_product_category_ids() {

		return (array) $this->get_option( 'fb_sync_exclude_categories', [] );
	}


	/**
	 * Gets the IDs of the tags to be excluded from sync.
	 *
	 * @since x.y.z
	 *
	 * @return int[]
	 */
	public function get_excluded_product_tag_ids() {

		return (array) $this->get_option( 'fb_sync_exclude_tags', [] );
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

		$error_msg = get_transient( 'facebook_plugin_api_error' );

		if ( $error_msg ) {

			$message = sprintf(
				__(
					'Facebook extension error: %s ',
					'facebook-for-woocommerce'
				),
				$error_msg
			);

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


	function get_page_name() {
		$page_name = '';
		if ( ! empty( $this->settings['fb_page_id'] ) &&
		! empty( $this->settings['fb_api_key'] ) ) {

			$page_name = $this->fbgraph->get_page_name(
				$this->settings['fb_page_id'],
				$this->settings['fb_api_key']
			);
		}
		return $page_name;
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

		$domain          = 'facebook-for-woocommerce';
		$cta_button_text = __( 'Get Started', $domain );
		$page_name       = $this->get_page_name();

		$can_manage     = current_user_can( 'manage_woocommerce' );
		$pre_setup      = empty( $this->settings['fb_page_id'] ) || empty( $this->settings['fb_api_key'] );
		$apikey_invalid = ! $pre_setup && $this->settings['fb_api_key'] && ! $page_name;

		$redirect_uri           = '';
		$remove_http_active     = is_plugin_active( 'remove-http/remove-http.php' );
		$https_will_be_stripped = $remove_http_active && ! get_option( 'factmaven_rhttp' )['external'];

		if ( $https_will_be_stripped ) {

			$this->display_sticky_message(
				__( 'You\'re using Remove HTTP which has incompatibilities with our extension. Please disable it, or select the "Ignore external links" option on the Remove HTTP settings page.' )
			);
		}

		if ( ! $pre_setup ) {

			$cta_button_text = __( 'Create Ad', $domain );
			$redirect_uri    = 'https://www.facebook.com/ads/dia/redirect/?settings_id='
				. $this->external_merchant_settings_id . '&version=2'
				. '&entry_point=admin_panel';
		}

		$currently_syncing = get_transient( self::FB_SYNC_IN_PROGRESS );
		$connected         = ( $page_name != '' );
		$hide_test         = ( $connected && $currently_syncing ) || ! defined( 'WP_DEBUG' ) || WP_DEBUG !== true;

		?>
		<h2><?php esc_html_e( 'Facebook', $domain ); ?></h2>
		<p>
			<?php
			esc_html_e(
				'Control how WooCommerce integrates with your Facebook store.',
				$domain
			);
			?>
		</p>
		<hr/>

		<div id="fbsetup">
			<div class="wrapper">
				<header>
					<div class="help-center">
						<a href="https://www.facebook.com/business/help/900699293402826" target="_blank">Help Center <i class="help-center-icon"></i></a>
					</div>
				</header>

				<div class="content">
					<h1 id="setup_h1">
						<?php
							$pre_setup
								? esc_html_e( 'Grow your business on Facebook', $domain )
								: esc_html_e( 'Reach The Right People and Sell More Online', $domain );
						?>
					</h1>

					<h2>
						<?php
							esc_html_e(
								'Use this WooCommerce and Facebook integration to:',
								$domain
							);
						?>
					</h2>

					<ul>
						<li id="setup_l1">
							<?php
								$pre_setup
									? esc_html_e( 'Easily install a tracking pixel', $domain )
									: esc_html_e( 'Create an ad in a few steps', $domain );
							?>
						</li>
						<li id="setup_l2">
							<?php
								$pre_setup
									? esc_html_e( 'Upload your products and create a shop', $domain )
									: esc_html_e( 'Use built-in best practices for online sales', $domain );
							?>
						</li>
						<li id="setup_l3">
							<?php
								$pre_setup
									? esc_html_e( 'Create dynamic ads with your products and pixel', $domain )
									: esc_html_e( 'Get reporting on sales and revenue', $domain );
							?>
						</li>
					</ul>

					<span <?php echo ( ! $can_manage || $apikey_invalid || ! isset( $this->external_merchant_settings_id ) ) ? ' style="pointer-events: none;"' : ''; ?>>

						<?php if ( $pre_setup ) : ?>

							<a href="#" class="btn pre-setup" onclick="facebookConfig()" id="cta_button">
								<?php echo esc_html( $cta_button_text ); ?>
							</a>

						<?php else : ?>

							<a href='<?php esc_attr( $redirect_uri ); ?>' class="btn" id="cta_button">
								<?php echo esc_html( $cta_button_text ); ?>
							</a>
							<a href="https://www.facebook.com/business/m/drive-more-online-sales"
								class="btn grey" id="learnmore_button">
								<?php echo esc_html__('Learn More' ); ?>
							</a>

						<?php endif; ?>

					</span>

					<hr />

					<div class="settings-container">
						<div id="plugins" class="settings-section"
							<?php echo ( $pre_setup && $can_manage ) ? ' style="display:none;"' : ''; ?>
						>

							<h1><?php esc_html_e( 'Add Ways for People to Shop' ); ?></h1>
							<h2><?php esc_html_e( 'Connect your business with features such as Messenger and more.' ); ?></h2>
							<a href="#" class="btn small" onclick="facebookConfig()" id="connect_button">
								<?php esc_html_e( 'Add Features' ); ?>
							</a>
						</div>

						<div id="settings" class="settings-section"
							<?php echo ( $pre_setup && $can_manage ) ? ' style="display:none;"' : ''; ?>
						>

							<h1><?php echo esc_html__( 'Settings', $domain ); ?></h1>

							<?php if ( $apikey_invalid ) : // API key is set, but no page name ?>

								<h2 id="token_text" style="color:red;">
									<?php esc_html_e('Your API key is no longer valid. Please click "Settings > Advanced Options > Update Token".', $domain); ?>
								</h2>

								<span>
									<a href="#" class="btn small" onclick="facebookConfig()" id="setting_button">
										<?php esc_html_e( 'Settings', $domain ); ?>
									</a>
								</span>

							<?php else : ?>

								<?php if ( ! $can_manage ) : ?>

									<h2 style="color:red;">
										<?php esc_html_e( 'You must have "manage_woocommerce" permissions to use this plugin.', $domain ); ?>
									</h2>

								<?php else : ?>

									<h2>
										<span id="connection_status" <?php echo ! $connected ? ' style="display: none;"' : ''; ?>>
											<?php esc_html_e( 'Your WooCommerce store is connected to ', $domain ); ?>
											<?php if ( $page_name != '' ) : ?>
												<?php echo sprintf(
													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													__( 'the Facebook page <a target="_blank" href="https://www.facebook.com/%1$s">%2$s</a></span>', $domain ),
													esc_html( $this->settings['fb_page_id'] ),
													esc_html( $page_name ) ); ?>
											<?php else : ?>
												<?php echo sprintf(
													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													__( '<a target="_blank" href="https://www.facebook.com/%1$s">your Facebook page</a></span>', $domain ),
													esc_html( $this->settings['fb_page_id'] ) ); ?>
											<?php endif; ?>

											<span id="sync_complete" style="margin-left: 5px; <?php echo ( ! $connected || $currently_syncing ) ? ' display: none;' : ''; ?>">
												<?php esc_html_e( 'Status', $domain ); ?>:
												<?php esc_html_e( 'Products are synced to Facebook.', $domain ); ?>
											</span>

											<span>
												<a href="#" onclick="show_debug_info()" id="debug_info" style="display:none;">
													<?php esc_html_e( 'More Info', $domain ); ?>
												</a>
											</span>
										</span>
									</h2>

									<span>
										<a href="#" class="btn small" onclick="facebookConfig()" id="setting_button"
											<?php echo $currently_syncing ? ' style="pointer-events: none;"' : ''; ?>
										>
											<?php esc_html_e( 'Manage Settings', $domain ); ?>
										</a>
									</span>

									<span>
										<a href="#" class="btn small" onclick="sync_confirm()" id="resync_products"
											<?php echo ( $connected && $currently_syncing ) ? ' style="pointer-events: none;" ' : ''; ?>
										>
											<?php esc_html_e( 'Sync Products', $domain ); ?>
										</a>
									</span>

									<p id="sync_progress">
										<?php if ( $connected && $currently_syncing ): ?>
											<hr/>
											<?php esc_html_e( 'Syncing... Keep this browser open', $domain ); ?>
											<br/>
											<?php esc_html_e( 'Until sync is complete', $domain ); ?>
										<?php endif; ?>
									</p>

								<?php endif; ?>

							<?php endif; ?>

						</div>
						<hr />
					</div>

					<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $this->get_nux_message_ifexist();
					?>

					<div>
						<div id='fbAdvancedOptionsText' onclick="toggleAdvancedOptions();">
							Show Advanced Settings
						</div>
						<div id='fbAdvancedOptions'>
							<div class='autosync' title="This experimental feature will call force resync at the specified time using WordPress cron scheduling.">
								<input type="checkbox"
									onclick="saveAutoSyncSchedule()"
									class="autosyncCheck"
									<?php echo get_option( 'woocommerce_fb_autosync_time', false ) ? 'checked' : 'unchecked'; ?>>
								Automatically Force Resync of Products At

								<input
									type="time"
									value="<?php echo esc_attr( get_option( 'woocommerce_fb_autosync_time', '23:00' ) ); ?>"
									class="autosyncTime"
									onfocusout="saveAutoSyncSchedule()"
									<?php echo get_option( 'woocommerce_fb_autosync_time', 0 ) ? '' : 'disabled'; ?> />
								Every Day.
								<span class="autosyncSavedNotice" disabled> Saved </span>
							</div>

							<div title="This option is meant for development and testing environments.">
								<input type="checkbox"
									onclick="onSetDisableSyncOnDevEnvironment()"
									class="disableOnDevEnvironment"
									<?php echo get_option( 'fb_disable_sync_on_dev_environment', false ) ? 'checked' : 'unchecked'; ?>/>
								Disable Product Sync with FB
							</div>

							<div class='shortdescr' title="This experimental feature will import short description instead of description for all products.">
								<input type="checkbox"
									onclick="syncShortDescription()"
									class="syncShortDescription"
									<?php echo get_option( 'fb_sync_short_description', false ) ? 'checked' : 'unchecked'; ?>/>
								Sync Short Description Instead of Description
							</div>
						</div>
					</div>
				</div>
			</div>

			<div <?php echo ( $hide_test ) ? ' style="display:none;" ' : ''; ?> >
				<p class="tooltip" id="test_product_sync">
					<?php // WP_DEBUG mode: button to launch test ?>
					<a href="<?php echo esc_attr( WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL ); ?>&fb_test_product_sync=true">
						<?php echo esc_html__( 'Launch Test', $domain ); ?>
						<span class='tooltiptext'>
							<?php esc_html_e( 'This button will run an integration test suite verifying the extension. Note that this will reset your products and resync them to Facebook. Not recommended to use unless you are changing the extension code and want to test your changes.', $domain ); ?>
						</span>
					</a>
				</p>
				<p id="stack_trace"></p>
			</div>
			<br/><hr/><br/>

			<?php $GLOBALS['hide_save_button'] = true; ?>
			<?php if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ): ?>
				<?php $GLOBALS['hide_save_button'] = false; ?>
				<table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table><!--/.form-table-->
			<?php endif; ?>
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

	function on_quick_and_bulk_edit_save( $product ) {

		// bail if not a product or product is not enabled for sync
		if ( ! $product instanceof \WC_Product || ! Products::is_sync_enabled_for_product( $product ) ) {
			return;
		}

		$wp_id      = $product->get_id();
		$visibility = get_post_status( $wp_id ) === 'publish'
		? self::FB_SHOP_PRODUCT_VISIBLE
		: self::FB_SHOP_PRODUCT_HIDDEN;
		// case 1: new status is 'publish' regardless of old status, sync to FB
		if ( $visibility === self::FB_SHOP_PRODUCT_VISIBLE ) {
			$this->on_product_publish( $wp_id );
		} else {
			// case 2: product never publish to FB, new status is not publish
			// case 3: product new status is not publish and published before
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
			$this->product_catalog_id,
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

			if ( $body && $body->id ) {

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
	 * Schedule Force Resync
	 */
	function ajax_schedule_force_resync() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'resync schedule', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		if ( isset( $_POST ) && isset( $_POST['enabled'] ) ) {
			if ( isset( $_POST['time'] ) && ! empty( $_POST['enabled'] ) ) { // Enabled
				$time = sanitize_text_field( wp_unslash( $_POST['time'] ) );
				wp_clear_scheduled_hook( 'sync_all_fb_products_using_feed' );
				wp_schedule_event(
					strtotime( $time ),
					'daily',
					'sync_all_fb_products_using_feed'
				);
				WC_Facebookcommerce_Utils::fblog( 'Scheduled autosync for ' . $time );
				update_option( 'woocommerce_fb_autosync_time', $time );
			} elseif ( empty( $_POST['enabled'] ) ) { // Disabled
				wp_clear_scheduled_hook( 'sync_all_fb_products_using_feed' );
				WC_Facebookcommerce_Utils::fblog( 'Autosync disabled' );
				delete_option( 'woocommerce_fb_autosync_time' );
			}
		} else {
			WC_Facebookcommerce_Utils::fblog( 'Autosync AJAX Problem', $_POST, true );
		}
		wp_die();
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
}
