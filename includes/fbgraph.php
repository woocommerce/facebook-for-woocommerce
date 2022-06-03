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

use SkyVerge\WooCommerce\Facebook\Events\Event;
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
		const API_VERSION   = 'v12.0';
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
		 * @TODO: could be made private since it not used outside of the class.
		 *
		 * @param string $url request URL
		 * @param string $api_key Graph API key
		 * @return array|WP_Error
		 */
		public function _get( $url, $api_key = '' ) {
			$api_key      = $api_key ?: $this->api_key;
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
		 * @TODO: can be replaced with _get, _post methods, is not used outside the class.
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

		/**
		 * @TODO: can be made private, is not used outside the class.
		 *
		 * @param $url
		 * @param $data
		 * @param $api_key
		 * @return array|WP_Error
		 */
		public function _post( $url, $data, $api_key = '' ) {
			if ( class_exists( 'WC_Facebookcommerce_Async_Request' ) ) {
				return self::_post_async( $url, $data );
			} else {
				return self::_post_sync( $url, $data );
			}
		}

		/**
		 * @TODO: can be removed, not used at all.
		 *
		 * @param $url
		 * @param $data
		 * @param $api_key
		 * @return array|WP_Error
		 */
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
		 * @TODO: can be removed, not used at all.
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
		 * @TODO: can be made private, not used outside the class.
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
		 * @TODO: remove it, method is not used anywhere at all.
		 *
		 * Returns true if the product catalog ID can be successfully retrieved using the Graph API.
		 *
		 * TODO: deprecate this method in 1.11.0 or newer {WV 2020-03-12}
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

		/**
		 * Gets a Catalog name from Facebook.
		 *
		 * @param $catalog_id
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function get_catalog( $catalog_id ): array {
			$url      = $this->build_url( $catalog_id, '?fields=name' );
			$response = $this->_get( $url );
			return self::process_response_body( $response );
		}

		/**
		 * Gets Facebook user information.
		 *
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function get_user(): array {
			$url      = $this->build_url( 'me' );
			$response = $this->_get( $url );
			return self::process_response_body( $response );
		}

		/**
		 * Used to revoke Facebook user permission.
		 *
		 * @param string $user_id Facebook bigint user id
		 * @param string $permission Facebook permissions
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function revoke_user_permission( string $user_id, string $permission ): array {
			$url      = $this->build_url( "{$user_id}/permissions/{$permission}" );
			$response = $this->_delete( $url );
			return self::process_response_body( $response );
		}

		/**
		 * Uses the Catalog Batch API to update or remove items from catalog.
		 *
		 * @param string $catalog_id Facebook, possibly bigint, catalog id
		 * @param array  $requests array of requests to be made to Facebook Catalog batch api
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function send_item_updates( $catalog_id, array $requests ): array {
			$url    = $this->build_url( "{$catalog_id}/items_batch" );
			$data   = array(
				'allow_upsert' => true,
				'requests'     => json_encode( $requests ),
				'item_type'    => 'PRODUCT_ITEM',
			);
			return self::process_response_body( $this->_post( $url, $data ) );
		}

		/**
		 * Sends pixel events to Facebook.
		 *
		 * @param string $pixel_id
		 * @param array $events
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function send_pixel_events( string $pixel_id, array $events ): array {

			$url  = $this->build_url( "{$pixel_id}/events" );
			$data = array(
				'data'          => array(),
				'partner_agent' => Event::get_platform_identifier(),
			);
			foreach ( $events as $event ) {
				if ( ! $event instanceof Event ) {
					continue;
				}
				$event_data = $event->get_data();
				if ( isset( $event_data['user_data']['click_id'] ) ) {
					$event_data['user_data']['fbc'] = $event_data['user_data']['click_id'];
					unset( $event_data['user_data']['click_id'] );
				}
				if ( isset( $event_data['user_data']['browser_id'] ) ) {
					$event_data['user_data']['fbp'] = $event_data['user_data']['browser_id'];
					unset( $event_data['user_data']['browser_id'] );
				}
				$data['data'][] = array_filter( $event_data );
			}

			/**
			 * Filters the Pixel event API request data.
			 *
			 * @since 2.0.0
			 *
			 * @param array $data request data
			 * @param Request $request request object
			 */
			$data = apply_filters( 'wc_facebook_api_pixel_event_request_data', $data, $this );

			return self::process_response_body( $this->_post( $url, $data ) );
		}


		/**
		 * Gets the business configuration.
		 *
		 * @param $external_business_id
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function get_business_configuration( $external_business_id ): array {
			$url      = $this->build_url( 'fbe_business', '?fbe_external_business_id=' . $external_business_id );
			$response = $this->_get( $url );
			return self::process_response_body( $response );
		}


		/**
		 * Updates the messenger configuration.
		 *
		 * @param $external_business_id
		 * @param $configuration
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function update_messenger_configuration( $external_business_id, $configuration ): array {
			$url  = $this->build_url( 'fbe_business', '?fbe_external_business_id=' . $external_business_id );
			$data = array(
				'fbe_external_business_id' => $external_business_id,
				'messenger_chat'           => array(
					'enabled' => $configuration['enabled'] ?? false,
					'domains' => $configuration['domains'] ?? array(),
				),
			);
			$response = $this->_post( $url, $data );
			return self::process_response_body( $response );
		}


		/**
		 * Fetches Facebook Business Extension object ids.
		 * 	e.g. business manager id, merchant settings id and others.
		 *
		 * @param $external_business_id
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function get_installation_ids( $external_business_id ): array {
			$url      = $this->build_url( 'fbe_business/fbe_installs', '?fbe_external_business_id=' . $external_business_id );
			$response = $this->_get( $url );
			return self::process_response_body( $response );
		}


		/**
		 * Fetches Facebook Page Access Token.
		 *
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function retrieve_page_access_token(): array {
			$url      = $this->build_url( 'me/accounts' );
			$response = $this->_get( $url );
			return self::process_response_body( $response );
		}


		/**
		 * Create Variable Product.
		 *
		 * @param string $product_catalog_id - Facebook Catalog ID.
		 * @param array $data - Variable Product data.
		 * @return array|WP_Error
		 */
		public function create_product_group( $product_catalog_id, $data ) {
			$url = $this->build_url( $product_catalog_id, '/product_groups' );
			return self::_post( $url, $data );
		}


		/**
		 * Add Variable Product Item.
		 *
		 * @param string $product_group_id - Variable Product ID.
		 * @param array $data - Variable product item.
		 * @return array|WP_Error
		 */
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

		/**
		 * Gets a list of Product Item ids in the given Product Group.
		 *
		 * @param string $product_group_id product group ID
		 * @param int    $limit max number of results returned per page of data
		 * @return array
		 * @throws Exception|JsonException
		 */
		public function get_product_group_product_ids( $product_group_id, $limit = 1000 ): array {
			$request  = $this->build_url(
				"{$product_group_id}/products",
				"?fields=id,retailer_id&limit={$limit}"
			);
			$response = $this->_get( $request );
			return self::process_response_body( $response );
		}

		/**
		 * Retrieves response body and parses it to return array of results.
		 *
		 * @param array|WP_Error $response
		 * @return array
		 * @throws Exception|JsonException
		 */
		private static function process_response_body( $response ): array {
			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), $response->get_error_code() );
			}
			$body = wp_remote_retrieve_body( $response );
			return json_decode( $body, true, 512, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
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
			$url = $this->build_url( $facebook_catalog_id, '/product_feeds' );
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

		/**
		 * Used to parse and decorate/transform response data if needed
		 *
		 * @param array $response
		 * @param callable|null $decorator
		 * @return mixed
		 */
		public static function get_data( array $response, callable $decorator = null ) {
			/* for the requests with paging, response is packed into `data` key, for others - straight in the root */
			$data = $response['data'] ?? $response;
			if ( is_callable( $decorator ) ) {
				$data = call_user_func( $decorator, $data );
			}
			return $data;
		}

		/**
		 * @param array $response
		 * @param int $steps_till_stop parameter used to limit paging if any.
		 * 	e.g.
		 * 		$response = $this->_get( $url );
		 *      // ...
		 * 		$pages    = 2;
		 * 		while ( $next_url = WC_Facebookcommerce_Graph_API::get_paging_next( $response, $pages-- ) ) {
		 * 			$response = $this->_get( $next_url );
		 * 			// do something
		 * 		}
		 * @return string next graph api url to make request to
		 */
		public static function get_paging_next( array $response, int $steps_till_stop = 1 ) {
			if ( 0 === $steps_till_stop ) {
				return '';
			}
			return $response['paging']['next'] ?? '';
		}

		/**
		 * Gets the next page of results for a paginated response.
		 *
		 * @param string $url paging data from previous response.
		 * @return array
		 * @throws Exception
		 */
		public function next( string $url ): array {
			try {
				$response = $this->_get( $url );
				return self::process_response_body( $response );
			} catch ( JsonException $e ) {
				return array();
			}
		}
	}

endif;
