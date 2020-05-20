<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;
use SkyVerge\WooCommerce\Facebook\API\Response;

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
		require_once 'includes/API/Response.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


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

		// TODO
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

		// TODO
	}

	/** @see API::update_product_item() */
	public function test_update_product_item() {

		// TODO
	}

	/** @see API::delete_product_item() */
	public function test_delete_product_item() {

		// TODO
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
