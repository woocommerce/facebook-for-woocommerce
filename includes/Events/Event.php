<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Events;

defined( 'ABSPATH' ) or exit;

/**
 * Event object.
 *
 * @since 2.0.0-dev.1
 */
class Event {


	/**
	 * @var array data specific to this event instance with the same structure as the event’s payload
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/payload-helper
	 */
	protected $data = [];


	/**
	 * Constructor.
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/parameters
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $data event data
	 */
	public function __construct( $data = [] ) {

		$this->prepare_data( $data );
	}


	/**
	 * Provides defaults for properties if not already defined.
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/parameters/server-event
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/parameters/custom-data
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $data event data
	 */
	protected function prepare_data( $data ) {

		$this->data = wp_parse_args( $data, [
			'event_time'       => time(),
			'event_id'         => $this->generate_event_id(),
			'event_source_url' => $this->get_current_url(),
			'custom_data'      => [],
			'user_data'        => [],
		] );

		$this->prepare_user_data( $this->data['user_data'] );
	}


	/**
	 * Provides defaults for user properties if not already defined.
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/parameters/user-data
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $data user data
	 */
	protected function prepare_user_data( $data ) {

		$this->data['user_data'] = wp_parse_args( $data, [
			'client_ip_address' => $this->get_client_ip(),
			'client_user_agent' => $this->get_client_user_agent(),
			'click_id'          => $this->get_click_id(),
			'browser_id'        => $this->get_browser_id(),
		] );
	}


	/**
	 * Generates a UUIDv4 unique ID for the event.
	 *
	 * @see https://stackoverflow.com/a/15875555
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function generate_event_id() {

		try {
			$data = random_bytes( 16 );

			$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100
			$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10

			return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );

		} catch ( \Exception $e ) {

			// fall back to mt_rand if random_bytes is unavailable
			return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),

				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,

				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,

				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
		}
	}


	/**
	 * Gets the current URL.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function get_current_url() {

		if ( wp_doing_ajax() ) {

			$url = $_SERVER['HTTP_REFERER'];

		} else {

			/**
			 * Instead of relying on the HTTP_HOST server var, we use home_url(),
			 * so that we get the host configured in site options.
			 * Additionally, this automatically uses the correct domain when
			 * using Forward with the WooCommerce Dev Helper plugin.
			 */
			$url = home_url() . $_SERVER['REQUEST_URI'];
		}

		return $url;
	}


	/**
	 * Gets the client IP address.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function get_client_ip() {

		return \WC_Geolocation::get_ip_address();
	}


	/**
	 * Gets the client user agent.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function get_client_user_agent() {

		return ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}


	/**
	 * Gets the click ID from the cookie or the query parameter.
	 *
	 * @see https://developers.facebook.com/docs/marketing-api/server-side-api/parameters/fbp-and-fbc#fbp-and-fbc-parameters
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function get_click_id() {

		$click_id = '';

		if ( ! empty( $_COOKIE['_fbc'] ) ) {

			$click_id = $_COOKIE['_fbc'];

		} elseif ( ! empty( $_REQUEST['fbclid'] ) ) {

			// generate the click ID based on the query parameter
			$version         = 'fb';
			$subdomain_index = 1;
			$creation_time   = time();
			$fbclid          = $_REQUEST['fbclid'];

			$click_id = "{$version}.{$subdomain_index}.{$creation_time}.{$fbclid}";
		}

		return $click_id;
	}


	/**
	 * Gets the browser ID from the cookie.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	protected function get_browser_id() {

		return ! empty( $_COOKIE['_fbp'] ) ? $_COOKIE['_fbp'] : '';
	}


	/**
	 * Gets the data.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_data() {

		return $this->data;
	}


	/**
	 * Gets the event ID.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_id() {

		return ! empty( $this->data['event_id'] ) ? $this->data['event_id'] : '';
	}


	/**
	 * Gets the event name.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_name() {

		return ! empty( $this->data['event_name'] ) ? $this->data['event_name'] : '';
	}


	/**
	 * Gets the user data.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_user_data() {

		return ! empty( $this->data['user_data'] ) ? $this->data['user_data'] : [];
	}


	/**
	 * Gets the event custom data.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_custom_data() {

		return ! empty( $this->data['custom_data'] ) ? $this->data['custom_data'] : [];
	}


}
