<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Configuration;

use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Request;

/**
 * Tests the API\FBE\Configuration\Request class.
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

		$external_business_id = '1234';
		$method               = 'GET';

		$request = new Request( $external_business_id, $method );

		$this->assertEquals( '/fbe_business', $request->get_path() );
		$this->assertArrayHasKey( 'fbe_external_business_id', $request->get_params() );
		$this->assertEquals( $external_business_id, $request->get_params()['fbe_external_business_id'] );
		$this->assertEquals( $method, $request->get_method() );
	}


}
