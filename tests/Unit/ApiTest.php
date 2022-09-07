<?php
declare( strict_types=1 );

use WooCommerce\Facebook\Api;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

/**
 * Api unit test clas.
 */
class ApiTest extends WP_UnitTestCase {

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->api = new Api( 'test-api-key-9678djyad552' );
	}

	/**
	 * Tests preform request succeeds.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_perform_request_performs_successful_request() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/facebook-page-id/?fields=name,link', $url );
			return [
				'body'     => '{"name":"Facebook for WooCommerce 2 - Catalog","link":"https://google.com","id":"2536275516506259"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->get_page( 'facebook-page-id' );

		$this->assertEquals( 'Facebook for WooCommerce 2 - Catalog', $response->name );
		$this->assertEquals( 'https://google.com', $response->link );
	}

	/**
	 * Tests preform request method returns WP_Error response.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_perform_request_produces_wp_error() {
		$this->expectException( ApiException::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'WP Error Message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/facebook-page-id/?fields=name,link', $url );
			return new WP_Error( 007, 'WP Error Message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$this->api->get_page( 'facebook-page-id' );
	}
}
