<?php

use Codeception\Util\Stub;
use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\Facebook\Products\Sync;

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
		require_once 'includes/API/Request.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see API::send_item_updates() */
	public function test_send_item_updates() {

		require_once 'includes/API/Catalog/Send_Item_Updates/Request.php';

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
