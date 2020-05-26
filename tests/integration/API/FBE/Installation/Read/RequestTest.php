<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Installation\Read;

use SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Read\Request;

/**
 * Tests the API\FBE\Installation\Read\Request class.
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


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234' );

		$this->assertStringContainsString( '/fbe_installs', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
		$this->assertEquals( [ 'fbe_external_business_id' => '1234', ], $request->get_params() );
	}


}
