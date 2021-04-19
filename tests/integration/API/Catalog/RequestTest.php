<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog;

use SkyVerge\WooCommerce\Facebook\API\Catalog\Request;

/**
 * Tests the API\Catalog\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Request::class ) ) {
			// the API cannot be instantiated if an access token is not defined
			facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

			// create an instance of the API and load all the request and response classes
			facebook_for_woocommerce()->get_api();
		}
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

		$this->assertEquals( [ 'fields' => 'name' ], $request->get_params() );
	}


	/** @see \SkyVerge\WooCommerce\Facebook\API\Catalog\Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'ads_management', Request::get_rate_limit_id() );
	}


}
