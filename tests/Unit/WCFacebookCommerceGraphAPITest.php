<?php
declare( strict_types=1 );

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_API_Exception;

class WCFacebookCommerceGraphAPITest extends WP_UnitTestCase {

	private WC_Facebookcommerce_Graph_API $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api = new WC_Facebookcommerce_Graph_API( 'test-api-key-09869asfdasf56' );
	}

	public function test_api_has_authorisation_header_with_proper_api_key() {
		$api = new WC_Facebookcommerce_Graph_API( 'test-api-key-09869asfdasf56' );
		$response = function( $result, $parsed_args, $url ) {
			$this->assertArrayHasKey( 'headers', $parsed_args );
			$this->assertArrayHasKey( 'Authorization', $parsed_args['headers'] );
			$this->assertEquals( 'Bearer test-api-key-09869asfdasf56', $parsed_args['headers']['Authorization'] );
			return [];
		};
		add_filter( 'pre_http_request', $response, 10, 3);
		/* Call any api, does not matter much, all the api calls must have Auth header. */
		$api->is_product_catalog_valid( 'product-catalog-id-654129' );
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
		add_filter( 'pre_http_request', $response, 10, 3);
		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );
		$this->assertTrue( $is_valid );
	}

	public function test_is_product_catalog_valid_returns_false() {
		$this->expectException( SV_WC_API_Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-2174129410', $url );
			return new WP_Error( 007, 'message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3);
		$this->api->is_product_catalog_valid( 'product-catalog-id-2174129410' );
	}
}
