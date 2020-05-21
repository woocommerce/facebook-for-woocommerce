<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * Tests the API class.
 */
class APITest extends \Codeception\TestCase\WPTestCase {


	use \Codeception\Test\Feature\Stub;


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		require_once 'includes/API.php';
		require_once 'includes/API/Exceptions/Request_Limit_Reached.php';
		require_once 'includes/API/Request.php';
		require_once 'includes/API/Response.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see API::do_post_parse_response_validation()
	 *
	 * @param int $code error code
	 * @param string $exception expected exception class name
	 *
	 * @dataProvider provider_do_post_parse_response_validation
	 */
	public function test_do_post_parse_response_validation( $code, $exception ) {

		$message = sprintf( '(#%d) Message describing the error', $code );

		$this->expectException( $exception );
		$this->expectExceptionCode( $code );
		$this->expectExceptionMessageRegExp( '/' . preg_quote( $message, '/' ) . '/' );

		$args = [
			'request_path'     => '1234/product_groups',
			'response_body'    => [
				'error' => [
					'message'          => $message,
					'type'             => 'OAuthException',
					'code'             => $code,
				]
			],
			'response_code'    => 400,
			'response_message' => 'Bad Request',
		];

		$this->prepare_request_response( $args );

		$api = new API( 'access_token' );

		$api->create_product_group( '1234', [] );
	}


	/** @see API::test_do_post_parse_response_validation() */
	public function provider_do_post_parse_response_validation() {

		return [
			[ 4,     API\Exceptions\Request_Limit_Reached::class ],
			[ 17,    API\Exceptions\Request_Limit_Reached::class ],
			[ 32,    API\Exceptions\Request_Limit_Reached::class ],
			[ 613,   API\Exceptions\Request_Limit_Reached::class ],
			[ 80004, API\Exceptions\Request_Limit_Reached::class ],

			[ null, Framework\SV_WC_API_Exception::class ],
			[ 102,  Framework\SV_WC_API_Exception::class ],
			[ 190,  Framework\SV_WC_API_Exception::class ],
		];
	}


	/**
	 * Intercepts HTTP requests and returns a prepared response.
	 *
	 * @param array $args {
	 *     @type string $request_path a fragment of the URL that will be intercepted
	 *     @type array $response_headers HTTP headers for the response
	 *     @type array $response_body response data that will be JSON-encoded
	 *     @type int $response_code HTTP response code
	 *     @type string $response_message HTTP response message
	 * }
	 */
	private function prepare_request_response( $args ) {

		$args = wp_parse_args( $args, [
			'request_path'     => '',
			'response_headers' => [],
			'response_body'    => [],
			'response_code'    => 200,
			'response_message' => 'Ok'
		] );

		add_filter( 'pre_http_request', static function( $response, $parsed_args, $url ) use ( $args ) {

			if ( false !== strpos( $url, $args['request_path'] ) ) {

				$response = [
					'headers'       => $args['response_headers'],
					'body'          => json_encode( $args['response_body'] ),
					'response'      => [
						'code'    => $args['response_code'],
						'message' => $args['response_message'],
					],
					'cookies'       => [],
					'http_response' => null,
				];
			}

			return $response;
		}, 10, 3 );
	}


	/** @see API::do_post_parse_response_validation() */
	public function test_do_post_parse_response_validation_with_a_valid_response() {

		$product_group_id = '111001234947059';

		$args = [
			'request_path'  => '1234/product_groups',
			'response_body' => [
				'id' => $product_group_id,
			],
		];

		$this->prepare_request_response( $args );

		$api = new API( 'access_token' );

		$response = $api->create_product_group( '1234', [] );

		// TODO: replace with $response->get_id() the implementation for that method is merged
		$this->assertEquals( $product_group_id, $response->id );
	}


	/** @see API::create_product_group() */
	public function test_create_product_group() {

		$product_group_data = [ 'test' => 'test' ];

		// test will fail if Request::set_data() is not called once
		$request = $this->make( Request::class, [
			'set_data' => \Codeception\Stub\Expected::once( $product_group_data ),
		] );

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'get_new_request' => $request,
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->create_product_group( '123456', $product_group_data ) );
	}


	/** @see API::update_product_group() */
	public function test_update_product_group() {

		$product_group_data = [ 'test' => 'test' ];

		// test will fail if Request::set_data() is not called once
		$request = $this->make( Request::class, [
			'set_data' => \Codeception\Stub\Expected::once( $product_group_data ),
		] );

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'get_new_request' => $request,
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->update_product_group( '1234', $product_group_data ) );
	}


	/** @see API::delete_product_group() */
	public function test_delete_product_group() {

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->delete_product_group( '1234' ) );
	}


	/** @see API::find_product_item() */
	public function test_find_product_item() {

		// TODO
	}


	/** @see API::create_product_item() */
	public function test_create_product_item() {

		$product_data = [ 'test' => 'test' ];

		// test will fail if Request::set_data() is not called once
		$request = $this->make( Request::class, [
			'set_data' => \Codeception\Stub\Expected::once( $product_data ),
		] );

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'get_new_request' => $request,
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->create_product_item( '123456', $product_data ) );
	}


	/** @see API::update_product_item() */
	public function test_update_product_item() {

		$product_data = [ 'test' => 'test' ];

		// test will fail if Request::set_data() is not called once
		$request = $this->make( Request::class, [
			'set_data' => \Codeception\Stub\Expected::once( $product_data ),
		] );

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'get_new_request' => $request,
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->update_product_item( '123456', $product_data ) );
	}


	/** @see API::delete_product_item() */
	public function test_delete_product_item() {

		$response = new Response( '' );

		$api = $this->make( API::class, [
			'perform_request' => $response,
		] );

		// assert that perform_request() was called
		$this->assertSame( $response, $api->delete_product_item( '123456' ) );
	}


	/** @see API::set_rate_limit_delay() */
	public function test_set_rate_limit_delay() {

		// TODO
	}

	/** @see API::get_rate_limit_delay() */
	public function test_get_rate_limit_delay() {

		// TODO
	}

	/** @see API::calculate_rate_limit_delay() */
	public function test_calculate_rate_limit_delay() {

		// TODO
	}


}
