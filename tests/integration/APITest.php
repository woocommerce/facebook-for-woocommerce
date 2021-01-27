<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\Facebook\Commerce\Orders;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

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


	/** @see API::get_access_token() */
	public function test_get_access_token() {

		$this->assertEquals( 'access_token', ( new API( 'access_token' ) )->get_access_token() );
	}


	/** @see API::set_access_token() */
	public function test_set_access_token() {

		$api = new API( 'access_token' );

		$api->set_access_token( 'new_access_token' );

		$this->assertEquals( 'new_access_token', $api->get_access_token() );
	}


	/** @see API::set_request_authorization_header() */
	public function test_set_request_authorization_header() {

		$api = new API( 'access_token' );

		$property = new ReflectionProperty( $api, 'request_headers' );
		$property->setAccessible( true );

		$method = new ReflectionMethod( $api, 'set_request_authorization_header' );
		$method->setAccessible( true );
		$method->invokeArgs( $api, [ 'new_access_token' ] );

		$request_headers = $property->getValue( $api );

		$this->assertIsArray( $request_headers );
		$this->assertArrayHasKey( 'Authorization', $request_headers );
		$this->assertEquals( 'Bearer new_access_token', $request_headers['Authorization'] );
	}


	/** @see API::perform_request() */
	public function test_do_post_parse_response_validation_retry() {

		$request = new class extends API\Request {

			public function __construct() {

				parent::__construct( null, null );

				$this->retry_codes[] = 5678;
			}
		};

		// mock the API to use fake data instead of making a real API call and return a valid response handler
		$api = $this->make( facebook_for_woocommerce()->get_api(), [
			'get_parsed_response'    => \Codeception\Stub\Expected::exactly( 6, new API\Response( json_encode( [
				'error' => [
					'message'          => 'Retry me!',
					'type'             => 'OAuthException',
					'code'             => 5678,
				]
			] ) ) ),
		] );

		// the request is expected to be retried 5 times using the Expected::exactly() call, then it lets this exception through
		$this->expectException( Framework\SV_WC_API_Exception::class );

		$api->perform_request( $request );
	}


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


	/** @see API::get_user() */
	public function test_get_user() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_user();

		$this->assertInstanceOf( API\User\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( '/me', $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\User\Response::class, $api->get_response() );
	}


	/** @see API::delete_user_permission() */
	public function test_delete_user_permission() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$user_id    = '1234';
		$permission = 'permission';

		$api->delete_user_permission( $user_id, $permission );

		$this->assertInstanceOf( API\User\Permissions\Delete\Request::class, $api->get_request() );
		$this->assertEquals( 'DELETE', $api->get_request()->get_method() );
		$this->assertEquals( "/{$user_id}/permissions/{$permission}", $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_data() );

		$this->assertInstanceOf( API\Response::class, $api->get_response() );
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
		$this->assertEquals( [ 'fields' => 'name,link,commerce_merchant_settings' ], $api->get_request()->get_params() );
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

		$catalog_id   = '123456';
		$requests     = [
			[ '1234' => Sync::ACTION_UPDATE ],
			[ '4567' => Sync::ACTION_DELETE ],
			[ '8901' => Sync::ACTION_UPDATE ],
		];
		$allow_upsert = true;

		$expected_request_data = [
			'allow_upsert' => $allow_upsert,
			'requests'     => $requests,
			'item_type'    => 'PRODUCT_ITEM',
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
		$this->assertEquals( "/{$catalog_id}/items_batch", $api->get_request()->get_path() );
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

		$pixel_id = '123456';

		$events = [
			new \SkyVerge\WooCommerce\Facebook\Events\Event( [ 'event_name' => 'Test' ] ),
		];

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->send_pixel_events( $pixel_id, $events );

		$this->assertInstanceOf( SkyVerge\WooCommerce\Facebook\API\Pixel\Events\Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( "/{$pixel_id}/events", $api->get_request()->get_path() );
		$this->assertArrayHasKey( 'data', $api->get_request()->get_data() );

		$this->assertInstanceOf( Response::class, $api->get_response() );
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


	/** @see API::get_new_orders() */
	public function test_get_new_orders() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_new_orders( '1234' );

		$this->assertInstanceOf( API\Orders\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( '/1234/commerce_orders', $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_data() );
		$expected_params = [
			'state'  => implode( ',', [
				API\Orders\Order::STATUS_PROCESSING,
				API\Orders\Order::STATUS_CREATED,
			] ),
			'fields' => implode( ',', [
				'id',
				'order_status',
				'created',
				'last_updated',
				'items{id,retailer_id,product_id,quantity,price_per_unit,tax_details}',
				'ship_by_date',
				'merchant_order_id',
				'channel',
				'selected_shipping_option',
				'shipping_address',
				'estimated_payment_details',
				'buyer_details',
			] ),
		];
		$this->assertEquals( $expected_params, $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Orders\Response::class, $api->get_response() );
	}


	/** @see API::get_cancelled_orders() */
	public function test_get_cancelled_orders() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_cancelled_orders( '1234' );

		$this->assertInstanceOf( API\Orders\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( '/1234/commerce_orders', $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_data() );
		$expected_params = [
			'state'  => implode( ',', [
				API\Orders\Order::STATUS_COMPLETED,
			] ),
			'updated_after' => time() - 5 * MINUTE_IN_SECONDS,
			'filters' => 'has_cancellations',
			'fields' => implode( ',', [
				'id',
				'order_status',
				'created',
				'last_updated',
				'items{id,retailer_id,product_id,quantity,price_per_unit,tax_details}',
				'ship_by_date',
				'merchant_order_id',
				'channel',
				'selected_shipping_option',
				'shipping_address',
				'estimated_payment_details',
				'buyer_details',
			] ),
		];
		$this->assertEquals( $expected_params, $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Orders\Response::class, $api->get_response() );
	}


	/** @see API::get_order() */
	public function test_get_order() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->get_order( '335211597203390' );

		$this->assertInstanceOf( API\Orders\Read\Request::class, $api->get_request() );
		$this->assertEquals( 'GET', $api->get_request()->get_method() );
		$this->assertEquals( '/335211597203390', $api->get_request()->get_path() );
		$this->assertEquals( [], $api->get_request()->get_data() );
		$expected_params = [
			'fields' => implode( ',', [
				'id',
				'order_status',
				'created',
				'last_updated',
				'items{id,retailer_id,product_id,quantity,price_per_unit,tax_details}',
				'ship_by_date',
				'merchant_order_id',
				'channel',
				'selected_shipping_option',
				'shipping_address',
				'estimated_payment_details',
				'buyer_details',
			] ),
		];
		$this->assertEquals( $expected_params, $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Orders\Read\Response::class, $api->get_response() );
	}


	/** @see API::acknowledge_order() */
	public function test_acknowledge_order() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->acknowledge_order( '335211597203390', '64241' );

		$this->assertInstanceOf( API\Orders\Acknowledge\Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( '/335211597203390/acknowledge_order', $api->get_request()->get_path() );
		$expected_data = [
			'merchant_order_reference' => '64241',
			'idempotency_key'          => $api->get_request()->get_idempotency_key(),
		];
		$this->assertEquals( $expected_data, $api->get_request()->get_data() );
		$this->assertEquals( [], $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Response::class, $api->get_response() );
	}


	/** @see API::fulfill_order() */
	public function test_fulfill_order() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$fulfillment_data = [
			'items'         => [
				[
					'retailer_id' => 'FB_product_1238',
					'quantity'    => 1,
				],
				[
					'retailer_id' => 'FB_product_5624',
					'quantity'    => 2,
				],
			],
			'tracking_info' => [
				'tracking_number'      => 'ship 1',
				'carrier'              => 'FEDEX',
				'shipping_method_name' => '2 Day Fedex',
			],
		];

		$api->fulfill_order( '335211597203390', $fulfillment_data );

		$this->assertInstanceOf( API\Orders\Fulfillment\Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( '/335211597203390/shipments', $api->get_request()->get_path() );
		$expected_data = $fulfillment_data;
		$expected_data['idempotency_key'] = $api->get_request()->get_idempotency_key();
		$this->assertEquals( $expected_data, $api->get_request()->get_data() );
		$this->assertEquals( [], $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Response::class, $api->get_response() );
	}


	/** @see API::cancel_order() */
	public function test_cancel_order() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$api->cancel_order( '335211597203390', Orders::CANCEL_REASON_CUSTOMER_REQUESTED, true );

		$this->assertInstanceOf( API\Orders\Cancel\Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( '/335211597203390/cancellations', $api->get_request()->get_path() );
		$expected_data = [
			'cancel_reason'   => [
				'reason_code' => Orders::CANCEL_REASON_CUSTOMER_REQUESTED,
			],
			'restock_items'   => true,
			'idempotency_key' => $api->get_request()->get_idempotency_key(),
		];
		$this->assertEquals( $expected_data, $api->get_request()->get_data() );
		$this->assertEquals( [], $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Response::class, $api->get_response() );
	}


	/** @see API::add_order_refund() */
	public function test_add_order_refund() {

		// test will fail if do_remote_request() is not called once
		$api = $this->make( API::class, [
			'do_remote_request' => \Codeception\Stub\Expected::once(),
		] );

		$sample_refund_data = [
			'items'       => [
				[
					'retailer_id'        => '45251',
					'item_refund_amount' => [
						'amount'   => '50.00',
						'currency' => 'USD',
					],
				],
				[
					'retailer_id'          => '45252',
					'item_refund_quantity' => 2,
				],
			],
			'reason_code' => Orders::REFUND_REASON_BUYERS_REMORSE,
			'reason_text' => 'Optional description of the reason',
			'shipping'    => [
				'shipping_refund' => [
					'amount'   => '10.00',
					'currency' => 'USD',
				],
			],
			'deductions'  => [
				[
					'deduction_type'   => 'RETURN_SHIPPING',
					'deduction_amount' => [
						'amount'   => '5.00',
						'currency' => 'USD',
					],
				],
			],
		];

		$api->add_order_refund( '335211597203390', $sample_refund_data );

		$this->assertInstanceOf( API\Orders\Refund\Request::class, $api->get_request() );
		$this->assertEquals( 'POST', $api->get_request()->get_method() );
		$this->assertEquals( '/335211597203390/refunds', $api->get_request()->get_path() );
		$expected_data = $sample_refund_data;
		$expected_data['idempotency_key'] = $api->get_request()->get_idempotency_key();
		$this->assertEquals( $expected_data, $api->get_request()->get_data() );
		$this->assertEquals( [], $api->get_request()->get_params() );

		$this->assertInstanceOf( API\Response::class, $api->get_response() );
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
