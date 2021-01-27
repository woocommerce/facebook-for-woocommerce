<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Pages\Read;

use SkyVerge\WooCommerce\Facebook\API\Pages\Read\Request;

/**
 * Tests the API\Pages\Read\Request class.
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

		$this->assertEquals( '/1234', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
	}


	/** @see Request::get_params() */
	public function test_get_params() {

		$request = new Request( '1234' );

		$this->assertEquals( [ 'fields' => 'name,link,commerce_merchant_settings' ], $request->get_params() );
	}


}
