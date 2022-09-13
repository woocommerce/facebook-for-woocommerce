<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

require_once 'fbasync.php';

/**
 * FB Graph API helper functions
 */
class WC_Facebookcommerce_Graph_API {
	const GRAPH_API_URL = 'https://graph.facebook.com/';
	const API_VERSION   = 'v13.0';
	const CURL_TIMEOUT  = 500;

	/**
	 * Cache the api_key
	 */
	var $api_key;

	/**
	 * Init
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Issues a GET request to the Graph API.
	 *
	 * @param string $url request URL
	 * @param string $api_key Graph API key
	 * @return array|\WP_Error
	 */
	public function _get( $url, $api_key = '' ) {

		$api_key = $api_key ?: $this->api_key;

		$request_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => self::CURL_TIMEOUT,
		);

		$response = wp_remote_get( $url, $request_args );

		$this->log_request( $url, $request_args, $response );

		return $response;
	}

	/**
	 * Performs a Graph API request to the given URL.
	 *
	 * Throws an exception if a WP_Error is returned or we receive a 401 Not Authorized response status.
	 *
	 * @since 1.10.2
	 *
	 * @param string $url
	 * @throws ApiException
	 * @return array
	 */
	public function perform_request( $url ) {

		$request_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
			),
			'timeout' => self::CURL_TIMEOUT,
		);

		$response = wp_remote_get( $url, $request_args );

		$this->log_request( $url, $request_args, $response );

		if ( is_wp_error( $response ) ) {

			throw new ApiException( $response->get_error_message(), $response->get_error_code() );

		} elseif ( 401 === (int) wp_remote_retrieve_response_code( $response ) ) {

			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $response_body->error->code, $response_body->error->message ) ) {
				throw new ApiException( $response_body->error->message, $response_body->error->code );
			} else {
				throw new ApiException( sprintf( __( 'HTTP %1$s: %2$s', 'facebook-for-woocommerce' ), wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) ) );
			}
		}

		return $response;
	}

	public function _post( $url, $data, $api_key = '' ) {
		if ( class_exists( 'WC_Facebookcommerce_Async_Request' ) ) {
			return self::_post_async( $url, $data );
		} else {
			return self::_post_sync( $url, $data );
		}
	}

	public function _post_sync( $url, $data, $api_key = '' ) {
		$api_key = $api_key ?: $this->api_key;

		$request_args = array(
			'body'    => $data,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => self::CURL_TIMEOUT,
		);

		$response = wp_remote_post( $url, $request_args );

		$this->log_request( $url, $request_args, $response, 'POST' );

		return $response;
	}

	/**
	 * Issues an asynchronous POST request to the Graph API.
	 *
	 * @param string $url request URL
	 * @param array  $data request data
	 * @param string $api_key Graph API key
	 * @return array|\WP_Error
	 */
	public function _post_async( $url, $data, $api_key = '' ) {

		if ( ! class_exists( 'WC_Facebookcommerce_Async_Request' ) ) {
			return;
		}

		$api_key = $api_key ?: $this->api_key;

		$request_args = array(
			'body'    => $data,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => self::CURL_TIMEOUT,
		);

		$fbasync             = new WC_Facebookcommerce_Async_Request();
		$fbasync->query_url  = $url;
		$fbasync->query_args = array();
		$fbasync->post_args  = $request_args;

		$response = $fbasync->dispatch();

		$this->log_request( $url, $request_args, $response, 'POST' );

		return $response;
	}

	/**
	 * Issues a DELETE request to the Graph API.
	 *
	 * @param string $url request URL
	 * @param string $api_key Graph API key
	 * @return array|\WP_Error
	 */
	public function _delete( $url, $api_key = '' ) {

		$api_key = $api_key ?: $this->api_key;

		$request_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => self::CURL_TIMEOUT,
			'method'  => 'DELETE',
		);

		$response = wp_remote_request( $url, $request_args );

		$this->log_request( $url, $request_args, $response, 'DELETE' );

		return $response;
	}

	/**
	 * Logs the request and response data.
	 *
	 * @since 1.10.2
	 *
	 * @param $url
	 * @param $request_args
	 * @param array|\WP_Error $response WordPress response object
	 * @param string          $method
	 */
	private function log_request( $url, $request_args, $response, $method = '' ) {

		// bail if this class is loaded incorrectly or logging is disabled
		if ( ! function_exists( 'facebook_for_woocommerce' ) || ! facebook_for_woocommerce()->get_integration()->is_debug_mode_enabled() ) {
			return;
		}

		// add the URI to the data
		$request_data = array_merge(
			array(
				'uri' => $url,
			),
			$request_args
		);

		// the request args may not include the method, so allow it to be set
		if ( $method ) {
			$request_data['method'] = $method;
		}

		// mask the page access token
		if ( ! empty( $request_data['headers']['Authorization'] ) ) {

			$auth_value = $request_data['headers']['Authorization'];

			$request_data['headers']['Authorization'] = str_replace( $auth_value, str_repeat( '*', strlen( $auth_value ) ), $auth_value );
		}

		// if there was a problem
		if ( is_wp_error( $response ) ) {

			$code    = $response->get_error_code();
			$message = $response->get_error_message();
			$headers = array();
			$body    = '';

		} else {

			$headers = wp_remote_retrieve_headers( $response );

			if ( is_object( $headers ) ) {
				$headers = $headers->getAll();
			} elseif ( ! is_array( $headers ) ) {
				$headers = array();
			}

			$code    = wp_remote_retrieve_response_code( $response );
			$message = wp_remote_retrieve_response_message( $response );
			$body    = wp_remote_retrieve_body( $response );
		}

		$response_data = array(
			'code'    => $code,
			'message' => $message,
			'headers' => $headers,
			'body'    => $body,
		);

		facebook_for_woocommerce()->log_api_request( $request_data, $response_data );
	}

	/**
	 * Determines whether the product catalog ID is valid.
	 *
	 * Returns true if the product catalog ID can be successfully retrieved using the Graph API.
	 *
	 * @since 1.10.2
	 *
	 * @param int $product_catalog_id the ID of the product catalog
	 * @return boolean
	 * @throws ApiException
	 */
	public function is_product_catalog_valid( $product_catalog_id ) {

		$response = $this->perform_request( $this->build_url( $product_catalog_id ) );

		return 200 === (int) wp_remote_retrieve_response_code( $response );
	}

	public function log( $ems_id, $message, $error ) {
		$log_url = $this->build_url( $ems_id, '/log_events' );

		$data = array(
			'message' => $message,
			'error'   => $error,
		);

		self::_post( $log_url, $data );
	}

	public function get_upload_status( $facebook_upload_id ) {
		$url = $this->build_url( $facebook_upload_id, '/?fields=end_time' );
		// success API call will return
		// {id: <upload id>, end_time: <time when upload completes>}
		// failure API will return {error: <error message>}
		return self::_get( $url );
	}

	// success API call will return a JSON of tip info
	public function get_tip_info( $external_merchant_settings_id ) {
		$url      = $this->build_url( $external_merchant_settings_id, '/?fields=connect_woo' );
		$response = self::_get( $url, $this->api_key );
		$data     = array(
			'response' => $response,
		);
		if ( is_wp_error( $response ) ) {
			$data['error_type'] = 'is_wp_error';
			WC_Facebookcommerce_Utils::fblog(
				'Failed to get AYMT tip info via API.',
				$data,
				true
			);
			return;
		}
		if ( $response['response']['code'] != '200' ) {
			$data['error_type'] = 'Non-200 error code from FB';
			WC_Facebookcommerce_Utils::fblog(
				'Failed to get AYMT tip info via API.',
				$data,
				true
			);
			return;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$connect_woo   =
		WC_Facebookcommerce_Utils::decode_json( $response_body )->connect_woo;
		if ( ! isset( $connect_woo ) ) {
			$data['error_type'] = 'Response body not set';
			WC_Facebookcommerce_Utils::fblog(
				'Failed to get AYMT tip info via API.',
				$data,
				true
			);
		}
		return $connect_woo;
	}

	/**
	 * Gets the connected asset IDs.
	 *
	 * These will be things like pixel & page ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $external_business_id the connected external business ID
	 * @return array
	 * @throws ApiException
	 */
	public function get_asset_ids( $external_business_id ) {

		$url = $this->build_url( 'fbe_business/fbe_installs?fbe_external_business_id=', $external_business_id );

		$response = $this->perform_request( $url );

		$data = wp_remote_retrieve_body( $response );
		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || empty( $data['data'][0] ) ) {
			throw new ApiException( 'Data is missing' );
		}

		$ids = $data['data'][0];

		// normalize the page ID to match the others
		if ( ! empty( $ids['profiles'] ) && is_array( $ids['profiles'] ) ) {
			$ids['page_id'] = current( $ids['profiles'] );
		}

		return $ids;
	}

	private function build_url( $field_id, $param = '', $api_version = '' ) {
		$api_url = self::GRAPH_API_URL;
		if ( ! empty( $api_version ) ) {
			$api_url = $api_url . $api_version . '/';
		} else {
			$api_url = $api_url . self::API_VERSION . '/';
		}
		return $api_url . (string) $field_id . $param;
	}
}
