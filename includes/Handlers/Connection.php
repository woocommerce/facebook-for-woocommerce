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

defined( 'ABSPATH' ) or exit;

/**
 * The connection handler.
 *
 * @since 2.0.0-dev.1
 */
class Connection {

  
	/** @var string the WordPress option name where the external business ID is stored */
	const OPTION_EXTERNAL_BUSINESS_ID = 'wc_facebook_external_business_id';

	/** @var string the business manager ID option name */
	const OPTION_BUSINESS_MANAGER_ID = 'wc_facebook_business_manager_id';


	/** @var string|null the generated external merchant settings ID */
	private $external_business_id;


	/** @var string the access token option name */
	const OPTION_ACCESS_TOKEN = 'wc_facebook_access_token';


	/**
	 * Constructs a new Connection.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

	}


	/**
	 * Processes the returned connection.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function handle_connect() {

	}


	/**
	 * Disconnects the integration using the Graph API.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function handle_disconnect() {

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

		return get_option( self::OPTION_ACCESS_TOKEN, '' );
	}


	/**
	 * Gets the URL to start the connection flow.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_connect_url() {

		return '';
	}


	/**
	 * Gets the URL for disconnecting.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_disconnect_url() {

		return '';
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
	 * Gets the full redirect URL where the user will return to after OAuth.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_redirect_url() {

		return '';
	}


	/**
	 * Gets the full set of connection parameters for starting OAuth.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_connect_parameters() {

		return [];
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

		return true;
	}


}
