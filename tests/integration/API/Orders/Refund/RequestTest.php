<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders\Refund;

use SkyVerge\WooCommerce\Facebook\API\Orders\Refund\Request;

/**
 * Tests the API\Orders\Refund\Request class.
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

		$data = [
			'reason_code' => Request::REASON_BUYERS_REMORSE,
		];

		$request = new Request( '368508827392800', $data );

		$this->assertEquals( '/368508827392800/refunds', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );

		$expected_data                    = $data;
		$expected_data['idempotency_key'] = $request->get_idempotency_key();
		$this->assertEquals( $expected_data, $request->get_data() );
	}


}
