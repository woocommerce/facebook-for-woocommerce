<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

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


	/** @see Request::set_data() */
	public function test_set_data() {

		$request = new Request( null, null, null );
		$data    = [ 'key' => 'value' ];

		$request->set_data( $data );

		$this->assertEquals( $data, $request->get_data() );
	}


}
