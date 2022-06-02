<?php
declare( strict_types=1 );

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_API_Exception;

class WCFacebookCommerceGraphAPITest extends WP_UnitTestCase {

	/** @var WC_Facebookcommerce_Graph_API */
	private $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api = new WC_Facebookcommerce_Graph_API( 'test-api-key-9678djyad552' );
	}

	public function test_api_has_authorisation_header_with_proper_api_key() {
		$api = new WC_Facebookcommerce_Graph_API( 'test-api-key-09869asfdasf56' );
		$response = function( $result, $parsed_args ) {
			$this->assertArrayHasKey( 'headers', $parsed_args );
			$this->assertArrayHasKey( 'Authorization', $parsed_args['headers'] );
			$this->assertEquals( 'Bearer test-api-key-09869asfdasf56', $parsed_args['headers']['Authorization'] );
			return [];
		};
		add_filter( 'pre_http_request', $response, 10, 2 );

		/* Call any api, does not matter much, all the api calls must have Auth header. */
		$api->is_product_catalog_valid( 'product-catalog-id-654129' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_parses_response_body() {
		$expected = [
			'id'     => '2536275516506259',
			'name'   => 'Facebook for WooCommerce 2 - Catalog',
			'custom' => 'John Doe',
		];

		$response = function() {
			return [
				'body' => '{"name":"Facebook for WooCommerce 2 - Catalog","custom":"John Doe","id":"2536275516506259"}'
			];
		};
		add_filter( 'pre_http_request', $response );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertEquals( $expected, $data );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_throws_an_exception_when_gets_connection_wp_error() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'WP Error Message' );

		$response = function() {
			return new WP_Error( 007, 'WP Error Message' );
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_throws_an_exception_when_there_is_no_response_body() {
		$this->expectException( JsonException::class );
		$this->expectExceptionMessage( 'Syntax error' );

		$response = function() {
			return [];
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	public function test_is_product_catalog_valid_returns_true() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 200,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertTrue( $is_valid );
	}

	public function test_is_product_catalog_valid_returns_false() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 400,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertFalse( $is_valid );
	}

	public function test_is_product_catalog_valid_throws_an_error() {
		$this->expectException( SV_WC_API_Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-2174129410', $url );
			return new WP_Error( 007, 'message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$this->api->is_product_catalog_valid( 'product-catalog-id-2174129410' );
	}

	public function test_get_catalog_returns_catalog_id_and_name() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2536275516506259?fields=name', $url );
			return [
				'body' => '{"name":"Facebook for WooCommerce 2 - Catalog","id":"2536275516506259"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );

		$this->assertEquals( 'Facebook for WooCommerce 2 - Catalog', $data['name'] );
		$this->assertEquals( '2536275516506259', $data['id'] );
	}

	public function test_get_user_must_return_facebook_user_id() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/me', $url );
			return [
				'body' => '{"id":"2525362755165069"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_user();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( '2525362755165069', $data['id'] );
	}

	public function test_revoke_user_permission_must_result_success() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'DELETE', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2525362755165069/permissions/manage_business_extension', $url );
			return [
				'body' => '{"success":true}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->revoke_user_permission( '2525362755165069', 'manage_business_extension' );

		$this->assertArrayhasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}
}

/**
 * $response = [
		'headers'       => $args['response_headers'],
		'body'          => json_encode( $args['response_body'] ),
		'response'      => [
			'code'    => $args['response_code'],
			'message' => $args['response_message'],
		],
		'cookies'       => [],
		'http_response' => null,
	];
 */
