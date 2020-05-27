<?php

use Codeception\Util\Stub;
use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
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

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
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

		// mock the response for the HTTP request
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

		$this->assertEquals( $product_group_id, $response->get_id() );
	}


	/** @see API::get_business_manager() */
	public function test_get_business_manager() {

		$id = '123456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_business_manager( $id );

		$this->assertInstanceOf( API\Business_Manager\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( "/{$id}", $api->get_request()->get_path() );
		$this->assertEquals( [ 'fields' => 'name,link' ], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Business_Manager\Response::class, $api->get_response() );
	}


	/** @see API::get_catalog() */
	public function test_get_catalog() {

		$catalog_id = '123456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_catalog( $catalog_id );

		$this->assertInstanceOf( API\Catalog\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( "/{$catalog_id}", $api->get_request()->get_path() );
		$this->assertEquals( [ 'fields' => 'name' ], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Catalog\Response::class, $api->get_response() );
	}


	/** @see API::get_page() */
	public function test_get_page() {

		$page_id   = '123456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_page( $page_id );

		$this->assertInstanceOf( API\Pages\Read\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( "/{$page_id}", $api->get_request()->get_path() );
		$this->assertEquals( [ 'fields' => 'name,link' ], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Pages\Read\Response::class, $api->get_response() );
	}


	/** @see API::get_installation_ids() */
	public function test_get_installation_ids() {

		$external_business_id = '123456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_installation_ids( $external_business_id );

		$this->assertInstanceOf( API\FBE\Installation\Read\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( '/fbe_business/fbe_installs', $api->get_request()->get_path() );
		$this->assertEquals( [ 'fbe_external_business_id' => $external_business_id, ], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\FBE\Installation\Read\Response::class, $api->get_response() );
	}


	/** @see API::send_item_updates() */
	public function test_send_item_updates() {

		if ( ! class_exists( API\Catalog\Send_Item_Updates\Request::class ) ) {
			require_once 'includes/API/Catalog/Send_Item_Updates/Request.php';
		}

		$catalog_id   = '123456';
		$requests     = [
			[ '1234' => Sync::ACTION_UPDATE ],
			[ '4567' => Sync::ACTION_DELETE ],
			[ '8901' => Sync::ACTION_UPDATE ],
		];
		$allow_upsert = true;

		$expected_request_data = [
			'allow_upsert' => $allow_upsert,
			'requests'     => $requests
		];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->send_item_updates( $catalog_id, $requests, $allow_upsert );

		$this->assertInstanceOf( API\Catalog\Send_Item_Updates\Request::class, $api->get_request() );
		$this->assertEquals( $requests, $api->get_request()->get_requests() );
		$this->assertEquals( $allow_upsert, $api->get_request()->get_allow_upsert() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$catalog_id}/batch", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( $expected_request_data, $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Catalog\Send_Item_Updates\Response::class, $api->get_response() );
	}


	/** @see API::create_product_group() */
	public function test_create_product_group() {

		$catalog_id         = '123456';
		$product_group_data = [ 'test' => 'test' ];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->create_product_group( '123456', $product_group_data );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$catalog_id}/product_groups", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( $product_group_data, $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::update_product_group() */
	public function test_update_product_group() {

		$product_group_id   = '1234';
		$product_group_data = [ 'test' => 'test' ];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->update_product_group( $product_group_id, $product_group_data );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_group_id}", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( $product_group_data, $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::delete_product_group() */
	public function test_delete_product_group() {

		$product_group_id = '1234';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->delete_product_group( $product_group_id );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'DELETE', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_group_id}", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::get_product_group_products() */
	public function test_get_product_group_products() {

		$product_group_id = '1234';
		$limit            = 42;

		$request_params   = [
			'fields' => 'id,retailer_id',
			'limit'  => $limit,
		];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_product_group_products( $product_group_id, $limit );

		$this->assertInstanceOf( API\Catalog\Product_Group\Products\Read\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_group_id}/products", $api->get_request()->get_path() );
		$this->assertEquals( $request_params, $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Catalog\Product_Group\Products\Read\Response::class, $api->get_response() );
	}


	/** @see API::find_product_item() */
	public function test_find_product_item() {

		$catalog_id  = '123456';
		$retailer_id = '456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->find_product_item( $catalog_id, $retailer_id );

		$this->assertInstanceOf( API\Catalog\Product_Item\Find\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( "catalog:{$catalog_id}:" . base64_encode( $retailer_id ), $api->get_request()->get_path() );
		$this->assertEquals( [ 'fields' => 'id,product_group{id}' ], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Catalog\Product_Item\Response::class, $api->get_response() );
	}


	/** @see API::create_product_item() */
	public function test_create_product_item() {

		$product_group_id = '123456';
		$product_data     = [ 'test' => 'test' ];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->create_product_item( $product_group_id, $product_data );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_group_id}/products", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( $product_data, $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::update_product_item() */
	public function test_update_product_item() {

		$product_item_id = '123456';
		$product_data    = [ 'test' => 'test' ];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->update_product_item( $product_item_id, $product_data );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_item_id}", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( $product_data, $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::delete_product_item() */
	public function test_delete_product_item() {

		$product_item_id = '123456';

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->delete_product_item( $product_item_id );

		$this->assertInstanceOf( Request::class, $api->get_request() );
		$this->assertEquals( 'DELETE', $api->get_request()->get_method() );
		$this->assertEquals( "/{$product_item_id}", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_params() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
	}


	/** @see API::send_pixel_events() */
	public function test_send_pixel_events() {

		// TODO: implement
	}


	/** @see API::next() */
	public function test_next() {

		$response_data = [
			'paging' => [
				'next' => 'https://graph.facebook.com/v7.0/1234/products?fields=id,retailer_id&limit=1000&after=ABCD',
			],
		];

		$request_args = [
			'path'   => '/1234/products',
			'method' => 'GET',
			'params' => [
				'fields' => 'id,retailer_id',
				'limit'  => 1000,
				'after'  => 'ABCD',
			],
		];

		$response      = $this->tester->get_paginated_response( $response_data );
		$next_response = $this->tester->get_paginated_response();

		$api = $this->make( API::class, [
			'perform_request' => function( API\Request $request ) use ( $request_args, $next_response ) {

				$this->assertEquals( $request_args['path'],   $request->get_path() );
				$this->assertEquals( $request_args['method'], $request->get_method() );
				$this->assertEquals( $request_args['params'], $request->get_params() );

				return $next_response;
			},
		] );

		$this->assertSame( $next_response, $api->next( $response ) );
		$this->assertEquals( $next_response->get_pages_retrieved(), $response->get_pages_retrieved() + 1 );
	}


	/** @see API::next() */
	public function test_next_when_there_is_no_next_page() {

		$response = $this->tester->get_paginated_response();

		$api = $this->make( API::class, [
			'perform_request' => Codeception\Stub\Expected::never(),
		] );

		$this->assertNull( $api->next( $response ) );
	}


	/** @see API::next() */
	public function test_next_when_enough_pages_have_been_retrieved() {

		$response_data = [
			'paging' => [
				'next' => 'https://graph.facebook.com/v7.0/1234/products?fields=id,retailer_id&limit=1000&after=ABCD',
			],
		];

		$additional_pages = 2;
		$pages_retrieved  = 3; // the first page from the original response and two more using next()

		$response = $this->tester->get_paginated_response( $response_data );
		$response->set_pages_retrieved( $pages_retrieved );

		$api = $this->make( API::class, [
			'perform_request' => Codeception\Stub\Expected::never(),
		] );

		$this->assertNull( $api->next( $response, $additional_pages ) );
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


	/**
	 * @see API::get_new_request()
	 *
	 * @param array $args test case
	 * @param string $expected_path expected request path
	 * @param string $expected_method expected request method
	 * @param string $expected_params optional array of expected requested parameters
	 * @throws ReflectionException
	 *
	 * @dataProvider provider_get_new_request
	 */
	public function test_get_new_request( $args, $expected_path, $expected_method, $expected_params = [] ) {

		$api = new API( 'fake-token' );

		$method  = IntegrationTester::getMethod( API::class, 'get_new_request' );
		$request = $method->invokeArgs( $api, [ $args ] );

		$this->assertEquals( $expected_path, $request->get_path() );
		$this->assertEquals( $expected_method, $request->get_method() );
		$this->assertEquals( $expected_params, $request->get_params() );
	}


	/** @see test_get_new_request() */
	public function provider_get_new_request() {

		$params = [
			'fields' => 'id',
			'limit'  => 100,
		];

		return [
			[ [ 'path' => '/me', 'method' => 'GET', 'params' => $params ], '/me', 'GET', $params ],
			[ [ 'path' => '/me', 'method' => 'GET' ], '/me', 'GET' ],
			[ [ 'path' => '/1234/products', 'method' => 'GET' ], '/1234/products', 'GET' ],
			[ [ 'path' => '/1234/batch', 'method' => 'POST' ], '/1234/batch', 'POST' ],
			[ [ 'path' => '/1234/batch' ], '/1234/batch', 'GET' ],
			[ [ 'method' => 'DELETE' ], '/', 'DELETE' ],
			[ [], '/', 'GET' ],
		];
	}


}
