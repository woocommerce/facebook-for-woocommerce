<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Request;

/**
 * Tests the API\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Request::__construct()
	 *
	 * @param string $path endpoint route
	 * @param string $method HTTP method
	 *
	 * @dataProvider provider_constructor
	 */
	public function test_constructor( $path, $method ) {

		$request = new Request( $path, $method );

		$this->assertEquals( $path, $request->get_path() );
		$this->assertEquals( $method, $request->get_method() );
	}

	/** @see test_constructor */
	public function provider_constructor( $requests ) {

		return [
			[ '/me', 'GET' ],
			[ '/1234/products', 'GET' ],

			[ '/1234/batch', 'POST' ],
			[ '/1234', 'POST' ],
		];
	}


	/** @see Request::set_params() */
	public function test_set_params() {

		$request = new Request( null, null, null );
		$params  = [ 'fields' => 'id' ];

		$request->set_params( $params );

		$this->assertEquals( $params, $request->get_params() );
	}


	/** @see Request::set_data() */
	public function test_set_data() {

		$request = new Request( null, null, null );
		$data    = [ 'key' => 'value' ];

		$request->set_data( $data );

		$this->assertEquals( $data, $request->get_data() );
	}


	/** @see Request::get_retry_count() */
	public function test_get_retry_count() {

		$this->assertSame( 0, ( new Request( null, null ) )->get_retry_count() );
	}


	/** @see Request::mark_retry() */
	public function test_mark_retry() {

		$request = new Request( null, null );

		$request->mark_retry();

		$this->assertSame( 1, $request->get_retry_count() );

		$request->mark_retry();

		$this->assertSame( 2, $request->get_retry_count() );
	}


	/** @see Request::get_retry_limit() */
	public function test_get_retry_limit() {

		$this->assertSame( 5, ( new Request( null, null ) )->get_retry_limit() );
	}


	/** @see Request::get_retry_codes() */
	public function test_get_retry_codes() {

		$codes = ( new Request( null, null ) )->get_retry_codes();

		$this->assertIsArray( $codes );
		$this->assertEmpty( $codes );
	}


}
