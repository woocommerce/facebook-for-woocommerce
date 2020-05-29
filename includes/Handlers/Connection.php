<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Handlers;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception;

defined( 'ABSPATH' ) or exit;

/**
 * The connection handler.
 *
 * @since 2.0.0-dev.1
 */
class Connection {


	/** @var string Facebook client identifier */
	const CLIENT_ID = '1234';

	/** @var string Facebook OAuth URL */
	const OAUTH_URL = 'https://facebook.com/dialog/oauth';

	/** @var string WooCommerce connection proxy URL */
	const PROXY_URL = 'https://connect.woocommerce.com/auth/facebook/';

	/** @var string the action callback for the connection */
	const ACTION_CONNECT = 'wc_facebook_connect';

	/** @var string the action callback for the disconnection */
	const ACTION_DISCONNECT = 'wc_facebook_disconnect';

	/** @var string the WordPress option name where the external business ID is stored */
	const OPTION_EXTERNAL_BUSINESS_ID = 'wc_facebook_external_business_id';

	/** @var string the business manager ID option name */
	const OPTION_BUSINESS_MANAGER_ID = 'wc_facebook_business_manager_id';

	/** @var string the access token option name */
	const OPTION_ACCESS_TOKEN = 'wc_facebook_access_token';


	/** @var string|null the generated external merchant settings ID */
	private $external_business_id;

	/** @var \WC_Facebookcommerce */
	private $plugin;


	/**
	 * Constructs a new Connection.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct( \WC_Facebookcommerce $plugin ) {

		$this->plugin = $plugin;

		add_action( 'admin_init', [ $this, 'refresh_installation_data' ] );

		add_action( 'woocommerce_api_' . self::ACTION_CONNECT, [ $this, 'handle_connect' ] );

		add_action( 'admin_action_' . self::ACTION_DISCONNECT, [ $this, 'handle_disconnect' ] );
	}


	/**
	 * Refreshes the connected installation data.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function refresh_installation_data() {

		// bail if not connected
		if ( ! $this->is_connected() ) {
			return;
		}

		// only refresh once a day
		if ( get_transient( 'wc_facebook_connection_refresh' ) ) {
			return;
		}

		$integration = $this->get_plugin()->get_integration();

		try {

			$response = $this->get_plugin()->get_api()->get_installation_ids( $this->get_external_business_id() );

			if ( $response->get_page_id() ) {
				update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, sanitize_text_field( $response->get_page_id() ) );
			}

			if ( $response->get_pixel_id() ) {
				update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, sanitize_text_field( $response->get_pixel_id() ) );
			}

			if ( $response->get_catalog_id() ) {
				update_option( \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, sanitize_text_field( $response->get_catalog_id() ) );
			}

			if ( $response->get_business_manager_id() ) {
				$this->update_business_manager_id( sanitize_text_field( $response->get_business_manager_id() ) );
			}

		} catch ( SV_WC_API_Exception $exception ) {

			if ( $integration->is_debug_mode_enabled() ) {
				$this->get_plugin()->log( 'Could not refresh installation data. ' . $exception->getMessage() );
			}
		}

		set_transient( 'wc_facebook_connection_refresh', time(), DAY_IN_SECONDS );
	}


	/**
	 * Processes the returned connection.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function handle_connect() {

		// don't handle anything unless the user can manage WooCommerce settings
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		try {

			if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], self::ACTION_CONNECT ) ) {
				throw new SV_WC_API_Exception( 'Invalid nonce' );
			}

			$access_token = ! empty( $_GET['access_token'] ) ? sanitize_text_field( $_GET['access_token'] ) : '';

			if ( ! $access_token ) {
				throw new SV_WC_API_Exception( 'Access token is missing' );
			}

			$access_token = $this->create_system_user_token( $access_token );

			$this->update_access_token( $access_token );

			$api = new \WC_Facebookcommerce_Graph_API( $access_token );

			$asset_ids = $api->get_asset_ids( $this->get_external_business_id() );

			if ( ! empty( $asset_ids['page_id'] ) ) {
				update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, sanitize_text_field( $asset_ids['page_id'] ) );
			}

			if ( ! empty( $asset_ids['pixel_id'] ) ) {
				update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, sanitize_text_field( $asset_ids['pixel_id'] ) );
			}

			if ( ! empty( $asset_ids['catalog_id'] ) ) {
				update_option( \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, sanitize_text_field( $asset_ids['catalog_id'] ) );
			}

			if ( ! empty( $asset_ids['business_manager_id'] ) ) {
				$this->update_business_manager_id( sanitize_text_field( $asset_ids['business_manager_id'] ) );
			}

			facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_all_products();

			update_option( 'wc_facebook_has_connected_fbe_2', 'yes' );

			facebook_for_woocommerce()->get_message_handler()->add_message( __( 'Connection complete! Thanks for using Facebook for WooCommerce.', 'facebook-for-woocommerce' ) );

		} catch ( SV_WC_API_Exception $exception ) {

			facebook_for_woocommerce()->log( sprintf( 'Connection failed: %s', $exception->getMessage() ) );

			set_transient( 'wc_facebook_connection_failed', time(), 30 );
		}

		wp_safe_redirect( facebook_for_woocommerce()->get_settings_url() );
		exit;
	}


	/**
	 * Disconnects the integration using the Graph API.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function handle_disconnect() {

		check_admin_referer( self::ACTION_DISCONNECT );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have permission to uninstall Facebook Business Extension.', 'facebook-for-woocommerce' ) );
		}

		try {

			$response = facebook_for_woocommerce()->get_api()->get_user();
			$response = facebook_for_woocommerce()->get_api()->delete_user_permission( $response->get_id(), 'manage_business_extension' );

			$this->disconnect();

			facebook_for_woocommerce()->get_message_handler()->add_message( __( 'Uninstall successful', 'facebook-for-woocommerce' ) );

		} catch ( SV_WC_API_Exception $exception ) {

			facebook_for_woocommerce()->log( sprintf( 'Uninstall failed: %s', $exception->getMessage() ) );

			facebook_for_woocommerce()->get_message_handler()->add_error( __( 'Uninstall unsuccessful. Please try again.', 'facebook-for-woocommerce' ) );
		}

		wp_safe_redirect( facebook_for_woocommerce()->get_settings_url() );
		exit;
	}


	/**
	 * Disconnects the plugin.
	 *
	 * Deletes local asset data.
	 *
	 * @since 2.0.0-dev.1
	 */
	private function disconnect() {

		$this->update_access_token( '' );
		$this->update_business_manager_id( '' );

		update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, '' );
		update_option( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, '' );
		facebook_for_woocommerce()->get_integration()->update_product_catalog_id( '' );
	}


	/**
	 * Converts a temporary user token to a system user token via the Graph API.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $user_token
	 * @return string
	 */
	public function create_system_user_token( $user_token ) {

		return $user_token;
	}


	/**
	 * Gets the API access token.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_access_token() {

		$access_token = get_option( self::OPTION_ACCESS_TOKEN, '' );

		/**
		 * Filters the API access token.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $access_token access token
		 * @param Connection $connection connection handler instance
		 */
		return apply_filters( 'wc_facebook_connection_access_token', $access_token, $this );
	}


	/**
	 * Gets the URL to start the connection flow.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_connect_url() {

		return add_query_arg( rawurlencode_deep( $this->get_connect_parameters() ), self::OAUTH_URL );
	}


	/**
	 * Gets the URL to manage the connection.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_manage_url() {

		$app_id      = $this->get_client_id();
		$business_id = $this->get_external_business_id();

		return "https://www.facebook.com/facebook_business_extension?app_id={$app_id}&external_business_id={$business_id}";
	}


	/**
	 * Gets the URL for disconnecting.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_disconnect_url() {

		return wp_nonce_url( add_query_arg( 'action', self::ACTION_DISCONNECT, admin_url( 'admin.php' ) ), self::ACTION_DISCONNECT );
	}


	/**
	 * Gets the scopes that will be requested during the connection flow.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string[]
	 */
	public function get_scopes() {

		$scopes = [
			'manage_business_extension',
			'catalog_management',
			'business_management',
		];

		/**
		 * Filters the scopes that will be requested during the connection flow.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string[] $scopes connection scopes
		 * @param Connection $connection connection handler instance
		 */
		return (array) apply_filters( 'wc_facebook_connection_scopes', $scopes, $this );
	}


	/**
	 * Gets the stored external business ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_external_business_id() {

		if ( ! is_string( $this->external_business_id ) ) {

			$value = get_option( self::OPTION_EXTERNAL_BUSINESS_ID );

			if ( ! is_string( $value ) ) {

				$value = sanitize_title( get_bloginfo( 'name' ) ) . '-' . uniqid();

				update_option( self::OPTION_EXTERNAL_BUSINESS_ID, $value );
			}

			$this->external_business_id = $value;
		}

		/**
		 * Filters the external business ID.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $external_business_id stored external business ID
		 * @param Connection $connection connection handler instance
		 */
		return (string) apply_filters( 'wc_facebook_external_business_id', $this->external_business_id, $this );
	}


	/**
	 * Gets the site's business name.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_business_name() {

		$business_name = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );

		/**
		 * Filters the shop's business name.
		 *
		 * This is passed to Facebook when connecting. Defaults to the site name.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $business_name the shop's business name
		 */
		return apply_filters( 'wc_facebook_connection_business_name', $business_name );
	}


	/**
	 * Gets the business manager ID value.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_business_manager_id() {

		return get_option( self::OPTION_BUSINESS_MANAGER_ID, '' );
	}


	/**
	 * Gets the proxy URL.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string URL
	 */
	public function get_proxy_url() {

		/**
		 * Filters the proxy URL.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $proxy_url the connection proxy URL
		 */
		return (string) apply_filters( 'wc_facebook_connection_proxy_url', self::PROXY_URL );
	}


	/**
	 * Gets the full redirect URL where the user will return to after OAuth.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_redirect_url() {

		$redirect_url = add_query_arg( [
			'wc-api' => self::ACTION_CONNECT,
			'nonce'  => wp_create_nonce( self::ACTION_CONNECT ),
		], home_url( '/' ) );

		/**
		 * Filters the redirect URL where the user will return to after OAuth.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $redirect_url redirect URL
		 * @param Connection $connection connection handler instance
		 */
		return (string) apply_filters( 'wc_facebook_connection_redirect_url', $redirect_url, $this );
	}


	/**
	 * Gets the full set of connection parameters for starting OAuth.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_connect_parameters() {

		/**
		 * Filters the connection parameters.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param array $parameters connection parameters
		 */
		return apply_filters( 'wc_facebook_connection_parameters', [
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_proxy_url(),
			'state'         => $this->get_redirect_url(),
			'display'       => 'page',
			'response_type' => 'code',
			'scope'         => implode( ',', $this->get_scopes() ),
			'extras'        => json_encode( $this->get_connect_parameters_extras() ),
		] );
	}


	/**
	 * Gets connection parameters extras.
	 *
	 * @see Connection::get_connect_parameters()
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array associative array (to be converted to JSON encoded for connection purposes)
	 */
	private function get_connect_parameters_extras() {

		$parameters = [
			'setup' => [
				'external_business_id' => $this->get_external_business_id(),
				'timezone'             => wc_timezone_string(),
				'currency'             => get_woocommerce_currency(),
				'business_vertical'    => 'ECOMMERCE',
			],
			'business_config' => [
				'business' => [
					'name' => $this->get_business_name(),
				],
				'page_shop' => [
					'enabled'               => true,
					'visible_product_count' => facebook_for_woocommerce()->get_integration()->get_product_count(),
				],
				'messenger_chat' => [
					'enabled' => true,
					'domains' => home_url(),
				],
				'ig_shopping' => [
					'enabled' => true,
				],
			],
			'repeat' => false,
		];

		if ( $external_merchant_settings_id = facebook_for_woocommerce()->get_integration()->get_external_merchant_settings_id() ) {
			$parameters['setup']['merchant_settings_id'] = $external_merchant_settings_id;
		}

		return $parameters;
	}


	/**
	 * Stores the given ID value.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $value the business manager ID
	 */
	public function update_business_manager_id( $value ) {

		update_option( self::OPTION_BUSINESS_MANAGER_ID, $value );
	}


	/**
	 * Stores the given token value.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $value the access token
	 */
	public function update_access_token( $value ) {

		update_option( self::OPTION_ACCESS_TOKEN, $value );
	}


	/**
	 * Determines whether the site is connected.
	 *
	 * A site is connected if there is an access token stored.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return bool
	 */
	public function is_connected() {

		return (bool) $this->get_access_token();
	}


	/**
	 * Gets the client ID for connection.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	private function get_client_id() {

		/**
		 * Filters the client ID.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param string $client_id the client ID
		 */
		return apply_filters( 'wc_facebook_connection_client_id', self::CLIENT_ID );
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return \WC_Facebookcommerce
	 */
	public function get_plugin() {

		return $this->plugin;
	}


}
