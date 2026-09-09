<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */

declare( strict_types=1 );

namespace WooCommerce\Facebook\API\CommerceIntegration\Finalize;

defined( 'ABSPATH' ) || exit;

/**
 * Client for the Commerce Partner Integration finalize-install endpoint.
 */
class Client {

	/** @var string Commerce Partner Integration finalize-install endpoint. */
	const ENDPOINT = 'https://api.facebook.com/commerce-partner-integrations/finalize-install';

	/**
	 * Finalizes an installation after the durable access token has been stored.
	 *
	 * @param string $access_token The durable business integration system user access token.
	 * @param string $external_business_id The stable external business ID for this store.
	 * @param string $extension_version The installed Meta for WooCommerce version.
	 * @return Response
	 * @throws Exception If the request fails or returns an invalid response.
	 */
	public function finalize_install( string $access_token, string $external_business_id, string $extension_version ): Response {
		$body = array(
			'external_business_id' => $external_business_id,
		);

		if ( '' !== $extension_version ) {
			$body['extension_version'] = $extension_version;
		}

		$response = wp_safe_remote_post(
			self::ENDPOINT,
			array(
				'headers'     => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
				'redirection' => 0,
				'sslverify'   => true,
				'timeout'     => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception(
				esc_html( $response->get_error_message() ),
				'transport_error'
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			throw new Exception(
				sprintf( 'Finalize install request failed with status %d.', $status_code ),
				$this->get_http_failure_reason( $status_code ),
				$status_code
			);
		}

		$finalize_response = new Response( wp_remote_retrieve_body( $response ) );
		if ( ! $finalize_response->is_successful() ) {
			throw new Exception(
				'Finalize install response was missing required installation data.',
				'invalid_response'
			);
		}

		if ( $external_business_id !== $finalize_response->get_external_business_id() ) {
			throw new Exception(
				'Finalize install response external business ID did not match this store.',
				'external_business_id_mismatch'
			);
		}

		return $finalize_response;
	}

	/**
	 * Maps HTTP statuses to bounded values suitable for rollout monitoring.
	 *
	 * @param int $status_code HTTP response status.
	 * @return string
	 */
	private function get_http_failure_reason( int $status_code ): string {
		switch ( $status_code ) {
			case 400:
				return 'invalid_request';
			case 401:
				return 'unauthorized';
			case 403:
				return 'forbidden';
			case 404:
				return 'not_found';
			case 409:
				return 'conflict';
			case 429:
				return 'rate_limited';
			default:
				return $status_code >= 500 ? 'server_error' : 'http_error';
		}
	}
}
