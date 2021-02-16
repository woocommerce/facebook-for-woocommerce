<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Lifecycle;
use SkyVerge\WooCommerce\Facebook\Utilities\Background_Handle_Virtual_Products_Variations;
use SkyVerge\WooCommerce\Facebook\Utilities\Background_Remove_Duplicate_Visibility_Meta;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! class_exists( 'WC_Facebookcommerce' ) ) :

	include_once 'includes/fbutils.php';

	class WC_Facebookcommerce extends Framework\SV_WC_Plugin {


		/** @var string the plugin version */
		const VERSION = '2.2.1-dev.1';

		/** @var string for backwards compatibility TODO: remove this in v2.0.0 {CW 2020-02-06} */
		const PLUGIN_VERSION = self::VERSION;

		/** @var string the plugin ID */
		const PLUGIN_ID = 'facebook_for_woocommerce';

		/** @var string the integration ID */
		const INTEGRATION_ID = 'facebookcommerce';

		/** @var string the product set categories meta name */
		const PRODUCT_SET_META = '_wc_facebook_product_cats';


		/** @var \WC_Facebookcommerce singleton instance */
		protected static $instance;

		/** @var SkyVerge\WooCommerce\Facebook\API instance */
		private $api;

		/** @var \WC_Facebookcommerce_Integration instance */
		private $integration;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin admin handler instance */
		private $admin;

		/** @var \SkyVerge\WooCommerce\Facebook\Admin\Settings */
		private $admin_settings;

		/** @var \SkyVerge\WooCommerce\Facebook\AJAX Ajax handler instance */
		private $ajax;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Feed product feed handler */
		private $product_feed;

		/** @var Background_Handle_Virtual_Products_Variations instance */
		protected $background_handle_virtual_products_variations;

		/** @var Background_Remove_Duplicate_Visibility_Meta job handler instance */
		protected $background_remove_duplicate_visibility_meta;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Stock products stock handler */
		private $products_stock_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Sync products sync handler */
		private $products_sync_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Products\Sync\Background background sync handler */
		private $sync_background_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\ProductSets\Sync product sets sync handler */
		private $product_sets_sync_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Handlers\Connection connection handler */
		private $connection_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Handlers\WebHook webhook handler */
		private $webhook_handler;

		/** @var \SkyVerge\WooCommerce\Facebook\Integrations\Integrations integrations handler */
		private $integrations;

		/** @var \SkyVerge\WooCommerce\Facebook\Commerce commerce handler */
		private $commerce_handler;


		/**
		 * Constructs the plugin.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			parent::__construct(
				self::PLUGIN_ID,
				self::VERSION,
				[
					'text_domain' => 'facebook-for-woocommerce',
				]
			);

			$this->init();
		}


		/**
		 * Initializes the plugin.
		 *
		 * @internal
		 */
		public function init() {

			add_action( 'init', [ $this, 'get_integration' ] );
			add_action( 'init', [ $this, 'register_custom_taxonomy' ] );
			add_action( 'add_meta_boxes_product' , [ $this, 'remove_product_fb_product_set_metabox' ], 50 );
			add_filter( 'fb_product_set_row_actions', [ $this, 'product_set_links' ] );
			add_filter( 'manage_edit-fb_product_set_columns', [ $this, 'manage_fb_product_set_columns' ] );

			// Product Set breadcrumb filters
			add_filter( 'woocommerce_navigation_is_connected_page', [ $this, 'is_current_page_conected_filter' ], 99, 2 );
			add_filter( 'woocommerce_navigation_get_breadcrumbs', [ $this, 'wc_page_breadcrumbs_filter' ], 99 );

			if ( \WC_Facebookcommerce_Utils::isWoocommerceIntegration() ) {

				include_once 'facebook-commerce.php';

				require_once $this->get_framework_path() . '/utilities/class-sv-wp-async-request.php';
				require_once $this->get_framework_path() . '/utilities/class-sv-wp-background-job-handler.php';

				require_once __DIR__ . '/includes/Locale.php';
				require_once __DIR__ . '/includes/AJAX.php';
				require_once __DIR__ . '/includes/Handlers/Connection.php';
				require_once __DIR__ . '/includes/Handlers/WebHook.php';
				require_once __DIR__ . '/includes/Integrations/Integrations.php';
				require_once __DIR__ . '/includes/Product_Categories.php';
				require_once __DIR__ . '/includes/Products.php';
				require_once __DIR__ . '/includes/Products/Feed.php';
				require_once __DIR__ . '/includes/Products/FBCategories.php';
				require_once __DIR__ . '/includes/Products/Stock.php';
				require_once __DIR__ . '/includes/Products/Sync.php';
				require_once __DIR__ . '/includes/Products/Sync/Background.php';
				require_once __DIR__ . '/includes/ProductSets/Sync.php';
				require_once __DIR__ . '/includes/fbproductfeed.php';
				require_once __DIR__ . '/facebook-commerce-messenger-chat.php';
				require_once __DIR__ . '/includes/Commerce.php';
				require_once __DIR__ . '/includes/Events/Event.php';
				require_once __DIR__ . '/includes/Events/Normalizer.php';
				require_once __DIR__ . '/includes/Events/AAMSettings.php';
				require_once __DIR__ . '/includes/Utilities/Shipment.php';

				$this->product_feed              = new \SkyVerge\WooCommerce\Facebook\Products\Feed();
				$this->products_stock_handler    = new \SkyVerge\WooCommerce\Facebook\Products\Stock();
				$this->products_sync_handler     = new \SkyVerge\WooCommerce\Facebook\Products\Sync();
				$this->sync_background_handler   = new \SkyVerge\WooCommerce\Facebook\Products\Sync\Background();
				$this->product_sets_sync_handler = new \SkyVerge\WooCommerce\Facebook\ProductSets\Sync();
				$this->commerce_handler          = new \SkyVerge\WooCommerce\Facebook\Commerce();
				$this->fb_categories             = new \SkyVerge\WooCommerce\Facebook\Products\FBCategories();

				if ( is_ajax() ) {
					$this->ajax = new \SkyVerge\WooCommerce\Facebook\AJAX();
				}

				$this->integrations = new \SkyVerge\WooCommerce\Facebook\Integrations\Integrations( $this );

				if ( 'yes' !== get_option( 'wc_facebook_background_handle_virtual_products_variations_complete', 'no' ) ) {

					require_once __DIR__ . '/includes/Utilities/Background_Handle_Virtual_Products_Variations.php';

					$this->background_handle_virtual_products_variations = new Background_Handle_Virtual_Products_Variations();

				}

				if ( 'yes' !== get_option( 'wc_facebook_background_remove_duplicate_visibility_meta_complete', 'no' ) ) {

					require_once __DIR__ . '/includes/Utilities/Background_Remove_Duplicate_Visibility_Meta.php';

					$this->background_remove_duplicate_visibility_meta = new Background_Remove_Duplicate_Visibility_Meta();
				}

				$this->connection_handler = new \SkyVerge\WooCommerce\Facebook\Handlers\Connection( $this );
				$this->webhook_handler = new \SkyVerge\WooCommerce\Facebook\Handlers\WebHook( $this );

				// load admin handlers, before admin_init
				if ( is_admin() ) {

					require_once __DIR__ . '/includes/Admin/Settings.php';
					require_once __DIR__ . '/includes/Admin/Abstract_Settings_Screen.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Connection.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Product_Sync.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Product_Sets.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Messenger.php';
					require_once __DIR__ . '/includes/Admin/Settings_Screens/Advertise.php';
					require_once __DIR__ . '/includes/Admin/Google_Product_Category_Field.php';
					require_once __DIR__ . '/includes/Admin/Enhanced_Catalog_Attribute_Fields.php';

					$this->admin_settings = new \SkyVerge\WooCommerce\Facebook\Admin\Settings();
				}
			}
		}


		/**
		 * Initializes the admin handling.
		 *
		 * @internal
		 *
		 * @since 1.10.0
		 */
		public function init_admin() {

			require_once __DIR__ . '/includes/Admin.php';

			$this->admin = new \SkyVerge\WooCommerce\Facebook\Admin();
		}


		/**
		 * Gets deprecated and removed hooks.
		 *
		 * @since 2.1.0
		 *
		 * @return array
		 */
		protected function get_deprecated_hooks() {

			return [
				'wc_facebook_page_access_token' => [
					'version'     => '2.1.0',
					'replacement' => false,
				],
			];
		}


		/**
		 * Adds the plugin admin notices.
		 *
		 * @since 1.11.0
		 */
		public function add_admin_notices() {

			parent::add_admin_notices();

			// inform users who are not connected to Facebook
			if ( ! $this->get_connection_handler()->is_connected() ) {

				// users who've never connected to FBE 2 but have previously connected to FBE 1
				if ( ! $this->get_connection_handler()->has_previously_connected_fbe_2() && $this->get_connection_handler()->has_previously_connected_fbe_1() ) {

					$message = sprintf(
						/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
						__( '%1$sHeads up!%2$s You\'re ready to migrate to a more secure, reliable Facebook for WooCommerce connection. Please %3$sclick here%4$s to reconnect!', 'facebook-for-woocommerce' ),
						'<strong>', '</strong>',
						'<a href="' . esc_url( $this->get_connection_handler()->get_connect_url() ) . '">', '</a>'
					);

					$this->get_admin_notice_handler()->add_admin_notice( $message, self::PLUGIN_ID . '_migrate_to_v2_0', [
						'dismissible'  => false,
						'notice_class' => 'notice-info',
					] );

					// direct these users to the new plugin settings page
					if ( ! $this->is_plugin_settings() ) {

						$message = sprintf(
							/* translators: Placeholders %1$s - opening link HTML tag, %2$s - closing link HTML tag */
							__( 'For your convenience, the Facebook for WooCommerce settings are now located under %1$sWooCommerce > Facebook%2$s.', 'facebook-for-woocommerce' ),
							'<a href="' . esc_url( facebook_for_woocommerce()->get_settings_url() ) . '">', '</a>'
						);

						$this->get_admin_notice_handler()->add_admin_notice( $message, self::PLUGIN_ID . '_relocated_settings', [
							'dismissible'  => true,
							'notice_class' => 'notice-info',
						] );
					}

				// otherwise, a general getting started message
				} elseif ( ! $this->is_plugin_settings() ) {

					$message = sprintf(
						/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
						esc_html__(
							'%1$sFacebook for WooCommerce is almost ready.%2$s To complete your configuration, %3$scomplete the setup steps%4$s.',
							'facebook-for-woocommerce'
						),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url( facebook_for_woocommerce()->get_settings_url() ) . '">',
						'</a>'
					);

					$this->get_admin_notice_handler()->add_admin_notice( $message, self::PLUGIN_ID . '_get_started', [
						'dismissible'  => true,
						'notice_class' => 'notice-info',
					] );
				}

			// notices for those connected to FBE 2
			} else {

				// if upgraders had messenger enabled and one of the removed settings was customized, alert them to reconfigure
				if (
					   $this->get_integration()->get_external_merchant_settings_id()
					&& $this->get_integration()->is_messenger_enabled()
					&& ( '#0084ff' !== $this->get_integration()->get_messenger_color_hex() || ! in_array( $this->get_integration()->get_messenger_greeting(), [ 'Hi! How can we help you?', "Hi! We're here to answer any questions you may have.", '' ], true ) )
				) {

					$message = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
						__( '%1$sHeads up!%2$s If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again from the %3$sManage Connection%4$s area.', 'facebook-for-woocommerce' ),
						'<strong>', '</strong>',
						'<a href="' . esc_url( $this->get_connection_handler()->get_manage_url() ) . '" target="_blank">', '</a>'
					);

					$this->get_admin_notice_handler()->add_admin_notice( $message, 'update_messenger', [
						'always_show_on_settings' => false,
						'notice_class'            => 'notice-info',
					] );
				}
			}

			// if the connection is otherwise invalid, but there is an access token
			if ( get_transient( 'wc_facebook_connection_invalid' ) && $this->get_connection_handler()->is_connected() ) {

				$message = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sHeads up!%2$s Your connection to Facebook is no longer valid. Please %3$sclick here%4$s to securely reconnect your account and continue syncing products.', 'facebook-for-woocommerce' ),
					'<strong>', '</strong>',
					'<a href="' . esc_url( $this->get_connection_handler()->get_connect_url() ) . '">', '</a>'
				);

				$this->get_admin_notice_handler()->add_admin_notice( $message, 'connection_invalid', [
					'notice_class' => 'notice-error',
				] );
			}

			if ( Framework\SV_WC_Plugin_Compatibility::is_enhanced_admin_available() ) {

				$is_marketing_enabled = is_callable( 'Automattic\WooCommerce\Admin\Loader::is_feature_enabled' )
				                        && Automattic\WooCommerce\Admin\Loader::is_feature_enabled( 'marketing' );

				if ( $is_marketing_enabled ) {

					$this->get_admin_notice_handler()->add_admin_notice(
						sprintf(
							/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
							esc_html__( 'Heads up! The Facebook menu is now located under the %1$sMarketing%2$s menu.', 'facebook-for-woocommerce' ),
							'<a href="' . esc_url( $this->get_settings_url() ) . '">','</a>'
						),
						'settings_moved_to_marketing',
						[
							'dismissible'             => true,
							'always_show_on_settings' => false,
							'notice_class'            => 'notice-info',
						]
					);
				}
			}
		}


		public function add_wordpress_integration() {
			new WP_Facebook_Integration();
		}


		/**
		 * Logs an API request.
		 *
		 * @since 2.0.0
		 *
		 * @param array $request request data
		 * @param array $response response data
		 * @param null $log_id log ID
		 */
		public function log_api_request( $request, $response, $log_id = null ) {

			// bail if logging isn't enabled
			if ( ! $this->get_integration() || ! $this->get_integration()->is_debug_mode_enabled() ) {
				return;
			}

			parent::log_api_request( $request, $response, $log_id );
		}

		/**
		 * Remove Product Set metabox from Product edit page
		 *
		 * @since 2.2.1-dev.1
		 */
		public function remove_product_fb_product_set_metabox() {
			remove_meta_box( 'fb_product_setdiv', 'product', 'side' );
		}

		/**
		 * Register FB Product Set Taxonomy
		 *
		 * @since 2.2.1-dev.1
		 */
		public function register_custom_taxonomy() {

			$plural   = esc_html__( 'FB Product Sets', 'facebook-for-woocommerce' );
			$singular = esc_html__( 'FB Product Set', 'facebook-for-woocommerce' );

			$args = array(
				'labels'            => array(
					'name'                       => $plural,
					'singular_name'              => $singular,
					'menu_name'                  => $plural,
					// translators: Edit item label
					'edit_item'                  => sprintf( esc_html__( 'Edit %s', 'facebook-for-woocommerce' ), $singular ),
					// translators: Add new label
					'add_new_item'               => sprintf( esc_html__( 'Add new %s', 'facebook-for-woocommerce' ), $singular ),
					'menu_name'                  => $plural,
					// translators: No items found text
					'not_found'                  => sprintf( esc_html__( 'No %s found.', 'facebook-for-woocommerce' ), $plural ),
					// translators: Search label
					'search_items'               => sprintf( esc_html__( 'Search %s.', 'facebook-for-woocommerce' ), $plural ),
					// translators: Text label
					'separate_items_with_commas' => sprintf( esc_html__( 'Separate %s with commas', 'facebook-for-woocommerce' ), $plural ),
					// translators: Text label
					'choose_from_most_used'      => sprintf( esc_html__( 'Choose from the most used %s', 'facebook-for-woocommerce' ), $plural ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
			);

			register_taxonomy( 'fb_product_set', array( 'product' ), $args );
		}


		/**
		 * Filter FB Product Set Taxonomy table links
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @param array $actions Item Actions.
		 *
		 * @return array
		 */
		public function product_set_links( $actions ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
			return $actions;
		}


		/**
		 * Remove posts count column from FB Product Set custom taxonomy
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @param array $columns Taxonomy columns.
		 *
		 * @return array
		 */
		public function manage_fb_product_set_columns( $columns ) {
			unset( $columns['posts'] );
			return $columns;
		}


		/**
		 * Filter WC Breadcrumbs when the page is FB Product Sets
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @param array $breadcrumbs Page breadcrumbs.
		 *
		 * @return array
		 */
		public function wc_page_breadcrumbs_filter( $breadcrumbs ) {

			if ( 'edit-fb_product_set' !== $this->get_current_page_id() ) {
				return $breadcrumbs;
			}

			$breadcrumbs = array(
				array( 'admin.php?page=wc-admin', 'WooCommerce' ),
				array( 'edit.php?post_type=product', 'Products' ),
			);

			$term_id = empty( $_GET['tag_ID'] ) ? '' : $_GET['tag_ID']; //phpcs:ignore WordPress.Security

			if ( ! empty( $term_id ) ) {
				$breadcrumbs[] = array( 'edit-tags.php?taxonomy=fb_product_set&post_type=product', 'Products Sets' );
			}

			$breadcrumbs[] = ( empty( $term_id ) ? 'Product Sets' : 'Edit Product Set' );

			return $breadcrumbs;
		}


		/**
		 * Return that FB Product Set page is a WC Conected Page
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @param boolean $is_conected If it's connected or not.
		 *
		 * @return boolean
		 */
		public function is_current_page_conected_filter( $is_conected ) {

			if ( 'edit-fb_product_set' === $this->get_current_page_id() ) {
				return true;
			}

			return $is_conected;
		}


		/** Getter methods ********************************************************************************************/


		/**
		 * Gets the API instance.
		 *
		 * @since 2.0.0
		 *
		 * @param string $access_token access token to use for this API request
		 * @return \SkyVerge\WooCommerce\Facebook\API
		 * @throws Framework\SV_WC_API_Exception
		 */
		public function get_api( $access_token = '' ) {

			// if none provided, use the general access token
			if ( ! $access_token ) {
				$access_token = $this->get_connection_handler()->get_access_token();
			}

			if ( ! is_object( $this->api ) ) {

				if ( ! $access_token ) {
					throw new Framework\SV_WC_API_Exception( __( 'Cannot create the API instance because the access token is missing.', 'facebook-for-woocommerce' ) );
				}

				if ( ! class_exists( API\Traits\Rate_Limited_API::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_API.php';
				}

				if ( ! class_exists( API\Traits\Rate_Limited_Request::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_Request.php';
				}

				if ( ! class_exists( API\Traits\Rate_Limited_Response::class ) ) {
					require_once __DIR__ . '/includes/API/Traits/Rate_Limited_Response.php';
				}

				if ( ! trait_exists( API\Traits\Paginated_Response::class, false ) ) {
					require_once __DIR__ . '/includes/API/Traits/Paginated_Response.php';
				}

				if ( ! trait_exists( API\Traits\Idempotent_Request::class, false ) ) {
					require_once __DIR__ . '/includes/API/Traits/Idempotent_Request.php';
				}

				if ( ! class_exists( API::class ) ) {
					require_once __DIR__ . '/includes/API.php';
				}

				if ( ! class_exists( API\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Request.php';
				}

				if ( ! class_exists( API\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Response.php';
				}

				if ( ! class_exists( API\Pixel\Events\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Pixel/Events/Request.php';
				}

				if ( ! class_exists( API\Catalog\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Request.php';
				}

				if ( ! class_exists( API\Catalog\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Response.php';
				}

				if ( ! class_exists( API\Catalog\Send_Item_Updates\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Send_Item_Updates/Request.php';
				}

				if ( ! class_exists( API\Catalog\Send_Item_Updates\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Send_Item_Updates/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Group\Products\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Group/Products/Read/Request.php';
				}

				if ( ! class_exists( API\Catalog\Product_Group\Products\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Group/Products/Read/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Item\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Item/Response.php';
				}

				if ( ! class_exists( API\Catalog\Product_Item\Find\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Catalog/Product_Item/Find/Request.php';
				}

				if ( ! class_exists( API\User\Request::class ) ) {
					require_once __DIR__ . '/includes/API/User/Request.php';
				}

				if ( ! class_exists( API\User\Response::class ) ) {
					require_once __DIR__ . '/includes/API/User/Response.php';
				}

				if ( ! class_exists( API\User\Permissions\Delete\Request::class ) ) {
					require_once __DIR__ . '/includes/API/User/Permissions/Delete/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Read/Request.php';
				}

				if ( ! class_exists( API\FBE\Installation\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Installation/Read/Response.php';
				}

				if ( ! class_exists( API\FBE\Configuration\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Configuration/Request.php';
				}

				if ( ! class_exists( API\FBE\Configuration\Messenger::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Configuration/Messenger.php';
				}

				if ( ! class_exists( API\FBE\Configuration\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Configuration/Read/Response.php';
				}

				if ( ! class_exists( API\FBE\Configuration\Update\Request::class ) ) {
					require_once __DIR__ . '/includes/API/FBE/Configuration/Update/Request.php';
				}

				if ( ! class_exists( API\Pages\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Pages/Read/Request.php';
				}

				if ( ! class_exists( API\Pages\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Pages/Read/Response.php';
				}

				if ( ! class_exists( API\Exceptions\Request_Limit_Reached::class ) ) {
					require_once __DIR__ . '/includes/API/Exceptions/Request_Limit_Reached.php';
				}

				if ( ! class_exists( API\Orders\Order::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Order.php';
				}

				if ( ! class_exists( API\Orders\Abstract_Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Abstract_Request.php';
				}

				if ( ! class_exists( API\Orders\Acknowledge\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Acknowledge/Request.php';
				}

				if ( ! class_exists( API\Orders\Cancel\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Cancel/Request.php';
				}

				if ( ! class_exists( API\Orders\Fulfillment\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Fulfillment/Request.php';
				}

				if ( ! class_exists( API\Orders\Read\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Read/Request.php';
				}

				if ( ! class_exists( API\Orders\Read\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Read/Response.php';
				}

				if ( ! class_exists( API\Orders\Refund\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Refund/Request.php';
				}

				if ( ! class_exists( API\Orders\Request::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Request.php';
				}

				if ( ! class_exists( API\Orders\Response::class ) ) {
					require_once __DIR__ . '/includes/API/Orders/Response.php';
				}

				$this->api = new SkyVerge\WooCommerce\Facebook\API( $access_token );

			} else {

				$this->api->set_access_token( $access_token );
			}

			return $this->api;
		}


		/**
		 * Gets the admin handler instance.
		 *
		 * @since 1.10.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Admin|null
		 */
		public function get_admin_handler() {

			return $this->admin;
		}


		/**
		 * Gets the AJAX handler instance.
		 *
		 * @sinxe 1.10.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\AJAX|null
		 */
		public function get_ajax_handler() {

			return $this->ajax;
		}


		/**
		 * Gets the product feed handler.
		 *
		 * @since 1.11.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Feed
		 */
		public function get_product_feed_handler() {

			return $this->product_feed;
		}

		/**
		 * Gets the category handler.
		 *
		 * @since 1.11.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\FBCategories
		 */
		public function get_facebook_category_handler() {
			return $this->fb_categories;
		}

		/**
		 * Gets the background handle virtual products and variations handler instance.
		 *
		 * @since 2.0.0
		 *
		 * @return Background_Handle_Virtual_Products_Variations
		 */
		public function get_background_handle_virtual_products_variations_instance() {

			return $this->background_handle_virtual_products_variations;
		}


		/**
		 * Gets the background remove duplicate visibility meta data handler instance.
		 *
		 * @since 2.0.3
		 *
		 * @return Background_Remove_Duplicate_Visibility_Meta
		 */
		public function get_background_remove_duplicate_visibility_meta_instance() {

			return $this->background_remove_duplicate_visibility_meta;
		}


		/**
		 * Gets the products stock handler.
		 *
		 * @since 2.0.5
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Stock
		 */
		public function get_products_stock_handler() {

			return $this->products_stock_handler;
		}


		/**
		 * Gets the products sync handler.
		 *
		 * @since 2.0.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Sync
		 */
		public function get_products_sync_handler() {

			return $this->products_sync_handler;
		}


		/**
		 * Gets the products sync background handler.
		 *
		 * @since 2.0.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Products\Sync\Background
		 */
		public function get_products_sync_background_handler() {

			return $this->sync_background_handler;
		}


		/**
		 * Gets the connection handler.
		 *
		 * @since 2.0.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Handlers\Connection
		 */
		public function get_connection_handler() {

			return $this->connection_handler;
		}


		/**
		 * Gets the integration instance.
		 *
		 * @since 1.10.0
		 *
		 * @return \WC_Facebookcommerce_Integration instance
		 */
		public function get_integration() {

			if ( null === $this->integration ) {
				$this->integration = new WC_Facebookcommerce_Integration();
			}

			return $this->integration;
		}


		/**
		 * Gets the commerce handler instance.
		 *
		 * @since 2.1.0
		 *
		 * @return \SkyVerge\WooCommerce\Facebook\Commerce commerce handler instance
		 */
		public function get_commerce_handler() {

			return $this->commerce_handler;
		}

		/**
		 * Gets the settings page URL.
		 *
		 * @since 1.10.0
		 *
		 * @param null $plugin_id unused
		 * @return string
		 */
		public function get_settings_url( $plugin_id = null ) {

			return admin_url( 'admin.php?page=wc-facebook' );
		}


		/**
		 * Gets the plugin's documentation URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_documentation_url() {

			return 'https://docs.woocommerce.com/document/facebook-for-woocommerce/';
		}


		/**
		 * Gets the plugin's support URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_support_url() {

			return 'https://wordpress.org/support/plugin/facebook-for-woocommerce/';
		}


		/**
		 * Gets the plugin's sales page URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_sales_page_url() {

			return 'https://woocommerce.com/products/facebook/';
		}


		/**
		 * Gets the plugin's reviews URL.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_reviews_url() {

			return 'https://wordpress.org/support/plugin/facebook-for-woocommerce/reviews/';
		}


		/**
		 * Gets the plugin name.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		public function get_plugin_name() {

			return __( 'Facebook for WooCommerce', 'facebook-for-woocommerce' );
		}


		/** Conditional methods ***************************************************************************************/


		/**
		 * Determines if viewing the plugin settings in the admin.
		 *
		 * @since 1.10.0
		 *
		 * @return bool
		 */
		public function is_plugin_settings() {

			return is_admin() && \SkyVerge\WooCommerce\Facebook\Admin\Settings::PAGE_ID === Framework\SV_WC_Helper::get_requested_value( 'page' );
		}


		/** Utility methods *******************************************************************************************/


		/**
		 * Initializes the lifecycle handler.
		 *
		 * @since 1.10.0
		 */
		protected function init_lifecycle_handler() {

			require_once __DIR__ . '/includes/Lifecycle.php';

			$this->lifecycle_handler = new Lifecycle( $this );
		}


		/**
		 * Gets the plugin singleton instance.
		 *
		 * @see \facebook_for_woocommerce()
		 *
		 * @since 1.10.0
		 *
		 * @return \WC_Facebookcommerce the plugin singleton instance
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Gets the plugin file.
		 *
		 * @since 1.10.0
		 *
		 * @return string
		 */
		protected function get_file() {

			return __FILE__;
		}


		/**
		 * Return current page ID
		 *
		 * @since 2.2.1-dev.1
		 *
		 * @return string
		 */
		protected function get_current_page_id() {

			$current_screen_id = '';
			$current_screen    = get_current_screen();
			if ( ! empty( $current_screen ) ) {
				$current_screen_id = $current_screen->id;
			}
			return $current_screen_id;
		}


		/** Deprecated methods ****************************************************************************************/


		/**
		 * Adds the settings link on the plugin page.
		 *
		 * @internal
		 *
		 * @since 1.10.0
		 * @deprecated 1.10.0
		 */
		public function add_settings_link() {

			wc_deprecated_function( __METHOD__, '1.10.0' );
		}

	}


	/**
	 * Gets the Facebook for WooCommerce plugin instance.
	 *
	 * @since 1.10.0
	 *
	 * @return \WC_Facebookcommerce instance of the plugin
	 */
	function facebook_for_woocommerce() {

		return \WC_Facebookcommerce::instance();
	}


endif;
