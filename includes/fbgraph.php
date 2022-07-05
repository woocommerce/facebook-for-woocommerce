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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! class_exists( 'WC_Facebookcommerce_Graph_API' ) ) :

	if ( ! class_exists( 'WC_Facebookcommerce_Async_Request' ) ) {
		include_once 'fbasync.php';
	}

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
		 * @throws Framework\SV_WC_API_Exception
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

				throw new Framework\SV_WC_API_Exception( $response->get_error_message(), $response->get_error_code() );

			} elseif ( 401 === (int) wp_remote_retrieve_response_code( $response ) ) {

				$response_body = json_decode( wp_remote_retrieve_body( $response ) );

				if ( isset( $response_body->error->code, $response_body->error->message ) ) {
					throw new Framework\SV_WC_API_Exception( $response_body->error->message, $response_body->error->code );
				} else {
					throw new Framework\SV_WC_API_Exception( sprintf( __( 'HTTP %1$s: %2$s', 'facebook-for-woocommerce' ), wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) ) );
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


		// GET https://graph.facebook.com/vX.X/{page-id}/?fields=name
		public function get_page_name( $page_id, $api_key = '' ) {
			$api_key  = $api_key ?: $this->api_key;
			$url      = $this->build_url( $page_id, '/?fields=name' );
			$response = self::_get( $url, $api_key );

			if ( is_wp_error( $response ) ) {
				WC_Facebookcommerce_Utils::log( $response->get_error_message() );
				return '';
			}

			if ( $response['response']['code'] != '200' ) {
				return '';
			}

			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			return isset( $response_body->name ) ? $response_body->name : '';
		}


		/**
		 * Gets a Facebook Page URL.
		 *
		 * Endpoint: https://graph.facebook.com/vX.X/{page-id}/?fields=link
		 *
		 * @param string|int $page_id page identifier
		 * @param string     $api_key API key
		 * @return string URL
		 */
		public function get_page_url( $page_id, $api_key = '' ) {

			$api_key  = $api_key ?: $this->api_key;
			$request  = $this->build_url( $page_id, '/?fields=link' );
			$response = $this->_get( $request, $api_key );
			$page_url = '';

			if ( is_wp_error( $response ) ) {

				\WC_Facebookcommerce_Utils::log( $response->get_error_message() );

			} elseif ( 200 === (int) $response['response']['code'] ) {

				$response_body = wp_remote_retrieve_body( $response );
				$page_url      = json_decode( $response_body )->link;
			}

			return $page_url;
		}


		/**
		 * Determines whether the product catalog ID is valid.
		 *
		 * Returns true if the product catalog ID can be successfully retrieved using the Graph API.
		 *
		 * TODO: deprecate this methid in 1.11.0 or newer {WV 2020-03-12}
		 *
		 * @param int $product_catalog_id the ID of the product catalog
		 * @return bool
		 */
		public function validate_product_catalog( $product_catalog_id ) {

			try {
				$is_valid = $this->is_product_catalog_valid( $product_catalog_id );
			} catch ( Framework\SV_WC_API_Exception $e ) {
				$is_valid = false;
			}

			return $is_valid;
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
		 * @throws Framework\SV_WC_API_Exception
		 */
		public function is_product_catalog_valid( $product_catalog_id ) {

			$response = $this->perform_request( $this->build_url( $product_catalog_id ) );

			return 200 === (int) wp_remote_retrieve_response_code( $response );
		}


		// POST https://graph.facebook.com/vX.X/{product-catalog-id}/product_groups
		public function create_product_group( $product_catalog_id, $data ) {
			$url = $this->build_url( $product_catalog_id, '/product_groups' );
			return self::_post( $url, $data );
		}

		// POST https://graph.facebook.com/vX.X/{product-group-id}/products
		public function create_product_item( $product_group_id, $data ) {
			$url = $this->build_url( $product_group_id, '/products' );
			return self::_post( $url, $data );
		}

		public function update_product_group( $product_catalog_id, $data ) {
			$url = $this->build_url( $product_catalog_id );
			return self::_post( $url, $data );
		}

		public function update_product_item( $product_id, $data ) {
			$url = $this->build_url( $product_id );
			return self::_post( $url, $data );
		}

		public function delete_product_item( $product_item_id ) {
			$product_item_url = $this->build_url( $product_item_id );
			return self::_delete( $product_item_url );
		}

		public function delete_product_group( $product_group_id ) {
			$product_group_url = $this->build_url( $product_group_id, '?deletion_method=delete_items' );
			return self::_delete( $product_group_url );
		}

		// POST https://graph.facebook.com/vX.X/{product-catalog-id}/product_sets
		public function create_product_set_item( $product_catalog_id, $data ) {
			$url = $this->build_url( $product_catalog_id, '/product_sets' );
			return self::_post( $url, $data );
		}

		// POST https://graph.facebook.com/vX.X/{product-set-id}
		public function update_product_set_item( $product_set_id, $data ) {
			$url = $this->build_url( $product_set_id, '' );
			return self::_post( $url, $data );
		}

		public function delete_product_set_item( $product_set_id ) {

			$params = ( true === apply_filters( 'wc_facebook_commerce_allow_live_product_set_deletion', true, $product_set_id ) ) ? '?allow_live_product_set_deletion=true' : '';

			$url = $this->build_url( $product_set_id, $params );

			return self::_delete( $url );
		}


		public function log( $ems_id, $message, $error ) {
			$log_url = $this->build_url( $ems_id, '/log_events' );

			$data = array(
				'message' => $message,
				'error'   => $error,
			);

			self::_post( $log_url, $data );
		}

		public function log_tip_event( $tip_id, $channel_id, $event ) {
			$tip_event_log_url = $this->build_url( '', '/log_tip_events' );

			$data = array(
				'tip_id'     => $tip_id,
				'channel_id' => $channel_id,
				'event'      => $event,
			);

			self::_post( $tip_event_log_url, $data );
		}

		public function create_upload( $facebook_feed_id, $path_to_feed_file ) {
			$url = $this->build_url(
				$facebook_feed_id,
				'/uploads?access_token=' . $this->api_key
			);

			$data = array(
				'file'        => new CurlFile( $path_to_feed_file, 'text/csv' ),
				'update_only' => true,
			);

			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => $url,
					CURLOPT_POST           => 1,
					CURLOPT_POSTFIELDS     => $data,
					CURLOPT_RETURNTRANSFER => 1,
				)
			);
			$response = curl_exec( $curl );
			if ( curl_errno( $curl ) ) {
				WC_Facebookcommerce_Utils::fblog( $response );
				return null;
			}
			return WC_Facebookcommerce_Utils::decode_json( $response, true );
		}

		public function create_feed( $facebook_catalog_id, $data ) {
			$url = $this->build_url( $facebook_catalog_id, '/product_feeds' );
			$url = $this->get_feed_endpoint_url( $facebook_catalog_id );
			// success API call will return {id: <product feed id>}
			// failure API will return {error: <error message>}
			return self::_post( $url, $data );
		}

		/**
		 * Get all feed configurations for a given catalog id.
		 *
		 * @see https://developers.facebook.com/docs/marketing-api/reference/product-feed/
		 * @since 2.6.0
		 *
		 * @param String $facebook_catalog_id Facebook Catalog Id.
		 * @return Array Facebook feeds configurations.
		 */
		public function read_feeds( $facebook_catalog_id ) {
			$url = $this->get_feed_endpoint_url( $facebook_catalog_id );
			return $this->_get( $url );
		}

		/**
		 * Get general info about a feed (data source) configured in Facebook Business.
		 *
		 * @see https://developers.facebook.com/docs/marketing-api/reference/product-feed/
		 * @since 2.6.0
		 *
		 * @param String $feed_id Feed Id.
		 * @return Array Facebook feeds configurations.
		 */
		public function read_feed_information( $feed_id ) {
			$url = $this->build_url( $feed_id, '/?fields=id,name,schedule,update_schedule,uploads' );
			return $this->_get( $url );
		}

		/**
		 * Get metadata about a feed (data source) configured in Facebook Business.
		 *
		 * @see https://developers.facebook.com/docs/marketing-api/reference/product-feed/
		 * @since 2.6.0
		 *
		 * @param String $feed_id Facebook Catalog Id.
		 * @return Array Facebook feed metadata.
		 */
		public function read_feed_metadata( $feed_id ) {
			$url = $this->build_url( $feed_id, '/?fields=created_time,latest_upload,product_count,schedule,update_schedule' );
			return $this->_get( $url );
		}

		/**
		 * Get metadata about a recent feed upload.
		 *
		 * @see https://developers.facebook.com/docs/marketing-api/reference/product-feed-upload/
		 * @since 2.6.0
		 *
		 * @param String $upload_id Feed Upload Id.
		 * @return Array Feed upload metadata.
		 */
		public function read_upload_metadata( $upload_id ) {
			$url = $this->build_url( $upload_id, '/?fields=error_count,warning_count,num_detected_items,num_persisted_items,url' );
			return $this->_get( $url );
		}

		/**
		 * Create product_feeds graph edge url.
		 *
		 * @since 2.6.0
		 *
		 * @param String $facebook_catalog_id Facebook Catalog Id.
		 * @return String Graph edge url.
		 */
		public function get_feed_endpoint_url( $facebook_catalog_id ) {
			return $this->build_url( $facebook_catalog_id, '/product_feeds' );
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

		public function get_facebook_id( $facebook_catalog_id, $product_id ) {
			$param = 'catalog:' . (string) $facebook_catalog_id . ':' .
			base64_encode( $product_id ) . '/?fields=id,product_group{id}';
			$url   = $this->build_url( '', $param );
			// success API call will return
			// {id: <fb product id>, product_group{id} <fb product group id>}
			// failure API will return {error: <error message>}
			return self::_get( $url );
		}

		public function check_product_info( $facebook_catalog_id, $product_id, $pr_v ) {
			$param = 'catalog:' . (string) $facebook_catalog_id . ':' .
			base64_encode( $product_id ) . '/?fields=id,name,description,price,' .
			'sale_price,sale_price_start_date,sale_price_end_date,image_url,' .
			'visibility';
			if ( $pr_v ) {
				$param = $param . ',additional_variant_attributes{value}';
			}
			$url = $this->build_url( '', $param );
			// success API call will return
			// {id: <fb product id>, name,description,price,sale_price,sale_price_start_date
			// sale_price_end_date
			// failure API will return {error: <error message>}
			return self::_get( $url );
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
		 * @throws Framework\SV_WC_API_Exception
		 */
		public function get_asset_ids( $external_business_id ) {

			$url = $this->build_url( 'fbe_business/fbe_installs?fbe_external_business_id=', $external_business_id );

			$response = $this->perform_request( $url );

			$data = wp_remote_retrieve_body( $response );
			$data = json_decode( $data, true );

			if ( ! is_array( $data ) || empty( $data['data'][0] ) ) {
				throw new Framework\SV_WC_API_Exception( 'Data is missing' );
			}

			$ids = $data['data'][0];

			// normalize the page ID to match the others
			if ( ! empty( $ids['profiles'] ) && is_array( $ids['profiles'] ) ) {
				$ids['page_id'] = current( $ids['profiles'] );
			}

			return $ids;
		}


		public function set_default_variant( $product_group_id, $data ) {
			$url = $this->build_url( $product_group_id );
			return self::_post( $url, $data );
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

endif;
