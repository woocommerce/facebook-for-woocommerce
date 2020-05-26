<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Installation;

use SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Request;

/**
 * Tests the API\FBE\Installation\Request class.
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
	 */
	public function test_constructor() {

		$path   = 'path';
		$method = 'GET';

		$request = new Request( $path, $method );

		$this->assertEquals( "/fbe_business/{$path}", $request->get_path() );
		$this->assertEquals( $method, $request->get_method() );
	}


}
